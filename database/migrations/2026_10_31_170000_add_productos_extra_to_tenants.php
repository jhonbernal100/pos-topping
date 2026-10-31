<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Excepción por negocio al límite de productos de la demo (lo asigna Avanzas Digital en el Panel Admin).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->unsignedSmallInteger('productos_extra')->default(0)->after('subscription_price');
        });

        // Gaspar: 8 productos adicionales en la demo (20 + 8 = 28)
        DB::table('tenants')->where('nit', '52934889-8')->update(['productos_extra' => 8]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('productos_extra');
        });
    }
};
