<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A diferencia de MySQL, PostgreSQL —el motor de producción— no crea un índice
 * automáticamente al declarar una clave foránea. Sin ellos, cada dashboard y
 * cada listado hace un recorrido secuencial de la tabla, y los chequeos de
 * integridad al borrar también se degradan.
 *
 * No se incluyen las columnas zona_id de las tablas de evaluación porque su
 * restricción unique ya lleva índice asociado.
 */
return new class extends Migration
{
    private array $indices = [
        'provincias'          => ['region_id'],
        'lugares'             => ['provincia_id'],
        'zonas'               => ['lugar_id', 'jefe_user_id'],
        'zona_equipo'         => ['user_id'],
        'categorias_recurso'  => ['parent_id'],
        'inventarios'         => ['zona_id', 'categoria_id', 'propietario_id', 'creado_por_user_id'],
        'inventario_imagenes' => ['inventario_id'],
        'users'               => ['role_id'],
    ];

    public function up(): void
    {
        foreach ($this->indices as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $table) use ($columnas) {
                foreach ($columnas as $columna) {
                    $table->index($columna);
                }
            });
        }
    }

    public function down(): void
    {
        foreach ($this->indices as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla, $columnas) {
                foreach ($columnas as $columna) {
                    $table->dropIndex("{$tabla}_{$columna}_index");
                }
            });
        }
    }
};
