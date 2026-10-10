<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('logo', 191)->nullable()->after('ciudad');
            $table->string('color_primario', 7)->default('#E4387A')->after('logo');
            $table->string('color_secundario', 7)->default('#4A2C2A')->after('color_primario');
            $table->string('color_fondo', 7)->default('#FFF6EE')->after('color_secundario');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['logo', 'color_primario', 'color_secundario', 'color_fondo']);
        });
    }
};