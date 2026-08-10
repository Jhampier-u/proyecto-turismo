<?php

namespace App\Matrices;

/**
 * Criterios de la Matriz de Factores Extrínsecos Territoriales (FET).
 *
 * Misma situación que App\Matrices\Fit -ver su docblock-: los 9 criterios
 * solo existían como dimensión => [campos] en
 * EvaluacionFetController::criterios(), sin ningún texto, y los nombres que
 * ve el evaluador eran literales sueltos en evaluacion_fet/form.blade.php.
 *
 * Mismos 4 niveles genéricos que FIT: Nulo/Bajo/Medio/Alto, iguales para
 * los 9 criterios.
 */
class Fet
{
    public const ESCALA_MIN = 0;
    public const ESCALA_MAX = 3;

    /** Etiquetas de los 4 niveles, iguales para los 9 criterios. */
    public const NIVELES = [
        0 => 'Nulo',
        1 => 'Bajo',
        2 => 'Medio',
        3 => 'Alto',
    ];

    /**
     * Bloque => nombre, peso y criterios. Ver App\Matrices\Fit::BLOQUES
     * sobre por qué el peso vive aquí y no en una constante aparte.
     *
     * @var array<string, array>
     */
    public const BLOQUES = [
        'demanda' => [
            'nombre' => 'Demanda Turística',
            'peso'   => 0.20,
            'criterios' => [
                'demanda_flujos'  => ['nombre' => 'Flujos Turísticos', 'niveles' => self::NIVELES],
                'demanda_estadia' => ['nombre' => 'Estadía Promedio', 'niveles' => self::NIVELES],
            ],
        ],
        'super' => [
            'nombre' => 'Superestructura',
            'peso'   => 0.40,
            'criterios' => [
                'super_institucionalidad' => ['nombre' => 'Institucionalidad Turística', 'niveles' => self::NIVELES],
                'super_organizacion'      => ['nombre' => 'Organización Comunitaria en Turismo', 'niveles' => self::NIVELES],
                'super_planificacion'     => ['nombre' => 'Planificación Turística', 'niveles' => self::NIVELES],
            ],
        ],
        'imagen' => [
            'nombre' => 'Imagen del Sitio',
            'peso'   => 0.40,
            'criterios' => [
                'imagen_apertura'   => ['nombre' => 'Grado de Apertura de la Comunidad', 'niveles' => self::NIVELES],
                'imagen_seguridad'  => ['nombre' => 'Seguridad del Destino', 'niveles' => self::NIVELES],
                'imagen_percibida'  => ['nombre' => 'Imagen Percibida del Visitante', 'niveles' => self::NIVELES],
                'imagen_marketing'  => ['nombre' => 'Marketing Turístico', 'niveles' => self::NIVELES],
            ],
        ],
    ];

    /** @return array<string, array> los 9 criterios, aplanados */
    public static function todos(): array
    {
        $criterios = [];

        foreach (self::BLOQUES as $bloque) {
            $criterios += $bloque['criterios'];
        }

        return $criterios;
    }
}
