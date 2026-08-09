<?php

namespace App\Matrices;

use App\Models\EvaluacionConcentracion;
use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\EvaluacionIrritacion;
use App\Models\EvaluacionPaisaje;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPotencialidad;
use App\Models\EvaluacionValoracionTerritorial;
use App\Models\InvolucradosConfig;

/**
 * Única lista de matrices del sistema.
 *
 * Antes, «qué matrices existen» estaba repartido entre el dashboard operativo,
 * la tabla del admin y las rutas, sin nada que lo comprobara. El resultado fue
 * que la Matriz de Paisaje quedó sin enlace en el admin durante meses.
 *
 * Añadir una matriz nueva es una entrada aquí. RegistroMatricesTest recorre
 * este array y falla si algo no encaja.
 */
final class Registro
{
    /**
     * Fases del estudio, en el orden en que se recorren.
     *
     * El orden de recorrido lo da el orden de declaración de este array —lo
     * consume EstadoZona::grupos()—, no una clave 'orden': tenerla aquí habría
     * sido una segunda fuente de verdad que nadie leía.
     *
     * Los grupos 'social' y 'presion' ya están declarados aunque les falten
     * matrices: sus entradas llegan cuando se implementen Involucrados,
     * Concentración y Frecuentación.
     */
    public const GRUPOS = [
        'base'       => ['titulo' => 'Base territorial'],
        'vocacion'   => ['titulo' => 'Vocación turística'],
        'valoracion' => ['titulo' => 'Valoración del territorio'],
        'social'     => ['titulo' => 'Dimensión social'],
        'presion'    => ['titulo' => 'Presión y uso'],
    ];

    /**
     * tipo:
     *   'matriz'    — tiene estado borrador/confirmado y cuenta para el progreso
     *   'inventario'— CRUD de recursos, sin estado
     *   'resultado' — derivado de otras entradas, no se rellena
     *   'actores'   — lista variable de actores con estado; cuenta para el progreso
     */
    public const ENTRADAS = [
        'inventario' => [
            'nombre'     => 'Inventario de recursos',
            'icono'      => 'lista',
            'grupo'      => 'base',
            'tipo'       => 'inventario',
            'modelo'     => null,
            'criterios'  => null,
            'rutas'      => ['editar' => 'operativo.inventarios.index'],
            'depende_de' => [],
        ],

        'fit' => [
            'nombre'     => 'Factores intrínsecos (FIT)',
            'icono'      => 'flecha-abajo',
            'grupo'      => 'vocacion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionFit::class,
            'criterios'  => 18,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_fit.edit',
                'ver'    => 'operativo.evaluacion_fit.ponderacion',
            ],
            'depende_de' => [],
        ],

        'fet' => [
            'nombre'     => 'Factores extrínsecos (FET)',
            'icono'      => 'flecha-arriba',
            'grupo'      => 'vocacion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionFet::class,
            'criterios'  => 9,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_fet.edit',
                'ver'    => 'operativo.evaluacion_fet.ponderacion',
            ],
            'depende_de' => [],
        ],

        'vtt' => [
            'nombre'     => 'Vocación del territorio',
            'icono'      => 'diana',
            'grupo'      => 'vocacion',
            'tipo'       => 'resultado',
            'modelo'     => null,
            'criterios'  => null,
            'rutas'      => ['ver' => 'operativo.vtt.final'],
            'depende_de' => ['fit', 'fet'],
        ],

        'potencialidad' => [
            'nombre'     => 'Potencialidad turística',
            'icono'      => 'estrella',
            'grupo'      => 'valoracion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionPotencialidad::class,
            'criterios'  => 156,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_potencialidad.edit',
                'ver'    => 'operativo.evaluacion_potencialidad.ponderacion',
            ],
            'depende_de' => [],
        ],

        'paisaje' => [
            'nombre'     => 'Paisaje',
            'icono'      => 'montana',
            'grupo'      => 'valoracion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionPaisaje::class,
            'criterios'  => 34,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_paisaje.edit',
                'ver'    => 'operativo.evaluacion_paisaje.ponderacion',
            ],
            'depende_de' => [],
        ],

        'valoracion_territorial' => [
            'nombre'     => 'Valoración territorial',
            'icono'      => 'mapa',
            'grupo'      => 'valoracion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionValoracionTerritorial::class,
            'criterios'  => 21,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_valoracion_territorial.edit',
                'ver'    => 'operativo.evaluacion_valoracion_territorial.ponderacion',
            ],
            'depende_de' => [],
        ],

        'percepcion' => [
            'nombre'     => 'Percepción de la localidad',
            'icono'      => 'brujula',
            'grupo'      => 'social',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionPercepcion::class,
            'criterios'  => 16,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_percepcion.edit',
                'ver'    => 'operativo.evaluacion_percepcion.ponderacion',
            ],
            'depende_de' => [],
        ],

        'irritacion' => [
            'nombre'     => 'Irritación turística',
            'icono'      => 'enfado',
            'grupo'      => 'presion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionIrritacion::class,
            'criterios'  => 12,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_irritacion.edit',
                'ver'    => 'operativo.evaluacion_irritacion.ponderacion',
            ],
            'depende_de' => [],
        ],

        'concentracion' => [
            'nombre'     => 'Concentración turística',
            'icono'      => 'barras',
            'grupo'      => 'presion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionConcentracion::class,
            // 113: verificado como count(Concentracion::campos()) al escribir
            // esta entrada, no una estimación -es la matriz con más criterios
            // del sistema y un número copiado a mano es justo el riesgo que
            // Concentracion.php se generó desde el Excel para evitar-.
            'criterios'  => 113,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_concentracion.edit',
                'ver'    => 'operativo.evaluacion_concentracion.ponderacion',
            ],
            'depende_de' => [],
        ],

        'involucrados' => [
            'nombre'     => 'Involucrados turísticos',
            'icono'      => 'usuarios',
            'grupo'      => 'social',
            'tipo'       => 'actores',
            'modelo'     => InvolucradosConfig::class,
            'criterios'  => null,
            'rutas'      => [
                'editar' => 'operativo.involucrados.index',
                'ver'    => 'operativo.involucrados.resultados',
            ],
            'depende_de' => [],
        ],
    ];

    /** Entradas de un grupo, conservando sus claves y el orden de declaración. */
    public static function deGrupo(string $grupo): array
    {
        return array_filter(
            self::ENTRADAS,
            fn(array $entrada) => $entrada['grupo'] === $grupo
        );
    }

    /**
     * Tipos de entrada que se validan y por tanto cuentan para el progreso de
     * la zona. `inventario` no está porque no tiene estado, y `resultado`
     * porque es derivado: contarlos haría que el denominador mintiera.
     */
    public const TIPOS_VALIDABLES = ['matriz', 'actores'];

    /** Solo las entradas validables: las que cuentan para el progreso. */
    public static function matrices(): array
    {
        return array_filter(
            self::ENTRADAS,
            fn(array $entrada) => in_array($entrada['tipo'], self::TIPOS_VALIDABLES, true)
        );
    }
}
