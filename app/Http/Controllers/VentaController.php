<?php

namespace App\Http\Controllers;

use App\Models\CategoriaMenu;
use App\Models\Producto;
use App\Models\Sede;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use App\Services\CalculadoraPedido;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with(['sede', 'pagos'])
            ->where('sede_id', session('sede_id'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('ventas.index', compact('ventas'));
    }

    // Pantalla de caja
    public function crear()
    {
        $sede = Sede::find(session('sede_id'));

        $categorias = CategoriaMenu::where('activo', true)
            ->orderBy('orden')
            ->with([
                'productos' => fn ($q) => $q->where('activo', true)->orderBy('orden_menu')->orderBy('nombre'),
                'productos.variantesActivas',
                'productos.gruposModificadores.modificadores',
            ])
            ->get();

        // Menú listo para la pantalla: solo lo disponible hoy en el local
        $menu = $categorias->map(function ($categoria) {
            $productos = $categoria->productos
                ->filter(fn ($p) => $p->disponiblePara('local'))
                ->map(fn ($p) => [
                    'id'          => $p->id,
                    'nombre'      => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'precio'      => (int) $p->precio_venta,
                    'tipo'        => $p->tipo_menu,
                    'stock'       => (int) $p->stock,
                    'variantes'   => $p->variantesActivas->map(fn ($v) => [
                        'id' => $v->id, 'nombre' => $v->nombre, 'precio' => (int) $v->precio,
                    ])->values(),
                    'grupos'      => $p->gruposModificadores->map(fn ($g) => [
                        'id'        => $g->id,
                        'nombre'    => $g->nombre,
                        'incluidos' => (int) $g->pivot->incluidos,
                        'minimo'    => (int) $g->pivot->minimo,
                        'maximo'    => $g->pivot->maximo !== null ? (int) $g->pivot->maximo : null,
                        'precio'    => (int) ($g->pivot->precio_extra ?? $g->precio_extra),
                        'opciones'  => $g->modificadores
                            ->filter(fn ($m) => $m->disponibleHoy())
                            ->map(fn ($m) => [
                                'id'     => $m->id,
                                'nombre' => $m->nombre,
                                'precio' => $m->precio_extra,   // null = usa el del grupo
                            ])->values(),
                    ])->values(),
                ])
                ->values();

            return [
                'id'        => $categoria->id,
                'nombre'    => $categoria->nombre,
                'productos' => $productos,
            ];
        })->filter(fn ($c) => $c['productos']->isNotEmpty())->values();

        return view('ventas.crear', compact('menu', 'sede'));
    }

    public function store(Request $request, CalculadoraPedido $calculadora)
    {
        $data = $request->validate([
            'nombre_pedido'           => 'required|string|max:60',
            'items'                   => 'required|array|min:1|max:50',
            'items.*.producto_id'     => 'required|integer',
            'items.*.variante_id'     => 'nullable|integer',
            'items.*.cantidad'        => 'required|integer|min:1|max:50',
            'items.*.modificadores'   => 'nullable|array|max:30',
            'items.*.modificadores.*' => 'integer',
            'items.*.notas'           => 'nullable|string|max:191',
            'pagos'                   => 'required|array|min:1|max:4',
            'pagos.*.metodo'          => 'required|in:efectivo,nequi,daviplata,tarjeta',
            'pagos.*.monto'           => 'required|integer|min:1',
            'pagos.*.referencia'      => 'nullable|string|max:60',
        ], [
            'nombre_pedido.required' => 'Escribe el nombre del cliente para llamarlo.',
            'items.required'         => 'Agrega al menos un producto.',
            'pagos.required'         => 'Registra el pago.',
        ]);

        $sedeId = (int) session('sede_id');
        if (!$sedeId) {
            return $this->error('No hay una sede activa. Selecciona una sede en el encabezado.');
        }

        // 1. Calcular precios en el servidor
        try {
            $items = collect($data['items'])->map(fn ($i) => $calculadora->calcularItem($i, 'local'));
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }

        $total = (int) $items->sum('subtotal');

        // 2. Validar pagos (todo se paga por adelantado)
        $pagos       = collect($data['pagos']);
        $recibido    = (int) $pagos->sum('monto');
        $electronico = (int) $pagos->where('metodo', '!=', 'efectivo')->sum('monto');

        if ($electronico > $total) {
            return $this->error('Los pagos por Nequi, Daviplata o tarjeta no pueden superar el total.');
        }

        if ($recibido < $total) {
            return $this->error('Faltan $' . number_format($total - $recibido, 0, ',', '.') . ' por pagar.');
        }

        $cambio = $recibido - $total;   // siempre sale del efectivo

        // 3. Registrar todo en una sola transacción
        try {
            $venta = DB::transaction(function () use ($data, $items, $pagos, $total, $recibido, $cambio, $sedeId) {

                // Stock de productos de reventa (ej: agua)
                foreach ($items as $it) {
                    if ($it['producto']->tipo_menu !== 'reventa') {
                        continue;
                    }

                    $producto = Producto::whereKey($it['producto']->id)->lockForUpdate()->first();
                    if ($producto->stock < $it['cantidad']) {
                        throw new InvalidArgumentException(
                            "No hay suficiente {$producto->nombre}. Disponibles: {$producto->stock}."
                        );
                    }
                    $producto->decrement('stock', $it['cantidad']);
                }

                $metodos     = $pagos->pluck('metodo')->unique();
                $tieneCocina = $items->contains(fn ($i) => $i['producto']->tipo_menu === 'preparado');

                $venta = Venta::create([
                    'tenant_id'          => session('tenant_id'),
                    'sede_id'            => $sedeId,
                    'nombre_pedido'      => trim($data['nombre_pedido']),
                    'numero_turno'       => $this->siguienteTurno($sedeId),
                    'canal'              => 'local',
                    'estado_preparacion' => $tieneCocina ? 'pendiente' : 'entregado',
                    'tipo_documento'     => 'ticket',
                    'estado'             => 'completada',
                    'subtotal'           => $total,
                    'descuento'          => 0,
                    'total'              => $total,
                    'metodo_pago'        => $metodos->count() > 1 ? 'mixto' : $metodos->first(),
                    'monto_pagado'       => $recibido,
                    'cambio'             => $cambio,
                ]);

                foreach ($items as $it) {
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

                // Pagos: el cambio se descuenta del efectivo, así la suma de pagos = total
                $cambioPendiente = $cambio;
                foreach ($pagos as $p) {
                    $monto = (int) $p['monto'];

                    if ($p['metodo'] === 'efectivo' && $cambioPendiente > 0) {
                        $descuento        = min($monto, $cambioPendiente);
                        $monto           -= $descuento;
                        $cambioPendiente -= $descuento;
                    }

                    if ($monto > 0) {
                        $venta->pagos()->create([
                            'metodo'     => $p['metodo'],
                            'monto'      => $monto,
                            'referencia' => $p['referencia'] ?? null,
                        ]);
                    }
                }

                return $venta;
            });
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage());
        }

        return response()->json([
            'success'  => true,
            'venta_id' => $venta->id,
            'turno'    => $venta->numero_turno,
            'etiqueta' => $venta->etiquetaPedido(),
            'total'    => $total,
            'cambio'   => $cambio,
        ]);
    }

    public function ticket(Venta $venta)
    {
        $venta->load(['detalles.modificadores', 'pagos', 'sede', 'tenant']);
        return view('ventas.ticket', compact('venta'));
    }

    // Heredado del ferretero: en POS Topping no hay créditos
    public function ticketAbono(Venta $venta)
    {
        abort(404);
    }

    public function anular(Venta $venta)
    {
        if (auth()->user()->rol !== 'dueno') {
            return $this->error('Solo un gerente o el propietario puede anular ventas.', 403);
        }

        if ($venta->estado === 'anulada') {
            return $this->error('La venta ya está anulada.');
        }

        DB::transaction(function () use ($venta) {
            // Devolver stock de productos de reventa
            foreach ($venta->detalles as $detalle) {
                $producto = Producto::withTrashed()->find($detalle->producto_id);
                if ($producto && $producto->tipo_menu === 'reventa') {
                    $producto->increment('stock', $detalle->cantidad);
                }
            }

            $venta->update(['estado' => 'anulada']);
        });

        return response()->json(['success' => true, 'mensaje' => 'Venta anulada']);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    // Consecutivo diario por sede. La fila queda bloqueada hasta el final
    // de la transacción, así dos cajas nunca reciben el mismo número.
    private function siguienteTurno(int $sedeId): int
    {
        $hoy = now()->toDateString();

        DB::table('turnos_diarios')->insertOrIgnore([
            'sede_id'    => $sedeId,
            'fecha'      => $hoy,
            'ultimo'     => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $fila = DB::table('turnos_diarios')
            ->where('sede_id', $sedeId)
            ->where('fecha', $hoy)
            ->lockForUpdate()
            ->first();

        $siguiente = (int) $fila->ultimo + 1;

        DB::table('turnos_diarios')
            ->where('id', $fila->id)
            ->update(['ultimo' => $siguiente, 'updated_at' => now()]);

        return $siguiente;
    }

    private function error(string $mensaje, int $status = 422)
    {
        return response()->json(['success' => false, 'mensaje' => $mensaje], $status);
    }
}