<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Todo el código trata estas tablas como 1:1 con la zona (firstOrNew,
 * updateOrCreate, where(...)->first()), pero la base de datos no lo garantizaba.
 * Dos peticiones concurrentes creaban filas duplicadas y, a partir de ahí,
 * los resultados mostrados dependían de cuál devolviera el first().
 *
 * vocacion_turistica_territorio y evaluaciones_percepcion ya tenían el unique.
 */
return new class extends Migration
{
    private array $tablas = [
        'evaluaciones_fit',
        'evaluaciones_fet',
        'evaluaciones_potencialidad',
        'potencialidad_campos_activos',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            $this->eliminarDuplicados($tabla);

            Schema::table($tabla, function (Blueprint $table) {
                $table->unique('zona_id');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            Schema::table($tabla, function (Blueprint $table) use ($tabla) {
                $table->dropUnique($tabla . '_zona_id_unique');
            });
        }
    }

    /**
     * Conserva la fila de mayor id por zona (la más reciente) y borra el resto,
     * porque el unique fallaría si ya existen duplicados en producción.
     */
    private function eliminarDuplicados(string $tabla): void
    {
        $zonasDuplicadas = DB::table($tabla)
            ->select('zona_id')
            ->groupBy('zona_id')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('zona_id');

        foreach ($zonasDuplicadas as $zonaId) {
            $conservar = DB::table($tabla)
                ->where('zona_id', $zonaId)
                ->orderByDesc('id')
                ->value('id');

            DB::table($tabla)
                ->where('zona_id', $zonaId)
                ->where('id', '!=', $conservar)
                ->delete();
        }
    }
};
