<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Catálogos del sistema. Se ejecuta en todos los entornos, incluido producción.
 *
 * Es idempotente: puede correr varias veces sin duplicar nada. Antes usaba
 * insertGetId()/insertOrIgnore() sobre tablas sin restricciones de unicidad, de
 * modo que cada ejecución añadía copias de toda la geografía y los catálogos.
 */
class SystemSeeder extends Seeder
{
    public function run(): void
    {
        $this->sembrarRoles();
        $this->sembrarGeografia();
        $this->sembrarCatalogosInventario();
    }

    /** Devuelve el id de la fila, creándola solo si no existe. */
    private function idDe(string $tabla, array $claves, array $extra = []): int
    {
        $consulta = DB::table($tabla);

        foreach ($claves as $columna => $valor) {
            // where('col', null) compila a "col = NULL", que nunca coincide.
            $valor === null
                ? $consulta->whereNull($columna)
                : $consulta->where($columna, $valor);
        }

        $id = $consulta->value('id');

        return $id ?? DB::table($tabla)->insertGetId($claves + $extra);
    }

    private function sembrarRoles(): void
    {
        foreach (['admin', 'jefe_zona', 'equipo'] as $rol) {
            DB::table('roles')->updateOrInsert(
                ['nombre' => $rol],
                ['descripcion' => 'Rol del sistema']
            );
        }
    }

    private function sembrarGeografia(): void
    {
        $geografia = [
            'Sierra' => [
                'Azuay', 'Bolívar', 'Cañar', 'Carchi', 'Chimborazo',
                'Cotopaxi', 'Imbabura', 'Loja', 'Pichincha', 'Tungurahua',
            ],
            'Costa' => [
                'El Oro', 'Esmeraldas', 'Guayas', 'Los Ríos',
                'Manabí', 'Santa Elena', 'Santo Domingo de los Tsáchilas',
            ],
            'Amazonía' => [
                'Morona Santiago', 'Napo', 'Orellana', 'Pastaza',
                'Sucumbíos', 'Zamora Chinchipe',
            ],
            'Insular' => ['Galápagos'],
        ];

        foreach ($geografia as $region => $provincias) {
            $regionId = $this->idDe('regiones', ['nombre' => $region]);

            foreach ($provincias as $provincia) {
                $this->idDe('provincias', ['region_id' => $regionId, 'nombre' => $provincia]);
            }
        }

        $azuayId = DB::table('provincias')->where('nombre', 'Azuay')->value('id');

        if ($azuayId) {
            $this->idDe('lugares', ['provincia_id' => $azuayId, 'nombre' => 'Cuenca Rural']);
        }
    }

    private function sembrarCatalogosInventario(): void
    {
        foreach (['Público', 'Privado', 'Comunitario', 'Mixto'] as $tipo) {
            $this->idDe('tipos_propietario', ['nombre' => $tipo]);
        }

        $categorias = [
            'Sitios Naturales' => ['Montañas', 'Planicies', 'Cuerpos de Agua', 'Fenómenos Geológicos'],
            'Manifestaciones Culturales' => ['Históricas', 'Etnográficas', 'Arquitectura', 'Folklore'],
        ];

        foreach ($categorias as $padre => $hijos) {
            $parentId = $this->idDe('categorias_recurso', ['parent_id' => null, 'nombre' => $padre]);

            foreach ($hijos as $hijo) {
                $this->idDe('categorias_recurso', ['parent_id' => $parentId, 'nombre' => $hijo]);
            }
        }
    }
}
