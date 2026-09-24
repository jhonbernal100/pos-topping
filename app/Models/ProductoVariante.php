<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductoVariante extends Model
{
    protected $table = 'producto_variantes';

    protected $fillable = ['producto_id', 'nombre', 'precio', 'orden', 'activo'];

    protected $casts = [
        'precio' => 'integer',
        'orden'  => 'integer',
        'activo' => 'boolean',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }
}