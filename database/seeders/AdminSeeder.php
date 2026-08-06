<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Crea la cuenta de administrador inicial a partir de ADMIN_EMAIL y
 * ADMIN_PASSWORD. Es idempotente: si la cuenta ya existe no la toca,
 * de modo que una contraseña cambiada desde la aplicación no se revierte.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email    = config('turismo.admin_email');
        $password = config('turismo.admin_password');

        if (! $email || ! $password) {
            $this->command?->warn(
                'ADMIN_EMAIL / ADMIN_PASSWORD no definidas: no se creó ningún administrador.'
            );
            return;
        }

        if (User::where('email', $email)->exists()) {
            $this->command?->info("El administrador {$email} ya existe; sin cambios.");
            return;
        }

        $rolAdmin = Role::where('nombre', 'admin')->value('id');

        User::create([
            'name'     => config('turismo.admin_nombre'),
            'email'    => $email,
            'password' => $password, // el cast 'hashed' del modelo lo hashea
            'role_id'  => $rolAdmin,
        ]);

        $this->command?->info("Administrador {$email} creado.");
    }
}
