<?php

namespace Database\Seeders;

use App\Models\User;
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

    /**
     * User::ROL_ADMIN / ROL_JEFE / ROL_EQUIPO asumen estos ids fijos y
     * esAdmin()/esJefe()/esEquipo() comparan role_id contra ellos. Antes esta
     * función insertaba los roles solo por nombre y el id salía del orden de
     * inserción: cierto por accidente, no por construcción.
     *
     * En PostgreSQL eso se rompía en la suite de tests: RefreshDatabase
     * revierte los datos entre tests pero no la secuencia de "roles", así que
     * cada test sembraba los roles con ids más altos que el anterior y, desde
     * el segundo test, role_id no coincidía con ninguna constante. Fijar el
     * id aquí hace la suposición cierta pase lo que pase con el orden de
     * inserción o el estado previo de la secuencia.
     */
    private function sembrarRoles(): void
    {
        $roles = [
            User::ROL_ADMIN  => 'admin',
            User::ROL_JEFE   => 'jefe_zona',
            User::ROL_EQUIPO => 'equipo',
        ];

        foreach ($roles as $id => $nombre) {
            // El seeder corre también en producción, donde el rol ya existe
            // con un id que usuarios reales referencian. Si la fila ya está,
            // solo se actualiza la descripción: reasignar su id rompería esas
            // referencias. El id explícito solo se usa al crear la fila.
            $existente = DB::table('roles')->where('nombre', $nombre)->first();

            if ($existente) {
                DB::table('roles')->where('id', $existente->id)
                    ->update(['descripcion' => 'Rol del sistema']);
            } else {
                DB::table('roles')->insert([
                    'id' => $id,
                    'nombre' => $nombre,
                    'descripcion' => 'Rol del sistema',
                ]);
            }
        }

        // Insertar con id explícito no avanza la secuencia de la columna en
        // PostgreSQL (la secuencia solo avanza con nextval(), y un id fijado
        // a mano no pasa por ahí). "roles" es un catálogo cerrado de tres
        // filas que nada más inserta, así que hoy no hay quien choque con
        // eso, pero se sincroniza la secuencia igual para que un futuro
        // insertGetId() sin id explícito no intente reutilizar el 1, 2 o 3.
        if (DB::connection()->getDriverName() === 'pgsql') {
            DB::statement(
                "SELECT setval(pg_get_serial_sequence('roles', 'id'), (SELECT COALESCE(MAX(id), 1) FROM roles))"
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
