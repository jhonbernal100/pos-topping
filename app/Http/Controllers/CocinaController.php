<?php

namespace App\Http\Controllers;

use App\Models\Sede;
use App\Models\Venta;
use Illuminate\Http\Request;

class CocinaController extends Controller
{
    private const ESTADOS = ['pendiente', 'en_preparacion', 'listo', 'entregado'];

    public function index()
    {
        $sede = Sede::find(session('sede_id'));
        return view('cocina.index', compact('sede'));
    }

    // La pantalla consulta esta ruta cada 5 segundos
    public function pedidos()
    {
        $ventas = Venta::with('detalles.modificadores')
            ->where('sede_id', (int) session('sede_id'))
            ->where('estado', 'completada')
            ->where('created_at', '>=', now()->subHours(12))
            ->where(function ($q) {
                $q->whereIn('estado_preparacion', ['pendiente', 'en_preparacion', 'listo'])
                  ->orWhere(function ($q2) {
                      $q2->where('estado_preparacion', 'entregado')
                         ->where('entregado_at', '>=', now()->subMinutes(10));
                  });
            })
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'ahora'   => now()->toIso8601String(),
            'pedidos' => $ventas->map(fn ($v) => $this->formatear($v))->values(),
        ]);
    }

    public function cambiarEstado(Request $request, Venta $venta)
    {
        $data = $request->validate([
            'estado' => 'required|in:' . implode(',', self::ESTADOS),
        ]);

        $this->verificarSede($venta);

        if ($venta->estado !== 'completada') {
            return response()->json(['success' => false, 'mensaje' => 'Este pedido fue anulado.'], 422);
        }

        $ahora  = now();
        $campos = ['estado_preparacion' => $data['estado']];

        switch ($data['estado']) {
            case 'pendiente':
                $campos += ['preparacion_at' => null, 'listo_at' => null, 'entregado_at' => null];
                break;
            case 'en_preparacion':
                $campos += ['preparacion_at' => $venta->preparacion_at ?? $ahora, 'listo_at' => null, 'entregado_at' => null];
                break;
            case 'listo':
                $campos += ['preparacion_at' => $venta->preparacion_at ?? $ahora, 'listo_at' => $ahora, 'entregado_at' => null];
                break;
            case 'entregado':
                $campos += ['listo_at' => $venta->listo_at ?? $ahora, 'entregado_at' => $ahora];
                break;
        }

        $venta->update($campos);

        return response()->json([
            'success' => true,
            'pedido'  => $this->formatear($venta->fresh(['detalles.modificadores'])),
        ]);
    }

    public function comanda(Venta $venta)
    {
        $this->verificarSede($venta);
        $venta->load(['detalles.modificadores', 'sede', 'tenant']);

        return view('cocina.comanda', compact('venta'));
    }

    // ---------------------------------------------------------------

    private function formatear(Venta $v): array
    {
        return [
            'id'     => $v->id,
            'turno'  => $v->numero_turno,
            'nombre' => $v->nombre_pedido,
            'canal'  => $v->canal,
            'estado' => $v->estado_preparacion,
            'creado' => $v->created_at->toIso8601String(),
            'hora'   => $v->created_at->format('h:i a'),
            'items'  => $v->detalles->map(fn ($d) => [
                'cantidad' => $d->cantidad,
                'nombre'   => $d->nombreCompleto(),
                'notas'    => $d->notas,
                // Agrupa por grupo y suma repetidos: "Frutas: 2× Fresa, Mango"
                'opciones' => $d->modificadores
                    ->groupBy('nombre_grupo')
                    ->map(fn ($mods, $grupo) => [
                        'grupo'   => $grupo,
                        'valores' => $mods->groupBy('nombre')
                            ->map(fn ($g, $nombre) => ($g->sum('cantidad') > 1 ? $g->sum('cantidad') . '× ' : '') . $nombre)
                            ->values(),
                    ])
                    ->values(),
            ])->values(),
        ];
    }

    private function verificarSede(Venta $venta): void
    {
        $permitidas = array_map('intval', auth()->user()->sedesPermitidasIds());

        if (!in_array((int) $venta->sede_id, $permitidas, true)) {
            abort(403, 'No autorizado');
        }
    }
}