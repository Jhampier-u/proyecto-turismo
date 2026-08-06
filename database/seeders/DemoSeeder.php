<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Usuarios y datos de ejemplo para desarrollo local.
 *
 * NO debe ejecutarse en producción: las contraseñas son triviales y conocidas
 * por estar en el repositorio. DatabaseSeeder solo lo invoca en entorno local.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment('local')) {
            $this->command?->error('DemoSeeder solo puede ejecutarse en entorno local.');
            return;
        }

        $roles = Role::pluck('id', 'nombre');

        $admin = User::create([
            'name'     => 'Admin Principal',
            'email'    => 'admin@local.test',
            'password' => 'password',
            'role_id'  => $roles['admin'] ?? null,
            'telefono' => '0999999999',
        ]);

        $jefe = User::create([
            'name'     => 'Jefe Juan Pérez',
            'email'    => 'jefe@local.test',
            'password' => 'password',
            'role_id'  => $roles['jefe_zona'] ?? null,
            'telefono' => '0988888888',
        ]);

        $equipo = User::create([
            'name'     => 'Estudiante Ana',
            'email'    => 'equipo@local.test',
            'password' => 'password',
            'role_id'  => $roles['equipo'] ?? null,
            'telefono' => '0977777777',
        ]);

        $lugarId = DB::table('lugares')->where('nombre', 'Cuenca Rural')->value('id');

        if ($lugarId) {
            $zonaId = DB::table('zonas')->insertGetId([
                'lugar_id'     => $lugarId,
                'jefe_user_id' => $jefe->id,
                'nombre'       => 'Zona El Cajas - Sector Test',
                'descripcion'  => 'Zona generada para pruebas locales.',
                'created_at'   => now(),
                'updated_at'   => now(),
            ]);

            DB::table('zona_equipo')->insert([
                'zona_id' => $zonaId,
                'user_id' => $equipo->id,
            ]);
        }

        $this->command?->info("Datos demo creados (admin: {$admin->email}, contraseña: password).");
    }
}
