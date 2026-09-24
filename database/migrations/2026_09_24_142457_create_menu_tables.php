<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Secciones de la carta
        Schema::create('categorias_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'activo', 'orden']);
        });

        // Variantes con precio propio (ej: yogurt / vainilla francesa)
        Schema::create('producto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->unsignedInteger('precio');
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['producto_id', 'activo']);
        });

        // Grupos reutilizables (Frutas, Toppings, Salsas...)
        Schema::create('grupos_modificadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->unsignedInteger('precio_extra')->default(0);   // precio por opción adicional
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'activo']);
        });

        // Opciones de cada grupo (Fresa, Mango, Oreo...)
        Schema::create('modificadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('grupo_id')->constrained('grupos_modificadores')->cascadeOnDelete();
            $table->string('nombre', 100);
            $table->unsignedInteger('precio_extra')->nullable();    // null = usa el precio del grupo
            $table->json('dias_disponibles')->nullable();           // [6,7] = sábado y domingo; null = todos
            $table->unsignedSmallInteger('orden')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['grupo_id', 'activo']);
        });

        // Qué grupos usa cada producto y cuántas opciones incluye sin costo
        Schema::create('producto_grupo_modificador', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos_modificadores')->cascadeOnDelete();
            $table->unsignedTinyInteger('incluidos')->default(0);   // opciones sin costo
            $table->unsignedTinyInteger('minimo')->default(0);      // mínimo a elegir
            $table->unsignedTinyInteger('maximo')->nullable();      // null = sin tope
            $table->unsignedInteger('precio_extra')->nullable();    // null = usa el precio del grupo
            $table->unsignedSmallInteger('orden')->default(0);
            $table->timestamps();

            $table->unique(['producto_id', 'grupo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('producto_grupo_modificador');
        Schema::dropIfExists('modificadores');
        Schema::dropIfExists('grupos_modificadores');
        Schema::dropIfExists('producto_variantes');
        Schema::dropIfExists('categorias_menu');
    }
};