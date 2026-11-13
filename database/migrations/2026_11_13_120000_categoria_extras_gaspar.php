<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Categoría "Extras" de Gaspar (13/11/2026) con las adiciones sueltas.
 * Cada adición pide elegir cuál topping / fruta, para que la cocina lo vea en la comanda
 * y se descuente de la receta.
 */
return new class extends Migration
{
    // [producto, precio, grupo del que se elige]
    private const PRODUCTOS = [
        ['Adición de topping', 2500, 'Toppings'],
        ['Adición de fruta', 2500, 'Frutas'],
    ];

    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('nit', '52934889-8')->value('id');
        if (!$tenantId) {
            return;
        }

        $ahora = now();

        $categoriaId = DB::table('categorias_menu')->where('tenant_id', $tenantId)->where('nombre', 'Extras')->value('id')
            ?? DB::table('categorias_menu')->insertGetId([
                'tenant_id'  => $tenantId,
                'nombre'     => 'Extras',
                'orden'      => (int) DB::table('categorias_menu')->where('tenant_id', $tenantId)->max('orden') + 1,
                'activo'     => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

        foreach (self::PRODUCTOS as $i => [$nombre, $precio, $grupo]) {
            if (DB::table('productos')->where('tenant_id', $tenantId)->where('nombre', $nombre)->whereNull('deleted_at')->exists()) {
                continue;
            }

            $productoId = DB::table('productos')->insertGetId([
                'tenant_id'            => $tenantId,
                'nombre'               => $nombre,
                'categoria_menu_id'    => $categoriaId,
                'categoria'            => 'Extras',
                'tipo_menu'            => 'preparado',
                'precio_venta'         => $precio,
                'precio_compra'        => 0,
                'stock'                => 0,
                'stock_minimo'         => 0,
                'unidad'               => 'unidad',
                'activo'               => true,
                'disponible_local'     => true,
                'disponible_domicilio' => true,
                'orden_menu'           => $i + 1,
                'created_at'           => $ahora,
                'updated_at'           => $ahora,
            ]);

            // Elegir exactamente 1 opción del grupo, incluida en el precio
            $grupoId = DB::table('grupos_modificadores')->where('tenant_id', $tenantId)->where('nombre', $grupo)->value('id');
            if ($grupoId) {
                DB::table('producto_grupo_modificador')->insert([
                    'producto_id'  => $productoId,
                    'grupo_id'     => $grupoId,
                    'incluidos'    => 1,
                    'minimo'       => 1,
                    'maximo'       => 1,
                    'precio_extra' => null,
                    'orden'        => 1,
                    'created_at'   => $ahora,
                    'updated_at'   => $ahora,
                ]);
            }
        }
    }

    public function down(): void
    {
        // Cambio de datos del menú: no se revierte automáticamente
    }
};
