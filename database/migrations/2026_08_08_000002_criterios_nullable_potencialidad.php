<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Potencialidad: 156 criterios a nullable.
 *
 * Aquí el problema no era hipotético. La validación no exigía nada, el
 * formulario preseleccionaba «0 - Nulo» y los campos ausentes entraban como 0,
 * así que un criterio que nadie miró bajaba la media de su grupo sin aviso.
 *
 * Los `val_*`, `fn_total` y `fx_total` NO se tocan aquí: la migración
 * original (2025_12_07_000001_create_evaluaciones_potencialidad_table.php)
 * ya los declaró `->nullable()` desde el principio —son resultados
 * calculados que no existen hasta que se computan—, y `val_*` usa
 * `decimal(6, 4)` mientras que los dos totales usan `decimal(8, 4)`.
 * Un `change()` con esos mismos tipos sería un no-op, y en SQLite `change()`
 * recrea la tabla entera: repetirlo sin necesidad es riesgo sin beneficio.
 * Se verificó columna por columna contra el esquema real que ninguna de las
 * 21 `val_*` ni los dos totales están hoy NOT NULL (ver informe de la tarea).
 */
return new class extends Migration
{
    public function up(): void
    {
        $criterios = array_values(array_filter(
            Schema::getColumnListing('evaluaciones_potencialidad'),
            fn (string $c) => ! str_starts_with($c, 'val_')
                && ! in_array($c, ['id', 'zona_id', 'user_id', 'estado', 'fn_total', 'fx_total', 'created_at', 'updated_at'], true)
        ));

        Schema::table('evaluaciones_potencialidad', function (Blueprint $t) use ($criterios) {
            foreach ($criterios as $columna) {
                $t->tinyInteger($columna)->nullable()->default(null)->change();
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migración irreversible: revertirla inventaría puntuaciones donde hay huecos.'
        );
    }
};
