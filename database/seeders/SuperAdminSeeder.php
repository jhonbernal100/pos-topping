<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = env('SUPERADMIN_EMAIL');
        $password = env('SUPERADMIN_PASSWORD');

        if (!$email || !$password) {
            $this->command->error('Define SUPERADMIN_EMAIL y SUPERADMIN_PASSWORD en el .env');
            return;
        }

        User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => 'Avanzas Digital',
                'tenant_id'         => null,
                'rol'               => 'superadmin',
                'activo'            => true,
                'password'          => Hash::make($password),
                'email_verified_at' => now(),
            ]
        );

        $this->command->info("Superadmin POS Topping listo: {$email}");
    }
}