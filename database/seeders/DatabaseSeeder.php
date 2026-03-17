<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SystemSeeder::class);

        // Crear Usuarios de Prueba
        
        // Admin
        User::create([
            'name' => 'Admin Principal',
            'email' => 'admin@turismo.com',
            'password' => 'password',
            'role_id' => 1, // Admin
            'telefono' => '0999999999'
        ]);

        $jefe = User::create([
            'name' => 'Jefe Juan Pérez',
            'email' => 'jefe@turismo.com',
            'password' => 'password',
            'role_id' => 2, // Jefe
            'telefono' => '0988888888'
        ]);

        $equipo = User::create([
            'name' => 'Estudiante Ana',
            'email' => 'equipo@turismo.com',
            'password' => 'password',
            'role_id' => 3, // Equipo
            'telefono' => '0977777777'
        ]);

        // Datos de prueba adicionales
        if (app()->environment('local')) {
            $lugarId = DB::table('lugares')->where('nombre', 'Cuenca Rural')->value('id');

            if ($lugarId) {
                $zonaId = DB::table('zonas')->insertGetId([
                    'lugar_id' => $lugarId,
                    'jefe_user_id' => $jefe->id,
                    'nombre' => 'Zona El Cajas - Sector Test',
                    'descripcion' => 'Zona generada manualmente para pruebas.',
                    'created_at' => now(),
                ]);

                DB::table('zona_equipo')->insert([
                    'zona_id' => $zonaId,
                    'user_id' => $equipo->id
                ]);
            }
        }
    }
}