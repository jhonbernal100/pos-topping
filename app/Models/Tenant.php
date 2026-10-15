<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Tenant extends Model
{
    protected $fillable = [
        'nombre', 'nit', 'telefono',
        'direccion', 'ciudad', 'plan', 'activo',
        'alegra_user', 'alegra_token',
        'alegra_resolucion_id', 'facturacion_electronica',
        'subscription_status', 'trial_ends_at',
        'subscription_ends_at', 'subscription_plan',
        'subscription_price',
        // Marca (apariencia de la caja)
        'logo', 'color_primario', 'color_secundario', 'color_fondo',
    ];

    protected $casts = [
        'activo'                  => 'boolean',
        'facturacion_electronica' => 'boolean',
        'trial_ends_at'           => 'datetime',
        'subscription_ends_at'    => 'datetime',
        'subscription_price'      => 'integer',
    ];

    // Límites de la demo de 30 días (null = ilimitado en planes pagos)
    public const TRIAL_MAX_SEDES             = 2;
    public const TRIAL_MAX_USUARIOS_POR_SEDE = 2;
    public const TRIAL_MAX_PRODUCTOS         = 20;

    // Precio mensual por sede y meses que se cobran en cada pago
    public const PLANES = [
        'trimestral' => ['nombre' => 'Trimestral', 'mensual_por_sede' => 45000, 'meses' => 3],
        'anual'      => ['nombre' => 'Anual',      'mensual_por_sede' => 35000, 'meses' => 12],
    ];

    /** Sedes que se cobran (las activas; mínimo 1). */
    public function sedesCobradas(): int
    {
        return max(1, $this->sedes()->where('activo', true)->count());
    }

    /** Valor de cada pago del plan: mensual por sede × meses × sedes. */
    public function precioPlan(?string $plan = null, ?int $sedes = null): int
    {
        $p = self::PLANES[$plan ?? $this->subscription_plan] ?? null;
        if (!$p) {
            return 0;
        }
        return $p['mensual_por_sede'] * $p['meses'] * ($sedes ?? $this->sedesCobradas());
    }

    public function tieneAcceso(): bool
    {
        return match($this->subscription_status) {
            'trial'  => $this->trial_ends_at && $this->trial_ends_at->isFuture(),
            'activa' => $this->subscription_ends_at && $this->subscription_ends_at->isFuture(),
            default  => false,
        };
    }

    public function diasRestantes(): int
    {
        $fecha = $this->subscription_status === 'trial'
            ? $this->trial_ends_at
            : $this->subscription_ends_at;

        if (!$fecha) return 0;
        return max(0, (int) now()->diffInDays($fecha, false));
    }

    public function esTrial(): bool
    {
        return $this->subscription_status === 'trial';
    }

    public function limiteSedes(): ?int
    {
        return $this->esTrial() ? self::TRIAL_MAX_SEDES : null;
    }

    public function limiteUsuariosPorSede(): ?int
    {
        return $this->esTrial() ? self::TRIAL_MAX_USUARIOS_POR_SEDE : null;
    }

    public function limiteProductos(): ?int
    {
        return $this->esTrial() ? self::TRIAL_MAX_PRODUCTOS : null;
    }

    public function puedeCrearSede(): bool
    {
        $limite = $this->limiteSedes();
        return $limite === null || $this->sedes()->count() < $limite;
    }

    public function productos()   { return $this->hasMany(Producto::class); }
    public function clientes()    { return $this->hasMany(Cliente::class); }
    public function proveedores() { return $this->hasMany(Proveedor::class); }
    public function ventas()      { return $this->hasMany(Venta::class); }
    public function usuarios()    { return $this->hasMany(User::class); }
    public function sedes()       { return $this->hasMany(Sede::class); }
}
