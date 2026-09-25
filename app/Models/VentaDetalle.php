<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalle extends Model
{
    protected $fillable = [
        'venta_id', 'producto_id', 'variante_id', 'nombre_producto', 'nombre_variante',
        'cantidad', 'precio_base', 'precio_adicionales', 'precio_unitario', 'subtotal', 'notas',
    ];

    protected $casts = [
        'cantidad'           => 'integer',
        'precio_base'        => 'integer',
        'precio_adicionales' => 'integer',
        'precio_unitario'    => 'integer',
        'subtotal'           => 'integer',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto()
    {
        return $this->belongsTo(Producto::class)->withTrashed();
    }

    public function modificadores()
    {
        return $this->hasMany(VentaDetalleModificador::class, 'venta_detalle_id');
    }

    // "Merengón Grande (Yogurt)"
    public function nombreCompleto(): string
    {
        return $this->nombre_variante
            ? "{$this->nombre_producto} ({$this->nombre_variante})"
            : $this->nombre_producto;
    }
}