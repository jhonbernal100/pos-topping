<?php

namespace App\Services\Domicilios;

use App\Models\ConversacionWa;
use App\Models\InsumoStock;
use App\Models\Producto;
use App\Models\ProductoVariante;
use App\Models\RecetaItem;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\CalculadoraPedido;
use App\Services\InventarioService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Pedidos a domicilio: se crean sin pagar (estado "borrador") y se vuelven ventas
 * cuando el cajero confirma el pago. Ahí reciben turno y descuentan inventario.
 */
class Pedidos
{
    public function __construct(
        private CalculadoraPedido $calculadora,
        private InventarioService $inventario,
        private Turnos $turnos,
        private Notificador $notificador,
    ) {
    }

    /** Crea el pedido desde el menú web. Devuelve la venta en borrador. */
    public function crear(ConversacionWa $conv, array $items, string $nombre, ?string $notas): Venta
    {
        if (!$conv->sede_id) {
            throw new InvalidArgumentException('Primero comparte tu ubicación por WhatsApp.');
        }

        $calculados = collect($items)->map(fn ($i) => $this->calculadora->calcularItem($i, 'domicilio'));
        $subtotal   = (int) $calculados->sum('subtotal');
        $minimo     = (int) ($conv->tenant->domicilio_minimo ?? 0);

        if ($minimo > 0 && $subtotal < $minimo) {
            throw new InvalidArgumentException('El pedido mínimo a domicilio es de $' . number_format($minimo, 0, ',', '.') . '.');
        }

        return DB::transaction(function () use ($conv, $calculados, $subtotal, $nombre, $notas) {
            // Si había un pedido sin pagar en esta conversación, se reemplaza
            if ($conv->venta_id) {
                Venta::withoutGlobalScopes()->whereKey($conv->venta_id)->where('estado', 'borrador')
                    ->update(['estado_pago' => 'cancelado']);
            }

            $venta = Venta::create([
                'tenant_id'          => $conv->tenant_id,
                'sede_id'            => $conv->sede_id,
                'nombre_pedido'      => mb_substr(trim($nombre), 0, 60),
                'canal'              => 'domicilio',
                'estado'             => 'borrador',
                'estado_preparacion' => null,
                'estado_pago'        => 'pendiente',
                'tipo_documento'     => 'ticket',
                'subtotal'           => $subtotal,
                'descuento'          => 0,
                'costo_domicilio'    => $conv->costo_domicilio,
                'total'              => $subtotal + $conv->costo_domicilio,
                'metodo_pago'        => 'nequi',
                'monto_pagado'       => 0,
                'cambio'             => 0,
                'notas'              => $notas ? mb_substr(trim($notas), 0, 500) : null,
                'telefono_cliente'   => $conv->telefono,
                'direccion_entrega'  => $conv->direccion,
                'latitud_entrega'    => $conv->latitud,
                'longitud_entrega'   => $conv->longitud,
                'distancia_km'       => $conv->distancia_km,
                'conversacion_wa_id' => $conv->id,
            ]);

            foreach ($calculados as $it) {
                $detalle = VentaDetalle::create([
                    'venta_id'           => $venta->id,
                    'producto_id'        => $it['producto']->id,
                    'variante_id'        => $it['variante']?->id,
                    'nombre_producto'    => $it['producto']->nombre,
                    'nombre_variante'    => $it['variante']?->nombre,
                    'cantidad'           => $it['cantidad'],
                    'precio_base'        => $it['precio_base'],
                    'precio_adicionales' => $it['precio_adicionales'],
                    'precio_unitario'    => $it['precio_unitario'],
                    'subtotal'           => $it['subtotal'],
                    'notas'              => $it['notas'],
                ]);
                foreach ($it['modificadores'] as $mod) {
                    $detalle->modificadores()->create($mod);
                }
            }

            $conv->update(['venta_id' => $venta->id, 'estado' => 'esperando_pago', 'nombre' => $conv->nombre ?: $nombre]);

            return $venta;
        });
    }

    /** El cajero confirma el pago: turno, pago registrado, inventario y a la cocina. */
    public function confirmarPago(Venta $venta, string $metodo): Venta
    {
        if (!in_array($metodo, ['nequi', 'daviplata', 'tarjeta', 'efectivo'], true)) {
            throw new InvalidArgumentException('Método de pago no válido.');
        }

        $venta = DB::transaction(function () use ($venta, $metodo) {
            $v = Venta::whereKey($venta->id)->lockForUpdate()->first();

            if ($v->estado !== 'borrador' || in_array($v->estado_pago, ['pagado', 'cancelado'], true)) {
                throw new InvalidArgumentException('Este pedido ya fue procesado.');
            }

            $v->load('detalles.modificadores');
            $items = $this->itemsDeVenta($v);
            $this->verificarStock($items, (int) $v->sede_id);

            $tieneCocina = $items->contains(fn ($i) => $i['producto']->tipo_menu === 'preparado');

            $v->update([
                'estado'             => 'completada',
                'estado_pago'        => 'pagado',
                'numero_turno'       => $this->turnos->siguiente((int) $v->sede_id),
                'estado_preparacion' => $tieneCocina ? 'pendiente' : 'listo',
                'listo_at'           => $tieneCocina ? null : now(),
                'metodo_pago'        => $metodo,
                'monto_pagado'       => $v->total,
            ]);
            $v->created_at = now();   // el turno, la cocina y los reportes cuentan desde el pago
            $v->save();

            $v->pagos()->create([
                'metodo'     => $metodo,
                'monto'      => $v->total,
                'referencia' => mb_substr((string) ($v->lectura_comprobante['referencia'] ?? ''), 0, 60) ?: null,
            ]);

            $this->inventario->descontarVenta($v, $this->inventario->consumoDeItems($items));

            return $v;
        });

        $this->notificador->aviso($venta, 'pago_confirmado');

        return $venta;
    }

    public function rechazarPago(Venta $venta, string $motivo): void
    {
        if ($venta->estado !== 'borrador' || $venta->estado_pago === 'cancelado') {
            throw new InvalidArgumentException('Este pedido ya fue procesado.');
        }

        $venta->update(['estado_pago' => 'rechazado', 'motivo_rechazo' => mb_substr($motivo, 0, 191)]);
        ConversacionWa::whereKey($venta->conversacion_wa_id)->update(['estado' => 'esperando_pago']);
        $this->notificador->aviso($venta, 'pago_rechazado');
    }

    /** Cancela un pedido que aún no se ha pagado. */
    public function cancelar(Venta $venta, bool $avisar = true): void
    {
        if ($venta->estado !== 'borrador') {
            throw new InvalidArgumentException('Solo se cancelan pedidos que aún no se han pagado.');
        }

        // Queda en borrador: nunca fue venta, así no aparece en ventas, cierres ni reportes
        $venta->update(['estado_pago' => 'cancelado']);
        if ($avisar) {
            $this->notificador->aviso($venta, 'anulado');
        }
    }

    // ---------------------------------------------------------------

    /** Reconstruye los ítems en el formato de CalculadoraPedido para descontar inventario. */
    private function itemsDeVenta(Venta $v): Collection
    {
        return $v->detalles->map(fn ($d) => [
            'producto'      => Producto::withoutGlobalScopes()->withTrashed()->find($d->producto_id),
            'variante'      => $d->variante_id ? ProductoVariante::find($d->variante_id) : null,
            'cantidad'      => (int) $d->cantidad,
            'modificadores' => $d->modificadores->map(fn ($m) => [
                'modificador_id' => $m->modificador_id,
                'cantidad'       => (int) $m->cantidad,
            ])->all(),
        ])->filter(fn ($i) => $i['producto'])->values();
    }

    /** Reventa (ej. agua): con receta se valida contra insumos de la sede; sin receta, contra el stock del producto. */
    private function verificarStock(Collection $items, int $sedeId): void
    {
        $reventa   = $items->filter(fn ($i) => $i['producto']->tipo_menu === 'reventa');
        $conReceta = RecetaItem::whereIn('producto_id', $reventa->map(fn ($i) => $i['producto']->id)->all())
            ->pluck('producto_id')->unique()->flip();

        $consumo = $this->inventario->consumoDeItems($reventa->filter(fn ($i) => isset($conReceta[$i['producto']->id]))->values());
        ksort($consumo);
        foreach ($consumo as $insumoId => $cantidad) {
            $stock = InsumoStock::with('insumo')->where('insumo_id', $insumoId)->where('sede_id', $sedeId)->lockForUpdate()->first();
            if ($stock && $stock->cantidad < $cantidad) {
                throw new InvalidArgumentException('No hay suficiente ' . ($stock->insumo?->nombre ?? 'producto') . ' en esta sede.');
            }
        }

        foreach ($reventa as $it) {
            if (isset($conReceta[$it['producto']->id])) {
                continue;
            }
            $p = Producto::withoutGlobalScopes()->whereKey($it['producto']->id)->lockForUpdate()->first();
            if ($p->stock < $it['cantidad']) {
                throw new InvalidArgumentException("No hay suficiente {$p->nombre}. Disponibles: {$p->stock}.");
            }
            $p->decrement('stock', $it['cantidad']);
        }
    }
}
