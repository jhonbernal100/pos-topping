<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->timestamp('preparacion_at')->nullable()->after('estado_preparacion');
            $table->timestamp('listo_at')->nullable()->after('preparacion_at');
            $table->timestamp('entregado_at')->nullable()->after('listo_at');

            $table->index(['sede_id', 'estado_preparacion']);
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['sede_id', 'estado_preparacion']);
            $table->dropColumn(['preparacion_at', 'listo_at', 'entregado_at']);
        });
    }
};