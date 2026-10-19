<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensajeWa extends Model
{
    protected $table = 'mensajes_wa';

    protected $fillable = ['tenant_id', 'conversacion_id', 'direccion', 'wa_id', 'tipo', 'cuerpo', 'media_path', 'enviado'];

    protected $casts = ['enviado' => 'boolean'];
}
