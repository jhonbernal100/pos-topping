<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Contratos firmados (PDF de Zoho Sign u otra plataforma) de cada negocio.
 * Los archivos se guardan en storage privado: solo el superadmin los descarga.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nombre', 191);                 // nombre original del PDF
            $table->string('archivo', 255);                // ruta en el disco local
            $table->unsignedInteger('tamano')->default(0);
            $table->date('fecha_firma');
            $table->date('vigencia_desde')->nullable();
            $table->date('vigencia_hasta')->nullable();
            $table->string('notas', 255)->nullable();
            $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'fecha_firma']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratos');
    }
};
