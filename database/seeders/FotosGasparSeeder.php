<?php

namespace Database\Seeders;

use App\Models\Producto;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class FotosGasparSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::where('nit', '529934889-8')->firstOrFail();
        $dir    = database_path('seeders/fotos/gaspar');

        $mapa = [
            'merengon-grande.webp'       => ['Merengón Grande'],
            'merengon-mediano.webp'      => ['Merengón Mediano'],
            'baby-merengon.webp'         => ['Baby Merengón'],
            'fresas-con-chocolate.webp'  => ['Fresas con Chocolate'],
            'nebula.webp'                => ['Nébula'],
            'roll-cake.webp'             => ['Roll Cake'],
            'red-velvet.webp'            => ['Red Velvet'],
            'gaspar-greek.webp'          => ['Gaspar Greek'],
            'torta-de-chocolate.webp'    => ['Torta de Chocolate'],
            'taiyaki.webp'               => ['Taiyaki'],
            'conochurro.webp'            => ['Conochurro'],
            'mexicanisimo.webp'          => ['Mexicanísimo'],
            'smoothie.webp'              => ['Smoothie'],
            'parfait.webp'               => ['Parfait'],
            'helado-con-2-toppings.webp' => ['Helado con 2 toppings'],
            'helado-en-cono.webp'        => ['Helado en Cono'],
            'oblea-gaspar.webp'          => ['Oblea Gaspar', 'Oblea Sencilla'],
        ];

        $filas = [];

        foreach ($mapa as $archivo => $nombres) {
            if (!is_file("{$dir}/{$archivo}")) {
                $this->command->warn("Falta el archivo {$archivo}");
                continue;
            }

            $ruta = "productos/{$tenant->id}/{$archivo}";
            Storage::disk('public')->put($ruta, file_get_contents("{$dir}/{$archivo}"));

            foreach ($nombres as $nombre) {
                $n = Producto::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->where('nombre', $nombre)
                    ->update(['foto' => $ruta]);

                $filas[] = [$nombre, $n ? 'OK' : 'NO ENCONTRADO'];
            }
        }

        // Logo y colores de la marca (tomados de la carta)
        $logo = 'logos/' . $tenant->id . '/logo-gaspar.png';
        if (is_file("{$dir}/logo-gaspar.png")) {
            Storage::disk('public')->put($logo, file_get_contents("{$dir}/logo-gaspar.png"));
        }

        $tenant->update([
            'logo'             => $logo,
            'color_primario'   => '#27A88A',
            'color_secundario' => '#563B38',
            'color_fondo'      => '#FFE0EA',
        ]);

        $this->command->table(['Producto', 'Foto'], $filas);
        $this->command->info('Logo y colores de Gaspar aplicados.');
    }
}