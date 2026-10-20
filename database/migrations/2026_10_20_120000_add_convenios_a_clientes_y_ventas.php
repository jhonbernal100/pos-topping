<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convenios con clientes/plataformas (ej: Rappi):
 * - % de ajuste sobre el precio de la carta (positivo = recargo, negativo = descuento)
 * - venta a crédito: no se cobra en caja, queda por cobrar y vence en N días
 * - código de pedido de la plataforma
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->decimal('ajuste_porcentaje', 5, 2)->default(0)->after('ciudad');
            $table->boolean('venta_credito')->default(false)->after('ajuste_porcentaje');
            $table->unsignedSmallInteger('dias_credito')->default(0)->after('venta_credito');
            $table->boolean('pide_codigo')->default(false)->after('dias_credito');
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->string('codigo_externo', 40)->nullable()->after('cliente_id');
            $table->decimal('ajuste_porcentaje', 5, 2)->nullable()->after('descuento');
            $table->integer('ajuste_valor')->default(0)->after('ajuste_porcentaje');
            $table->string('estado_cobro', 15)->nullable()->after('estado_pago');   // pendiente | cobrado
            $table->date('vence_cobro')->nullable()->after('estado_cobro');
            $table->dateTime('cobrado_at')->nullable()->after('vence_cobro');
            $table->string('referencia_cobro', 60)->nullable()->after('cobrado_at');

            $table->index(['tenant_id', 'estado_cobro']);
            $table->index(['cliente_id', 'codigo_externo']);
        });

        // Nuevo medio de pago "credito" (por cobrar) en los pagos de cada venta
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE venta_pagos MODIFY metodo
                ENUM('efectivo','nequi','daviplata','tarjeta','transferencia','credito') NOT NULL");
        }

        // Gaspar ya creó el cliente Rappi: dejarlo con el convenio acordado (+25 %, paga a 20 días)
        DB::table('clientes')
            ->where(fn ($q) => $q->where('nombre', 'like', 'rappi%')->orWhere('nombre', 'like', 'rappy%'))
            ->where('ajuste_porcentaje', 0)
            ->update([
                'ajuste_porcentaje' => 25,
                'venta_credito'     => true,
                'dias_credito'      => 20,
                'pide_codigo'       => true,
            ]);
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropIndex(['tenant_id', 'estado_cobro']);
            $table->dropIndex(['cliente_id', 'codigo_externo']);
            $table->dropColumn(['codigo_externo', 'ajuste_porcentaje', 'ajuste_valor', 'estado_cobro', 'vence_cobro', 'cobrado_at', 'referencia_cobro']);
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['ajuste_porcentaje', 'venta_credito', 'dias_credito', 'pide_codigo']);
        });
    }
};
