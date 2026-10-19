<?php

namespace App\Services\Domicilios;

use App\Models\Venta;
use App\Services\GeminiService;
use Illuminate\Support\Facades\Log;

/**
 * Lee con IA la captura de un pago por Nequi / Daviplata y la compara con el pedido.
 * Nunca confirma sola: deja el resultado para que el cajero decida.
 */
class LectorComprobante
{
    public function __construct(private GeminiService $gemini)
    {
    }

    public function leer(string $base64, string $mime, Venta $venta): array
    {
        try {
            $d = $this->gemini->json([
                ['inline_data' => ['mime_type' => $mime, 'data' => $base64]],
                ['text' => 'Esta imagen debería ser el comprobante de una transferencia hecha en Colombia (Nequi, Daviplata, '
                    . 'Bancolombia, Bre-B u otra). Extrae los datos tal como aparecen. "monto" como número sin puntos ni signos. '
                    . '"destino" es el número de celular o cuenta que recibió el dinero (puede aparecer parcialmente oculto). '
                    . 'Si no es un comprobante de pago, es_comprobante = false.'],
            ], [
                'type'       => 'OBJECT',
                'properties' => [
                    'es_comprobante' => ['type' => 'BOOLEAN'],
                    'plataforma'     => ['type' => 'STRING', 'nullable' => true],
                    'monto'          => ['type' => 'NUMBER', 'nullable' => true],
                    'referencia'     => ['type' => 'STRING', 'nullable' => true],
                    'fecha_hora'     => ['type' => 'STRING', 'nullable' => true],
                    'destino'        => ['type' => 'STRING', 'nullable' => true],
                    'destinatario'   => ['type' => 'STRING', 'nullable' => true],
                    'exitoso'        => ['type' => 'BOOLEAN', 'nullable' => true],
                ],
                'required' => ['es_comprobante'],
            ], 'Gemini comprobante');
        } catch (\Throwable $e) {
            Log::warning('Comprobante no leído: ' . $e->getMessage());
            return ['leido' => false, 'error' => 'No se pudo leer con IA: revísalo a mano.'];
        }

        $tenant  = $venta->tenant;
        $monto   = isset($d['monto']) ? (int) round((float) $d['monto']) : null;
        $destino = preg_replace('/\D/', '', (string) ($d['destino'] ?? ''));

        // Comparar los últimos 4 dígitos del destino con los números configurados (pueden venir ocultos: ***1234)
        $numeros = array_filter([preg_replace('/\D/', '', (string) $tenant->pago_nequi), preg_replace('/\D/', '', (string) $tenant->pago_daviplata)]);
        $coincideDestino = null;
        if ($destino !== '' && $numeros) {
            $coincideDestino = collect($numeros)->contains(fn ($n) => strlen($destino) >= 4 && str_ends_with($n, substr($destino, -4)));
        }

        return [
            'leido'            => true,
            'es_comprobante'   => (bool) ($d['es_comprobante'] ?? false),
            'plataforma'       => $d['plataforma'] ?? null,
            'monto'            => $monto,
            'referencia'       => $d['referencia'] ?? null,
            'fecha_hora'       => $d['fecha_hora'] ?? null,
            'destino'          => $d['destino'] ?? null,
            'destinatario'     => $d['destinatario'] ?? null,
            'exitoso'          => $d['exitoso'] ?? null,
            'coincide_monto'   => $monto !== null ? $monto === (int) $venta->total : null,
            'coincide_destino' => $coincideDestino,
        ];
    }
}
