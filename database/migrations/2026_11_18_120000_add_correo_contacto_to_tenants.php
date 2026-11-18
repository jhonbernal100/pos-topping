<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Correo de contacto del negocio: se publica en su política de privacidad
 * (canal para consultas y reclamos de datos personales, Ley 1581 de 2012).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('correo_contacto', 191)->nullable()->after('telefono');
        });

        // Gaspar (contrato del 18/11/2026)
        DB::table('tenants')->where('nit', '52934889-8')->update(['correo_contacto' => 'carol07_06@hotmail.com']);
        DB::table('tenants')->where('nit', '52934889-8')->where(fn ($q) => $q->whereNull('telefono')->orWhere('telefono', ''))
            ->update(['telefono' => '+57 310 663 1824']);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn('correo_contacto');
        });
    }
};
