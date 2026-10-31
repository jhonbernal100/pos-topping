<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Lista de frutas y toppings de Gaspar (30/10/2026), todos a $2.500 como adicional
 * y disponibles todos los días. Renombra los que ya existían, crea los nuevos y
 * marca como agotados los que ya no están en la lista (no se borran).
 */
return new class extends Migration
{
    // [nombre final, nombres anteriores con los que puede existir]
    private const FRUTAS = [
        ['Papayuela en almíbar casera', ['Papayuela']],
        ['Guanábana', []],
        ['Arándanos', []],
        ['Maracuyá', ['Maracuya']],
        ['Fresa', []],
        ['Mango', []],
        ['Banano en salsa de la casa', []],
        ['Durazno en almíbar casero', ['Durazno en almíbar']],
        ['Sandía', []],
        ['Kiwi', []],
        ['Lulo en almíbar', []],
    ];

    private const TOPPINGS = [
        ['Perlas explosivas blueberry', ['Perlas explosivas de blueberry']],
        ['Perlas explosivas Bom Bom Bum fresa', ['Fresa Bom Bom Bum', 'Perlas explosivas de fresa']],
        ['Perlas explosivas mango / limón', []],
        ['Galleta Oreo', []],
        ['Gran Ola', []],
        ['Gelatina', []],
        ['Quipitos', []],
        ['Chips de chocolate', []],
        ['M&M', []],
        ['Coco', ['Coco rayado']],
        ['Maní', ['Maní confitado', 'Mani']],
        ['Cereal cubierto de chocolate', ['Cereal recubierto de chocolate']],
        ['Masmelos', ['Mini masmelos']],
        ['Brownie', ['Trozos de brownie']],
        ['Chocorramo', ['Mini Chocorramo']],
        ['Gomas Nanos', ['Nanos gomas']],
        ['Gusanos ácidos', []],
        ['Gomas', []],
        ['Leche en polvo', []],
        ['Mini churros', []],
        ['Miga de red velvet', []],
        ['Barquillos', []],
        ['Perlas de chicle', []],
        ['Yexis', []],
        ['Granola con chocolate', ['Granola']],
        ['Huevos de chocolate', []],
        ['Gomas Trululú', ['Gomas trululu']],
        ['Almendras', []],
        ['Cerezas maraschino', []],
    ];

    public function up(): void
    {
        $tenantId = DB::table('tenants')->where('nit', '52934889-8')->value('id');
        if (!$tenantId) {
            return;
        }

        $frutas   = $this->grupo($tenantId, 'Frutas', 2);
        $toppings = $this->grupo($tenantId, 'Toppings', 3);
        $grupos   = [$frutas, $toppings];

        $conservar = [];
        foreach ([[$frutas, self::FRUTAS], [$toppings, self::TOPPINGS]] as [$grupoId, $lista]) {
            foreach ($lista as $i => [$nombre, $anteriores]) {
                $conservar[] = $this->opcion($grupos, $grupoId, $nombre, $anteriores, $i + 1);
            }
        }

        // Lo que ya no está en la lista: agotado (se puede reactivar tocándolo en Menú)
        DB::table('modificadores')
            ->whereIn('grupo_id', $grupos)
            ->whereNotIn('id', $conservar)
            ->update(['activo' => false, 'updated_at' => now()]);
    }

    public function down(): void
    {
        // Cambio de datos del menú: no se revierte automáticamente
    }

    private function grupo(int $tenantId, string $nombre, int $orden): int
    {
        $id = DB::table('grupos_modificadores')->where('tenant_id', $tenantId)->where('nombre', $nombre)->value('id');

        if ($id) {
            DB::table('grupos_modificadores')->where('id', $id)->update(['precio_extra' => 2500, 'activo' => true, 'updated_at' => now()]);
            return (int) $id;
        }

        return (int) DB::table('grupos_modificadores')->insertGetId([
            'tenant_id' => $tenantId, 'nombre' => $nombre, 'precio_extra' => 2500,
            'orden' => $orden, 'activo' => true, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    // Busca la opción por su nombre (o uno anterior) en Frutas o Toppings; la deja en su grupo y al día
    private function opcion(array $grupos, int $grupoId, string $nombre, array $anteriores, int $orden): int
    {
        $nombres = array_map('mb_strtolower', array_merge([$nombre], $anteriores));

        $existente = DB::table('modificadores')
            ->whereIn('grupo_id', $grupos)
            ->get(['id', 'nombre', 'grupo_id'])
            ->sortBy(fn ($m) => array_search(mb_strtolower(trim($m->nombre)), $nombres, true))
            ->first(fn ($m) => in_array(mb_strtolower(trim($m->nombre)), $nombres, true));

        $datos = [
            'grupo_id'         => $grupoId,
            'nombre'           => $nombre,
            'precio_extra'     => null,     // usa el del grupo: $2.500
            'dias_disponibles' => null,     // todos los días
            'orden'            => $orden,
            'activo'           => true,
            'updated_at'       => now(),
        ];

        if ($existente) {
            DB::table('modificadores')->where('id', $existente->id)->update($datos);
            return (int) $existente->id;
        }

        return (int) DB::table('modificadores')->insertGetId($datos + ['created_at' => now()]);
    }
};
