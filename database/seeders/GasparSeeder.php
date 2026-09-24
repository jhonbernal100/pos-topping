<?php

namespace Database\Seeders;

use App\Models\Sede;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GasparSeeder extends Seeder
{
    public function run(): void
    {
        // Negocio (se crea una sola vez; si ya existe no se modifica)
        $tenant = Tenant::firstOrCreate(
            ['nit' => '529934889-8'],
            [
                'nombre'                  => 'Gaspar Merengonería y Heladería',
                'telefono'                => '3106631824',
                'direccion'               => 'Carrera 53D Bis No. 2-06',
                'ciudad'                  => 'Bogotá',
                'plan'                    => 'basico',
                'activo'                  => true,
                'facturacion_electronica' => false,
                'subscription_status'     => 'trial',
                'trial_ends_at'           => now()->addDays(30)->endOfDay(),
            ]
        );

        // Sedes (se crean o se actualizan con estos datos; las coordenadas no se tocan)
        $sede1 = Sede::updateOrCreate(
            ['tenant_id' => $tenant->id, 'nombre' => 'Camelia'],
            [
                'direccion'         => 'Carrera 53D Bis No. 2-06',
                'barrio'            => 'Camelia',
                'ciudad'            => 'Bogotá',
                'telefono'          => '3106631824',
                'acepta_domicilios' => true,
                'activo'            => true,
            ]
        );

        $sede2 = Sede::updateOrCreate(
            ['tenant_id' => $tenant->id, 'nombre' => 'Diagonal 48 Sur'],
            [
                'direccion'         => 'Diagonal 48 Sur No. 53-95',
                'barrio'            => 'Venecia',
                'ciudad'            => 'Bogotá',
                'telefono'          => '3106631824',
                'acepta_domicilios' => true,
                'activo'            => true,
            ]
        );

        // Usuarios: sede null = propietario (ve todas las sedes)
        $usuarios = [
            ['name' => 'Hector Varon', 'email' => 'hectorcastro1607@gmail.com', 'sede' => null],   // propietario
            ['name' => 'Carolina',     'email' => 'carol07_06@hotmail.com',     'sede' => $sede2], // gerente Venecia
        ];

        $credenciales = [];

        foreach ($usuarios as $u) {
            $alcance = $u['sede']?->nombre ?? 'Todas (propietario)';

            if (User::where('email', $u['email'])->exists()) {
                $credenciales[] = [$u['name'], $u['email'], $alcance, '(ya existia, sin cambios)'];
                continue;
            }

            $password = Str::random(10);

            User::create([
                'name'              => $u['name'],
                'email'             => $u['email'],
                'password'          => Hash::make($password),
                'tenant_id'         => $tenant->id,
                'sede_id'           => $u['sede']?->id,
                'rol'               => 'dueno',
                'activo'            => true,
                'email_verified_at' => now(),
            ]);

            $credenciales[] = [$u['name'], $u['email'], $alcance, $password];
        }

        $this->command->info("Negocio: {$tenant->nombre} (ID {$tenant->id})");
        $this->command->info('Demo vence: ' . $tenant->trial_ends_at->format('d/m/Y'));
        $this->command->table(['Usuario', 'Correo', 'Sede', 'Contrasena temporal'], $credenciales);
        $this->command->warn('Entrega estas contrasenas en privado. No las pegues en el chat.');
    }
}