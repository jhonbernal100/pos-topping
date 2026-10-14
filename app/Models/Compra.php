<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Compra extends Model
{
    protected $table = 'compras';

    protected $fillable = [
        'tenant_id', 'sede_id', 'proveedor_id', 'user_id', 'numero_factura', 'fecha',
        'subtotal', 'impuestos', 'total', 'estado', 'origen', 'foto', 'lectura_ia', 'notas',
    ];

    protected $casts = [
        'fecha'      => 'date',
        'subtotal'   => 'integer',
        'impuestos'  => 'integer',
        'total'      => 'integer',
        'lectura_ia' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function items()
    {
        return $this->hasMany(CompraItem::class);
    }

    public function proveedor()
    {
        return $this->belongsTo(Proveedor::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
