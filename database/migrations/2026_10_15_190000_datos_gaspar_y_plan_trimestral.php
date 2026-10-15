<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * - Plan "trimestral" en tenants.subscription_plan (POS Topping vende trimestral y anual).
 * - NIT correcto de Gaspar: 52934889-8 (estaba 529934889-8).
 * - Menú de Gaspar: Arándanos en Frutas y "Fresa Bom Bom Bum" en Toppings.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE tenants MODIFY subscription_plan
                ENUM('mensual','trimestral','anual') NULL DEFAULT NULL");
        }

        DB::table('tenants')->where('nit', '529934889-8')->update(['nit' => '52934889-8', 'updated_at' => now()]);

        $tenantId = DB::table('tenants')->where('nit', '52934889-8')->value('id');
        if (!$tenantId) {
            return;   // otra instalación sin Gaspar
        }

        // Arándanos en Frutas
        $frutas = DB::table('grupos_modificadores')->where('tenant_id', $tenantId)->where('nombre', 'Frutas')->first();
        if ($frutas && !DB::table('modificadores')->where('grupo_id', $frutas->id)->where('nombre', 'Arándanos')->exists()) {
            DB::table('modificadores')->insert([
                'grupo_id'   => $frutas->id,
                'nombre'     => 'Arándanos',
                'orden'      => (int) DB::table('modificadores')->where('grupo_id', $frutas->id)->max('orden') + 1,
                'activo'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // "Fresa Bom Bom Bum" en Toppings: renombra la perla de fresa si existe; si no, la crea
        $toppings = DB::table('grupos_modificadores')->where('tenant_id', $tenantId)->where('nombre', 'Toppings')->first();
        if ($toppings && !DB::table('modificadores')->where('grupo_id', $toppings->id)->where('nombre', 'Fresa Bom Bom Bum')->exists()) {
            $perlaFresa = DB::table('modificadores')->where('grupo_id', $toppings->id)
                ->where('nombre', 'like', '%perla%')->where('nombre', 'like', '%fresa%')->first();

            if ($perlaFresa) {
                DB::table('modificadores')->where('id', $perlaFresa->id)
                    ->update(['nombre' => 'Fresa Bom Bom Bum', 'updated_at' => now()]);
            } else {
                DB::table('modificadores')->insert([
                    'grupo_id'   => $toppings->id,
                    'nombre'     => 'Fresa Bom Bom Bum',
                    'orden'      => (int) DB::table('modificadores')->where('grupo_id', $toppings->id)->max('orden') + 1,
                    'activo'     => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('tenants')->where('nit', '52934889-8')->update(['nit' => '529934889-8']);
    }
};
