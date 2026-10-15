<?php

namespace App\Http\Controllers;

use App\Models\CategoriaMenu;
use App\Models\InsumoStock;
use App\Models\Producto;
use App\Models\RecetaItem;
use App\Models\Sede;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Services\CalculadoraPedido;
use App\Services\InventarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VentaController extends Controller
{
    public function index()
    {
        $ventas = Venta::with(['sede', 'pagos'])->withCount('detalles')
            ->where('sede_id', session('sede_id'))
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('ventas.index', compact('ventas'));
    }

    // Pantalla de caja
    public function crear()
    {
        $sede   = Sede::find(session('sede_id'));
        $tenant = auth()->user()->tenant;

        $categorias = CategoriaMenu::where('activo', true)
            ->orderBy('orden')
            ->with([
                'productos' => fn ($q) => $q->where('activo', true)->orderBy('orden_menu')->orderBy('nombre'),
                'productos.variantesActivas',
                'productos.gruposModificadores.modificadores',
            ])
            ->get();

        // Reventa con receta (ej: agua): su existencia sale de los insumos de esta sede
        $reventaIds   = $categorias->flatMap(fn ($c) => $c->productos)->where('tipo_menu', 'reventa')->pluck('id');
        $stockReventa = $this->stockReventaPorInsumos($reventaIds->all(), (int) session('sede_id'));

        // Menú listo para la pantalla: solo lo disponible hoy en el local
        $menu = $categorias->map(function ($categoria) use ($stockReventa) {
            $productos = $categoria->productos
                ->filter(fn ($p) => $p->disponiblePara('local'))
                ->map(fn ($p) => [
                    'id'          => $p->id,
                    'nombre'      => $p->nombre,
                    'descripcion' => $p->descripcion,
                    'foto'        => $p->foto ? asset('storage/' . $p->foto) : null,
                    'precio'      => (int) $p->precio_venta,
                    'tipo'        => $p->tipo_menu,
                    'stock'       => array_key_exists($p->id, $stockReventa) ? $stockReventa[$p->id] : (int) $p->stock,
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

        return view('ventas.crear', compact('menu', 'sede', 'tenant'));
    }

    public function store(Request $request, CalculadoraPedido $calculadora, InventarioService $inventario)
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
            $venta = DB::transaction(function () use ($data, $items, $pagos, $total, $recibido, $cambio, $sedeId, $inventario) {

                // Reventa con receta: se controla con los insumos de la sede
                $reventa   = $items->filter(fn ($i) => $i['producto']->tipo_menu === 'reventa');
                $conReceta = RecetaItem::whereIn('producto_id', $reventa->map(fn ($i) => $i['producto']->id)->unique()->all())
                    ->pluck('producto_id')->unique()->flip();

                $this->verificarInsumosReventa(
                    $inventario->consumoDeItems($reventa->filter(fn ($i) => isset($conReceta[$i['producto']->id]))->values()),
                    $sedeId
                );

                // Reventa sin receta: stock propio del producto (esquema anterior)
                foreach ($reventa as $it) {
                    if (isset($conReceta[$it['producto']->id])) {
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

                // Descontar insumos de la sede según recetas y opciones elegidas
                $inventario->descontarVenta($venta, $inventario->consumoDeItems($items));

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

    public function anular(Venta $venta, InventarioService $inventario)
    {
        if (auth()->user()->rol !== 'dueno') {
            return $this->error('Solo un gerente o el propietario puede anular ventas.', 403);
        }

        if ($venta->estado === 'anulada') {
            return $this->error('La venta ya está anulada.');
        }

        DB::transaction(function () use ($venta, $inventario) {
            // Devolver stock de productos de reventa sin receta (los de receta vuelven por insumos)
            $conReceta = RecetaItem::whereIn('producto_id', $venta->detalles->pluck('producto_id')->unique()->all())
                ->pluck('producto_id')->unique()->flip();

            foreach ($venta->detalles as $detalle) {
                $producto = Producto::withTrashed()->find($detalle->producto_id);
                if ($producto && $producto->tipo_menu === 'reventa' && !isset($conReceta[$producto->id])) {
                    $producto->increment('stock', $detalle->cantidad);
                }
            }

            // Devolver insumos a la sede
            $inventario->revertirVenta($venta);

            $venta->update(['estado' => 'anulada']);
        });

        return response()->json(['success' => true, 'mensaje' => 'Venta anulada']);
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Unidades vendibles de productos de reventa que tienen receta, según los insumos de la sede.
     * Devuelve [producto_id => unidades]. Si algún insumo aún no se ha contado en la sede,
     * no se limita la venta (9999) para no bloquear la caja antes del primer conteo.
     */
    private function stockReventaPorInsumos(array $productoIds, int $sedeId): array
    {
        if (!$productoIds || !$sedeId) {
            return [];
        }

        $recetas = RecetaItem::whereIn('producto_id', $productoIds)->whereNull('variante_id')->get()->groupBy('producto_id');
        if ($recetas->isEmpty()) {
            return [];
        }

        $stocks = InsumoStock::where('sede_id', $sedeId)
            ->whereIn('insumo_id', $recetas->flatten()->pluck('insumo_id')->unique()->all())
            ->pluck('cantidad', 'insumo_id');

        $resultado = [];
        foreach ($recetas as $productoId => $items) {
            $unidades = null;
            foreach ($items as $r) {
                if ($r->cantidad <= 0) {
                    continue;
                }
                if (!$stocks->has($r->insumo_id)) {
                    $unidades = null;   // sin conteo: no limitar
                    break;
                }
                $posibles = (int) floor((float) $stocks[$r->insumo_id] / $r->cantidad);
                $unidades = $unidades === null ? $posibles : min($unidades, $posibles);
            }
            $resultado[$productoId] = $unidades ?? 9999;
        }

        return $resultado;
    }

    /**
     * Verifica con bloqueo que la sede tenga los insumos de los productos de reventa con receta.
     * Insumos que aún no se han contado en la sede no bloquean la venta.
     */
    private function verificarInsumosReventa(array $consumo, int $sedeId): void
    {
        ksort($consumo);   // mismo orden de bloqueo que InventarioService

        foreach ($consumo as $insumoId => $cantidad) {
            $stock = InsumoStock::with('insumo')
                ->where('insumo_id', $insumoId)
                ->where('sede_id', $sedeId)
                ->lockForUpdate()
                ->first();

            if ($stock && $stock->cantidad < $cantidad) {
                $nombre = $stock->insumo?->nombre ?? 'el producto';
                $hay    = max(0, (int) floor($stock->cantidad));
                throw new InvalidArgumentException(
                    "No hay suficiente {$nombre} en esta sede. Disponibles: {$hay}. Si llegó mercancía, regístrala en Compras."
                );
            }
        }
    }

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
