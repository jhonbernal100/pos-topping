<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modificador extends Model
{
    protected $table = 'modificadores';

    protected $fillable = ['grupo_id', 'nombre', 'precio_extra', 'dias_disponibles', 'orden', 'activo'];

    protected $casts = [
        'precio_extra'     => 'integer',
        'dias_disponibles' => 'array',
        'orden'            => 'integer',
        'activo'           => 'boolean',
    ];

    public function grupo()
    {
        return $this->belongsTo(GrupoModificador::class, 'grupo_id');
    }

    // Precio de la opción adicional: el suyo propio o el del grupo
    public function precioAdicional(): int
    {
        return $this->precio_extra ?? (int) $this->grupo?->precio_extra;
    }

    // Ej: la sandía solo sábado y domingo → dias_disponibles = [6, 7]
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
}