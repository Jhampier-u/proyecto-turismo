<?php

namespace App\Matrices;

/**
 * Criterios de la Matriz de Percepción de la Localidad hacia el Turismo.
 *
 * Antes de esta migración, los 16 criterios vivían en
 * EvaluacionPercepcionController::$categorias -a diferencia de Paisaje,
 * Irritación o Fit/Fet, que ya tenían su clase en App\Matrices-. No era el
 * mismo problema que resolvió la rama fit-fet-componentes: Percepción ya daba
 * su etiqueta en los mensajes de validación desde antes, porque
 * MatrizPonderadaController::etiquetas() ya leía esa propiedad del
 * controlador. Se mueve aquí por consistencia con el resto de matrices -el
 * formulario necesita recorrer los criterios sin tenerlos tecleados en el
 * Blade- y para que el modelo, el controlador y la vista beban todos de la
 * misma fuente, en vez de que el controlador sea a la vez la fuente y uno de
 * sus consumidores.
 *
 * Escala: 1 (Negativo), 2 (Neutral), 3 (Positivo), igual para los 16
 * criterios. Se comprobó cada atributo contra el instrumento original
 * (Documentación/IMPLEMENTADA MATRIZ PERCEPCIÓN DE LA LOCALIDAD.xlsx) antes
 * de asumir que "más alto es mejor" -que es lo que dan por hecho
 * <x-criterio-pildoras> y <x-franja-matriz>, coloreando por posición- valía
 * también para los atributos que suenan negativos, como "Presencia de
 * conflictos entre actores y grupos sociales" (NO4). La hoja original fija
 * Positivo=3/Neutral=2/Negativo=1 como una escala ÚNICA para los 16, no una
 * cantidad por atributo: no pregunta "cuántos conflictos hay", pregunta si
 * esa situación es favorable o no para la localidad. Bajo esa lectura, 3 es
 * siempre el mejor valor posible en los 16 -incluido NO4-, así que la
 * dirección es consistente y no hay ningún criterio formulado al revés.
 */
class Percepcion
{
    public const ESCALA_MIN = 1;
    public const ESCALA_MAX = 3;

    /** Etiquetas de los 3 niveles, iguales para los 16 criterios. */
    public const NIVELES = [
        1 => 'Negativo',
        2 => 'Neutral',
        3 => 'Positivo',
    ];

    /**
     * Categoría => nombre, peso e items (campo => etiqueta).
     *
     * Propiedad estática, no `const` como Fit::BLOQUES, Paisaje::CATEGORIAS
     * o Irritacion::ETIQUETAS: a propósito, para no perder la única prueba de
     * mutación real de la suite.
     * MensajesValidacionTest::test_la_etiqueta_de_percepcion_sale_del_instrumento_y_no_de_una_copia()
     * reasigna una etiqueta en caliente y comprueba que el mensaje de error
     * cambia con ella; PHP no permite reasignar una class constant ni por
     * Reflection, así que convertir esto en `const` habría obligado a
     * degradar ese test a "leer el valor vigente", como las demás matrices,
     * perdiendo la única cobertura que detecta una copia en vez de una
     * lectura dinámica.
     *
     * @var array<string, array{nombre: string, peso: float, items: array<string, string>}>
     */
    public static array $categorias = [
        'DS' => [
            'nombre' => 'Dimensión Social',
            'peso'   => 0.20,
            'items'  => [
                'ds1_posicion_turistica'  => 'Posición frente a la actividad turística en el lugar',
                'ds2_interes_participar'  => 'Grado de interés por participar en el desarrollo de actividades turísticas',
                'ds3_contribucion_social' => 'Contribución del turismo en los procesos sociales de la localidad',
            ],
        ],
        'PL' => [
            'nombre' => 'Percepción Local',
            'peso'   => 0.40,
            'items'  => [
                'pl1_conoc_recursos'         => 'Conocimiento de la existencia de recursos turísticos en el territorio',
                'pl2_conoc_atractivos'       => 'Conocimiento de la existencia de atractivos turísticos en el territorio',
                'pl3_conoc_motivo_visita'    => 'Conocimiento del motivo de visita hacia el territorio',
                'pl4_conoc_flujo_visitantes' => 'Conocimiento del flujo de visitantes en la comunidad',
                'pl5_sentimiento_visitantes' => 'Percepción o sentimiento causado por la presencia de visitantes',
                'pl6_necesidad_visitantes'   => 'Opinión sobre la necesidad de la llegada de visitantes al lugar',
            ],
        ],
        'PE' => [
            'nombre' => 'Percepción Económica',
            'peso'   => 0.20,
            'items'  => [
                'pe1_incidencia_ingresos'   => 'Incidencia de los ingresos económicos que perciben por su actividad actual',
                'pe2_beneficios_esperados'  => 'Percepción de los beneficios que pueden esperarse por efectos del turismo',
                'pe3_disposicion_inversion' => 'Grado de disposición de la localidad para invertir en actividades turísticas',
            ],
        ],
        'NO' => [
            'nombre' => 'Nivel de Organización',
            'peso'   => 0.20,
            'items'  => [
                'no1_organizacion_colectiva' => 'Nivel de organización colectiva entorno a objetivos comunes',
                'no2_lideres_sociales'       => 'Presencia de líderes sociales al interior de la comunidad',
                'no3_opinion_lideres'        => 'Opinión del trabajo realizado por los líderes',
                'no4_conflictos_sociales'    => 'Presencia de conflictos entre actores y grupos sociales',
            ],
        ],
    ];

    /** @return array<string, string> los 16 criterios, aplanados: campo => etiqueta */
    public static function todos(): array
    {
        $criterios = [];

        foreach (self::$categorias as $cat) {
            $criterios += $cat['items'];
        }

        return $criterios;
    }
}
