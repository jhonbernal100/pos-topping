<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cierres_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('fecha');

            // Resumen de ventas del día (foto al momento del cierre)
            $table->unsignedInteger('num_ventas')->default(0);
            $table->unsignedBigInteger('total_ventas')->default(0);
            $table->unsignedInteger('num_anuladas')->default(0);
            $table->unsignedBigInteger('total_anuladas')->default(0);
            $table->unsignedBigInteger('efectivo_ventas')->default(0);

            // Arqueo del efectivo
            $table->unsignedBigInteger('base_inicial')->default(0);
            $table->unsignedBigInteger('efectivo_esperado')->default(0);
            $table->unsignedBigInteger('efectivo_contado')->default(0);
            $table->bigInteger('diferencia')->default(0);   // + sobrante, - faltante

            $table->json('detalle')->nullable();             // medios de pago, productos, horas
            $table->string('notas', 500)->nullable();
            $table->timestamps();

            $table->unique(['sede_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cierres_caja');
    }
};
