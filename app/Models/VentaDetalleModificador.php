<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaDetalleModificador extends Model
{
    protected $table = 'venta_detalle_modificadores';

    protected $fillable = [
        'venta_detalle_id', 'modificador_id', 'grupo_id',
        'nombre_grupo', 'nombre', 'cantidad', 'precio_unitario',
    ];

    protected $casts = [
        'cantidad'        => 'integer',
        'precio_unitario' => 'integer',
    ];

    public function detalle()
    {
        return $this->belongsTo(VentaDetalle::class, 'venta_detalle_id');
    }

    public function esAdicional(): bool
    {
        return $this->precio_unitario > 0;
    }
}