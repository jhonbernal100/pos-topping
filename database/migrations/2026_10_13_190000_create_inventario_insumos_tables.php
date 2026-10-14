<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Materias primas: helado, frutas, toppings, salsas, empaques...
        Schema::create('insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nombre', 120);
            $table->enum('unidad', ['g', 'ml', 'und']);
            $table->string('categoria', 60)->nullable();
            $table->decimal('costo_unitario', 14, 4)->default(0);   // costo por g / ml / und (promedio ponderado)
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['tenant_id', 'nombre']);
        });

        // Existencias por sede
        Schema::create('insumo_stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->decimal('cantidad', 14, 3)->default(0);
            $table->decimal('stock_minimo', 14, 3)->default(0);
            $table->timestamps();

            $table->unique(['insumo_id', 'sede_id']);
        });

        // Receta de cada producto (variante_id null = aplica a todas las variantes)
        Schema::create('receta_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('variante_id')->nullable()->constrained('producto_variantes')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->decimal('cantidad', 12, 3);
            $table->timestamps();

            $table->index(['producto_id', 'variante_id']);
        });

        // Qué consume cada opción (ej: Fresa → 30 g de fresa)
        Schema::create('modificador_insumos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('modificador_id')->constrained('modificadores')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->decimal('cantidad', 12, 3);
            $table->timestamps();

            $table->unique(['modificador_id', 'insumo_id']);
        });

        // Facturas de compra de insumos (reemplaza el registro en Siigo)
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('numero_factura', 60)->nullable();
            $table->date('fecha');
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('impuestos')->default(0);
            $table->unsignedBigInteger('total')->default(0);
            $table->enum('estado', ['borrador', 'registrada', 'anulada'])->default('borrador');
            $table->enum('origen', ['manual', 'ia'])->default('manual');
            $table->string('foto', 191)->nullable();
            $table->json('lectura_ia')->nullable();          // respuesta original de Gemini, para auditoría
            $table->string('notas', 500)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'fecha']);
            $table->index(['proveedor_id', 'numero_factura']);
        });

        Schema::create('compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('insumo_id')->nullable()->constrained('insumos')->nullOnDelete();
            $table->string('descripcion', 191);              // texto tal como viene en la factura
            $table->decimal('cantidad_factura', 14, 3);       // ej: 2 (cajas)
            $table->string('unidad_factura', 30)->nullable(); // ej: "caja x 5 kg"
            $table->decimal('factor', 14, 4)->default(1);     // cuánto equivale 1 unidad de factura en unidad base (ej: 5000 g)
            $table->decimal('cantidad', 14, 3)->default(0);   // en unidad base del insumo
            $table->unsignedBigInteger('costo_total')->default(0);
            $table->timestamps();
        });

        // Aprendizaje: "Fresa x 5 kg Proveedor X" → insumo Fresa, factor 5000
        Schema::create('insumo_alias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->nullable()->constrained('proveedores')->nullOnDelete();
            $table->string('texto', 191);                     // descripción normalizada
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->decimal('factor', 14, 4)->default(1);
            $table->timestamps();

            $table->index(['tenant_id', 'texto']);
        });

        // Kardex por sede
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->foreignId('insumo_id')->constrained('insumos')->cascadeOnDelete();
            $table->enum('tipo', ['entrada', 'venta', 'anulacion', 'merma', 'ajuste', 'traslado']);
            $table->decimal('cantidad', 14, 3);             // positivo entra, negativo sale
            $table->decimal('saldo', 14, 3);                // existencia resultante en la sede
            $table->decimal('costo_unitario', 14, 4)->default(0);
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->foreignId('compra_id')->nullable()->constrained('compras')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('nota', 191)->nullable();
            $table->timestamps();

            $table->index(['sede_id', 'insumo_id', 'created_at']);
            $table->index(['venta_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
        Schema::dropIfExists('insumo_alias');
        Schema::dropIfExists('compra_items');
        Schema::dropIfExists('compras');
        Schema::dropIfExists('modificador_insumos');
        Schema::dropIfExists('receta_items');
        Schema::dropIfExists('insumo_stocks');
        Schema::dropIfExists('insumos');
    }
};
