<?php

namespace App\Services\WhatsApp;

use App\Models\Tenant;

/**
 * Conexión con WhatsApp. Hoy se implementa con OpenWA; si se cambia a la API
 * oficial de Meta solo se reemplaza esta clase (ver AppServiceProvider).
 */
interface WhatsAppGateway
{
    /** Envía un texto. Devuelve el id del mensaje en WhatsApp o null si falló. */
    public function enviarTexto(Tenant $tenant, string $chatId, string $texto): ?string;

    /** Estado de la sesión del negocio: ready, qr_ready, disconnected, … o null si no responde. */
    public function estado(Tenant $tenant): ?string;
}
