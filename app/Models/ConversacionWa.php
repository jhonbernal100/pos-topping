<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Conversación de WhatsApp con un cliente. No usa TenantScope porque el webhook
 * llega sin sesión: siempre se consulta filtrando por tenant_id.
 */
class ConversacionWa extends Model
{
    protected $table = 'conversaciones_wa';

    protected $fillable = [
        'tenant_id', 'chat_id', 'telefono', 'nombre', 'estado', 'sede_id', 'latitud', 'longitud',
        'distancia_km', 'costo_domicilio', 'direccion', 'venta_id', 'token', 'token_expira_at',
        'humano_hasta', 'ultimo_mensaje_at',
    ];

    protected $casts = [
        'latitud'           => 'float',
        'longitud'          => 'float',
        'distancia_km'      => 'float',
        'costo_domicilio'   => 'integer',
        'token_expira_at'   => 'datetime',
        'humano_hasta'      => 'datetime',
        'ultimo_mensaje_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class)->withoutGlobalScopes();
    }

    public function mensajes()
    {
        return $this->hasMany(MensajeWa::class, 'conversacion_id');
    }

    public function tokenVigente(): bool
    {
        return $this->token && $this->token_expira_at && $this->token_expira_at->isFuture();
    }
}
