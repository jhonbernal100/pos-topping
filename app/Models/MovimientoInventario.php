<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';

    protected $fillable = [
        'tenant_id', 'sede_id', 'insumo_id', 'tipo', 'cantidad', 'saldo',
        'costo_unitario', 'venta_id', 'compra_id', 'user_id', 'nota',
    ];

    protected $casts = [
        'cantidad'       => 'float',
        'saldo'          => 'float',
        'costo_unitario' => 'float',
    ];

    public const TIPOS = [
        'entrada'   => 'Entrada',
        'venta'     => 'Venta',
        'anulacion' => 'Anulación',
        'merma'     => 'Merma',
        'ajuste'    => 'Ajuste por conteo',
        'traslado'  => 'Traslado',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function venta()
    {
        return $this->belongsTo(Venta::class);
    }

    public function compra()
    {
        return $this->belongsTo(Compra::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
