<?php

namespace App\Services\WhatsApp;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * OpenWA (https://github.com/rmyndharis/OpenWA), pasarela autoalojada.
 * Cada negocio usa su propia sesión (tenants.wa_session_id).
 */
class OpenWaGateway implements WhatsAppGateway
{
    public function enviarTexto(Tenant $tenant, string $chatId, string $texto): ?string
    {
        if (!$this->configurado($tenant)) {
            Log::warning("OpenWA: tenant {$tenant->id} sin URL, llave o sesión configurada");
            return null;
        }

        try {
            $r = $this->http()
                ->withHeaders(['Idempotency-Key' => (string) Str::uuid()])
                ->post($this->url("sessions/{$tenant->wa_session_id}/messages/send-text"), [
                    'chatId' => $chatId,
                    'text'   => $texto,
                ]);

            if ($r->failed()) {
                Log::warning('OpenWA send-text HTTP ' . $r->status() . ': ' . mb_substr($r->body(), 0, 300));
                return null;
            }

            return (string) ($r->json('id') ?? $r->json('messageId') ?? $r->json('data.id') ?? 'ok');
        } catch (\Throwable $e) {
            Log::warning('OpenWA send-text: ' . $e->getMessage());
            return null;
        }
    }

    public function estado(Tenant $tenant): ?string
    {
        if (!$this->configurado($tenant)) {
            return null;
        }

        try {
            $r = $this->http()->get($this->url("sessions/{$tenant->wa_session_id}"));
            return $r->successful() ? (string) ($r->json('status') ?? $r->json('data.status')) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function configurado(Tenant $tenant): bool
    {
        return config('services.openwa.url') && config('services.openwa.api_key') && $tenant->wa_session_id;
    }

    private function http()
    {
        return Http::timeout(15)->acceptJson()->withHeaders(['X-API-Key' => config('services.openwa.api_key')]);
    }

    private function url(string $ruta): string
    {
        return rtrim(config('services.openwa.url'), '/') . '/api/' . $ruta;
    }
}
