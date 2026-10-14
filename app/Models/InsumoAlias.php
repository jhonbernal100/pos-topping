<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class InsumoAlias extends Model
{
    protected $table = 'insumo_alias';

    protected $fillable = ['tenant_id', 'proveedor_id', 'texto', 'insumo_id', 'factor'];

    protected $casts = [
        'factor' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    // "FRESA X 5KG  Calidad Exp." → "fresa x 5kg calidad exp"
    public static function normalizar(string $texto): string
    {
        $t = Str::of($texto)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]+/', ' ')->squish();
        return (string) $t->limit(191, '');
    }
}
