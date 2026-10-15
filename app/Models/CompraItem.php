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

    // Costo por unidad base (g / ml / und).
    // Si la compra suma impuestos al costo, se reparten en proporción al valor de cada línea.
    public function costoUnitario(): float
    {
        if ($this->cantidad <= 0) {
            return 0;
        }

        $costo  = (float) $this->costo_total;
        $compra = $this->compra;

        if ($compra && $compra->impuestos_al_costo && $compra->impuestos > 0 && $compra->subtotal > 0) {
            $costo *= 1 + $compra->impuestos / $compra->subtotal;
        }

        return $costo / $this->cantidad;
    }
}
