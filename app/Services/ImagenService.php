<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ImagenService
{
    /** Redimensiona, optimiza y guarda en el disco público. Devuelve la ruta relativa. */
    public function guardar(UploadedFile $archivo, string $carpeta, int $maximo = 900): string
    {
        $original = @imagecreatefromstring(file_get_contents($archivo->getRealPath()));
        if (!$original) {
            throw new InvalidArgumentException('La imagen no es válida. Usa una foto JPG, PNG o WEBP.');
        }

        $ancho  = imagesx($original);
        $alto   = imagesy($original);
        $escala = min(1, $maximo / max($ancho, $alto));
        $nuevoW = max(1, (int) round($ancho * $escala));
        $nuevoH = max(1, (int) round($alto * $escala));

        $lienzo = imagecreatetruecolor($nuevoW, $nuevoH);
        imagealphablending($lienzo, false);
        imagesavealpha($lienzo, true);
        imagefill($lienzo, 0, 0, imagecolorallocatealpha($lienzo, 0, 0, 0, 127));
        imagecopyresampled($lienzo, $original, 0, 0, 0, 0, $nuevoW, $nuevoH, $ancho, $alto);

        ob_start();
        if (function_exists('imagewebp')) {
            imagewebp($lienzo, null, 80);
            $extension = 'webp';
        } else {
            imagepng($lienzo, null, 8);
            $extension = 'png';
        }
        $binario = ob_get_clean();

        imagedestroy($original);
        imagedestroy($lienzo);

        $ruta = trim($carpeta, '/') . '/' . Str::uuid() . '.' . $extension;
        Storage::disk('public')->put($ruta, $binario);

        return $ruta;
    }

    public function borrar(?string $ruta): void
    {
        if ($ruta) {
            Storage::disk('public')->delete($ruta);
        }
    }
}
