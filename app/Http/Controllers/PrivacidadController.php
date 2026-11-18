<?php

namespace App\Http\Controllers;

use App\Models\Tenant;

/**
 * Políticas de tratamiento de datos personales (Ley 1581 de 2012, Decreto 1074 de 2015).
 * - /privacidad          → la de avanzas.digital (responsable de los datos de sus clientes y usuarios)
 * - /privacidad/{nit}    → la de cada negocio que usa POS Topping (responsable), con avanzas.digital como encargado
 * Una sola página sirve a todos los clientes: los datos del responsable salen de su registro.
 */
class PrivacidadController extends Controller
{
    public const AVANZAS = [
        'nombre'         => 'avanzas.digital',
        'representante'  => 'Luz Stella Remolina',
        'direccion'      => 'Calle 8C n.º 82A-91, Bogotá D.C., Colombia',
        'correo'         => 'soporte@avanzas.digital',
        'horario'        => 'lunes a viernes de 9:00 a. m. a 5:00 p. m., excepto festivos',
    ];

    public const VIGENCIA = '18 de noviembre de 2026';

    public function avanzas()
    {
        return view('privacidad.politica', [
            'tenant'      => null,
            'responsable' => self::AVANZAS + ['identificacion' => null, 'telefono' => null],
            'avanzas'     => self::AVANZAS,
            'vigencia'    => self::VIGENCIA,
        ]);
    }

    public function cliente(string $nit)
    {
        $tenant = Tenant::where('nit', $nit)->where('activo', true)->firstOrFail();

        return view('privacidad.politica', [
            'tenant'      => $tenant,
            'responsable' => [
                'nombre'         => $tenant->nombre,
                'identificacion' => 'NIT ' . $this->formatoNit($tenant->nit),
                'direccion'      => trim(collect([$tenant->direccion, $tenant->ciudad])->filter()->join(', ')) ?: null,
                'correo'         => $tenant->correo_contacto,
                'telefono'       => $tenant->telefono,
                'horario'        => null,
            ],
            'avanzas'     => self::AVANZAS,
            'vigencia'    => self::VIGENCIA,
        ]);
    }

    // 52934889-8 → 52.934.889-8
    private function formatoNit(string $nit): string
    {
        if (!preg_match('/^(\d+)-?(\d)?$/', str_replace('.', '', $nit), $m)) {
            return $nit;
        }
        $numero = number_format((int) $m[1], 0, ',', '.');
        return isset($m[2]) && $m[2] !== '' ? "{$numero}-{$m[2]}" : $numero;
    }
}
