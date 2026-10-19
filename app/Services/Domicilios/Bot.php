<?php

namespace App\Services\Domicilios;

use App\Models\ConversacionWa;
use App\Models\MensajeWa;
use App\Models\Sede;
use App\Models\Tenant;
use App\Models\Venta;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Conversación del bot de domicilios.
 *
 * inicio → esperando_ubicacion → esperando_direccion → esperando_pedido (enlace al menú)
 *        → esperando_pago (captura) → en_curso → finalizado
 *
 * Palabras clave en cualquier momento: asesor, cancelar, estado, hola/menu.
 */
class Bot
{
    private const HORAS_ENLACE = 3;
    private const MINUTOS_ASESOR = 60;

    public function __construct(
        private Notificador $notificador,
        private Cobertura $cobertura,
        private LectorComprobante $lector,
        private Pedidos $pedidos,
    ) {
    }

    /** Procesa un mensaje entrante ya validado (individual, no enviado por el negocio). */
    public function procesar(Tenant $tenant, array $m): void
    {
        $chatId = (string) ($m['chatId'] ?? $m['from'] ?? '');
        if ($chatId === '') {
            return;
        }

        $telefono = $this->telefono($m);
        $conv = ConversacionWa::firstOrCreate(
            ['tenant_id' => $tenant->id, 'chat_id' => $chatId],
            ['telefono' => $telefono, 'estado' => 'inicio']
        );
        if (!$conv->telefono && $telefono) {
            $conv->telefono = $telefono;
        }
        $nombre = $m['contact']['pushname'] ?? $m['contact']['name'] ?? $m['notifyName'] ?? null;
        if (!$conv->nombre && $nombre) {
            $conv->nombre = mb_substr($nombre, 0, 120);
        }
        $conv->ultimo_mensaje_at = now();
        $conv->save();

        $tipo  = (string) ($m['type'] ?? 'text');
        $texto = trim((string) ($m['body'] ?? ''));

        $mensaje = MensajeWa::create([
            'tenant_id'       => $tenant->id,
            'conversacion_id' => $conv->id,
            'direccion'       => 'entrante',
            'wa_id'           => isset($m['id']) ? mb_substr((string) (is_array($m['id']) ? ($m['id']['_serialized'] ?? json_encode($m['id'])) : $m['id']), 0, 191) : null,
            'tipo'            => mb_substr($tipo, 0, 20),
            'cuerpo'          => $tipo === 'location' ? json_encode($m['location'] ?? []) : ($texto ?: null),
        ]);

        $clave = Str::of($texto)->lower()->ascii()->trim()->toString();

        // Un asesor está atendiendo: el bot no responde (salvo que el cliente pida volver al bot)
        if ($conv->humano_hasta && $conv->humano_hasta->isFuture()) {
            if (in_array($clave, ['bot', 'hola', 'menu'], true)) {
                $conv->update(['humano_hasta' => null]);
            } else {
                return;
            }
        }

        if (in_array($clave, ['asesor', 'humano', 'persona', 'ayuda'], true)) {
            $conv->update(['humano_hasta' => now()->addMinutes(self::MINUTOS_ASESOR)]);
            $this->responder($conv, '🙋 Listo, una persona del equipo te va a responder por este chat en breve.');
            return;
        }

        if ($clave === 'cancelar') {
            $this->cancelar($conv);
            return;
        }

        if ($clave === 'estado') {
            $this->responder($conv, $this->textoEstado($conv));
            return;
        }

        $venta = $conv->venta_id ? Venta::withoutGlobalScopes()->find($conv->venta_id) : null;
        $pedidoActivo = $venta && $venta->estado === 'completada' && $venta->estado_preparacion !== 'entregado';

        if (in_array($clave, ['hola', 'menu', 'buenas', 'buenos dias', 'buenas tardes', 'buenas noches', 'pedido', 'pedir'], true) && !$pedidoActivo
            && !in_array($conv->estado, ['esperando_pago'], true)) {
            $conv->update(['estado' => 'inicio']);
        }

        // Un pedido pagado en curso: solo informar el estado
        if ($pedidoActivo) {
            $this->responder($conv, $this->textoEstado($conv) . "\n\nSi necesitas algo más escribe *asesor*.");
            return;
        }

        match ($conv->estado) {
            'esperando_ubicacion' => $this->enUbicacion($tenant, $conv, $tipo, $m),
            'esperando_direccion' => $this->enDireccion($tenant, $conv, $tipo, $texto),
            'esperando_pedido'    => $this->enPedido($tenant, $conv),
            'esperando_pago'      => $this->enPago($tenant, $conv, $tipo, $m, $mensaje),
            default               => $this->saludar($tenant, $conv),
        };
    }

    // ---------------------------------------------------------------
    // Pasos
    // ---------------------------------------------------------------

    private function saludar(Tenant $tenant, ConversacionWa $conv): void
    {
        $bienvenida = $tenant->wa_bienvenida
            ?: "¡Hola! 👋 Bienvenido a *{$tenant->nombre}* 🍦\nTe ayudo a pedir a domicilio.";

        $conv->update(['estado' => 'esperando_ubicacion', 'venta_id' => null, 'token' => null]);

        $this->responder($conv, $bienvenida
            . "\n\n📍 Para empezar, *comparte tu ubicación*: toca el clip 📎 (o el +), elige *Ubicación* y envía tu ubicación actual."
            . "\n\nEn cualquier momento puedes escribir *asesor* para hablar con una persona.");
    }

    private function enUbicacion(Tenant $tenant, ConversacionWa $conv, string $tipo, array $m): void
    {
        $lat = $m['location']['latitude'] ?? null;
        $lng = $m['location']['longitude'] ?? null;

        if ($tipo !== 'location' || !is_numeric($lat) || !is_numeric($lng)) {
            $this->responder($conv, "Necesito tu *ubicación* para saber qué sede te queda más cerca 📍\n"
                . "Toca el clip 📎 (o el +) → *Ubicación* → *Enviar mi ubicación actual*.");
            return;
        }

        $cob = $this->cobertura->para($tenant, (float) $lat, (float) $lng);

        if (!$cob) {
            $sedes = Sede::where('tenant_id', $tenant->id)->where('activo', true)->get()
                ->map(fn ($s) => "• {$s->nombre}: {$s->direccion}")->implode("\n");
            $conv->update(['estado' => 'inicio']);
            $this->responder($conv, "😔 Lo sentimos, por ahora no llegamos a esa ubicación.\n"
                . ($sedes ? "Puedes visitarnos en:\n{$sedes}\n\n" : '')
                . 'Si quieres probar con otra dirección, escribe *hola*.');
            return;
        }

        $conv->update([
            'estado'          => 'esperando_direccion',
            'latitud'         => (float) $lat,
            'longitud'        => (float) $lng,
            'sede_id'         => $cob['sede']->id,
            'distancia_km'    => $cob['distancia'],
            'costo_domicilio' => $cob['costo'],
        ]);

        $sugerida = trim((string) ($m['location']['address'] ?? $m['location']['description'] ?? ''));
        $this->responder($conv, "✅ ¡Sí llegamos! Te atiende nuestra sede *{$cob['sede']->nombre}*.\n"
            . 'Domicilio: *' . ($cob['costo'] > 0 ? $this->dinero($cob['costo']) : 'gratis') . "*\n\n"
            . '🏠 Ahora escribe tu *dirección exacta*: calle o carrera, número, barrio, torre/apto y una referencia.'
            . ($sugerida ? "\n(Ubicación recibida: {$sugerida})" : ''));
    }

    private function enDireccion(Tenant $tenant, ConversacionWa $conv, string $tipo, string $texto): void
    {
        if ($tipo === 'location') {
            // Cambió de ubicación: volver a calcular
            $conv->update(['estado' => 'esperando_ubicacion']);
            $this->responder($conv, 'Recibí una nueva ubicación. Envíala de nuevo para confirmar la sede 📍');
            return;
        }

        if (mb_strlen($texto) < 8) {
            $this->responder($conv, 'Escribe la dirección completa para que el domiciliario te encuentre, por ejemplo: '
                . '*Cra 53D Bis # 2-06, barrio Camelia, apto 302, portón blanco*.');
            return;
        }

        $conv->update(['direccion' => mb_substr($texto, 0, 191)]);
        $this->enviarEnlace($tenant, $conv);
    }

    private function enPedido(Tenant $tenant, ConversacionWa $conv): void
    {
        $this->enviarEnlace($tenant, $conv, recordatorio: true);
    }

    private function enPago(Tenant $tenant, ConversacionWa $conv, string $tipo, array $m, MensajeWa $mensaje): void
    {
        $venta = $conv->venta_id ? Venta::withoutGlobalScopes()->find($conv->venta_id) : null;
        if (!$venta || $venta->estado !== 'borrador' || $venta->estado_pago === 'cancelado') {
            $this->saludar($tenant, $conv);
            return;
        }

        $media = $m['media'] ?? null;
        $esImagen = in_array($tipo, ['image', 'document'], true) && !empty($media['data'])
            && str_starts_with((string) ($media['mimetype'] ?? ''), 'image/');

        if (!$esImagen) {
            if ($venta->estado_pago === 'por_verificar') {
                $this->responder($conv, '⏳ Ya recibimos tu comprobante y lo estamos verificando. Te avisamos en un momento.');
                return;
            }
            $this->responder($conv, $this->instruccionesPago($tenant, $venta));
            return;
        }

        // Guardar la captura (privada) y leerla con IA
        $ext  = str_contains($media['mimetype'], 'png') ? 'png' : (str_contains($media['mimetype'], 'webp') ? 'webp' : 'jpg');
        $ruta = "comprobantes/{$tenant->id}/{$venta->id}-" . Str::random(8) . ".{$ext}";
        Storage::disk('local')->put($ruta, base64_decode($media['data']));
        $mensaje->update(['media_path' => $ruta]);

        $lectura = $this->lector->leer($media['data'], $media['mimetype'], $venta);
        $venta->update(['comprobante' => $ruta, 'lectura_comprobante' => $lectura, 'estado_pago' => 'por_verificar', 'motivo_rechazo' => null]);

        if (($lectura['leido'] ?? false) && !($lectura['es_comprobante'] ?? true)) {
            $this->responder($conv, '🤔 Esa imagen no parece un comprobante de pago. Si ya pagaste, envía la captura de la transferencia; '
                . 'igual la revisaremos.');
            return;
        }

        $this->responder($conv, "🧾 ¡Recibimos tu comprobante! Lo estamos verificando y en un momento confirmamos tu pedido.");
    }

    // ---------------------------------------------------------------

    private function enviarEnlace(Tenant $tenant, ConversacionWa $conv, bool $recordatorio = false): void
    {
        if (!$conv->tokenVigente()) {
            $conv->token = Str::random(32);
        }
        $conv->token_expira_at = now()->addHours(self::HORAS_ENLACE);
        $conv->estado = 'esperando_pedido';
        $conv->save();

        $url = url('/pedir/' . $conv->token);
        $this->responder($conv, ($recordatorio ? '👇 Arma tu pedido aquí:' : "📝 ¡Perfecto! Ahora arma tu pedido en nuestro menú:")
            . "\n{$url}\n\nCuando lo envíes te llegará el total y cómo pagar por Nequi o Daviplata. "
            . 'Para cambiar la dirección escribe *hola*.');
    }

    /** Texto con el total y los datos para pagar (lo usa también el menú web al crear el pedido). */
    public function instruccionesPago(Tenant $tenant, Venta $venta): string
    {
        $cuentas = [];
        if ($tenant->pago_nequi) {
            $cuentas[] = "• *Nequi:* {$tenant->pago_nequi}";
        }
        if ($tenant->pago_daviplata) {
            $cuentas[] = "• *Daviplata:* {$tenant->pago_daviplata}";
        }

        return '💳 Total a pagar: *' . $this->dinero($venta->total) . '*'
            . ($venta->costo_domicilio > 0 ? ' (incluye domicilio ' . $this->dinero($venta->costo_domicilio) . ')' : '')
            . "\n\nTransfiere a:\n" . implode("\n", $cuentas)
            . ($tenant->pago_titular ? "\nA nombre de: {$tenant->pago_titular}" : '')
            . "\n\n📸 Luego *envía aquí la captura del pago*. Si quieres cancelar escribe *cancelar*.";
    }

    private function cancelar(ConversacionWa $conv): void
    {
        $venta = $conv->venta_id ? Venta::withoutGlobalScopes()->find($conv->venta_id) : null;

        if ($venta && $venta->estado === 'completada') {
            $this->responder($conv, 'Tu pedido ya fue pagado y está en proceso, así que no se puede cancelar por aquí. '
                . 'Escribe *asesor* para hablar con una persona.');
            return;
        }

        if ($venta && $venta->estado === 'borrador') {
            try {
                $this->pedidos->cancelar($venta, avisar: false);
            } catch (\Throwable $e) {
                Log::info('Cancelar pedido: ' . $e->getMessage());
            }
        }

        $conv->update(['estado' => 'inicio', 'venta_id' => null, 'token' => null]);
        $this->responder($conv, 'Listo, cancelamos tu pedido. Cuando quieras pedir de nuevo escribe *hola* 🍦');
    }

    private function textoEstado(ConversacionWa $conv): string
    {
        $venta = $conv->venta_id ? Venta::withoutGlobalScopes()->find($conv->venta_id) : null;
        if (!$venta || $venta->estado_pago === 'cancelado') {
            return 'No tienes un pedido en curso. Escribe *hola* para hacer uno.';
        }

        $etiqueta = $venta->numero_turno ? ' #' . $venta->numero_turno : '';
        if ($venta->estado === 'borrador') {
            return match ($venta->estado_pago) {
                'por_verificar' => "⏳ Tu pedido{$etiqueta} está esperando la verificación del pago.",
                'rechazado'     => '⚠️ No pudimos confirmar tu pago. Envía de nuevo la captura o escribe *asesor*.',
                default         => '💳 Tu pedido está pendiente de pago. Total: *' . $this->dinero($venta->total) . '*.',
            };
        }

        return match ($venta->estado_preparacion) {
            'pendiente'      => "🧾 Tu pedido{$etiqueta} está pagado y en la cola de la cocina.",
            'en_preparacion' => "👩‍🍳 Tu pedido{$etiqueta} está en preparación.",
            'listo'          => "📦 Tu pedido{$etiqueta} está listo y pronto sale a domicilio.",
            'entregado'      => "🛵 Tu pedido{$etiqueta} ya salió a domicilio.",
            default          => "Tu pedido{$etiqueta} está en proceso.",
        };
    }

    private function responder(ConversacionWa $conv, string $texto): void
    {
        $this->notificador->enviar($conv, $texto);
    }

    private function telefono(array $m): ?string
    {
        $fuente = $m['senderPhone'] ?? null;
        if (!$fuente && isset($m['chatId']) && str_ends_with((string) $m['chatId'], '@c.us')) {
            $fuente = $m['chatId'];
        }
        $digitos = preg_replace('/\D/', '', (string) $fuente);

        return $digitos !== '' ? mb_substr($digitos, 0, 30) : null;
    }

    private function dinero(int $v): string
    {
        return '$' . number_format($v, 0, ',', '.');
    }
}
