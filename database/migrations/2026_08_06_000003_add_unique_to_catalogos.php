<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Los catálogos no tenían restricciones de unicidad, por lo que el
 * insertOrIgnore() de SystemSeeder no ignoraba nada: cada re-ejecución del
 * seeder duplicaba regiones, provincias, categorías y tipos de propietario.
 *
 * Antes de crear los índices hay que consolidar los duplicados que pudieran
 * existir, repuntando las filas hijas para no dejarlas huérfanas.
 */
return new class extends Migration
{
    /**
     * tabla => [columnas que identifican el duplicado, [tabla hija => columna]]
     */
    private array $catalogos = [
        'regiones' => [
            ['nombre'],
            ['provincias' => 'region_id'],
        ],
        'provincias' => [
            ['region_id', 'nombre'],
            ['lugares' => 'provincia_id'],
        ],
        'lugares' => [
            ['provincia_id', 'nombre'],
            ['zonas' => 'lugar_id'],
        ],
        'tipos_propietario' => [
            ['nombre'],
            ['inventarios' => 'propietario_id'],
        ],
        'categorias_recurso' => [
            ['parent_id', 'nombre'],
            ['categorias_recurso' => 'parent_id', 'inventarios' => 'categoria_id'],
        ],
    ];

    public function up(): void
    {
        foreach ($this->catalogos as $tabla => [$columnas, $hijas]) {
            $this->consolidarDuplicados($tabla, $columnas, $hijas);

            Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                $table->unique($columnas);
            });
        }
    }

    public function down(): void
    {
        foreach ($this->catalogos as $tabla => [$columnas, $hijas]) {
            Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                $table->dropUnique($columnas);
            });
        }
    }

    /**
     * Conserva la fila de menor id de cada grupo duplicado, repunta las filas
     * hijas hacia ella y elimina el resto.
     */
    private function consolidarDuplicados(string $tabla, array $columnas, array $hijas): void
    {
        $grupos = DB::table($tabla)
            ->select($columnas)
            ->groupBy($columnas)
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($grupos as $grupo) {
            $consulta = DB::table($tabla);

            foreach ((array) $grupo as $columna => $valor) {
                // where('col', null) compila a "col = NULL", que nunca coincide.
                $valor === null
                    ? $consulta->whereNull($columna)
                    : $consulta->where($columna, $valor);
            }

            $ids = $consulta->orderBy('id')->pluck('id');

            $conservar = $ids->shift();

            foreach ($ids as $duplicado) {
                foreach ($hijas as $tablaHija => $columnaHija) {
                    DB::table($tablaHija)
                        ->where($columnaHija, $duplicado)
                        ->update([$columnaHija => $conservar]);
                }

                DB::table($tabla)->where('id', $duplicado)->delete();
            }
        }
    }
};
