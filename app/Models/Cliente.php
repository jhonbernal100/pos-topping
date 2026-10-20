<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $fillable = [
        'tenant_id', 'nombre', 'tipo_documento',
        'numero_documento', 'telefono', 'email',
        'direccion', 'ciudad', 'activo',
        // Convenio (ej: Rappi)
        'ajuste_porcentaje', 'venta_credito', 'dias_credito', 'pide_codigo',
    ];

    protected $casts = [
        'activo'            => 'boolean',
        'ajuste_porcentaje' => 'float',
        'venta_credito'     => 'boolean',
        'dias_credito'      => 'integer',
        'pide_codigo'       => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function ventas()
    {
        return $this->hasMany(Venta::class);
    }

    // Tiene condiciones especiales en la caja (precio distinto, crédito o código de pedido)
    public function tieneConvenio(): bool
    {
        return $this->ajuste_porcentaje != 0 || $this->venta_credito || $this->pide_codigo;
    }

    // Precio de la carta con el ajuste del convenio, redondeado al peso
    public function ajustarPrecio(int $precio): int
    {
        return (int) round($precio * (1 + $this->ajuste_porcentaje / 100));
    }

    // "+25 %" / "−10 %"
    public function etiquetaAjuste(): ?string
    {
        if ($this->ajuste_porcentaje == 0) {
            return null;
        }
        $valor = rtrim(rtrim(number_format(abs($this->ajuste_porcentaje), 2, ',', ''), '0'), ',');
        return ($this->ajuste_porcentaje > 0 ? '+' : '−') . $valor . ' %';
    }
}
