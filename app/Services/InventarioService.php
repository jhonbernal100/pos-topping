<?php

namespace App\Services;

use App\Models\Compra;
use App\Models\Insumo;
use App\Models\InsumoStock;
use App\Models\ModificadorInsumo;
use App\Models\MovimientoInventario;
use App\Models\RecetaItem;
use App\Models\Venta;
use Illuminate\Support\Collection;

/**
 * Toda modificación de existencias pasa por aquí, para que el kardex siempre cuadre.
 * Debe llamarse dentro de una transacción (DB::transaction).
 */
class InventarioService
{
    /**
     * Consumo de insumos de los ítems calculados por CalculadoraPedido.
     * Devuelve [insumo_id => cantidad]. Sin recetas cargadas devuelve [] y no afecta nada.
     */
    public function consumoDeItems(Collection $items): array
    {
        $productoIds = $items->map(fn ($i) => $i['producto']->id)->unique()->values();

        $recetas = RecetaItem::whereIn('producto_id', $productoIds)->get()->groupBy('producto_id');

        $modIds = $items
            ->flatMap(fn ($i) => collect($i['modificadores'])->pluck('modificador_id'))
            ->unique()
            ->values();

        $modInsumos = ModificadorInsumo::whereIn('modificador_id', $modIds)->get()->groupBy('modificador_id');

        $consumo = [];

        foreach ($items as $it) {
            $unidades   = (int) $it['cantidad'];
            $varianteId = $it['variante']?->id;

            // Receta: ítems generales + ítems de la variante elegida
            foreach ($recetas->get($it['producto']->id, collect()) as $r) {
                if ($r->variante_id !== null && (int) $r->variante_id !== (int) $varianteId) {
                    continue;
                }
                $consumo[$r->insumo_id] = ($consumo[$r->insumo_id] ?? 0) + $r->cantidad * $unidades;
            }

            // Opciones elegidas (incluidas o adicionales consumen igual)
            foreach ($it['modificadores'] as $m) {
                foreach ($modInsumos->get($m['modificador_id'], collect()) as $mi) {
                    $consumo[$mi->insumo_id] = ($consumo[$mi->insumo_id] ?? 0)
                        + $mi->cantidad * (int) $m['cantidad'] * $unidades;
                }
            }
        }

        return $consumo;
    }

    /** Descuenta los insumos de una venta en su sede. */
    public function descontarVenta(Venta $venta, array $consumo): void
    {
        ksort($consumo); // mismo orden de bloqueo en todas las cajas: evita bloqueos cruzados

        foreach ($consumo as $insumoId => $cantidad) {
            if ($cantidad > 0) {
                $this->registrar((int) $venta->sede_id, (int) $insumoId, -$cantidad, 'venta', [
                    'venta_id' => $venta->id,
                ]);
            }
        }
    }

    /** Devuelve los insumos de una venta anulada. */
    public function revertirVenta(Venta $venta): void
    {
        $movimientos = MovimientoInventario::where('venta_id', $venta->id)
            ->where('tipo', 'venta')
            ->orderBy('insumo_id')
            ->get();

        foreach ($movimientos as $m) {
            $this->registrar((int) $m->sede_id, (int) $m->insumo_id, -$m->cantidad, 'anulacion', [
                'venta_id' => $venta->id,
                'nota'     => 'Anulación ' . $venta->etiquetaPedido(),
            ]);
        }
    }

    /** Entrada de inventario por cada ítem de una factura de compra registrada. */
    public function registrarCompra(Compra $compra): void
    {
        $items = $compra->items()->whereNotNull('insumo_id')->where('cantidad', '>', 0)->orderBy('insumo_id')->get();

        foreach ($items as $item) {
            $this->registrar((int) $compra->sede_id, (int) $item->insumo_id, (float) $item->cantidad, 'entrada', [
                'compra_id'      => $compra->id,
                'costo_unitario' => $item->costoUnitario(),
                'nota'           => trim('Factura ' . ($compra->numero_factura ?? '') . ' ' . ($compra->proveedor?->nombre ?? '')),
            ]);
        }
    }

    /**
     * Registra un movimiento y actualiza la existencia de la sede.
     * $cantidad positiva = entra; negativa = sale.
     * $extra: venta_id, compra_id, nota, costo_unitario (para entradas).
     */
    public function registrar(int $sedeId, int $insumoId, float $cantidad, string $tipo, array $extra = []): MovimientoInventario
    {
        InsumoStock::firstOrCreate(
            ['insumo_id' => $insumoId, 'sede_id' => $sedeId],
            ['cantidad' => 0, 'stock_minimo' => 0]
        );

        $stock = InsumoStock::where('insumo_id', $insumoId)
            ->where('sede_id', $sedeId)
            ->lockForUpdate()
            ->first();

        $stock->cantidad = round($stock->cantidad + $cantidad, 3);
        $stock->save();

        $insumo = Insumo::withoutGlobalScopes()->findOrFail($insumoId);

        // Costo promedio ponderado en las entradas
        if ($tipo === 'entrada' && $cantidad > 0 && isset($extra['costo_unitario'])) {
            $existenciaTotal = (float) InsumoStock::where('insumo_id', $insumoId)->sum('cantidad');
            $antes           = max(0, $existenciaTotal - $cantidad);
            $nuevoCosto      = (float) $extra['costo_unitario'];

            $insumo->costo_unitario = $antes > 0
                ? round(($antes * $insumo->costo_unitario + $cantidad * $nuevoCosto) / ($antes + $cantidad), 4)
                : $nuevoCosto;
            $insumo->save();
        }

        return MovimientoInventario::create([
            'tenant_id'      => $insumo->tenant_id,
            'sede_id'        => $sedeId,
            'insumo_id'      => $insumoId,
            'tipo'           => $tipo,
            'cantidad'       => round($cantidad, 3),
            'saldo'          => $stock->cantidad,
            'costo_unitario' => $extra['costo_unitario'] ?? $insumo->costo_unitario,
            'venta_id'       => $extra['venta_id'] ?? null,
            'compra_id'      => $extra['compra_id'] ?? null,
            'user_id'        => auth()->id(),
            'nota'           => $extra['nota'] ?? null,
        ]);
    }

    /** Existencias de una sede: [insumo_id => cantidad]. */
    public function stockSede(int $sedeId): array
    {
        return InsumoStock::where('sede_id', $sedeId)
            ->pluck('cantidad', 'insumo_id')
            ->map(fn ($c) => (float) $c)
            ->all();
    }
}
