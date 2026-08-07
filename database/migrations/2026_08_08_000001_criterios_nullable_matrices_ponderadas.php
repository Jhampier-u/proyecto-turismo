<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite guardar una matriz a medias.
 *
 * Las columnas estaban NOT NULL con defecto 0, y 0 es una puntuación con
 * significado —«Afectado», «Desfavorable»—. Sin nullable, un criterio sin
 * responder puntuaría como lo peor. Por eso esto no es cosmética: es lo que
 * impide que «no contestado» se confunda con «pésimo».
 *
 * Las columnas de FIT/FET se escriben literalmente en vez de derivarlas de
 * las clases de App\Matrices: una migración es un registro histórico
 * congelado, y regenerar esas clases no debe cambiar lo que hizo una
 * migración ya aplicada.
 *
 * Es un ensanchamiento: las filas existentes tienen valor en todas las
 * columnas, así que no hay datos que rellenar ni riesgo de pérdida.
 */
return new class extends Migration
{
    /** tabla => columnas de criterio */
    private function objetivos(): array
    {
        return [
            'evaluaciones_fit' => [
                'recursos_culturales', 'recursos_naturales',
                'atractivos_manifestaciones', 'atractivos_sitios',
                'prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza',
                'productos_territoriales',
                'infraestructura_basica', 'infraestructura_apoyo',
                'facilidades_senaletica', 'facilidades_recepcion',
                'facilidades_interpretacion', 'facilidades_senderos',
                'facilidades_estacionamientos', 'facilidades_campamentos',
                'facilidades_miradores', 'facilidades_sanitarios',
            ],
            'evaluaciones_fet' => [
                'demanda_flujos', 'demanda_estadia',
                'super_institucionalidad', 'super_organizacion', 'super_planificacion',
                'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing',
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->objetivos() as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $t) use ($columnas) {
                foreach ($columnas as $columna) {
                    $t->tinyInteger($columna)->nullable()->default(null)->change();
                }
            });
        }

        // Percepción, Paisaje y Valoración Territorial declaran sus criterios en
        // clases generadas; aquí se leen los nombres de columna del esquema real
        // para no repetir 71 nombres a mano y arriesgar una errata silenciosa.
        foreach ($this->tablasGeneradas() as $tabla => $prefijos) {
            $columnas = array_values(array_filter(
                Schema::getColumnListing($tabla),
                fn (string $c) => $this->esCriterio($c, $prefijos)
            ));

            Schema::table($tabla, function (Blueprint $t) use ($columnas) {
                foreach ($columnas as $columna) {
                    $t->tinyInteger($columna)->nullable()->default(null)->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Volver a NOT NULL exigiría inventar un valor para las filas con nulos,
        // y ese valor sería una puntuación falsa. Se deja irreversible a
        // propósito: revertir esto es restaurar una copia, no correr un down().
        throw new RuntimeException(
            'Migración irreversible: revertirla inventaría puntuaciones donde hay huecos.'
        );
    }

    /** tabla => prefijos de columna que son criterios */
    private function tablasGeneradas(): array
    {
        return [
            'evaluaciones_percepcion'              => ['ds', 'pl', 'pe', 'no'],
            'evaluaciones_paisaje'                 => ['ep', 'pn', 'pc', 'iv', 'cp'],
            'evaluaciones_valoracion_territorial'  => ['ct', 'uc'],
        ];
    }

    /**
     * Un criterio empieza por un prefijo de categoría y NO es una columna
     * calculada: los promedios y totales conservan su tipo decimal.
     *
     * El prefijo va seguido de un dígito opcional antes del guión bajo: en
     * evaluaciones_percepcion los criterios se numeran dentro de su categoría
     * (ds1_, ds2_, ds3_, pl1_ … pl6_, etc.), mientras que en paisaje y
     * valoración territorial el guión bajo va pegado al prefijo (ep_, ct_).
     * Un match literal de "prefijo_" deja fuera los 16 criterios de
     * percepción sin avisar — se verificó contra el esquema real que este
     * patrón da exactamente 16/34/21 columnas en las tres tablas y que
     * ninguna de las capturadas es ya decimal o float (ver informe de la
     * tarea).
     */
    private function esCriterio(string $columna, array $prefijos): bool
    {
        if (str_ends_with($columna, '_promedio') || str_ends_with($columna, '_total')) {
            return false;
        }

        foreach ($prefijos as $prefijo) {
            if (preg_match('/^'.$prefijo.'\d*_/', $columna) === 1) {
                return true;
            }
        }

        return false;
    }
};
