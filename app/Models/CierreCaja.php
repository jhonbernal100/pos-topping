<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class CierreCaja extends Model
{
    protected $table = 'cierres_caja';

    protected $fillable = [
        'tenant_id', 'sede_id', 'user_id', 'fecha',
        'num_ventas', 'total_ventas', 'num_anuladas', 'total_anuladas', 'efectivo_ventas',
        'base_inicial', 'efectivo_esperado', 'efectivo_contado', 'diferencia',
        'detalle', 'notas',
    ];

    protected $casts = [
        'fecha'             => 'date',
        'num_ventas'        => 'integer',
        'total_ventas'      => 'integer',
        'num_anuladas'      => 'integer',
        'total_anuladas'    => 'integer',
        'efectivo_ventas'   => 'integer',
        'base_inicial'      => 'integer',
        'efectivo_esperado' => 'integer',
        'efectivo_contado'  => 'integer',
        'diferencia'        => 'integer',
        'detalle'           => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // cuadra | sobrante | faltante
    public function estadoArqueo(): string
    {
        if ($this->diferencia === 0) return 'cuadra';
        return $this->diferencia > 0 ? 'sobrante' : 'faltante';
    }
}
