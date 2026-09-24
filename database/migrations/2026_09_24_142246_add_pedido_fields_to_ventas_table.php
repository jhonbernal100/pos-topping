<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Nombre con el que se llama al cliente (estilo Juan Valdez)
            $table->string('nombre_pedido', 60)->nullable()->after('sede_id');

            // Consecutivo diario por sede: #1, #2, #3... se reinicia cada día
            $table->unsignedSmallInteger('numero_turno')->nullable()->after('nombre_pedido');

            // local = mostrador, domicilio = bot (módulo adicional)
            $table->enum('canal', ['local', 'domicilio'])->default('local')->after('numero_turno');

            $table->index(['sede_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['sede_id', 'created_at']);
            $table->dropColumn(['nombre_pedido', 'numero_turno', 'canal']);
        });
    }
};