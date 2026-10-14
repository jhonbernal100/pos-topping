<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Insumo extends Model
{
    protected $table = 'insumos';

    protected $fillable = ['tenant_id', 'nombre', 'unidad', 'categoria', 'costo_unitario', 'activo'];

    protected $casts = [
        'costo_unitario' => 'float',
        'activo'         => 'boolean',
    ];

    public const UNIDADES = ['g' => 'gramos', 'ml' => 'mililitros', 'und' => 'unidades'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function stocks()
    {
        return $this->hasMany(InsumoStock::class);
    }

    public function stockEnSede(int $sedeId): ?InsumoStock
    {
        return $this->stocks->firstWhere('sede_id', $sedeId);
    }

    public function movimientos()
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function alias()
    {
        return $this->hasMany(InsumoAlias::class);
    }

    // 2500 g → "2,5 kg" · 750 ml → "750 ml" · 12 und → "12 und"
    public static function formatear(float $cantidad, string $unidad): string
    {
        $abs = abs($cantidad);

        if ($unidad === 'g' && $abs >= 1000) {
            return number_format($cantidad / 1000, 2, ',', '.') . ' kg';
        }
        if ($unidad === 'ml' && $abs >= 1000) {
            return number_format($cantidad / 1000, 2, ',', '.') . ' L';
        }

        $decimales = fmod($abs, 1.0) > 0 ? 1 : 0;
        return number_format($cantidad, $decimales, ',', '.') . ' ' . $unidad;
    }
}
