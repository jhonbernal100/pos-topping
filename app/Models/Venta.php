<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Venta extends Model
{
    protected $fillable = [
        'tenant_id', 'sede_id', 'cliente_id', 'numero_factura',
        'tipo_documento', 'estado', 'subtotal',
        'descuento', 'total', 'metodo_pago',
        'monto_pagado', 'cambio', 'factura_enviada_dian', 'notas',
        // POS Topping
        'nombre_pedido', 'numero_turno', 'canal', 'estado_preparacion',
        'preparacion_at', 'listo_at', 'entregado_at',
        // Domicilios por WhatsApp
        'telefono_cliente', 'direccion_entrega', 'latitud_entrega', 'longitud_entrega', 'distancia_km',
        'costo_domicilio', 'estado_pago', 'comprobante', 'lectura_comprobante', 'motivo_rechazo', 'conversacion_wa_id',
    ];

    protected $casts = [
        'subtotal'              => 'integer',
        'descuento'             => 'integer',
        'total'                 => 'integer',
        'monto_pagado'          => 'integer',
        'cambio'                => 'integer',
        'numero_turno'          => 'integer',
        'factura_enviada_dian'  => 'boolean',
        'preparacion_at'        => 'datetime',
        'listo_at'              => 'datetime',
        'entregado_at'          => 'datetime',
        'costo_domicilio'       => 'integer',
        'distancia_km'          => 'float',
        'latitud_entrega'       => 'float',
        'longitud_entrega'      => 'float',
        'lectura_comprobante'   => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public function detalles()
    {
        return $this->hasMany(VentaDetalle::class);
    }

    public function pagos()
    {
        return $this->hasMany(VentaPago::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    // "LAURA · #14"
    public function etiquetaPedido(): string
    {
        $nombre = mb_strtoupper($this->nombre_pedido ?? 'CLIENTE');
        return $this->numero_turno ? "{$nombre} · #{$this->numero_turno}" : $nombre;
    }

    // "Efectivo + Nequi"
    public function resumenPago(): string
    {
        if ($this->relationLoaded('pagos') && $this->pagos->isNotEmpty()) {
            return $this->pagos->map->nombreMetodo()->unique()->join(' + ');
        }
        return ucfirst((string) $this->metodo_pago);
    }
}
