<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Promedios y totales de Paisaje, Percepción y Valoración Territorial a nullable.
 *
 * 2026_08_08_000001 dejó fuera a propósito las columnas "_promedio"/"_total"
 * de estas tres tablas: entonces aún no existía la lógica de la Task 3 que
 * las deja en null mientras falten criterios por responder. Con
 * columnasCalculadasVacias() ya escribiendo null ahí cuando la matriz está a
 * medias, el NOT NULL heredado de la migración original revienta el guardado
 * de cualquier borrador incompleto: exactamente el caso que esta tarea existe
 * para resolver.
 *
 * FIT, FET y Potencialidad no aparecen aquí porque sus columnas calculadas ya
 * eran nullable desde su migración de creación (Potencialidad se verificó
 * explícitamente en 2026_08_08_000002).
 */
return new class extends Migration
{
    /** tabla => columnas calculadas (promedios y totales) */
    private function objetivos(): array
    {
        return [
            'evaluaciones_paisaje' => [
                'ep_promedio', 'pn_promedio', 'pc_promedio', 'iv_promedio', 'cp_promedio',
                'paisaje_total',
            ],
            'evaluaciones_percepcion' => [
                'media_ds', 'pond_ds', 'media_pl', 'pond_pl',
                'media_pe', 'pond_pe', 'media_no', 'pond_no',
                'percepcion_total',
            ],
            'evaluaciones_valoracion_territorial' => [
                'ct_total', 'uc_total',
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->objetivos() as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $t) use ($columnas) {
                foreach ($columnas as $columna) {
                    // Mismo decimal(5, 3) que la migración de creación de cada
                    // tabla: un change() con otro tipo recrearía la columna.
                    $t->decimal($columna, 5, 3)->nullable()->default(null)->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Igual que 2026_08_08_000001 y _000002: revertir exigiría inventar un
        // resultado donde hoy hay un hueco legítimo.
        throw new RuntimeException(
            'Migración irreversible: revertirla inventaría resultados donde hay huecos.'
        );
    }
};
