<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sede extends Model
{
    protected $table = 'sedes';

    protected $fillable = [
        'tenant_id', 'nombre', 'direccion', 'barrio', 'ciudad', 'telefono',
        'latitud', 'longitud', 'radio_cobertura_km', 'acepta_domicilios', 'activo',
    ];

    protected $casts = [
        'latitud'            => 'float',
        'longitud'           => 'float',
        'radio_cobertura_km' => 'float',
        'acepta_domicilios'  => 'boolean',
        'activo'             => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function usuarios()
    {
        return $this->hasMany(User::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }
}