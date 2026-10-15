<?php

namespace App\Services;

use App\Models\Insumo;
use App\Models\InsumoAlias;
use App\Models\Proveedor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Lee una factura de proveedor (PDF o foto) con Gemini y la prepara para la pantalla de compras:
 * identifica el proveedor, las líneas, los impuestos y propone el insumo de cada línea.
 */
class LectorFacturaService
{
    public const MIMES = [
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
    ];

    public function __construct(private GeminiService $gemini)
    {
    }

    /** Lectura cruda de Gemini (estructura del esquema). */
    public function leer(UploadedFile $archivo): array
    {
        $ext  = strtolower($archivo->getClientOriginalExtension() ?: $archivo->extension());
        $mime = self::MIMES[$ext] ?? $archivo->getMimeType();

        try {
            $datos = $this->gemini->json([
                ['inline_data' => ['mime_type' => $mime, 'data' => base64_encode(file_get_contents($archivo->getRealPath()))]],
                ['text' => $this->prompt()],
            ], $this->esquema(), 'Gemini factura');
        } catch (RuntimeException $e) {
            throw new RuntimeException(str_contains($e->getMessage(), 'interpretar')
                ? 'No se pudo interpretar la factura. Prueba con una foto más nítida o el PDF original.'
                : $e->getMessage());
        }

        if (isset($datos['es_factura']) && $datos['es_factura'] === false) {
            throw new RuntimeException('El archivo no parece una factura de compra.');
        }

        return $datos;
    }

    /**
     * Convierte la lectura en datos listos para el formulario:
     * proveedor existente o nuevo, líneas con insumo sugerido y factor, impuestos y totales.
     */
    public function preparar(array $lectura): array
    {
        $proveedor = $this->buscarProveedor($lectura['proveedor_nit'] ?? null, $lectura['proveedor_nombre'] ?? null);
        $insumos   = Insumo::where('activo', true)->get(['id', 'nombre', 'unidad']);

        $lineas = collect($lectura['items'] ?? [])
            ->filter(fn ($it) => trim((string) ($it['descripcion'] ?? '')) !== '')
            ->map(fn ($it) => $this->prepararLinea($it, $proveedor?->id, $insumos))
            ->values();

        $impuestos = collect($lectura['impuestos'] ?? [])
            ->filter(fn ($i) => (float) ($i['valor'] ?? 0) > 0)
            ->map(fn ($i) => ['nombre' => trim((string) ($i['nombre'] ?? 'Impuesto')), 'valor' => round((float) $i['valor'])])
            ->values();

        return [
            'proveedor' => $proveedor
                ? ['id' => $proveedor->id, 'nombre' => $proveedor->nombre, 'nuevo' => false]
                : ['id' => null, 'nombre' => trim((string) ($lectura['proveedor_nombre'] ?? '')), 'nit' => trim((string) ($lectura['proveedor_nit'] ?? '')), 'nuevo' => true],
            'numero_factura' => trim((string) ($lectura['numero_factura'] ?? '')) ?: null,
            'fecha'          => $this->fecha($lectura['fecha'] ?? null),
            'lineas'         => $lineas,
            'impuestos'      => $impuestos,
            'total_impuestos'=> (int) $impuestos->sum('valor'),
            'total_factura'  => isset($lectura['total']) ? (int) round((float) $lectura['total']) : null,
        ];
    }

    // ---------------------------------------------------------------

    private function prepararLinea(array $it, ?int $proveedorId, Collection $insumos): array
    {
        $descripcion = trim((string) $it['descripcion']);
        $cantidad    = (float) ($it['cantidad'] ?? 0) ?: 1;
        $total       = (float) ($it['valor_total'] ?? 0);

        // Si la IA no trae el total de la línea, se calcula con unitario, cantidad y descuento
        if ($total <= 0 && !empty($it['valor_unitario'])) {
            $total = (float) $it['valor_unitario'] * $cantidad * (1 - ((float) ($it['descuento_porcentaje'] ?? 0)) / 100);
        }

        $linea = [
            'descripcion'      => $descripcion,
            'codigo'           => $it['codigo'] ?? null,
            'unidad_factura'   => mb_substr(trim((string) ($it['unidad'] ?? '')), 0, 30) ?: null,
            'cantidad_factura' => round($cantidad, 3),
            'costo_total'      => (int) round($total),
            'es_servicio'      => (bool) ($it['es_servicio'] ?? false),
            'insumo_id'        => null,
            'factor'           => null,
            'coincidencia'     => null,   // alias | sugerido | null
        ];

        if ($linea['es_servicio']) {
            return $linea;
        }

        // 1. Lo que ya se aprendió de facturas anteriores
        $texto = InsumoAlias::normalizar($descripcion);
        // Primero el alias de este mismo proveedor; si no, el de cualquier proveedor (el más reciente)
        $alias = InsumoAlias::where('texto', $texto)
            ->whereHas('insumo', fn ($q) => $q->where('activo', true))
            ->orderByDesc('updated_at')
            ->get()
            ->sortByDesc(fn ($a) => (int) ($proveedorId !== null && (int) $a->proveedor_id === $proveedorId))
            ->first();

        if ($alias) {
            $linea['insumo_id']    = $alias->insumo_id;
            $linea['factor']       = (float) $alias->factor;
            $linea['coincidencia'] = 'alias';
            return $linea;
        }

        // 2. Parecido con el nombre de un insumo (el usuario confirma)
        $mejor = null;
        $mejorPuntaje = 0;
        foreach ($insumos as $insumo) {
            $puntaje = $this->parecido($texto, InsumoAlias::normalizar($insumo->nombre));
            if ($puntaje > $mejorPuntaje) {
                $mejorPuntaje = $puntaje;
                $mejor = $insumo;
            }
        }

        if ($mejor && $mejorPuntaje >= 0.6) {
            $linea['insumo_id']    = $mejor->id;
            $linea['coincidencia'] = 'sugerido';
        }

        return $linea;
    }

    /** Proporción de palabras del insumo que aparecen en la descripción (0 a 1). */
    private function parecido(string $descripcion, string $nombre): float
    {
        $ignorar  = ['de', 'del', 'la', 'el', 'x', 'y', 'con', 'en', 'por', 'und', 'unidad', 'gr', 'g', 'kg', 'ml', 'lt'];
        $palabras = array_values(array_diff(array_filter(explode(' ', $nombre), fn ($p) => mb_strlen($p) > 1), $ignorar));
        if (!$palabras) {
            return 0;
        }

        $enDescripcion = explode(' ', $descripcion);
        $aciertos = 0;
        foreach ($palabras as $p) {
            foreach ($enDescripcion as $d) {
                // coincidencia exacta o singular/plural (vaso / vasos)
                if ($d === $p || rtrim($d, 's') === rtrim($p, 's')) {
                    $aciertos++;
                    break;
                }
            }
        }

        return $aciertos / count($palabras);
    }

    private function buscarProveedor(?string $nit, ?string $nombre): ?Proveedor
    {
        $proveedores = Proveedor::where('activo', true)->get(['id', 'nombre', 'nit']);

        $digitos = preg_replace('/\D/', '', (string) $nit);
        if (strlen($digitos) >= 6) {
            foreach ($proveedores as $p) {
                $d = preg_replace('/\D/', '', (string) $p->nit);
                if ($d === '') {
                    continue;
                }
                // Con o sin dígito de verificación
                if ($d === $digitos || substr($d, 0, -1) === $digitos || $d === substr($digitos, 0, -1)
                    || substr($d, 0, 9) === substr($digitos, 0, 9) && strlen($d) >= 9 && strlen($digitos) >= 9) {
                    return $p;
                }
            }
        }

        $n = InsumoAlias::normalizar((string) $nombre);
        if ($n !== '') {
            $limpiar = fn ($t) => trim(preg_replace('/\b(sas|s a s|ltda|limitada|sa|s a)\b/', '', $t));
            foreach ($proveedores as $p) {
                $pn = InsumoAlias::normalizar($p->nombre);
                if ($pn !== '' && ($limpiar($pn) === $limpiar($n) || str_contains($n, $limpiar($pn)) || str_contains($pn, $limpiar($n)))) {
                    return $p;
                }
            }
        }

        return null;
    }

    private function fecha(?string $fecha): string
    {
        $hoy = now()->toDateString();
        if (!$fecha || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || !strtotime($fecha)) {
            return $hoy;
        }
        return $fecha > $hoy ? $hoy : $fecha;
    }

    private function prompt(): string
    {
        return <<<TXT
Eres un asistente contable colombiano. Lee esta factura de compra de un proveedor (PDF o foto).
El COMPRADOR es una heladería; el PROVEEDOR es quien EMITE la factura (su nombre y NIT suelen ir arriba, junto al logo).
No confundas los datos del cliente/comprador con los del proveedor.

Reglas:
- Devuelve los números como números simples, sin separadores de miles. Las facturas colombianas usan
  a veces "." para miles y "," para decimales ($316.750,0) y otras veces "," para miles y "." para decimales (64,860.00):
  deduce el formato por el contexto (el total debe cuadrar con la suma de las líneas más impuestos).
- Una línea por cada producto o servicio facturado, en el orden de la factura.
- "valor_total" de cada línea es el valor DESPUÉS del descuento de la línea y ANTES de impuestos.
- "unidad" es la unidad tal como aparece (UND, CAJ, Kilogramo, Bolsa, Galón...).
- Marca "es_servicio" = true para domicilios, fletes, transporte, envíos u otros servicios que no son mercancía.
- "impuestos": cada impuesto del total de la factura por separado (IVA, ICUI, impoconsumo, bolsas...). No incluyas retenciones.
- "fecha" en formato AAAA-MM-DD (fecha de expedición o generación).
- "numero_factura" con prefijo si lo tiene (ej. FEE 1772 → "FEE1772", No. 12554 → "12554").
- Si el documento no es una factura o cuenta de cobro de compra, pon "es_factura" = false.
TXT;
    }

    private function esquema(): array
    {
        $texto  = ['type' => 'STRING', 'nullable' => true];
        $numero = ['type' => 'NUMBER', 'nullable' => true];

        return [
            'type'       => 'OBJECT',
            'properties' => [
                'es_factura'       => ['type' => 'BOOLEAN'],
                'proveedor_nombre' => $texto,
                'proveedor_nit'    => $texto,
                'numero_factura'   => $texto,
                'fecha'            => $texto,
                'items'            => [
                    'type'  => 'ARRAY',
                    'items' => [
                        'type'       => 'OBJECT',
                        'properties' => [
                            'codigo'               => $texto,
                            'descripcion'          => ['type' => 'STRING'],
                            'unidad'               => $texto,
                            'cantidad'             => ['type' => 'NUMBER'],
                            'valor_unitario'       => $numero,
                            'descuento_porcentaje' => $numero,
                            'valor_total'          => ['type' => 'NUMBER'],
                            'es_servicio'          => ['type' => 'BOOLEAN'],
                        ],
                        'required' => ['descripcion', 'cantidad', 'valor_total', 'es_servicio'],
                    ],
                ],
                'subtotal'  => $numero,
                'impuestos' => [
                    'type'  => 'ARRAY',
                    'items' => [
                        'type'       => 'OBJECT',
                        'properties' => ['nombre' => ['type' => 'STRING'], 'valor' => ['type' => 'NUMBER']],
                        'required'   => ['nombre', 'valor'],
                    ],
                ],
                'total' => $numero,
            ],
            'required' => ['es_factura', 'items', 'impuestos'],
        ];
    }
}
