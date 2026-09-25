<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Detalle: variante elegida, desglose de precio y nota ("sin salsa")
        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->unsignedBigInteger('variante_id')->nullable()->after('producto_id');
            $table->string('nombre_variante', 100)->nullable()->after('nombre_producto');
            $table->unsignedInteger('precio_base')->default(0)->after('cantidad');
            $table->unsignedInteger('precio_adicionales')->default(0)->after('precio_base');
            $table->string('notas', 191)->nullable();
        });

        // Frutas, toppings, salsas… elegidos en cada producto vendido
        Schema::create('venta_detalle_modificadores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_detalle_id')->constrained('venta_detalles')->cascadeOnDelete();
            $table->unsignedBigInteger('modificador_id')->nullable();   // sin FK: el histórico no se pierde si se borra la opción
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->string('nombre_grupo', 100);
            $table->string('nombre', 100);
            $table->unsignedTinyInteger('cantidad')->default(1);
            $table->unsignedInteger('precio_unitario')->default(0);    // 0 si fue incluido
            $table->timestamps();

            $table->index('modificador_id');
        });

        // Pagos de la venta (uno o varios si es mixto)
        Schema::create('venta_pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->enum('metodo', ['efectivo', 'nequi', 'daviplata', 'tarjeta', 'transferencia']);
            $table->unsignedInteger('monto');
            $table->string('referencia', 60)->nullable();
            $table->timestamps();
        });

        // Consecutivo diario de turnos por sede
        Schema::create('turnos_diarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sede_id')->constrained('sedes')->cascadeOnDelete();
            $table->date('fecha');
            $table->unsignedSmallInteger('ultimo')->default(0);
            $table->timestamps();

            $table->unique(['sede_id', 'fecha']);
        });

        // Nuevos métodos de pago (se conservan los antiguos por compatibilidad)
        DB::statement("ALTER TABLE ventas MODIFY metodo_pago
            ENUM('efectivo','transferencia','credito','nequi','daviplata','tarjeta','mixto')
            NOT NULL DEFAULT 'efectivo'");

        // Estado en cocina (lo usa F4: comandas)
        Schema::table('ventas', function (Blueprint $table) {
            $table->enum('estado_preparacion', ['pendiente', 'en_preparacion', 'listo', 'entregado'])
                  ->nullable()->after('canal');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('estado_preparacion');
        });

        DB::statement("ALTER TABLE ventas MODIFY metodo_pago
            ENUM('efectivo','transferencia','credito') NOT NULL DEFAULT 'efectivo'");

        Schema::dropIfExists('turnos_diarios');
        Schema::dropIfExists('venta_pagos');
        Schema::dropIfExists('venta_detalle_modificadores');

        Schema::table('venta_detalles', function (Blueprint $table) {
            $table->dropColumn(['variante_id', 'nombre_variante', 'precio_base', 'precio_adicionales', 'notas']);
        });
    }
};