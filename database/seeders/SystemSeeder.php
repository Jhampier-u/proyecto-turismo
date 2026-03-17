<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SystemSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $roles = ['admin', 'jefe_zona', 'equipo'];
        foreach ($roles as $rol) {
            DB::table('roles')->updateOrInsert(['nombre' => $rol], ['descripcion' => 'Rol del sistema']);
        }

        // 2. Geografía Completa
        $geografia = [
            'Sierra' => [
                'Azuay', 'Bolívar', 'Cañar', 'Carchi', 'Chimborazo', 
                'Cotopaxi', 'Imbabura', 'Loja', 'Pichincha', 'Tungurahua'
            ],
            'Costa' => [
                'El Oro', 'Esmeraldas', 'Guayas', 'Los Ríos', 
                'Manabí', 'Santa Elena', 'Santo Domingo de los Tsáchilas'
            ],
            'Amazonía' => [
                'Morona Santiago', 'Napo', 'Orellana', 'Pastaza', 
                'Sucumbíos', 'Zamora Chinchipe'
            ],
            'Insular' => ['Galápagos']
        ];

        foreach ($geografia as $region => $provincias) {
            $regionId = DB::table('regiones')->insertGetId(['nombre' => $region]);
            foreach ($provincias as $provincia) {
                DB::table('provincias')->insert(['region_id' => $regionId, 'nombre' => $provincia]);
            }
        }

        // 3. Lugar de Prueba (Cuenca Rural)
        $azuayId = DB::table('provincias')->where('nombre', 'Azuay')->value('id');
        if ($azuayId) {
            DB::table('lugares')->insertOrIgnore([
                'provincia_id' => $azuayId,
                'nombre' => 'Cuenca Rural'
            ]);
        }

        // 4. Catálogos de Inventario
        $tipos = ['Público', 'Privado', 'Comunitario', 'Mixto'];
        foreach ($tipos as $t) {
            DB::table('tipos_propietario')->insertOrIgnore(['nombre' => $t]);
        }

        $cats = [
            'Sitios Naturales' => ['Montañas', 'Planicies', 'Cuerpos de Agua', 'Fenómenos Geológicos'],
            'Manifestaciones Culturales' => ['Históricas', 'Etnográficas', 'Arquitectura', 'Folklore']
        ];

        foreach ($cats as $padre => $hijos) {
            $parentId = DB::table('categorias_recurso')->insertGetId(['nombre' => $padre]);
            foreach ($hijos as $hijo) {
                DB::table('categorias_recurso')->insert(['parent_id' => $parentId, 'nombre' => $hijo]);
            }
        }

    }
}