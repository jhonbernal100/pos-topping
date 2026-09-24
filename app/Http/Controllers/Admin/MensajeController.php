<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Mensaje;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MensajeController extends Controller
{
    // Enviar mensaje desde el panel admin
    public function enviar(Request $request)
    {
        $request->validate([
            'tenant_id' => 'required|exists:tenants,id',
            'asunto'    => 'required|string|max:191',
            'contenido' => 'required|string|max:500',
            'tipo'      => 'required|in:plataforma,sms,ambos',
        ]);

        $tenant = Tenant::findOrFail($request->tenant_id);

        // Obtener gerente del tenant
        $gerente = User::where('tenant_id', $tenant->id)
            ->where('rol', 'dueno')
            ->first();

        if (!$gerente) {
            return response()->json([
                'success' => false,
                'mensaje' => 'No se encontró gerente para este negocio',
            ], 422);
        }

        // Crear mensaje en plataforma
        $mensaje = Mensaje::create([
            'tenant_id'     => $tenant->id,
            'de_usuario_id' => auth()->id(),
            'asunto'        => $request->asunto,
            'contenido'     => $request->contenido,
            'tipo'          => $request->tipo,
            'leido'         => false,
            'estado_sms'    => in_array($request->tipo, ['sms', 'ambos']) ? 'pendiente' : null,
        ]);

        // Enviar SMS si corresponde
        $resultadoSms = null;
        if (in_array($request->tipo, ['sms', 'ambos']) && $tenant->telefono) {
            $resultadoSms = $this->enviarSms($tenant->telefono, $request->asunto, $request->contenido);
            $mensaje->update(['estado_sms' => $resultadoSms ? 'enviado' : 'fallido']);
        }

        return response()->json([
            'success'      => true,
            'mensaje'      => 'Mensaje enviado correctamente',
            'sms_enviado'  => $resultadoSms,
            'sin_telefono' => in_array($request->tipo, ['sms', 'ambos']) && !$tenant->telefono,
        ]);
    }

    // Enviar a todas los negocios
    public function enviarMasivo(Request $request)
    {
        $request->validate([
            'asunto'    => 'required|string|max:191',
            'contenido' => 'required|string|max:500',
            'tipo'      => 'required|in:plataforma,sms,ambos',
            'filtro'    => 'nullable|in:todas,trial,activa,vencida',
        ]);

        $query = Tenant::query();

        if ($request->filtro && $request->filtro !== 'todas') {
            $query->where('subscription_status', $request->filtro);
        }

        $tenants = $query->get();
        $enviados = 0;

        foreach ($tenants as $tenant) {
            Mensaje::create([
                'tenant_id'     => $tenant->id,
                'de_usuario_id' => auth()->id(),
                'asunto'        => $request->asunto,
                'contenido'     => $request->contenido,
                'tipo'          => $request->tipo,
                'leido'         => false,
                'estado_sms'    => in_array($request->tipo, ['sms', 'ambos']) ? 'pendiente' : null,
            ]);

            if (in_array($request->tipo, ['sms', 'ambos']) && $tenant->telefono) {
                $this->enviarSms($tenant->telefono, $request->asunto, $request->contenido);
            }

            $enviados++;
        }

        return response()->json([
            'success' => true,
            'mensaje' => "Mensaje enviado a {$enviados} negocios",
        ]);
    }

    // Marcar como leido
    public function marcarLeido(Request $request)
    {
        Mensaje::where('tenant_id', session('tenant_id'))
            ->where('leido', false)
            ->update([
                'leido'    => true,
                'leido_at' => now(),
            ]);

        return response()->json(['success' => true]);
    }

    // Obtener mensajes no leidos (para campanita)
    public function noLeidos()
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) {
            return response()->json(['count' => 0, 'mensajes' => []]);
        }

        $mensajes = Mensaje::where('tenant_id', $tenantId)
            ->where('leido', false)
            ->orderByDesc('created_at')
            ->get(['id', 'asunto', 'contenido', 'created_at']);

        return response()->json([
            'count'    => $mensajes->count(),
            'mensajes' => $mensajes,
        ]);
    }

    // Enviar SMS via Infobip
    private function enviarSms(string $telefono, string $asunto, string $contenido): bool
    {
        try {
            $apiKey  = config('services.infobip.api_key');
            $baseUrl = config('services.infobip.base_url');
            $from    = config('services.infobip.from', 'POS-Ferretero');

            // Limpiar y formatear número colombiano
            $numero = preg_replace('/[^0-9]/', '', $telefono);
            if (strlen($numero) === 10 && str_starts_with($numero, '3')) {
                $numero = '57' . $numero;
            }

            $texto = "POS Topping - {$asunto}: {$contenido}";

            $response = Http::withHeaders([
                'Authorization' => "App {$apiKey}",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ])->post("https://{$baseUrl}/sms/2/text/advanced", [
                'messages' => [[
                    'from'         => $from,
                    'destinations' => [['to' => $numero]],
                    'text'         => mb_substr($texto, 0, 160),
                ]],
            ]);

            return $response->successful();

        } catch (\Exception $e) {
            \Log::error('Infobip SMS error: ' . $e->getMessage());
            return false;
        }
    }
}