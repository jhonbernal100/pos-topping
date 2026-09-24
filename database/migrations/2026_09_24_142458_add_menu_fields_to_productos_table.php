<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->foreignId('categoria_menu_id')->nullable()->after('tenant_id')
                  ->constrained('categorias_menu')->nullOnDelete();

            // preparado = se arma con insumos (merengón); reventa = se vende tal cual con stock (agua embotellada)
            $table->enum('tipo_menu', ['preparado', 'reventa'])->default('preparado')->after('categoria_menu_id');

            $table->boolean('disponible_local')->default(true)->after('tipo_menu');
            $table->boolean('disponible_domicilio')->default(true)->after('disponible_local');
            $table->json('dias_disponibles')->nullable()->after('disponible_domicilio');   // [5,6,7] = vie a dom; null = todos
            $table->unsignedSmallInteger('orden_menu')->default(0)->after('dias_disponibles');
        });
    }

    public function down(): void
    {
        Schema::table('productos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('categoria_menu_id');
            $table->dropColumn(['tipo_menu', 'disponible_local', 'disponible_domicilio', 'dias_disponibles', 'orden_menu']);
        });
    }
};