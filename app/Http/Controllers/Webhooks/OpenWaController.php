<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\MensajeWa;
use App\Models\Tenant;
use App\Services\Domicilios\Bot;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Recibe los eventos de OpenWA. Responde 200 de inmediato y procesa después de
 * enviar la respuesta, para que OpenWA no reintente mientras la IA lee un comprobante.
 */
class OpenWaController extends Controller
{
    public function __invoke(Request $request)
    {
        $secreto = (string) config('services.openwa.webhook_secret');
        if ($secreto === '') {
            Log::warning('OpenWA webhook rechazado: falta OPENWA_WEBHOOK_SECRET');
            return response()->json(['ok' => false], 503);
        }

        $esperada = 'sha256=' . hash_hmac('sha256', $request->getContent(), $secreto);
        if (!hash_equals($esperada, (string) $request->header('X-OpenWA-Signature'))) {
            return response()->json(['ok' => false], 401);
        }

        $evento = $request->input('event');
        $data   = (array) $request->input('data', []);
        $tenant = Tenant::where('wa_session_id', (string) $request->input('sessionId'))->first();

        if (!$tenant) {
            return response()->json(['ok' => true, 'ignorado' => 'sesion']);
        }

        if ($evento === 'session.status' || $evento === 'session.disconnected') {
            Log::info("OpenWA {$tenant->nombre}: {$evento} " . json_encode($data));
            return response()->json(['ok' => true]);
        }

        if ($evento !== 'message.received' || !$tenant->wa_activo || !$tenant->tieneBotDomicilios()) {
            return response()->json(['ok' => true, 'ignorado' => 'evento']);
        }

        // Solo chats individuales y mensajes del cliente
        $kind = $data['kind'] ?? (($data['isGroup'] ?? false) ? 'group' : 'individual');
        if (($data['fromMe'] ?? false) || $kind !== 'individual') {
            return response()->json(['ok' => true, 'ignorado' => 'chat']);
        }

        // Reintentos de OpenWA: el mismo mensaje no se procesa dos veces
        $waId = isset($data['id']) ? (is_array($data['id']) ? ($data['id']['_serialized'] ?? null) : (string) $data['id']) : null;
        if ($waId && MensajeWa::where('wa_id', mb_substr($waId, 0, 191))->exists()) {
            return response()->json(['ok' => true, 'duplicado' => true]);
        }

        dispatch(function () use ($tenant, $data) {
            try {
                app(Bot::class)->procesar($tenant, $data);
            } catch (\Illuminate\Database\UniqueConstraintViolationException) {
                // otro reintento ya lo procesó
            } catch (\Throwable $e) {
                Log::error('Bot domicilios: ' . $e->getMessage(), ['trace' => mb_substr($e->getTraceAsString(), 0, 2000)]);
            }
        })->afterResponse();

        return response()->json(['ok' => true]);
    }
}
