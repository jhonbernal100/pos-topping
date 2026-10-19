<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bot de domicilios por WhatsApp (plan anual).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('wa_activo')->default(false);
            $table->string('wa_session_id', 100)->nullable()->unique();
            $table->string('wa_numero', 30)->nullable();
            $table->string('pago_nequi', 30)->nullable();
            $table->string('pago_daviplata', 30)->nullable();
            $table->string('pago_titular', 120)->nullable();
            $table->unsignedInteger('domicilio_minimo')->default(0);   // valor mínimo del pedido sin domicilio
            $table->string('wa_bienvenida', 500)->nullable();
        });

        Schema::create('tarifas_domicilio', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->decimal('hasta_km', 5, 2);
            $table->unsignedInteger('valor');
            $table->timestamps();
            $table->index(['tenant_id', 'hasta_km']);
        });

        Schema::create('conversaciones_wa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('chat_id', 100);
            $table->string('telefono', 30)->nullable();
            $table->string('nombre', 120)->nullable();
            $table->string('estado', 30)->default('inicio');
            $table->foreignId('sede_id')->nullable()->constrained('sedes')->nullOnDelete();
            $table->decimal('latitud', 10, 7)->nullable();
            $table->decimal('longitud', 10, 7)->nullable();
            $table->decimal('distancia_km', 6, 2)->nullable();
            $table->unsignedInteger('costo_domicilio')->default(0);
            $table->string('direccion', 191)->nullable();
            $table->foreignId('venta_id')->nullable()->constrained('ventas')->nullOnDelete();
            $table->string('token', 40)->nullable()->unique();
            $table->timestamp('token_expira_at')->nullable();
            $table->timestamp('humano_hasta')->nullable();   // un asesor atiende: el bot no responde
            $table->timestamp('ultimo_mensaje_at')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'chat_id']);
        });

        Schema::create('mensajes_wa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('conversacion_id')->nullable()->constrained('conversaciones_wa')->cascadeOnDelete();
            $table->enum('direccion', ['entrante', 'saliente']);
            $table->string('wa_id', 191)->nullable()->unique();
            $table->string('tipo', 20)->default('text');
            $table->text('cuerpo')->nullable();
            $table->string('media_path', 191)->nullable();
            $table->boolean('enviado')->default(true);
            $table->timestamps();
            $table->index(['conversacion_id', 'created_at']);
        });

        Schema::table('ventas', function (Blueprint $table) {
            $table->string('telefono_cliente', 30)->nullable();
            $table->string('direccion_entrega', 191)->nullable();
            $table->decimal('latitud_entrega', 10, 7)->nullable();
            $table->decimal('longitud_entrega', 10, 7)->nullable();
            $table->decimal('distancia_km', 6, 2)->nullable();
            $table->unsignedInteger('costo_domicilio')->default(0);
            $table->string('estado_pago', 20)->nullable();          // pendiente | por_verificar | pagado | rechazado
            $table->string('comprobante', 191)->nullable();
            $table->json('lectura_comprobante')->nullable();
            $table->string('motivo_rechazo', 191)->nullable();
            $table->foreignId('conversacion_wa_id')->nullable()->constrained('conversaciones_wa')->nullOnDelete();
            $table->index(['sede_id', 'canal', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('conversacion_wa_id');
            $table->dropIndex(['sede_id', 'canal', 'estado']);
            $table->dropColumn(['telefono_cliente', 'direccion_entrega', 'latitud_entrega', 'longitud_entrega', 'distancia_km',
                'costo_domicilio', 'estado_pago', 'comprobante', 'lectura_comprobante', 'motivo_rechazo']);
        });
        Schema::dropIfExists('mensajes_wa');
        Schema::dropIfExists('conversaciones_wa');
        Schema::dropIfExists('tarifas_domicilio');
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropUnique(['wa_session_id']);
            $table->dropColumn(['wa_activo', 'wa_session_id', 'wa_numero', 'pago_nequi', 'pago_daviplata', 'pago_titular', 'domicilio_minimo', 'wa_bienvenida']);
        });
    }
};
