<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estas claves foráneas se declararon sin acción de borrado, es decir con
 * RESTRICT. Eliminar a un usuario que hubiera creado un inventario o guardado
 * una evaluación lanzaba una QueryException sin capturar: pantalla 500.
 *
 * Se pasan a nullOnDelete, que conserva el dato de campo (que es lo valioso)
 * y solo pierde la autoría. evaluaciones_percepcion ya lo hacía así.
 *
 * zona_equipo.user_id se deja en cascade a propósito: una asignación a un
 * usuario que ya no existe no tiene sentido que sobreviva.
 */
return new class extends Migration
{
    private array $tablasConUserId = [
        'evaluaciones_fit',
        'evaluaciones_fet',
        'evaluaciones_potencialidad',
        'vocacion_turistica_territorio',
    ];

    public function up(): void
    {
        // creado_por_user_id era NOT NULL, así que primero debe admitir nulos.
        Schema::table('inventarios', function (Blueprint $table) {
            $table->unsignedBigInteger('creado_por_user_id')->nullable()->change();
        });

        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropForeign(['creado_por_user_id']);
            $table->foreign('creado_por_user_id')->references('id')->on('users')->nullOnDelete();
        });

        foreach ($this->tablasConUserId as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablasConUserId as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->foreign('user_id')->references('id')->on('users');
            });
        }

        Schema::table('inventarios', function (Blueprint $table) {
            $table->dropForeign(['creado_por_user_id']);
            $table->foreign('creado_por_user_id')->references('id')->on('users');
        });
    }
};
