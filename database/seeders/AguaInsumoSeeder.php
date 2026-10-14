<?php

namespace Database\Seeders;

use App\Models\Insumo;
use App\Models\Producto;
use App\Models\RecetaItem;
use Illuminate\Database\Seeder;

/**
 * Pasa los productos de reventa "Agua..." a control por insumo (existencia por sede).
 * Crea el insumo "Agua Manantial 500 ml" (und) en cada negocio que venda agua y le pone receta de 1.
 * Se puede correr varias veces sin duplicar.
 */
class AguaInsumoSeeder extends Seeder
{
    public function run(): void
    {
        $productos = Producto::withoutGlobalScopes()
            ->where('tipo_menu', 'reventa')
            ->where('nombre', 'like', '%agua%')
            ->get();

        if ($productos->isEmpty()) {
            $this->command?->warn('No se encontró ningún producto de reventa con "agua" en el nombre.');
            return;
        }

        foreach ($productos as $producto) {
            $insumo = Insumo::withoutGlobalScopes()->firstOrCreate(
                ['tenant_id' => $producto->tenant_id, 'nombre' => 'Agua Manantial 500 ml'],
                ['unidad' => 'und', 'categoria' => 'Bebidas', 'activo' => true, 'costo_unitario' => 0]
            );

            RecetaItem::firstOrCreate(
                ['producto_id' => $producto->id, 'variante_id' => null, 'insumo_id' => $insumo->id],
                ['cantidad' => 1]
            );

            $this->command?->info(
                "{$producto->nombre} (tenant {$producto->tenant_id}) ahora descuenta el insumo #{$insumo->id}. "
                . "Stock anterior del producto: {$producto->stock}. Cuenta las botellas en Insumos en cada sede."
            );
        }
    }
}
