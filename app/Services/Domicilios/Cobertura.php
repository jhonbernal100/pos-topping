<?php

namespace App\Services\Domicilios;

use App\Models\Sede;
use App\Models\TarifaDomicilio;
use App\Models\Tenant;

/**
 * Elige la sede más cercana que atiende domicilios y calcula el valor del envío.
 */
class Cobertura
{
    /**
     * @return array{sede: Sede, distancia: float, costo: int}|null  null = fuera de cobertura
     */
    public function para(Tenant $tenant, float $lat, float $lng): ?array
    {
        $sedes = Sede::where('tenant_id', $tenant->id)
            ->where('activo', true)
            ->where('acepta_domicilios', true)
            ->whereNotNull('latitud')
            ->whereNotNull('longitud')
            ->get();

        $tarifas = TarifaDomicilio::withoutGlobalScopes()->where('tenant_id', $tenant->id)->orderBy('hasta_km')->get();

        $mejor = null;
        foreach ($sedes as $sede) {
            $km = $this->distanciaKm($lat, $lng, $sede->latitud, $sede->longitud);

            if ($sede->radio_cobertura_km && $km > $sede->radio_cobertura_km) {
                continue;
            }

            $costo = $this->tarifa($tarifas, $km);
            if ($costo === null) {
                continue;
            }

            if (!$mejor || $km < $mejor['distancia']) {
                $mejor = ['sede' => $sede, 'distancia' => round($km, 2), 'costo' => $costo];
            }
        }

        return $mejor;
    }

    /** Valor del primer rango que cubre la distancia; null si ninguno. Sin tarifas configuradas = $0. */
    private function tarifa($tarifas, float $km): ?int
    {
        if ($tarifas->isEmpty()) {
            return 0;
        }
        $t = $tarifas->first(fn ($t) => $km <= $t->hasta_km);
        return $t ? (int) $t->valor : null;
    }

    /** Distancia en línea recta (Haversine), aumentada 30% para aproximar el recorrido por calles. */
    public function distanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r    = 6371;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a))) * 1.3;
    }
}
