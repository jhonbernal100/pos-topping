<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Sede;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Pedidos de convenios a crédito (ej: Rappi paga a ~20 días).
 * Aquí se ve lo pendiente y se marca como cobrado cuando llega la consignación.
 */
class CuentaCobrarController extends Controller
{
    public function index(Request $request)
    {
        $this->soloGerente();

        $usuario  = auth()->user();
        $sedeIds  = $usuario->sedesPermitidasIds();
        $sedes    = Sede::whereIn('id', $sedeIds)->orderBy('id')->get(['id', 'nombre']);
        $clientes = Cliente::where('venta_credito', true)->orderBy('nombre')->get(['id', 'nombre', 'dias_credito']);

        $estado    = $request->estado === 'cobrado' ? 'cobrado' : 'pendiente';
        $clienteId = (int) $request->cliente;
        $sedeId    = (int) $request->sede;
        $filtroSedes = $sedeId && in_array($sedeId, $sedeIds, true) ? [$sedeId] : $sedeIds;

        $base = Venta::whereIn('sede_id', $filtroSedes)
            ->where('estado', 'completada')
            ->when($clienteId, fn ($q) => $q->where('cliente_id', $clienteId));

        // Resumen de lo pendiente por cliente (sin importar el filtro de estado)
        $hoy     = now()->startOfDay();
        $resumen = (clone $base)->where('estado_cobro', 'pendiente')
            ->with('cliente:id,nombre')
            ->get(['id', 'cliente_id', 'total', 'vence_cobro'])
            ->groupBy('cliente_id')
            ->map(fn ($g) => [
                'nombre'   => $g->first()->cliente?->nombre ?? 'Cliente',
                'pedidos'  => $g->count(),
                'total'    => (int) $g->sum('total'),
                'vencido'  => (int) $g->filter(fn ($v) => $v->vence_cobro && $v->vence_cobro->lt($hoy))->sum('total'),
            ])
            ->sortByDesc('total')
            ->values();

        $desde = $this->fecha($request->desde);
        $hasta = $this->fecha($request->hasta);

        $ventas = (clone $base)->where('estado_cobro', $estado)
            ->when($desde, fn ($q) => $q->where('created_at', '>=', $desde->copy()->startOfDay()))
            ->when($hasta, fn ($q) => $q->where('created_at', '<=', $hasta->copy()->endOfDay()))
            ->with(['cliente:id,nombre', 'sede:id,nombre'])
            ->orderBy($estado === 'cobrado' ? 'cobrado_at' : 'created_at', $estado === 'cobrado' ? 'desc' : 'asc')
            ->limit(500)
            ->get();

        return view('cobros.index', [
            'ventas'      => $ventas,
            'resumen'     => $resumen,
            'clientes'    => $clientes,
            'sedes'       => $sedes,
            'estado'      => $estado,
            'clienteId'   => $clienteId,
            'sedeId'      => $sedeId,
            'desde'       => $desde,
            'hasta'       => $hasta,
            'variasSedes' => $sedes->count() > 1,
            'hoy'         => $hoy,
        ]);
    }

    // Marcar pedidos como pagados por la plataforma
    public function marcar(Request $request)
    {
        $this->soloGerente();

        $data = $request->validate([
            'ventas'     => 'required|array|min:1|max:500',
            'ventas.*'   => 'integer',
            'fecha'      => 'required|date|before_or_equal:today',
            'referencia' => 'nullable|string|max:60',
        ], [
            'ventas.required' => 'Selecciona al menos un pedido.',
            'fecha.required'  => 'Escribe la fecha en que llegó el pago.',
        ]);

        $n = Venta::whereIn('id', $data['ventas'])
            ->whereIn('sede_id', auth()->user()->sedesPermitidasIds())
            ->where('estado', 'completada')
            ->where('estado_cobro', 'pendiente')
            ->update([
                'estado_cobro'     => 'cobrado',
                'cobrado_at'       => Carbon::parse($data['fecha'])->setTimeFrom(now()),
                'referencia_cobro' => trim((string) ($data['referencia'] ?? '')) ?: null,
            ]);

        return back()->with('ok', $n === 1 ? '1 pedido marcado como cobrado.' : "{$n} pedidos marcados como cobrados.");
    }

    // Deshacer un cobro marcado por error
    public function deshacer(Venta $venta)
    {
        $this->soloGerente();

        if (!in_array((int) $venta->sede_id, auth()->user()->sedesPermitidasIds(), true) || $venta->estado_cobro !== 'cobrado') {
            return back()->with('error', 'Ese pedido no se puede devolver a pendiente.');
        }

        $venta->update(['estado_cobro' => 'pendiente', 'cobrado_at' => null, 'referencia_cobro' => null]);

        return back()->with('ok', "El pedido {$venta->codigo_externo} volvió a pendiente.");
    }

    private function fecha(?string $valor): ?Carbon
    {
        if (!$valor) {
            return null;
        }
        try {
            return Carbon::parse($valor);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function soloGerente(): void
    {
        if (auth()->user()->rol !== 'dueno') {
            abort(403, 'Solo un gerente o el propietario puede ver las cuentas por cobrar.');
        }
    }
}
