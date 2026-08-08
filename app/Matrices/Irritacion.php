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

    /**
     * Los tres tramos de clasificación con su rango descriptivo y su color.
     * Antes vivía duplicado en la vista de resultados; la leyenda del
     * formulario necesita exactamente lo mismo, así que sube aquí como
     * conocimiento del instrumento, igual que los umbrales de arriba.
     *
     * El rango es solo para el humano que lee la leyenda o la tabla: quien
     * decide de verdad la clasificación es clasificar(), con estos mismos
     * umbrales.
     *
     * Clases de Tailwind completas y no por concatenación: app/Matrices está
     * en el content de tailwind.config.js precisamente para que el purgado
     * las vea aquí y no solo en las vistas.
     *
     * @var array<string, array{rango: string, color: string}>
     */
    public const TRAMOS = [
        'Bajo'     => ['rango' => '0 a 2',  'color' => 'bg-green-100 text-green-800'],
        'Moderado' => ['rango' => '3 a 6',  'color' => 'bg-yellow-100 text-yellow-800'],
        'Crítico'  => ['rango' => '7 a 10', 'color' => 'bg-red-100 text-red-800'],
    ];

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

    /**
     * Los dos bloques del instrumento: clave, título de sección, bajada de
     * texto y los campos que lo componen. Antes vivía repartido en tres
     * sitios —el criterios() del controlador, los <h3> del formulario y el
     * array de la vista de resultados— así que un cambio de título había que
     * hacerlo tres veces y nada avisaba si se olvidaba una.
     *
     * @var array<string, array{titulo: string, subtitulo: string, campos: array<int, string>}>
     */
    public const BLOQUES = [
        'visitantes' => [
            'titulo'    => 'Encuesta a visitantes',
            'subtitulo' => 'Percepción de quien visita el destino.',
            'campos'    => self::VISITANTES,
        ],
        'residentes' => [
            'titulo'    => 'Encuesta a residentes',
            'subtitulo' => 'Percepción de la localidad receptora.',
            'campos'    => self::RESIDENTES,
        ],
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
