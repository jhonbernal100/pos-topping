<?php

namespace App\Models;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Producto extends Model
{
    use SoftDeletes;

    protected $table = 'productos';

    protected $fillable = [
        'tenant_id', 'nombre', 'referencia', 'marca',
        'categoria', 'unidad', 'precio_compra', 'precio_venta',
        'stock', 'stock_minimo', 'codigo_barras', 'descripcion',
        'foto', 'activo',
        // Menú POS Topping
        'categoria_menu_id', 'tipo_menu', 'disponible_local',
        'disponible_domicilio', 'dias_disponibles', 'orden_menu',
    ];

    protected $casts = [
        'activo'               => 'boolean',
        'precio_compra'        => 'integer',
        'precio_venta'         => 'integer',
        'stock'                => 'integer',
        'stock_minimo'         => 'integer',
        'disponible_local'     => 'boolean',
        'disponible_domicilio' => 'boolean',
        'dias_disponibles'     => 'array',
        'orden_menu'           => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope(new TenantScope());
    }

    // ---------------------------------------------------------------
    // Relaciones
    // ---------------------------------------------------------------

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function categoriaMenu()
    {
        return $this->belongsTo(CategoriaMenu::class, 'categoria_menu_id');
    }

    public function variantes()
    {
        return $this->hasMany(ProductoVariante::class)->orderBy('orden')->orderBy('precio');
    }

    public function variantesActivas()
    {
        return $this->variantes()->where('activo', true);
    }

    public function gruposModificadores()
    {
        return $this->belongsToMany(GrupoModificador::class, 'producto_grupo_modificador', 'producto_id', 'grupo_id')
            ->withPivot(['incluidos', 'minimo', 'maximo', 'precio_extra', 'orden'])
            ->withTimestamps()
            ->orderByPivot('orden');
    }

    // ---------------------------------------------------------------
    // Scopes
    // ---------------------------------------------------------------

    // Productos que forman parte de la carta (activos y con categoría)
    public function scopeDelMenu(Builder $query): Builder
    {
        return $query->where('activo', true)
            ->whereNotNull('categoria_menu_id')
            ->orderBy('orden_menu')
            ->orderBy('nombre');
    }

    // ---------------------------------------------------------------
    // Reglas de disponibilidad
    // ---------------------------------------------------------------

    // Ej: torta de chocolate solo viernes a domingo → [5, 6, 7]
    public function disponibleHoy(): bool
    {
        if (!$this->activo) {
            return false;
        }

        if (empty($this->dias_disponibles)) {
            return true;
        }

        return in_array(now()->dayOfWeekIso, $this->dias_disponibles, true);
    }

    // $canal: 'local' o 'domicilio' (ej: la Nèbula no va a domicilio)
    public function disponibleEnCanal(string $canal): bool
    {
        return $canal === 'domicilio' ? $this->disponible_domicilio : $this->disponible_local;
    }

    public function disponiblePara(string $canal): bool
    {
        return $this->disponibleHoy() && $this->disponibleEnCanal($canal);
    }

    // ---------------------------------------------------------------
    // Precios
    // ---------------------------------------------------------------

    public function tieneVariantes(): bool
    {
        return $this->variantesActivas()->exists();
    }

    // Precio para mostrar en la carta: el menor de sus variantes, o su precio base
    public function precioDesde(): int
    {
        $minimo = $this->variantesActivas()->min('precio');
        return $minimo !== null ? (int) $minimo : (int) $this->precio_venta;
    }
}