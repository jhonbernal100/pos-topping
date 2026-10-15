<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Llamadas a Gemini (Google AI) para todo POS Topping.
 *
 * - Usa el modelo Flash más reciente que Google tenga disponible para la llave
 *   (los modelos se retiran y en proyectos nuevos responden 404).
 * - Si un modelo está saturado (503/429) reintenta una vez y pasa al siguiente.
 * - Se puede fijar un modelo con GEMINI_MODELO en .env.
 */
class GeminiService
{
    private const API = 'https://generativelanguage.googleapis.com/v1beta/';
    private const TRANSITORIO = [429, 500, 503, 504];

    /**
     * Envía partes (texto, archivos) y devuelve el JSON de la respuesta como arreglo.
     *
     * @param array      $partes  [['text' => '...'], ['inline_data' => ['mime_type' => ..., 'data' => base64]]]
     * @param array|null $esquema responseSchema de Gemini (opcional)
     */
    public function json(array $partes, ?array $esquema = null, string $etiqueta = 'Gemini'): array
    {
        $config = ['temperature' => 0, 'responseMimeType' => 'application/json'];
        if ($esquema) {
            $config['responseSchema'] = $esquema;
        }

        $texto = $this->generar(['contents' => [['role' => 'user', 'parts' => $partes]], 'generationConfig' => $config], $etiqueta);
        $texto = trim(preg_replace('/^```(json)?|```$/m', '', $texto));
        $datos = json_decode($texto, true);

        if (!is_array($datos)) {
            Log::warning("{$etiqueta}: respuesta no es JSON: " . mb_substr($texto, 0, 500));
            throw new RuntimeException('No se pudo interpretar la respuesta de la IA.');
        }

        return $datos;
    }

    /** Devuelve el texto de la respuesta. */
    public function generar(array $cuerpo, string $etiqueta = 'Gemini'): string
    {
        $clave = config('gemini.api_key') ?: env('GEMINI_API_KEY');
        if (!$clave) {
            throw new RuntimeException('Falta configurar la llave de Gemini (GEMINI_API_KEY).');
        }

        @set_time_limit(240);

        $respuesta = null;
        $llamadas  = 0;

        foreach ($this->candidatos($clave) as $modelo) {
            for ($intento = 0; $intento < 2 && $llamadas < 4; $intento++) {
                $llamadas++;
                $respuesta = $this->llamar($clave, $modelo, $cuerpo);
                if (!in_array($respuesta->status(), self::TRANSITORIO, true)) {
                    break;
                }
                Log::info("{$etiqueta}: {$modelo} respondió {$respuesta->status()}, reintentando");
                if ($intento === 0) {
                    sleep(2);
                }
            }

            if ($respuesta->successful()) {
                Cache::put('gemini_modelo_ok', $modelo, now()->addDay());
                break;
            }

            // 400 / 403: el problema no es el modelo, no tiene sentido probar otro
            if (!in_array($respuesta->status(), array_merge([404], self::TRANSITORIO), true) || $llamadas >= 4) {
                break;
            }
            if ($respuesta->status() === 404) {
                Cache::forget('gemini_modelo_ok');
            }
        }

        if (!$respuesta || $respuesta->failed()) {
            $estado = $respuesta?->status();
            Log::warning("{$etiqueta}: HTTP {$estado} " . mb_substr((string) $respuesta?->body(), 0, 500));
            throw new RuntimeException(in_array($estado, self::TRANSITORIO, true)
                ? 'El servicio de IA de Google está saturado en este momento. Intenta de nuevo en un minuto.'
                : 'El servicio de lectura no respondió. Intenta de nuevo en un momento.');
        }

        return collect($respuesta->json('candidates.0.content.parts', []))->pluck('text')->filter()->implode('');
    }

    private function llamar(string $clave, string $modelo, array $cuerpo): Response
    {
        return Http::timeout(60)
            ->withHeaders(['x-goog-api-key' => $clave])
            ->post(self::API . 'models/' . $modelo . ':generateContent', $cuerpo);
    }

    /**
     * Modelos a probar, en orden: GEMINI_MODELO del .env (o el antiguo GEMINI_MODELO_FACTURAS),
     * el último que funcionó y los Flash disponibles para esta llave (lista consultada una vez al día).
     */
    private function candidatos(string $clave): array
    {
        $disponibles = Cache::get('gemini_modelos_disponibles') ?: $this->listarModelos($clave);

        $lista = array_merge(
            array_filter([env('GEMINI_MODELO') ?: env('GEMINI_MODELO_FACTURAS'), Cache::get('gemini_modelo_ok')]),
            $disponibles ?: ['gemini-flash-latest', 'gemini-flash-lite-latest']
        );

        return array_values(array_unique($lista));
    }

    private function listarModelos(string $clave): array
    {
        $nombres = [];
        $pagina  = null;

        do {
            $r = Http::timeout(20)->withHeaders(['x-goog-api-key' => $clave])
                ->get(self::API . 'models', array_filter(['pageSize' => 200, 'pageToken' => $pagina]));
            if ($r->failed()) {
                Log::warning('Gemini: no se pudo listar modelos, HTTP ' . $r->status() . ' ' . mb_substr($r->body(), 0, 300));
                return [];   // sin caché: se vuelve a consultar en la próxima llamada
            }
            foreach ($r->json('models', []) as $m) {
                if (in_array('generateContent', $m['supportedGenerationMethods'] ?? [], true)) {
                    $nombres[] = str_replace('models/', '', $m['name']);
                }
            }
            $pagina = $r->json('nextPageToken');
        } while ($pagina);

        $ordenados = self::ordenarModelos($nombres);
        if ($ordenados) {
            Cache::put('gemini_modelos_disponibles', $ordenados, now()->addDay());
        }

        return $ordenados;
    }

    /**
     * Orden de preferencia: gemini-X-flash estable (versión más alta primero), luego Flash en preview,
     * luego Flash-Lite y al final los alias "latest". Se descartan image, tts, live, audio y embedding.
     */
    public static function ordenarModelos(array $nombres): array
    {
        $version = fn ($n) => preg_match('/gemini-(\d+(?:\.\d+)?)/', $n, $m) ? (float) $m[1] : 0;
        $flash   = array_filter($nombres, fn ($n) => str_starts_with($n, 'gemini-') && str_contains($n, 'flash')
            && !preg_match('/image|tts|live|audio|embedding|thinking-exp|latest/', $n));

        $grupo = fn ($n) => match (true) {
            (bool) preg_match('/^gemini-\d+(\.\d+)?-flash$/', $n) => 0,   // estable
            !str_contains($n, 'lite')                              => 1,   // preview / variantes
            default                                                => 2,   // lite
        };

        usort($flash, fn ($a, $b) => [$grupo($a), -$version($a), $a] <=> [$grupo($b), -$version($b), $b]);

        foreach (['gemini-flash-latest', 'gemini-flash-lite-latest'] as $alias) {
            if (in_array($alias, $nombres, true)) {
                $flash[] = $alias;
            }
        }

        return array_values($flash);
    }
}
