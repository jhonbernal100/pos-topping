<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InsumoStock extends Model
{
    protected $table = 'insumo_stocks';

    protected $fillable = ['insumo_id', 'sede_id', 'cantidad', 'stock_minimo'];

    protected $casts = [
        'cantidad'     => 'float',
        'stock_minimo' => 'float',
    ];

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }

    public function sede()
    {
        return $this->belongsTo(Sede::class);
    }

    // agotado | bajo | ok
    public function estado(): string
    {
        if ($this->cantidad <= 0) return 'agotado';
        if ($this->stock_minimo > 0 && $this->cantidad <= $this->stock_minimo) return 'bajo';
        return 'ok';
    }
}
