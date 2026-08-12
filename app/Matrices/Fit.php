<?php

namespace App\Matrices;

/**
 * Criterios de la Matriz de Factores Intrínsecos Territoriales (FIT).
 *
 * Antes de esta migración, los 18 criterios solo existían como
 * dimensión => [campos] en EvaluacionFitController::criterios(), sin ningún
 * texto: los nombres que ve el evaluador -«Culturales», «Señalética»...-
 * eran literales sueltos en evaluacion_fit/form.blade.php, tecleados a mano.
 * Eso dejó a FIT sin etiqueta en sus mensajes de validación -a diferencia de
 * las otras matrices ponderadas- porque no había ninguna clase PHP de la que
 * derivarla sin copiarla. Ver docs/ESTADO-PROYECTO.md, rama
 * mensajes-validacion.
 *
 * A diferencia de Paisaje o Valoración Territorial, los 4 niveles son
 * genéricos e iguales para los 18 criterios (no hay una descripción propia
 * por criterio): es una escala Nulo/Bajo/Medio/Alto aplicada por igual a
 * recursos, prestadores, infraestructura y facilidades.
 */
class Fit
{
    public const ESCALA_MIN = 0;
    public const ESCALA_MAX = 3;

    /** Etiquetas de los 4 niveles, iguales para los 18 criterios. */
    public const NIVELES = [
        0 => 'Nulo',
        1 => 'Bajo',
        2 => 'Medio',
        3 => 'Alto',
    ];

    /**
     * Bloque => nombre, peso y criterios. El peso vive aquí y no en una
     * constante aparte del controlador: es la misma razón que llevó a
     * App\Matrices\Paisaje a guardar 'peso' junto a cada categoría, en vez
     * de una segunda lista que se puede desincronizar de la primera.
     *
     * @var array<string, array>
     */
    public const BLOQUES = [
        'rtt' => [
            'nombre' => 'Recursos Turísticos Territoriales',
            'peso'   => 0.30,
            'criterios' => [
                'recursos_culturales' => ['nombre' => 'Culturales', 'niveles' => self::NIVELES],
                'recursos_naturales'  => ['nombre' => 'Naturales', 'niveles' => self::NIVELES],
            ],
        ],
        'at' => [
            'nombre' => 'Atractivos Turísticos',
            'peso'   => 0.05,
            'criterios' => [
                'atractivos_manifestaciones' => ['nombre' => 'Manifestaciones Culturales', 'niveles' => self::NIVELES],
                'atractivos_sitios'          => ['nombre' => 'Sitios Naturales', 'niveles' => self::NIVELES],
            ],
        ],
        'pst' => [
            'nombre' => 'Prestadores de Servicios',
            'peso'   => 0.20,
            'criterios' => [
                'prestadores_alojamiento'  => ['nombre' => 'Alojamiento', 'niveles' => self::NIVELES],
                'prestadores_restauracion' => ['nombre' => 'Restauración', 'niveles' => self::NIVELES],
                'prestadores_guianza'      => ['nombre' => 'Guianza', 'niveles' => self::NIVELES],
            ],
        ],
        'ptt' => [
            'nombre' => 'Producto Turístico',
            'peso'   => 0.05,
            'criterios' => [
                'productos_territoriales' => ['nombre' => 'Productos Turísticos Territoriales', 'niveles' => self::NIVELES],
            ],
        ],
        'i' => [
            'nombre' => 'Infraestructura',
            'peso'   => 0.20,
            'criterios' => [
                'infraestructura_basica' => ['nombre' => 'Infraestructura Básica', 'niveles' => self::NIVELES],
                'infraestructura_apoyo'  => ['nombre' => 'Infraestructura de Apoyo', 'niveles' => self::NIVELES],
            ],
        ],
        'ft' => [
            'nombre' => 'Facilidades Turísticas',
            'peso'   => 0.20,
            'criterios' => [
                'facilidades_senaletica'       => ['nombre' => 'Señalética', 'niveles' => self::NIVELES],
                'facilidades_recepcion'        => ['nombre' => 'Recepción Visitantes', 'niveles' => self::NIVELES],
                'facilidades_interpretacion'   => ['nombre' => 'Centros Interpretación', 'niveles' => self::NIVELES],
                'facilidades_senderos'         => ['nombre' => 'Senderos', 'niveles' => self::NIVELES],
                'facilidades_estacionamientos' => ['nombre' => 'Estacionamientos', 'niveles' => self::NIVELES],
                'facilidades_campamentos'      => ['nombre' => 'Campamentos', 'niveles' => self::NIVELES],
                'facilidades_miradores'        => ['nombre' => 'Miradores', 'niveles' => self::NIVELES],
                'facilidades_sanitarios'       => ['nombre' => 'Baterías Sanitarias', 'niveles' => self::NIVELES],
            ],
        ],
    ];

    /** @return array<string, array> los 18 criterios, aplanados */
    public static function todos(): array
    {
        $criterios = [];

        foreach (self::BLOQUES as $bloque) {
            $criterios += $bloque['criterios'];
        }

        return $criterios;
    }
}
