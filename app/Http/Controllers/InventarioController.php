<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\Proveedor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\HttpHandler\HttpHandlerFactory;

class InventarioController extends Controller
{
    public function index()
    {
        $productos = Producto::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('inventario.index', compact('productos'));
    }

    public function capturar()
    {
        $proveedores = Proveedor::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('inventario.capturar', compact('proveedores'));
    }

    public function analizar(Request $request)
    {
        $request->validate([
            'imagen' => 'required|string',
        ]);

        try {
            $keyFile = json_decode(
                file_get_contents(base_path('gcp-vision-key.json')),
                true
            );

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/cloud-vision',
                $keyFile
            );

            $token = $credentials->fetchAuthToken(
                HttpHandlerFactory::build()
            );

            $visionResponse = Http::withHeaders([
                'Authorization' => "Bearer {$token['access_token']}",
                'Content-Type'  => 'application/json',
            ])->post('https://vision.googleapis.com/v1/images:annotate', [
                'requests' => [[
                    'image'    => ['content' => $request->imagen],
                    'features' => [['type' => 'TEXT_DETECTION', 'maxResults' => 1]],
                ]],
            ]);

            $textoOCR = $visionResponse->json(
                'responses.0.textAnnotations.0.description'
            ) ?? '';

            if (empty($textoOCR)) {
                return response()->json([
                    'success' => false,
                    'mensaje' => 'No se detecto texto en la imagen. Intenta con mejor iluminacion.',
                ], 422);
            }

            $prompt = "Eres un experto ferretero colombiano.
Analiza este texto extraído de una etiqueta de producto de ferretería:

\"$textoOCR\"

Devuélveme ÚNICAMENTE un JSON válido con esta estructura, sin texto adicional:
{
  \"nombre\": \"nombre del producto corregido y completo\",
  \"marca\": \"marca si se identifica o null\",
  \"categoria\": \"una de: Tornillería, Herramientas, Construcción, Eléctrico, Plomería, Pintura, Seguridad, General\",
  \"unidad\": \"una de: unidad, metro, kilo, litro, bolsa, caja\",
  \"referencia\": \"referencia o código si se identifica o null\"
}";

            $jsonTexto = app(\App\Services\GeminiService::class)->generar([
                'contents'         => [['role' => 'user', 'parts' => [['text' => $prompt]]]],
                'generationConfig' => ['temperature' => 0, 'responseMimeType' => 'application/json'],
            ], 'Gemini');
            $jsonTexto = preg_replace('/```json|```/', '', $jsonTexto);
            $jsonTexto = trim($jsonTexto);
            $producto  = json_decode($jsonTexto, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json([
                    'success'   => false,
                    'mensaje'   => 'Error procesando respuesta de IA',
                    'texto_ocr' => $textoOCR,
                ], 500);
            }

            $existente = Producto::where('tenant_id', session('tenant_id'))
                ->where(function ($q) use ($producto) {
                    $q->where('nombre', 'like', '%' . $producto['nombre'] . '%')
                      ->orWhere('referencia', $producto['referencia'] ?? '');
                })->first();

            return response()->json([
                'success'   => true,
                'producto'  => $producto,
                'texto_ocr' => $textoOCR,
                'existente' => $existente ? [
                    'id'            => $existente->id,
                    'nombre'        => $existente->nombre,
                    'stock'         => $existente->stock,
                    'precio_compra' => $existente->precio_compra,
                    'precio_venta'  => $existente->precio_venta,
                ] : null,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'mensaje' => $e->getMessage(),
            ], 500);
        }
    }

    // Optimizar y guardar imagen
    private function optimizarYGuardarImagen(string $imagenBase64, string $nombre): string
    {
        $imagen = base64_decode($imagenBase64);
        $img    = @imagecreatefromstring($imagen);

        if ($img !== false) {
            $anchoOriginal = imagesx($img);
            $altoOriginal  = imagesy($img);
            $maxSize       = 400;

            if ($anchoOriginal > $maxSize || $altoOriginal > $maxSize) {
                $ratio      = min($maxSize / $anchoOriginal, $maxSize / $altoOriginal);
                $nuevoAncho = (int)($anchoOriginal * $ratio);
                $nuevoAlto  = (int)($altoOriginal  * $ratio);

                $imgRedim = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

                // Fondo blanco para transparencias
                $blanco = imagecolorallocate($imgRedim, 255, 255, 255);
                imagefill($imgRedim, 0, 0, $blanco);

                imagecopyresampled(
                    $imgRedim, $img,
                    0, 0, 0, 0,
                    $nuevoAncho, $nuevoAlto,
                    $anchoOriginal, $altoOriginal
                );

                ob_start();
                imagejpeg($imgRedim, null, 75);
                $imagenOptimizada = ob_get_clean();

                imagedestroy($img);
                imagedestroy($imgRedim);
            } else {
                ob_start();
                imagejpeg($img, null, 75);
                $imagenOptimizada = ob_get_clean();
                imagedestroy($img);
            }

            \Storage::disk('public')->put($nombre, $imagenOptimizada);
        } else {
            // Fallback si GD no puede procesar
            \Storage::disk('public')->put($nombre, $imagen);
        }

        return $nombre;
    }

    public function guardar(Request $request)
    {
        $request->validate([
            'nombre'        => 'required|string|max:191',
            'precio_compra' => 'required|integer|min:0',
            'precio_venta'  => 'required|integer|min:0',
            'stock'         => 'required|integer|min:0',
        ]);

        // Guardar foto optimizada
        $foto = null;
        if ($request->foto_base64) {
            $nombre = 'productos/' . uniqid() . '.jpg';
            $foto   = $this->optimizarYGuardarImagen($request->foto_base64, $nombre);
        }

        // Solo buscar duplicado si tiene referencia definida
        $productoExistente = null;
        if ($request->referencia) {
            $productoExistente = Producto::where('tenant_id', session('tenant_id'))
                ->where('referencia', $request->referencia)
                ->first();
        }

        if ($productoExistente) {
            $productoExistente->update([
                'nombre'        => $request->nombre,
                'marca'         => $request->marca,
                'categoria'     => $request->categoria,
                'unidad'        => $request->unidad ?? 'unidad',
                'precio_compra' => $request->precio_compra,
                'precio_venta'  => $request->precio_venta,
                'stock'         => $request->stock,
                'stock_minimo'  => $request->stock_minimo ?? 5,
                'foto'          => $foto ?? $productoExistente->foto,
                'activo'        => true,
            ]);

            return response()->json([
                'success'  => true,
                'producto' => $productoExistente,
                'mensaje'  => 'Producto ya existia — inventario actualizado',
            ]);
        }

        $producto = Producto::create([
            'tenant_id'     => session('tenant_id'),
            'nombre'        => $request->nombre,
            'marca'         => $request->marca,
            'categoria'     => $request->categoria,
            'unidad'        => $request->unidad ?? 'unidad',
            'referencia'    => $request->referencia,
            'precio_compra' => $request->precio_compra,
            'precio_venta'  => $request->precio_venta,
            'stock'         => $request->stock,
            'stock_minimo'  => $request->stock_minimo ?? 5,
            'foto'          => $foto,
            'activo'        => true,
        ]);

        return response()->json([
            'success'  => true,
            'producto' => $producto,
            'mensaje'  => 'Producto agregado al inventario',
        ]);
    }

    public function actualizar(Request $request, Producto $producto)
    {
        $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $producto->increment('stock', $request->stock);

        return response()->json([
            'success' => true,
            'mensaje' => "Stock actualizado. Total: {$producto->fresh()->stock} unidades",
        ]);
    }

    public function crear()
    {
        $proveedores = Proveedor::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('inventario.crear-manual', compact('proveedores'));
    }

    public function editar(Producto $producto)
    {
        $proveedores = Proveedor::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('inventario.editar', compact('producto', 'proveedores'));
    }

    public function actualizar_producto(Request $request, Producto $producto)
    {
        $request->validate([
            'nombre'        => 'required|string|max:191',
            'precio_compra' => 'required|integer|min:0',
            'precio_venta'  => 'required|integer|min:0',
            'stock'         => 'required|integer|min:0',
        ]);

        // Guardar foto optimizada si viene nueva
        $foto = $producto->foto;

        if ($request->hasFile('foto')) {
            $nombre = 'productos/' . uniqid() . '.jpg';
            $imagen = file_get_contents($request->file('foto')->getRealPath());
            $foto   = $this->optimizarYGuardarImagen(base64_encode($imagen), $nombre);
        } elseif ($request->foto_base64) {
            $nombre = 'productos/' . uniqid() . '.jpg';
            $foto   = $this->optimizarYGuardarImagen($request->foto_base64, $nombre);
        }

        $producto->update([
            'nombre'        => $request->nombre,
            'marca'         => $request->marca,
            'categoria'     => $request->categoria,
            'unidad'        => $request->unidad ?? 'unidad',
            'referencia'    => $request->referencia,
            'precio_compra' => $request->precio_compra,
            'precio_venta'  => $request->precio_venta,
            'stock'         => $request->stock,
            'stock_minimo'  => $request->stock_minimo ?? 5,
            'descripcion'   => $request->descripcion,
            'foto'          => $foto,
            'activo'        => true,
        ]);

        return response()->json([
            'success' => true,
            'mensaje' => 'Producto actualizado correctamente',
        ]);
    }

    public function eliminar(Producto $producto)
    {
        $producto->delete();

        return response()->json([
            'success' => true,
            'mensaje' => 'Producto eliminado correctamente',
        ]);
    }
}