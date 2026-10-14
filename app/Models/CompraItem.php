<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompraItem extends Model
{
    protected $table = 'compra_items';

    protected $fillable = [
        'compra_id', 'insumo_id', 'descripcion', 'cantidad_factura',
        'unidad_factura', 'factor', 'cantidad', 'costo_total',
    ];

    protected $casts = [
        'cantidad_factura' => 'float',
        'factor'           => 'float',
        'cantidad'         => 'float',
        'costo_total'      => 'integer',
    ];

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    // Costo por unidad base (g / ml / und)
    public function costoUnitario(): float
    {
        return $this->cantidad > 0 ? $this->costo_total / $this->cantidad : 0;
    }
}
