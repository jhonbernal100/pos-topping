
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sedes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->string('direccion', 191);
            $table->string('barrio', 100)->nullable();
            $table->string('ciudad', 100)->default('Bogotá');
            $table->string('telefono', 20)->nullable();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->decimal('radio_cobertura_km', 5, 2)->nullable();
            $table->boolean('acepta_domicilios')->default(true);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sedes');
    }
};