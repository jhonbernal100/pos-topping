<?php

namespace App\Http\Controllers;

use App\Models\Compra;
use App\Models\Gasto;
use App\Models\Insumo;
use App\Models\MovimientoInventario;
use App\Models\RecetaItem;
use App\Models\Sede;
use App\Models\Venta;
use App\Models\VentaDetalle;
use App\Models\VentaPago;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Reportes de POS Topping, por sede o de todas las sedes (solo el propietario).
 * Todo pedido se paga por adelantado: no hay créditos ni cartera.
 */
class ReporteController extends Controller
{
    public function index()
    {
        $this->soloGerente();

        $usuario = auth()->user();
        $sedes   = Sede::whereIn('id', $usuario->sedesPermitidasIds())->orderBy('id')->get(['id', 'nombre']);

        return view('reportes.index', [
            'sedes'         => $sedes,
            'sedeActiva'    => (int) session('sede_id'),
            'puedeVerTodas' => $usuario->esPropietario() && $sedes->count() > 1,
        ]);
    }

    // ---------------------------------------------------------------
    // Ventas en un rango de fechas
    // ---------------------------------------------------------------
    public function ventas(Request $request)
    {
        $this->soloGerente();
        [$desde, $hasta] = $this->rango($request);
        [$sedeIds, $titulo] = $this->sedes($request);

        $ventas = Venta::with(['sede:id,nombre', 'pagos'])
            ->whereIn('sede_id', $sedeIds)
            ->whereBetween('created_at', [$desde, $hasta])
            ->orderBy('created_at')
            ->get();

        $completadas = $ventas->where('estado', 'completada');
        $anuladas    = $ventas->where('estado', 'anulada');
        $total       = (int) $completadas->sum('total');
        $pedidos     = $completadas->count();

        // Métodos de pago (los pagos mixtos se reparten en cada método)
        $pagos     = $completadas->flatMap->pagos;
        $porMetodo = collect(VentaPago::METODOS)->map(fn ($nombre, $clave) => [
            'nombre' => $nombre,
            'total'  => (int) $pagos->where('metodo', $clave)->sum('monto'),
        ])->filter(fn ($m) => $m['total'] > 0);

        $porSede = $completadas->groupBy('sede_id')->map(fn ($g) => [
            'nombre'  => $g->first()->sede?->nombre ?? 'Sede',
            'pedidos' => $g->count(),
            'total'   => (int) $g->sum('total'),
        ])->sortByDesc('total')->values();

        $unDia  = $desde->isSameDay($hasta);
        $porDia = $unDia ? collect() : $completadas
            ->groupBy(fn ($v) => $v->created_at->format('Y-m-d'))
            ->map(fn ($g, $dia) => ['dia' => Carbon::parse($dia), 'pedidos' => $g->count(), 'total' => (int) $g->sum('total')])
            ->values();

        $porHora = $completadas
            ->groupBy(fn ($v) => (int) $v->created_at->format('G'))
            ->map(fn ($g, $hora) => ['hora' => $hora, 'pedidos' => $g->count(), 'total' => (int) $g->sum('total')])
            ->sortKeys()
            ->values();

        $topProductos = VentaDetalle::whereIn('venta_id', $completadas->pluck('id'))
            ->selectRaw('nombre_producto, SUM(cantidad) as cantidad, SUM(subtotal) as total')
            ->groupBy('nombre_producto')
            ->orderByDesc('cantidad')
            ->limit(15)
            ->get();

        $datos = compact(
            'desde', 'hasta', 'titulo', 'unDia', 'total', 'pedidos', 'anuladas',
            'porMetodo', 'porSede', 'porDia', 'porHora', 'topProductos'
        ) + [
            'promedio'     => $pedidos ? (int) round($total / $pedidos) : 0,
            'listaPedidos' => $unDia ? $ventas : collect(),
            'variasSedes'  => count($sedeIds) > 1,
        ];

        return $this->pdf('reportes.pdf.ventas', $datos, 'ventas-' . $desde->format('Y-m-d') . ($unDia ? '' : '-a-' . $hasta->format('Y-m-d')));
    }

    // ---------------------------------------------------------------
    // Estado de resultados del mes
    // ---------------------------------------------------------------
    public function resultados(Request $request)
    {
        $this->soloGerente();
        $mes   = (int) ($request->mes ?: now()->month);
        $anio  = (int) ($request->anio ?: now()->year);
        $desde = Carbon::create($anio, $mes, 1)->startOfMonth();
        $hasta = $desde->copy()->endOfMonth();
        [$sedeIds, $titulo] = $this->sedes($request);

        $ventas   = Venta::whereIn('sede_id', $sedeIds)->whereBetween('created_at', [$desde, $hasta])
            ->where('estado', 'completada')->get(['id', 'total']);
        $ingresos = (int) $ventas->sum('total');

        // Costo de insumos: salidas por venta menos lo devuelto por anulaciones, a su costo del momento
        $movs = MovimientoInventario::whereIn('sede_id', $sedeIds)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get(['tipo', 'cantidad', 'costo_unitario', 'compra_id']);

        $valor = fn (Collection $m) => (int) round(-$m->sum(fn ($x) => (float) $x->cantidad * (float) $x->costo_unitario));

        $costoInsumos = $valor($movs->whereIn('tipo', ['venta', 'anulacion']));
        $mermas       = $valor($movs->where('tipo', 'merma'));
        $ajustes      = $valor($movs->where('tipo', 'ajuste')->whereNull('compra_id'));   // conteos físicos
        $utilidadBruta = $ingresos - $costoInsumos;

        // Gastos: se registran para todo el negocio (no por sede)
        $todas        = $this->esTodas($request);
        $gastosMes    = Gasto::whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->get();
        $gastosPorCat = $gastosMes->groupBy(fn ($g) => $g->categoria ?: 'Otros')->map(fn ($g) => (int) $g->sum('monto'))->sortDesc();
        $totalGastos  = (int) $gastosMes->sum('monto');
        $incluyeGastos = $todas || Sede::where('tenant_id', (int) session('tenant_id'))->count() <= 1;

        $utilidad = $utilidadBruta - $mermas - $ajustes - ($incluyeGastos ? $totalGastos : 0);

        $compras = (int) Compra::whereIn('sede_id', $sedeIds)->where('estado', 'registrada')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])->sum('total');

        // Qué tanto de lo vendido tiene receta (si no tiene, su costo no aparece arriba)
        $detalles      = VentaDetalle::whereIn('venta_id', $ventas->pluck('id'))->get(['producto_id', 'subtotal']);
        $conReceta     = RecetaItem::whereIn('producto_id', $detalles->pluck('producto_id')->unique())->pluck('producto_id')->unique()->flip();
        $ventasSinReceta = (int) $detalles->filter(fn ($d) => !isset($conReceta[$d->producto_id]))->sum('subtotal');

        return $this->pdf('reportes.pdf.resultados', compact(
            'desde', 'hasta', 'titulo', 'ingresos', 'costoInsumos', 'mermas', 'ajustes', 'utilidadBruta',
            'gastosPorCat', 'totalGastos', 'incluyeGastos', 'utilidad', 'compras', 'ventasSinReceta'
        ) + ['pedidos' => $ventas->count()], 'resultados-' . $desde->format('Y-m'));
    }

    // ---------------------------------------------------------------
    // Inventario de insumos
    // ---------------------------------------------------------------
    public function insumos(Request $request)
    {
        $this->soloGerente();
        [$sedeIds, $titulo] = $this->sedes($request);
        $soloAlertas = $request->boolean('alertas');

        $sedes   = Sede::whereIn('id', $sedeIds)->orderBy('id')->get(['id', 'nombre']);
        $insumos = Insumo::with(['stocks' => fn ($q) => $q->whereIn('sede_id', $sedeIds)])
            ->where('activo', true)->orderBy('categoria')->orderBy('nombre')->get()
            ->map(function ($i) use ($sedes) {
                $porSede = $sedes->mapWithKeys(fn ($s) => [$s->id => $i->stocks->firstWhere('sede_id', $s->id)]);
                $cantidad = (float) $porSede->filter()->sum('cantidad');
                $alerta = $porSede->filter()->contains(fn ($st) => in_array($st->estado(), ['agotado', 'bajo'], true));
                return (object) [
                    'nombre'    => $i->nombre,
                    'categoria' => $i->categoria ?: 'Sin categoría',
                    'unidad'    => $i->unidad,
                    'costo'     => (float) $i->costo_unitario,
                    'porSede'   => $porSede,
                    'cantidad'  => $cantidad,
                    'valor'     => max(0, $cantidad) * (float) $i->costo_unitario,
                    'alerta'    => $alerta,
                    'contado'   => $porSede->filter()->isNotEmpty(),
                ];
            })
            ->when($soloAlertas, fn ($c) => $c->where('alerta', true))
            ->values();

        return $this->pdf('reportes.pdf.insumos', [
            'titulo'      => $titulo,
            'sedes'       => $sedes,
            'insumos'     => $insumos,
            'soloAlertas' => $soloAlertas,
            'valorTotal'  => (int) round($insumos->sum('valor')),
            'alertas'     => $insumos->where('alerta', true)->count(),
        ], ($soloAlertas ? 'insumos-alertas-' : 'insumos-') . now()->format('Y-m-d'));
    }

    // ---------------------------------------------------------------
    // Compras a proveedores
    // ---------------------------------------------------------------
    public function compras(Request $request)
    {
        $this->soloGerente();
        [$desde, $hasta] = $this->rango($request);
        [$sedeIds, $titulo] = $this->sedes($request);

        $compras = Compra::with(['proveedor:id,nombre', 'sede:id,nombre'])->withCount('items')
            ->whereIn('sede_id', $sedeIds)->where('estado', 'registrada')
            ->whereBetween('fecha', [$desde->toDateString(), $hasta->toDateString()])
            ->orderBy('fecha')->get();

        $porProveedor = $compras->groupBy(fn ($c) => $c->proveedor?->nombre ?? 'Sin proveedor')
            ->map(fn ($g) => ['facturas' => $g->count(), 'total' => (int) $g->sum('total')])
            ->sortByDesc('total');

        return $this->pdf('reportes.pdf.compras', [
            'desde'        => $desde,
            'hasta'        => $hasta,
            'titulo'       => $titulo,
            'compras'      => $compras,
            'porProveedor' => $porProveedor,
            'total'        => (int) $compras->sum('total'),
            'impuestos'    => (int) $compras->sum('impuestos'),
            'variasSedes'  => count($sedeIds) > 1,
        ], 'compras-' . $desde->format('Y-m-d') . '-a-' . $hasta->format('Y-m-d'));
    }

    // ---------------------------------------------------------------

    /** [ids de sedes, texto para el encabezado] según ?sede=ID|todas */
    private function sedes(Request $request): array
    {
        $usuario    = auth()->user();
        $permitidas = array_map('intval', $usuario->sedesPermitidasIds());

        if ($this->esTodas($request)) {
            return [$permitidas, 'Todas las sedes'];
        }

        $id = (int) ($request->sede ?: session('sede_id'));
        if (!in_array($id, $permitidas, true)) {
            $id = (int) session('sede_id');
        }
        if (!in_array($id, $permitidas, true)) {
            abort(403, 'No autorizado');
        }

        return [[$id], 'Sede ' . (Sede::find($id)?->nombre ?? '')];
    }

    private function esTodas(Request $request): bool
    {
        return $request->sede === 'todas' && auth()->user()->esPropietario();
    }

    /** ?periodo=hoy|ayer|semana|mes|mes_anterior o ?desde=&hasta= (máximo 1 año) */
    private function rango(Request $request): array
    {
        $hoy = now();

        [$desde, $hasta] = match ($request->periodo) {
            'ayer'         => [$hoy->copy()->subDay()->startOfDay(), $hoy->copy()->subDay()->endOfDay()],
            'semana'       => [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfDay()],
            'mes'          => [$hoy->copy()->startOfMonth(), $hoy->copy()->endOfDay()],
            'mes_anterior' => [$hoy->copy()->subMonthNoOverflow()->startOfMonth(), $hoy->copy()->subMonthNoOverflow()->endOfMonth()],
            'rango'        => [
                $this->fecha($request->desde, $hoy)->startOfDay(),
                $this->fecha($request->hasta, $hoy)->endOfDay(),
            ],
            default        => [$hoy->copy()->startOfDay(), $hoy->copy()->endOfDay()],
        };

        if ($desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta->copy()->startOfDay(), $desde->copy()->endOfDay()];
        }
        if ($desde->diffInDays($hasta) > 366) {
            $desde = $hasta->copy()->subYear()->startOfDay();
        }

        return [$desde, $hasta];
    }

    private function fecha(?string $valor, Carbon $defecto): Carbon
    {
        try {
            return $valor ? Carbon::parse($valor) : $defecto->copy();
        } catch (\Throwable) {
            return $defecto->copy();
        }
    }

    private function pdf(string $vista, array $datos, string $archivo)
    {
        $datos['tenant']   = auth()->user()->tenant;
        $datos['generado'] = now();

        return Pdf::loadView($vista, $datos)->setPaper('letter', 'portrait')->stream($archivo . '.pdf');
    }

    private function soloGerente(): void
    {
        if (auth()->user()->rol !== 'dueno') {
            abort(403, 'Solo un gerente o el propietario puede ver los reportes.');
        }
    }
}
