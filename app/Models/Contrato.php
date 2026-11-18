<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contrato extends Model
{
    protected $table = 'contratos';

    protected $fillable = [
        'tenant_id', 'nombre', 'archivo', 'tamano', 'fecha_firma',
        'vigencia_desde', 'vigencia_hasta', 'notas', 'subido_por',
    ];

    protected $casts = [
        'fecha_firma'    => 'date',
        'vigencia_desde' => 'date',
        'vigencia_hasta' => 'date',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'subido_por');
    }
}
