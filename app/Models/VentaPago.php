<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaPago extends Model
{
    protected $table = 'venta_pagos';

    protected $fillable = ['venta_id', 'metodo', 'monto', 'referencia'];

    protected $casts = [
        'monto' => 'integer',
    ];

    public const METODOS = [
        'efectivo'  => 'Efectivo',
        'nequi'     => 'Nequi',
        'daviplata' => 'Daviplata',
        'tarjeta'   => 'Tarjeta',
    ];

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function nombreMetodo(): string
    {
        return self::METODOS[$this->metodo] ?? ucfirst($this->metodo);
    }
}