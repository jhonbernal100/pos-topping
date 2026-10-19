<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class TarifaDomicilio extends Model
{
    protected $table = 'tarifas_domicilio';

    protected $fillable = ['tenant_id', 'hasta_km', 'valor'];

    protected $casts = ['hasta_km' => 'float', 'valor' => 'integer'];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }
}
