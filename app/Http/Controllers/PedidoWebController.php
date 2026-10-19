<?php

namespace App\Http\Controllers;

use App\Models\ConversacionWa;
use App\Models\Tenant;
use App\Models\Venta;
use App\Services\Domicilios\Bot;
use App\Services\Domicilios\Catalogo;
use App\Services\Domicilios\Notificador;
use App\Services\Domicilios\Pedidos;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Menú web público para armar el pedido a domicilio (enlace que envía el bot).
 */
class PedidoWebController extends Controller
{
    public function mostrar(string $token, Catalogo $catalogo)
    {
        [$conv, $tenant, $error] = $this->conversacion($token);
        if ($error) {
            return response()->view('pedir.cerrado', ['mensaje' => $error, 'tenant' => $tenant], 410);
        }

        return view('pedir.menu', [
            'conv'   => $conv,
            'tenant' => $tenant,
            'sede'   => $conv->sede,
            'menu'   => $catalogo->domicilio($tenant->id),
        ]);
    }

    public function enviar(Request $request, string $token, Pedidos $pedidos, Notificador $notificador, Bot $bot)
    {
        [$conv, $tenant, $error] = $this->conversacion($token);
        if ($error) {
            return response()->json(['success' => false, 'mensaje' => $error], 410);
        }

        $data = $request->validate([
            'nombre'                  => 'required|string|max:60',
            'notas'                   => 'nullable|string|max:300',
            'items'                   => 'required|array|min:1|max:30',
            'items.*.producto_id'     => 'required|integer',
            'items.*.variante_id'     => 'nullable|integer',
            'items.*.cantidad'        => 'required|integer|min:1|max:20',
            'items.*.modificadores'   => 'nullable|array|max:30',
            'items.*.modificadores.*' => 'integer',
            'items.*.notas'           => 'nullable|string|max:191',
        ], [
            'nombre.required' => 'Escribe tu nombre para el pedido.',
            'items.required'  => 'Agrega al menos un producto.',
        ]);

        try {
            $venta = $pedidos->crear($conv, $data['items'], $data['nombre'], $data['notas'] ?? null);
        } catch (InvalidArgumentException $e) {
            return response()->json(['success' => false, 'mensaje' => $e->getMessage()], 422);
        }

        // El enlace ya se usó: el siguiente paso es el pago por WhatsApp
        $conv->update(['token' => null, 'token_expira_at' => null]);
        $notificador->enviar($conv->fresh(), $this->resumen($venta) . "\n\n" . $bot->instruccionesPago($tenant, $venta));

        return response()->json([
            'success'  => true,
            'total'    => $venta->total,
            'whatsapp' => $tenant->wa_numero ? 'https://wa.me/' . preg_replace('/\D/', '', $tenant->wa_numero) : null,
        ]);
    }

    // ---------------------------------------------------------------

    /** @return array{0: ?ConversacionWa, 1: ?Tenant, 2: ?string} */
    private function conversacion(string $token): array
    {
        $conv = strlen($token) >= 20 ? ConversacionWa::with('sede')->where('token', $token)->first() : null;
        if (!$conv) {
            return [null, null, 'Este enlace ya no es válido. Escríbenos de nuevo por WhatsApp para recibir uno nuevo.'];
        }

        $tenant = Tenant::find($conv->tenant_id);
        // Los modelos del menú se filtran por el negocio del enlace
        session(['tenant_id' => $tenant->id]);

        if (!$tenant->wa_activo || !$tenant->tieneBotDomicilios()) {
            return [$conv, $tenant, 'Los pedidos a domicilio no están disponibles en este momento.'];
        }
        if (!$conv->tokenVigente()) {
            return [$conv, $tenant, 'Este enlace venció. Escribe *hola* por WhatsApp para recibir uno nuevo.'];
        }
        if (!$conv->sede_id || !$conv->direccion) {
            return [$conv, $tenant, 'Primero comparte tu ubicación y dirección por WhatsApp.'];
        }

        return [$conv, $tenant, null];
    }

    private function resumen(Venta $venta): string
    {
        $venta->load('detalles.modificadores');
        $dinero = fn ($v) => '$' . number_format((int) $v, 0, ',', '.');

        $lineas = $venta->detalles->map(function ($d) use ($dinero) {
            $txt = "• {$d->cantidad}× {$d->nombreCompleto()} — " . $dinero($d->subtotal);
            $ops = $d->modificadores->groupBy('nombre_grupo')
                ->map(fn ($g, $grupo) => "   {$grupo}: " . $g->groupBy('nombre')
                    ->map(fn ($mods, $nombre) => ($mods->sum('cantidad') > 1 ? $mods->sum('cantidad') . '× ' : '') . $nombre)->implode(', '))
                ->implode("\n");
            return $txt . ($ops ? "\n{$ops}" : '') . ($d->notas ? "\n   Nota: {$d->notas}" : '');
        })->implode("\n");

        return "🧾 *Pedido recibido* a nombre de *{$venta->nombre_pedido}*\n{$lineas}\n\n"
            . 'Subtotal: ' . $dinero($venta->subtotal) . "\n"
            . 'Domicilio: ' . ($venta->costo_domicilio > 0 ? $dinero($venta->costo_domicilio) : 'gratis') . "\n"
            . "Entrega en: {$venta->direccion_entrega}";
    }
}
