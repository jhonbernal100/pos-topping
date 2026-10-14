<?php

namespace App\Http\Controllers;

use App\Models\CierreCaja;
use App\Models\Sede;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CierreCajaController extends Controller
{
    private const METODOS = [
        'efectivo'      => 'Efectivo',
        'nequi'         => 'Nequi',
        'daviplata'     => 'Daviplata',
        'tarjeta'       => 'Tarjeta',
        'transferencia' => 'Transferencia',
    ];

    // Pantalla de arqueo del día
    public function crear(Request $request)
    {
        $sedeId = (int) session('sede_id');
        $sede   = Sede::find($sedeId);

        if (!$sede) {
            return view('caja.cierre', ['sede' => null]);
        }

        $fecha     = $this->fechaPermitida($request->query('fecha'));
        $resumen   = $this->resumen($sedeId, $fecha);
        $existente = CierreCaja::with('usuario')
            ->where('sede_id', $sedeId)
            ->whereDate('fecha', $fecha->toDateString())
            ->first();

        return view('caja.cierre', compact('sede', 'fecha', 'resumen', 'existente'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'fecha'            => 'nullable|date',
            'base_inicial'     => 'required|integer|min:0|max:100000000',
            'efectivo_contado' => 'required|integer|min:0|max:100000000',
            'notas'            => 'nullable|string|max:500',
        ], [
            'base_inicial.required'     => 'Escribe la base con la que abrió la caja (puede ser 0).',
            'efectivo_contado.required' => 'Escribe el efectivo que hay en la caja.',
        ]);

        $sedeId = (int) session('sede_id');
        if (!$sedeId) {
            return back()->withErrors(['general' => 'No hay una sede activa.']);
        }

        $fecha     = $this->fechaPermitida($data['fecha'] ?? null);
        $existente = CierreCaja::where('sede_id', $sedeId)->whereDate('fecha', $fecha->toDateString())->first();

        if ($existente && auth()->user()->rol !== 'dueno') {
            return back()->withErrors([
                'general' => 'La caja de este día ya fue cerrada. Solo un gerente o el propietario puede corregir el cierre.',
            ]);
        }

        // El resumen siempre se recalcula en el servidor
        $r         = $this->resumen($sedeId, $fecha);
        $base      = (int) $data['base_inicial'];
        $contado   = (int) $data['efectivo_contado'];
        $esperado  = $base + $r['efectivo'];

        $valores = [
            'user_id'           => auth()->id(),
            'num_ventas'        => $r['num_ventas'],
            'total_ventas'      => $r['total'],
            'num_anuladas'      => $r['num_anuladas'],
            'total_anuladas'    => $r['total_anuladas'],
            'efectivo_ventas'   => $r['efectivo'],
            'base_inicial'      => $base,
            'efectivo_esperado' => $esperado,
            'efectivo_contado'  => $contado,
            'diferencia'        => $contado - $esperado,
            'detalle'           => [
                'metodos'   => $r['metodos'],
                'productos' => $r['productos'],
                'primera'   => $r['primera'],
                'ultima'    => $r['ultima'],
            ],
            'notas'             => isset($data['notas']) ? trim($data['notas']) ?: null : null,
        ];

        if ($existente) {
            $existente->update($valores);
            $cierre = $existente;
        } else {
            $cierre = CierreCaja::create($valores + [
                'tenant_id' => (int) session('tenant_id'),
                'sede_id'   => $sedeId,
                'fecha'     => $fecha->toDateString(),
            ]);
        }

        $mensaje = match (true) {
            $cierre->diferencia === 0 => 'Caja cerrada. El efectivo cuadra.',
            $cierre->diferencia > 0   => 'Caja cerrada con un sobrante de $' . number_format($cierre->diferencia, 0, ',', '.') . '.',
            default                   => 'Caja cerrada con un faltante de $' . number_format(abs($cierre->diferencia), 0, ',', '.') . '.',
        };

        return redirect('/caja/cierre?fecha=' . $fecha->toDateString())
            ->with('ok', $mensaje)
            ->with('imprimir', $cierre->id);
    }

    // Historial (gerentes y propietario)
    public function index()
    {
        $this->soloGerente();

        $sede    = Sede::find(session('sede_id'));
        $cierres = CierreCaja::with('usuario')
            ->where('sede_id', (int) session('sede_id'))
            ->orderByDesc('fecha')
            ->paginate(31);

        return view('caja.cierres', compact('sede', 'cierres'));
    }

    public function imprimir(CierreCaja $cierre)
    {
        $permitidas = array_map('intval', auth()->user()->sedesPermitidasIds());
        if (!in_array((int) $cierre->sede_id, $permitidas, true)) {
            abort(403, 'No autorizado');
        }

        $cierre->load(['sede', 'tenant', 'usuario']);

        return view('caja.imprimir', compact('cierre'));
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    // Auxiliares: solo el día de hoy. Gerentes: hasta 60 días atrás.
    private function fechaPermitida(?string $valor): Carbon
    {
        $hoy = today();

        if (!$valor || auth()->user()->rol !== 'dueno') {
            return $hoy;
        }

        try {
            $fecha = Carbon::parse($valor)->startOfDay();
        } catch (\Throwable $e) {
            return $hoy;
        }

        if ($fecha->gt($hoy) || $fecha->lt($hoy->copy()->subDays(60))) {
            return $hoy;
        }

        return $fecha;
    }

    private function resumen(int $sedeId, Carbon $fecha): array
    {
        $tenantId = (int) session('tenant_id');
        $dia      = $fecha->toDateString();

        $ventas = DB::table('ventas')
            ->where('tenant_id', $tenantId)
            ->where('sede_id', $sedeId)
            ->whereDate('created_at', $dia);

        $completadas = (clone $ventas)->where('estado', 'completada');
        $anuladas    = (clone $ventas)->where('estado', 'anulada');

        $pagos = DB::table('venta_pagos')
            ->join('ventas', 'ventas.id', '=', 'venta_pagos.venta_id')
            ->where('ventas.tenant_id', $tenantId)
            ->where('ventas.sede_id', $sedeId)
            ->where('ventas.estado', 'completada')
            ->whereDate('ventas.created_at', $dia)
            ->groupBy('venta_pagos.metodo')
            ->select('venta_pagos.metodo', DB::raw('SUM(venta_pagos.monto) AS total'), DB::raw('COUNT(*) AS n'))
            ->get()
            ->keyBy('metodo');

        $metodos = [];
        foreach (self::METODOS as $clave => $nombre) {
            $fila = $pagos->get($clave);
            if ($fila || $clave !== 'transferencia') {
                $metodos[$clave] = [
                    'nombre' => $nombre,
                    'total'  => (int) ($fila->total ?? 0),
                    'n'      => (int) ($fila->n ?? 0),
                ];
            }
        }

        $productos = DB::table('venta_detalles')
            ->join('ventas', 'ventas.id', '=', 'venta_detalles.venta_id')
            ->where('ventas.tenant_id', $tenantId)
            ->where('ventas.sede_id', $sedeId)
            ->where('ventas.estado', 'completada')
            ->whereDate('ventas.created_at', $dia)
            ->groupBy('venta_detalles.nombre_producto')
            ->select(
                'venta_detalles.nombre_producto AS nombre',
                DB::raw('SUM(venta_detalles.cantidad) AS cantidad'),
                DB::raw('SUM(venta_detalles.subtotal) AS total')
            )
            ->orderByDesc('cantidad')
            ->limit(10)
            ->get()
            ->map(fn ($p) => ['nombre' => $p->nombre, 'cantidad' => (int) $p->cantidad, 'total' => (int) $p->total])
            ->all();

        $primera = (clone $completadas)->min('created_at');
        $ultima  = (clone $completadas)->max('created_at');

        return [
            'num_ventas'     => (clone $completadas)->count(),
            'total'          => (int) (clone $completadas)->sum('total'),
            'num_anuladas'   => (clone $anuladas)->count(),
            'total_anuladas' => (int) (clone $anuladas)->sum('total'),
            'efectivo'       => $metodos['efectivo']['total'],
            'metodos'        => $metodos,
            'productos'      => $productos,
            'primera'        => $primera ? Carbon::parse($primera)->format('h:i a') : null,
            'ultima'         => $ultima ? Carbon::parse($ultima)->format('h:i a') : null,
        ];
    }

    private function soloGerente(): void
    {
        if (auth()->user()->rol !== 'dueno') {
            abort(403, 'Solo un gerente o el propietario puede ver el historial de cierres.');
        }
    }
}
