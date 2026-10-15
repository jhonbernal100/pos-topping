<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            // Si es true, los impuestos de la factura (IVA, ICUI...) se suman al costo de los insumos
            $table->boolean('impuestos_al_costo')->default(false)->after('impuestos');
            $table->unsignedBigInteger('total_factura')->nullable()->after('total'); // total impreso en la factura (lectura IA)
        });
    }

    public function down(): void
    {
        Schema::table('compras', function (Blueprint $table) {
            $table->dropColumn(['impuestos_al_costo', 'total_factura']);
        });
    }
};
