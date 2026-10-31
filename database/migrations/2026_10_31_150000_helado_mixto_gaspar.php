<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Helado "Mixto" en Gaspar (31/10/2026):
 * - Opción Mixto en "Sabor de helado": sin costo en los especiales, postres y oblea;
 *   en los merengones el cambio a Mixto cuesta $1.000 (vainilla y yogurt siguen sin costo).
 * - Variante Mixto (al precio de Yogurt) en Helado con 2 toppings, Parfait, Cono y Smoothie.
 * - "Porción extra de helado mixto" en Adición de helado.
 */
return new class extends Migration
{
    private const CAMBIO_MERENGON = 1000;

    // Productos que eligen el sabor con variantes (precio distinto por sabor)
    private const CON_VARIANTES = ['Helado con 2 toppings', 'Parfait', 'Helado en Cono', 'Smoothie'];

    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('nit', '52934889-8')->value('id');
        if (!$tenantId) {
            return;
        }

        $ahora = now();

        // 1. Mixto en "Sabor de helado"
        $sabor = DB::table('grupos_modificadores')->where('tenant_id', $tenantId)->where('nombre', 'Sabor de helado')->value('id');
        if ($sabor) {
            $this->opcion($sabor, 'Mixto', self::CAMBIO_MERENGON, $ahora);

            // Vainilla y yogurt sin costo propio (toman el del grupo: $0)
            DB::table('modificadores')->where('grupo_id', $sabor)->where('nombre', '!=', 'Mixto')
                ->update(['precio_extra' => null, 'updated_at' => $ahora]);
            DB::table('grupos_modificadores')->where('id', $sabor)->update(['precio_extra' => 0, 'updated_at' => $ahora]);

            // Merengones: el sabor no viene "incluido", así cada opción cobra su propio precio
            // (vainilla y yogurt $0, Mixto $1.000). Sigue siendo obligatorio elegir uno.
            $merengones = DB::table('productos')
                ->join('categorias_menu', 'categorias_menu.id', '=', 'productos.categoria_menu_id')
                ->where('productos.tenant_id', $tenantId)
                ->where('categorias_menu.nombre', 'Merengones')
                ->pluck('productos.id');

            DB::table('producto_grupo_modificador')
                ->whereIn('producto_id', $merengones)
                ->where('grupo_id', $sabor)
                ->update(['incluidos' => 0, 'minimo' => 1, 'maximo' => 1, 'updated_at' => $ahora]);
        }

        // 2. Variante Mixto donde el sabor es variante, al precio de Yogurt
        foreach (self::CON_VARIANTES as $nombre) {
            $productoId = DB::table('productos')->where('tenant_id', $tenantId)->where('nombre', $nombre)->whereNull('deleted_at')->value('id');
            if (!$productoId) {
                continue;
            }

            $variantes = DB::table('producto_variantes')->where('producto_id', $productoId)->get();
            if ($variantes->isEmpty() || $variantes->contains(fn ($v) => mb_strtolower($v->nombre) === 'mixto')) {
                continue;
            }

            $yogurt = $variantes->first(fn ($v) => mb_strtolower($v->nombre) === 'yogurt');
            DB::table('producto_variantes')->insert([
                'producto_id' => $productoId,
                'nombre'      => 'Mixto',
                'precio'      => $yogurt->precio ?? $variantes->max('precio'),
                'orden'       => (int) $variantes->max('orden') + 1,
                'activo'      => true,
                'created_at'  => $ahora,
                'updated_at'  => $ahora,
            ]);
        }

        // 3. Adición de helado mixto
        $adicion = DB::table('grupos_modificadores')->where('tenant_id', $tenantId)->where('nombre', 'Adición de helado')->value('id');
        if ($adicion) {
            $this->opcion($adicion, 'Porción extra de helado mixto', null, $ahora);
        }
    }

    public function down(): void
    {
        // Cambio de datos del menú: no se revierte automáticamente
    }

    private function opcion(int $grupoId, string $nombre, ?int $precio, $ahora): void
    {
        $existe = DB::table('modificadores')->where('grupo_id', $grupoId)->whereRaw('LOWER(nombre) = ?', [mb_strtolower($nombre)])->value('id');

        $datos = ['precio_extra' => $precio, 'dias_disponibles' => null, 'activo' => true, 'updated_at' => $ahora];

        if ($existe) {
            DB::table('modificadores')->where('id', $existe)->update($datos);
            return;
        }

        DB::table('modificadores')->insert($datos + [
            'grupo_id'   => $grupoId,
            'nombre'     => $nombre,
            'orden'      => (int) DB::table('modificadores')->where('grupo_id', $grupoId)->max('orden') + 1,
            'created_at' => $ahora,
        ]);
    }
};
