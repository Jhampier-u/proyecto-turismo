<?php

namespace App\Matrices;

/**
 * Matriz de Involucrados Turísticos Territoriales.
 *
 * A diferencia de las siete matrices anteriores, aquí no hay una lista fija
 * de criterios de la zona sino de actores: un municipio, una comunidad, una
 * operadora... Cada actor se puntúa con once criterios agrupados en tres
 * atributos —poder, legitimidad y urgencia—, en escala 0-3. Con esos tres
 * atributos convertidos a "lo tiene o no lo tiene" se clasifica al actor
 * según el modelo de stakeholder salience de Mitchell, Agle y Wood (1997).
 */
final class Involucrados
{
    public const ESCALA_MIN = 0;
    public const ESCALA_MAX = 3;

    /**
     * Los tres atributos del instrumento con su título y su mapa de campo a
     * etiqueta. Igual que BLOQUES en Irritacion o CRITERIOS en las matrices de
     * formulario, es la única fuente de verdad: de aquí salen tanto el
     * formulario como campos() y cualquier recorrido que necesite las once
     * etiquetas agrupadas.
     *
     * @var array<string, array{titulo: string, campos: array<string, string>}>
     */
    public const ATRIBUTOS = [
        'poder' => [
            'titulo' => 'Grado de poder',
            'campos' => [
                'pod_autoridad'     => 'Autoridad (medios coercitivos)',
                'pod_poder'         => 'Poder (medios coercitivos)',
                'pod_recursos'      => 'Recursos y atractivos',
                'pod_presupuesto'   => 'Presupuesto',
                'pod_tecnologicos'  => 'Medios tecnológicos',
                'pod_cadena_valor'  => 'Cadena de valor',
                'pod_intelectuales' => 'Medios intelectuales',
            ],
        ],
        'legitimidad' => [
            'titulo' => 'Grado de legitimidad',
            'campos' => [
                'leg_territorio' => 'Deseabilidad para el territorio',
                'leg_sociedad'   => 'Deseabilidad para la sociedad',
            ],
        ],
        'urgencia' => [
            'titulo' => 'Grado de urgencia',
            'campos' => [
                'urg_sensibilidad' => 'Sensibilidad temporal',
                'urg_criticidad'   => 'Criticidad',
            ],
        ],
    ];

    /**
     * Los once nombres de campo en orden, aplanando ATRIBUTOS. Es lo que usan
     * $fillable del modelo y cualquier construcción de datos de prueba: pedir
     * "todos los criterios" no debería tener que conocer los tres grupos.
     *
     * @return array<int, string>
     */
    public static function campos(): array
    {
        // array_values() antes del spread: ATRIBUTOS tiene claves de cadena
        // ('poder', 'legitimidad', 'urgencia') y array_map() las conserva, así
        // que sin esto el "..." las pasaría a array_merge() como argumentos
        // con nombre —cosa que esa función no acepta— en vez de como una
        // lista posicional de arrays a fusionar.
        return array_merge(...array_values(array_map(
            fn(array $atributo) => array_keys($atributo['campos']),
            self::ATRIBUTOS
        )));
    }

    /**
     * Clasifica un actor según el modelo de Mitchell, Agle y Wood (1997) a
     * partir de sus tres atributos ya reducidos a booleano ("lo tiene o no").
     *
     * OJO CON LA ERRATA DEL INSTRUMENTO: la tabla original del formulario
     * llama "Exigentes" al tipo que solo tiene legitimidad y "Discrecionales"
     * al que solo tiene urgencia, que es justo al revés de como Mitchell,
     * Agle y Wood definen esos dos nombres en el paper: el stakeholder
     * "discretionary" es el que solo tiene legitimidad (no hay poder que lo
     * obligue a nada ni urgencia que apremie) y el "demanding" es el que solo
     * tiene urgencia (reclama sin poder ni legitimidad para respaldarlo). Esta
     * función sigue la fuente académica, no la hoja del instrumento: si a
     * alguien "no le cuadra" y quiere intercambiar las dos ramas de vuelta a
     * como está en la hoja, que lea este comentario primero y confirme que
     * de verdad quiere alejarse de Mitchell et al. antes de tocarlo.
     */
    public static function tipoDe(bool $poder, bool $legitimidad, bool $urgencia): string
    {
        return match (true) {
            $poder && $legitimidad && $urgencia    => 'Definitivo',
            $poder && $urgencia                    => 'Peligroso',
            $poder && $legitimidad                 => 'Dominante',
            $legitimidad && $urgencia              => 'Dependiente',
            $poder                                 => 'Adormecido',
            $legitimidad                           => 'Discrecional',
            $urgencia                              => 'Exigente',
            default                                => 'No es actor relevante',
        };
    }
}
