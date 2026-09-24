<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class CategoriaMenu extends Model
{
    protected $table = 'categorias_menu';

    protected $fillable = ['tenant_id', 'nombre', 'orden', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
        'orden'  => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function productos()
    {
        return $this->hasMany(Producto::class, 'categoria_menu_id')->orderBy('orden_menu');
    }
}