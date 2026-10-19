<?php

namespace App\Services\Domicilios;

use Illuminate\Support\Facades\DB;

/** Consecutivo diario por sede (mismo esquema que la caja). Llamar dentro de una transacción. */
class Turnos
{
    public function siguiente(int $sedeId): int
    {
        $hoy = now()->toDateString();

        DB::table('turnos_diarios')->insertOrIgnore([
            'sede_id' => $sedeId, 'fecha' => $hoy, 'ultimo' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);

        $fila = DB::table('turnos_diarios')->where('sede_id', $sedeId)->where('fecha', $hoy)->lockForUpdate()->first();
        $siguiente = (int) $fila->ultimo + 1;

        DB::table('turnos_diarios')->where('id', $fila->id)->update(['ultimo' => $siguiente, 'updated_at' => now()]);

        return $siguiente;
    }
}
