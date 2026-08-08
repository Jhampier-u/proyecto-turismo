<?php

namespace App\Matrices;

/**
 * Índice de Irritación Turística.
 *
 * Mide cuánto molesta el turismo preguntando por separado a visitantes y a
 * residentes, seis atributos por bloque en escala 0-10. A diferencia del
 * resto de matrices del sistema, su escala es INVERSA: 0 es el mejor
 * resultado posible y 10 es irritación crítica.
 */
final class Irritacion
{
    public const ESCALA_MIN = 0;
    public const ESCALA_MAX = 10;

    /**
     * Umbrales del instrumento. Se aplican igual a un atributo suelto que al
     * promedio de un bloque: es la misma clasificar() para los dos casos.
     */
    public const UMBRAL_CRITICO  = 7;
    public const UMBRAL_MODERADO = 3;

    /** Bloque de visitantes — 6 atributos. */
    public const VISITANTES = [
        'vis_congestion',
        'vis_calidad_servicios',
        'vis_calidad_actividades',
        'vis_calidad_vida',
        'vis_apertura',
        'vis_seguridad',
    ];

    /** Bloque de la localidad receptora — 6 atributos. */
    public const RESIDENTES = [
        'res_congestion',
        'res_impacto_social',
        'res_impacto_economico',
        'res_impacto_ambiental',
        'res_calidad_vida',
        'res_seguridad',
    ];

    /** @var array<string, string> campo => etiqueta con la sigla del instrumento */
    public const ETIQUETAS = [
        'vis_congestion'          => 'Cg · Congestión de visitantes en el destino',
        'vis_calidad_servicios'   => 'Cs · Calidad percibida de servicios y productos',
        'vis_calidad_actividades' => 'Ca · Calidad percibida de actividades turísticas',
        'vis_calidad_vida'        => 'Cv · Calidad de vida de la localidad percibida por el visitante',
        'vis_apertura'            => 'Ga · Grado de apertura u hospitalidad de la localidad',
        'vis_seguridad'           => 'Sd · Seguridad percibida en el destino',

        'res_congestion'          => 'Cg · Congestión de visitantes en el destino',
        'res_impacto_social'      => 'Is · Impacto social percibido',
        'res_impacto_economico'   => 'Ie · Impacto económico percibido',
        'res_impacto_ambiental'   => 'Ia · Impacto ambiental percibido',
        'res_calidad_vida'        => 'Cv · Calidad de vida percibida por el residente',
        'res_seguridad'           => 'Sd · Seguridad percibida en el destino',
    ];

    /**
     * Clasifica un valor de la escala, sea un atributo suelto o el promedio de
     * un bloque: el instrumento aplica los mismos umbrales a los dos.
     *
     * Asume un valor ya validado en el rango ESCALA_MIN..ESCALA_MAX: es
     * pública y estática, y la consumen tanto el modelo como una vista, así
     * que no repite aquí una validación que ya hicieron sus llamantes.
     */
    public static function clasificar(?float $valor): ?string
    {
        return match (true) {
            $valor === null                 => null,
            $valor >= self::UMBRAL_CRITICO  => 'Crítico',
            $valor >= self::UMBRAL_MODERADO => 'Moderado',
            default                         => 'Bajo',
        };
    }
}
