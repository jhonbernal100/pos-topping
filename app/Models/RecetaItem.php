<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecetaItem extends Model
{
    protected $table = 'receta_items';

    protected $fillable = ['producto_id', 'variante_id', 'insumo_id', 'cantidad'];

    protected $casts = [
        'cantidad' => 'float',
    ];

    public function producto()
    {
        return $this->belongsTo(Producto::class);
    }

    public function variante()
    {
        return $this->belongsTo(ProductoVariante::class, 'variante_id');
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }
}
