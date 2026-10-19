<?php

namespace App\Http\Controllers;

use App\Models\ConversacionWa;
use App\Models\Sede;
use App\Models\TarifaDomicilio;
use App\Models\Venta;
use App\Services\Domicilios\Notificador;
use App\Services\Domicilios\Pedidos;
use App\Services\WhatsApp\WhatsAppGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;

/**
 * Pedidos a domicilio por WhatsApp en la caja: verificar pagos, despachar y configurar.
 */
class DomicilioController extends Controller
{
    public function index()
    {
        $tenant = auth()->user()->tenant;
        if (!$tenant->tieneBotDomicilios()) {
            return view('domicilios.no-disponible', compact('tenant'));
        }

        return view('domicilios.index', [
            'tenant' => $tenant,
            'sede'   => Sede::find(session('sede_id')),
        ]);
    }

    /** Pedidos de la sede activa (la pantalla consulta cada pocos segundos). */
    public function pedidos()
    {
        $this->soloConBot();
        $sedeId = (int) session('sede_id');

        $ventas = Venta::with(['detalles.modificadores'])
            ->where('sede_id', $sedeId)
            ->where('canal', 'domicilio')
            ->where(function ($q) {
                // Sin pagar (últimas 24 h) o pagados que no han salido, más los despachados de hoy
                $q->where(fn ($q) => $q->where('estado', 'borrador')->whereIn('estado_pago', ['pendiente', 'por_verificar', 'rechazado'])
                        ->where('created_at', '>=', now()->subDay()))
                  ->orWhere(fn ($q) => $q->where('estado', 'completada')->where(fn ($q) => $q
                        ->where('estado_preparacion', '!=', 'entregado')
                        ->orWhereDate('entregado_at', today())));
            })
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'pedidos' => $ventas->map(fn ($v) => $this->formatear($v))->values(),
            'hora'    => now()->format('h:i:s a'),
        ]);
    }

    public function comprobante(Venta $venta)
    {
        $this->verificar($venta);
        if (!$venta->comprobante || !Storage::disk('local')->exists($venta->comprobante)) {
            abort(404);
        }
        return Storage::disk('local')->response($venta->comprobante);
    }

    public function confirmarPago(Request $request, Venta $venta, Pedidos $pedidos)
    {
        $this->verificar($venta);
        $data = $request->validate(['metodo' => 'required|in:nequi,daviplata,tarjeta,efectivo']);

        try {
            $venta = $pedidos->confirmarPago($venta, $data['metodo']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'mensaje' => "Pago confirmado. Turno #{$venta->numero_turno} enviado a cocina."]);
    }

    public function rechazarPago(Request $request, Venta $venta, Pedidos $pedidos)
    {
        $this->verificar($venta);
        $data = $request->validate(['motivo' => 'required|string|max:191'], ['motivo.required' => 'Escribe el motivo para el cliente.']);

        try {
            $pedidos->rechazarPago($venta, $data['motivo']);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'mensaje' => 'Le avisamos al cliente que no se pudo confirmar el pago.']);
    }

    public function despachar(Venta $venta, Notificador $notificador)
    {
        $this->verificar($venta);
        if ($venta->estado !== 'completada') {
            return response()->json(['success' => false, 'mensaje' => 'El pedido aún no está pagado.'], 422);
        }
        if ($venta->estado_preparacion === 'entregado') {
            return response()->json(['success' => false, 'mensaje' => 'Este pedido ya salió.'], 422);
        }

        $venta->update([
            'estado_preparacion' => 'entregado',
            'preparacion_at'     => $venta->preparacion_at ?? now(),
            'listo_at'           => $venta->listo_at ?? now(),
            'entregado_at'       => now(),
        ]);
        $notificador->aviso($venta, 'despachado');

        return response()->json(['success' => true, 'mensaje' => 'Le avisamos al cliente que su domicilio salió.']);
    }

    public function cancelar(Venta $venta, Pedidos $pedidos)
    {
        $this->verificar($venta);
        try {
            $pedidos->cancelar($venta);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        }
        ConversacionWa::whereKey($venta->conversacion_wa_id)->update(['estado' => 'finalizado']);

        return response()->json(['success' => true, 'mensaje' => 'Pedido cancelado y cliente avisado.']);
    }

    // ---------------------------------------------------------------
    // Configuración (solo propietario)
    // ---------------------------------------------------------------

    public function configuracion(WhatsAppGateway $wa)
    {
        $this->soloPropietario();
        $tenant = auth()->user()->tenant;

        return view('domicilios.configuracion', [
            'tenant'   => $tenant,
            'sedes'    => Sede::where('tenant_id', $tenant->id)->where('activo', true)->orderBy('id')->get(),
            'tarifas'  => TarifaDomicilio::orderBy('hasta_km')->get(),
            'conexion' => $tenant->wa_session_id ? $wa->estado($tenant) : null,
            'plan'     => $tenant->tieneBotDomicilios(),
        ]);
    }

    public function guardarConfiguracion(Request $request)
    {
        $this->soloPropietario();
        $tenant = auth()->user()->tenant;

        $data = $request->validate([
            'wa_activo'                => 'boolean',
            'wa_numero'                => 'nullable|string|max:30',
            'wa_session_id'            => 'nullable|string|max:100|regex:/^[A-Za-z0-9_\-]+$/',
            'pago_nequi'               => 'nullable|string|max:30',
            'pago_daviplata'           => 'nullable|string|max:30',
            'pago_titular'             => 'nullable|string|max:120',
            'domicilio_minimo'         => 'nullable|integer|min:0|max:10000000',
            'wa_bienvenida'            => 'nullable|string|max:500',
            'tarifas'                  => 'array|max:15',
            'tarifas.*.hasta_km'       => 'required|numeric|gt:0|max:100',
            'tarifas.*.valor'          => 'required|integer|min:0|max:1000000',
            'sedes'                    => 'array',
            'sedes.*.id'               => 'required|integer',
            'sedes.*.latitud'          => 'nullable|numeric|between:-90,90',
            'sedes.*.longitud'         => 'nullable|numeric|between:-180,180',
            'sedes.*.radio'            => 'nullable|numeric|min:0|max:100',
            'sedes.*.acepta'           => 'boolean',
        ], [
            'wa_session_id.regex' => 'El ID de sesión solo puede tener letras, números, guiones y guiones bajos.',
        ]);

        if (!empty($data['wa_activo']) && empty($data['pago_nequi']) && empty($data['pago_daviplata'])) {
            return response()->json(['success' => false, 'mensaje' => 'Para activar el bot escribe al menos un número de Nequi o Daviplata.'], 422);
        }

        if (!empty($data['wa_session_id'])
            && \App\Models\Tenant::where('wa_session_id', $data['wa_session_id'])->where('id', '!=', $tenant->id)->exists()) {
            return response()->json(['success' => false, 'mensaje' => 'Ese ID de sesión ya lo usa otro negocio.'], 422);
        }

        DB::transaction(function () use ($data, $tenant) {
            $tenant->update([
                'wa_activo'        => (bool) ($data['wa_activo'] ?? false),
                'wa_numero'        => $data['wa_numero'] ?? null,
                'wa_session_id'    => $data['wa_session_id'] ?? null,
                'pago_nequi'       => $data['pago_nequi'] ?? null,
                'pago_daviplata'   => $data['pago_daviplata'] ?? null,
                'pago_titular'     => $data['pago_titular'] ?? null,
                'domicilio_minimo' => (int) ($data['domicilio_minimo'] ?? 0),
                'wa_bienvenida'    => $data['wa_bienvenida'] ?? null,
            ]);

            TarifaDomicilio::where('tenant_id', $tenant->id)->delete();
            foreach (collect($data['tarifas'] ?? [])->sortBy('hasta_km') as $t) {
                TarifaDomicilio::create(['tenant_id' => $tenant->id, 'hasta_km' => $t['hasta_km'], 'valor' => (int) $t['valor']]);
            }

            foreach ($data['sedes'] ?? [] as $s) {
                Sede::where('tenant_id', $tenant->id)->whereKey($s['id'])->update([
                    'latitud'            => $s['latitud'] ?? null,
                    'longitud'           => $s['longitud'] ?? null,
                    'radio_cobertura_km' => $s['radio'] ?? null,
                    'acepta_domicilios'  => (bool) ($s['acepta'] ?? false),
                ]);
            }
        });

        return response()->json(['success' => true, 'mensaje' => 'Configuración de domicilios guardada.']);
    }

    // ---------------------------------------------------------------

    private function formatear(Venta $v): array
    {
        $l = $v->lectura_comprobante ?? [];

        return [
            'id'          => $v->id,
            'turno'       => $v->numero_turno,
            'nombre'      => $v->nombre_pedido,
            'telefono'    => $v->telefono_cliente,
            'direccion'   => $v->direccion_entrega,
            'mapa'        => $v->latitud_entrega ? "https://www.google.com/maps?q={$v->latitud_entrega},{$v->longitud_entrega}" : null,
            'distancia'   => $v->distancia_km,
            'notas'       => $v->notas,
            'total'       => (int) $v->total,
            'domicilio'   => (int) $v->costo_domicilio,
            'estado'      => $v->estado,
            'pago'        => $v->estado_pago,
            'cocina'      => $v->estado_preparacion,
            'motivo'      => $v->motivo_rechazo,
            'comprobante' => (bool) $v->comprobante,
            'lectura'     => $l ? [
                'leido'      => $l['leido'] ?? false,
                'plataforma' => $l['plataforma'] ?? null,
                'monto'      => $l['monto'] ?? null,
                'referencia' => $l['referencia'] ?? null,
                'fecha'      => $l['fecha_hora'] ?? null,
                'destino'    => $l['destino'] ?? null,
                'okMonto'    => $l['coincide_monto'] ?? null,
                'okDestino'  => $l['coincide_destino'] ?? null,
                'esPago'     => $l['es_comprobante'] ?? null,
            ] : null,
            'hora'        => $v->created_at->format('h:i a'),
            'despachado'  => $v->entregado_at?->format('h:i a'),
            'items'       => $v->detalles->map(fn ($d) => [
                'texto'    => $d->cantidad . '× ' . $d->nombreCompleto(),
                'opciones' => $d->modificadores->groupBy('nombre_grupo')
                    ->map(fn ($g, $grupo) => $grupo . ': ' . $g->groupBy('nombre')
                        ->map(fn ($mods, $nombre) => ($mods->sum('cantidad') > 1 ? $mods->sum('cantidad') . '× ' : '') . $nombre)->implode(', '))
                    ->values(),
                'notas'    => $d->notas,
            ])->values(),
        ];
    }

    private function verificar(Venta $venta): void
    {
        $this->soloConBot();
        $permitidas = array_map('intval', auth()->user()->sedesPermitidasIds());
        if ($venta->canal !== 'domicilio' || !in_array((int) $venta->sede_id, $permitidas, true)) {
            abort(403, 'No autorizado');
        }
    }

    private function soloConBot(): void
    {
        if (!auth()->user()->tenant?->tieneBotDomicilios()) {
            abort(403, 'El bot de domicilios está incluido en el plan anual.');
        }
    }

    private function soloPropietario(): void
    {
        if (!auth()->user()->esPropietario()) {
            abort(403, 'Solo el propietario puede configurar los domicilios.');
        }
    }
}
