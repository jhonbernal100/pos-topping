<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ModificadorInsumo extends Model
{
    protected $table = 'modificador_insumos';

    protected $fillable = ['modificador_id', 'insumo_id', 'cantidad'];

    protected $casts = [
        'cantidad' => 'float',
    ];

    public function modificador()
    {
        return $this->belongsTo(Modificador::class);
    }

    public function insumo()
    {
        return $this->belongsTo(Insumo::class);
    }
}
