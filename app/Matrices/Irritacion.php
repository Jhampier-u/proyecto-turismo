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
     * Los tres tramos de clasificación con su rango descriptivo. Antes vivía
     * duplicado en la vista de resultados; la leyenda del formulario necesita
     * exactamente lo mismo, así que sube aquí como conocimiento del
     * instrumento, igual que los umbrales de arriba.
     *
     * El rango es solo para el humano que lee la leyenda o la tabla: quien
     * decide de verdad la clasificación es clasificar(), con estos mismos
     * umbrales. Por eso se deriva de ESCALA_MIN/UMBRAL_MODERADO/
     * UMBRAL_CRITICO/ESCALA_MAX en vez de escribirse a mano: mover un umbral
     * sin tocar esto dejaría la leyenda mintiendo con la suite en verde.
     *
     * Sin color: el color de cada tramo es una decisión de presentación, no
     * vocabulario del instrumento, y vive en el componente Blade
     * <x-insignia-clasificacion>, no aquí. Esta clase no tiene ninguna clase
     * de Tailwind, así que app/Matrices ya no necesita estar en el content de
     * tailwind.config.js.
     *
     * @var array<string, array{rango: string}>
     */
    public const TRAMOS = [
        'Bajo'     => ['rango' => self::ESCALA_MIN . ' a ' . (self::UMBRAL_MODERADO - 1)],
        'Moderado' => ['rango' => self::UMBRAL_MODERADO . ' a ' . (self::UMBRAL_CRITICO - 1)],
        'Crítico'  => ['rango' => self::UMBRAL_CRITICO . ' a ' . self::ESCALA_MAX],
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
            'titulo'    => 'Percepción de los visitantes',
            'subtitulo' => 'Percepción de quien visita el destino.',
            'campos'    => self::VISITANTES,
        ],
        'residentes' => [
            'titulo'    => 'Percepción de la localidad receptora',
            'subtitulo' => 'Percepción de quien reside en el destino.',
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
     * El texto de interpretación literal del instrumento para cada bloque y
     * tramo. Vocabulario del cuestionario, igual que ETIQUETAS y TRAMOS: vive
     * aquí y no en la vista de resultados, que solo indexa
     * INTERPRETACIONES[$bloque][$clasificacion] con la clave que ya conoce.
     *
     * @var array<string, array<string, string>>
     */
    public const INTERPRETACIONES = [
        'visitantes' => [
            'Bajo'     => 'Los visitantes presentan un nivel de aceptación amplio hacia el destino y su dinámica turística.',
            'Moderado' => 'Los visitantes empiezan a expresar descontento por la dinámica turística que se desarrolla en el lugar.',
            'Crítico'  => 'Los visitantes se encuentran en un estado de insatisfacción con la dinámica turística del sitio.',
        ],
        'residentes' => [
            'Bajo'     => 'Los residentes presentan un nivel de aceptación amplio hacia el destino y su dinámica turística.',
            'Moderado' => 'Los residentes empiezan a expresar descontento por la dinámica turística que se desarrolla en el lugar.',
            'Crítico'  => 'Los residentes se encuentran en un estado de insatisfacción con la dinámica turística del sitio.',
        ],
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
