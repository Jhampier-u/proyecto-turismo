<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Catálogos del sistema (roles, geografía, categorías). Necesarios en
        // todos los entornos, incluido producción.
        $this->call(SystemSeeder::class);

        // Administrador inicial a partir de ADMIN_EMAIL / ADMIN_PASSWORD.
        $this->call(AdminSeeder::class);

        // Usuarios y zona de ejemplo: solo en desarrollo.
        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);
        }
    }
}
