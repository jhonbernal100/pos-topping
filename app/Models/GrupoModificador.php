<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class GrupoModificador extends Model
{
    protected $table = 'grupos_modificadores';

    protected $fillable = ['tenant_id', 'nombre', 'precio_extra', 'orden', 'activo'];

    protected $casts = [
        'precio_extra' => 'integer',
        'orden'        => 'integer',
        'activo'       => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function modificadores()
    {
        return $this->hasMany(Modificador::class, 'grupo_id')->orderBy('orden')->orderBy('nombre');
    }

    public function productos()
    {
        return $this->belongsToMany(Producto::class, 'producto_grupo_modificador', 'grupo_id', 'producto_id')
            ->withPivot(['incluidos', 'minimo', 'maximo', 'precio_extra', 'orden'])
            ->withTimestamps();
    }
}