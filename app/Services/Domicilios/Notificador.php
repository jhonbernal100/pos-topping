<?php

namespace App\Services\Domicilios;

use App\Models\ConversacionWa;
use App\Models\MensajeWa;
use App\Models\Tenant;
use App\Models\Venta;
use App\Services\WhatsApp\WhatsAppGateway;

/**
 * Envía mensajes al cliente por WhatsApp y los deja registrados en la conversación.
 * Solo escribe a clientes que ya escribieron primero (nunca mensajes masivos).
 */
class Notificador
{
    public function __construct(private WhatsAppGateway $wa)
    {
    }

    public function enviar(ConversacionWa $conv, string $texto): bool
    {
        $tenant = Tenant::find($conv->tenant_id);
        $id     = $tenant ? $this->wa->enviarTexto($tenant, $conv->chat_id, $texto) : null;

        MensajeWa::create([
            'tenant_id'       => $conv->tenant_id,
            'conversacion_id' => $conv->id,
            'direccion'       => 'saliente',
            'wa_id'           => null,
            'tipo'            => 'text',
            'cuerpo'          => $texto,
            'enviado'         => $id !== null,
        ]);

        return $id !== null;
    }

    /** Avisos de estado del pedido: pago_confirmado | pago_rechazado | preparacion | despachado | anulado */
    public function aviso(Venta $venta, string $tipo): bool
    {
        if ($venta->canal !== 'domicilio' || !$venta->conversacion_wa_id) {
            return false;
        }

        $conv = ConversacionWa::find($venta->conversacion_wa_id);
        if (!$conv) {
            return false;
        }

        $etiqueta = $venta->numero_turno ? '#' . $venta->numero_turno : '';
        $texto = match ($tipo) {
            'pago_confirmado' => "✅ *¡Pago confirmado!* Tu pedido {$etiqueta} ya está en la cola de la cocina. Te avisamos cuando empecemos a prepararlo.",
            'pago_rechazado'  => "⚠️ No pudimos confirmar tu pago" . ($venta->motivo_rechazo ? ": {$venta->motivo_rechazo}" : '.')
                                 . "\nEnvía de nuevo la captura del pago o escribe *asesor* para hablar con una persona.",
            'preparacion'     => "👩‍🍳 *Tu pedido {$etiqueta} está en preparación.* ¡Ya casi!",
            'despachado'      => "🛵 *¡Tu domicilio salió!* Pedido {$etiqueta} va en camino a: {$venta->direccion_entrega}. ¡Gracias por pedir en {$venta->tenant?->nombre}! 🍦",
            'anulado'         => "Tu pedido fue cancelado. Si quieres hacer uno nuevo, escribe *hola*.",
            default           => null,
        };

        if (!$texto) {
            return false;
        }

        if ($tipo === 'despachado' || $tipo === 'anulado') {
            $conv->update(['estado' => 'finalizado']);
        }

        return $this->enviar($conv, $texto);
    }
}
