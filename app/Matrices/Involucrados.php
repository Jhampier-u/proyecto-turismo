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
        // El instrumento separa los siete criterios de poder en dos familias
        // de medios: coercitivos (autoridad, poder) y utilitarios (los otros
        // cinco). El calificador va en los siete y no en dos sueltos: dejarlo
        // a medias es peor que no ponerlo, porque sugiere que solo esos dos
        // tienen una naturaleza y los otros cinco no tienen ninguna.
        'poder' => [
            'titulo' => 'Grado de poder',
            'campos' => [
                'pod_autoridad'     => 'Autoridad (medios coercitivos)',
                'pod_poder'         => 'Poder (medios coercitivos)',
                'pod_recursos'      => 'Recursos y atractivos (medios utilitarios)',
                'pod_presupuesto'   => 'Presupuesto (medios utilitarios)',
                'pod_tecnologicos'  => 'Medios tecnológicos (medios utilitarios)',
                'pod_cadena_valor'  => 'Cadena de valor (medios utilitarios)',
                'pod_intelectuales' => 'Medios intelectuales (medios utilitarios)',
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
     * Vocabulario de la escala 0-3, común a poder y legitimidad.
     *
     * "0" no es "el peor valor de la escala": es "no lo posee", una
     * valoración cualitativa. Se define aquí y no en el componente de
     * formulario ni en la vista porque es conocimiento del instrumento —lo
     * mismo que ATRIBUTOS o tipoDe()—, no de cómo se pinta.
     *
     * @var array<int, string>
     */
    private const ETIQUETAS_ESCALA_COMUN = ['No lo posee', 'Poca', 'Media', 'Máxima'];

    /**
     * Excepciones al vocabulario común: el instrumento nombra la escala de
     * urgencia con sus propias palabras en cada uno de sus dos campos, no
     * "no posee / poca / media / máxima". El valor 0-3 es el mismo en los
     * tres atributos; cambia solo cómo se nombra, y quien rellena la ficha
     * reconoce estas palabras de su hoja.
     *
     * @var array<string, array<int, string>>
     */
    private const ETIQUETAS_ESCALA = [
        'urg_sensibilidad' => ['Nada sensible', 'Poco sensible', 'Sensible', 'Alta sensibilidad'],
        'urg_criticidad'   => ['Nada crítico', 'Baja criticidad', 'Media criticidad', 'Alta criticidad'],
    ];

    /**
     * Las cuatro etiquetas (0..3) de la escala para un campo concreto.
     *
     * @return array<int, string>
     */
    public static function etiquetasEscala(string $campo): array
    {
        return self::ETIQUETAS_ESCALA[$campo] ?? self::ETIQUETAS_ESCALA_COMUN;
    }

    /**
     * Los once nombres de campo en orden, aplanando ATRIBUTOS. Es lo que usan
     * $fillable del modelo y cualquier construcción de datos de prueba: pedir
     * "todos los criterios" no debería tener que conocer los tres grupos.
     *
     * @return array<int, string>
     */
    public static function campos(): array
    {
        // array_values(): ATRIBUTOS tiene claves de cadena, y array_merge()
        // no acepta argumentos con nombre.
        return array_merge(...array_map(
            fn(array $atributo) => array_keys($atributo['campos']),
            array_values(self::ATRIBUTOS)
        ));
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

    /**
     * Valida que un array de grados esté completo, sin huecos (null). Se
     * comprueba de forma explícita al principio de normalizar() y de
     * esAtributoDegenerado(), en vez de confiar en que un hueco reviente solo
     * más adelante en la aritmética: tanto array_sum() como
     * Collection::sum() reducen con "+", y "0 + null" da 0 sin ningún aviso
     * en PHP. Eso significa que un atributo con TODOS sus grados en null
     * —todos los actores a medias en ese atributo— cae exactamente en la
     * misma rama que "todos puntúan 0 de verdad" y, sin esta validación,
     * devolvería 1.0 en silencio para un conjunto que en realidad nunca
     * estuvo completo.
     *
     * @param array<int, mixed> $grados
     */
    private static function validarGradosCompletos(array $grados): void
    {
        foreach ($grados as $grado) {
            if (! is_int($grado)) {
                throw new \InvalidArgumentException(
                    'normalizar() y esAtributoDegenerado() exigen una lista de grados completa, sin huecos (null): normalizar un conjunto a medias no tiene ningún significado.'
                );
            }
        }
    }

    /**
     * Normaliza un array de grados de UN atributo (poder, legitimidad o
     * urgencia) frente al conjunto: (grado_i / suma) × n. Aritmética pura
     * sobre escalares —sin Eloquent, sin conocer el modelo Involucrado—, en
     * la línea de tipoDe(): quien reúne los grados de los actores de una zona
     * en este array es el controlador (ver
     * InvolucradosController::relevanciasDe()), igual que
     * EvaluacionFitController::calcular() recibe un array de valores en vez
     * de un modelo o una colección.
     *
     * DELIBERADO — no es un fallo que alguien deba "corregir": el resultado
     * de una posición depende, en general, de TODAS las demás, porque se
     * divide por la suma del conjunto. Dar de alta o de baja un actor
     * recalcula los normalizados de los que ya estaban, aunque sus propios
     * criterios no cambien un ápice —con una excepción trivial y no un caso
     * especial de la regla: un grado exactamente en 0 normaliza SIEMPRE a
     * 0.0 mientras la suma no sea 0 (0/suma×n es 0 para cualquier suma), así
     * que esa posición concreta no cambia porque ya estaba fija en 0.0 antes
     * de tocar el conjunto—. Decidido así con el responsable del proyecto:
     * comparar el normalizado de un actor entre dos listados distintos no
     * tiene sentido, solo lo tiene dentro del mismo conjunto. Ver el test
     * homónimo, que fija esta propiedad a propósito para que nadie la
     * "corrija" dentro de seis meses pensando que debería ser estable entre
     * altas.
     *
     * SUMA CERO → 1.0, Y POR QUÉ NO ES "SALVAR LA RELEVANCIA DEL ACTOR":
     * "Ninguno puntúa nada en este atributo" (los siete campos de poder en 0
     * para todos los actores, por ejemplo) es un caso real del instrumento,
     * y ahí la suma da 0. La justificación de devolver 1.0 NO es evitar que
     * un actor pierda su relevancia: esa pérdida ya ocurre todo el tiempo, de
     * forma perfectamente normal, cuando SOLO ESE actor puntúa 0 mientras
     * otros no —legitimidad y urgencia tienen solo dos criterios cada una,
     * así que un 0 aislado es corriente, y su normalizado 0.00 es
     * exactamente lo que debe salir, no un caso a evitar—. Lo distinto en la
     * suma-cero es que TODOS puntúan 0: el atributo no aporta ninguna
     * información para diferenciar a nadie del conjunto, así que 1.0 —el
     * neutro del producto de la relevancia— dice "este atributo no
     * interviene, que decidan los otros dos". Es también la continuación
     * algebraica de la fórmula: para cualquier suma > 0 la media de los
     * normalizados del conjunto da siempre exactamente 1 (la media de
     * (grado_i/suma)×n es n×(suma/suma)/n = 1), y con todos los grados
     * empatados en 0 esa media, en el límite, sigue siendo 1.
     *
     * OJO EN PANTALLA: un 1.0 aquí es indistinguible de "este actor está
     * justo en la media", que es un valor real y corriente. Quien pinte este
     * número no debe hacerlo con un 1.00 desnudo sin comprobar antes
     * esAtributoDegenerado(): ver InvolucradosController::relevanciasDe() y
     * la vista de resultados, que pintan "—" con una nota en vez del número
     * cuando el atributo resultó degenerado.
     *
     * @param array<int, int> $grados
     * @return array<int, float>
     */
    public static function normalizar(array $grados): array
    {
        self::validarGradosCompletos($grados);

        $n    = count($grados);
        $suma = array_sum($grados);

        if ($suma === 0) {
            return array_fill(0, $n, 1.0);
        }

        return array_map(fn(int $grado) => ($grado / $suma) * $n, $grados);
    }

    /**
     * Un atributo es degenerado cuando su normalizado no diferencia a NINGÚN
     * actor del conjunto:
     *
     * - Porque todos puntúan 0 (la suma da 0, ver normalizar()), o
     * - Porque solo hay un actor: con uno solo, grado/grado siempre da 1 (y
     *   si ese único grado es 0, cae en la rama de suma 0, que también da
     *   1.0), así que el normalizado sale 1.0 pase lo que pase con sus once
     *   criterios y sin que eso diga nada de ese actor en particular.
     *
     * Es una propiedad del CONJUNTO para ESE atributo, no de un actor
     * concreto, y existe para que quien pinte esto sepa cuándo un 1.00
     * necesita la aclaración de normalizar(): sin esto, "nadie puntúa poder"
     * y "este actor está justo en la media" se pintarían idénticos.
     *
     * @param array<int, int> $grados
     */
    public static function esAtributoDegenerado(array $grados): bool
    {
        self::validarGradosCompletos($grados);

        return count($grados) <= 1 || array_sum($grados) === 0;
    }

    /**
     * La relevancia de un actor: el producto de sus tres normalizados —poder,
     * legitimidad y urgencia—. Un atributo degenerado normaliza a 1.0 (el
     * neutro del producto, ver normalizar()), así que la relevancia queda
     * decidida solo por los atributos que sí diferencian al conjunto, sin que
     * quien ensambla las filas tenga que excluir nada a mano.
     *
     * @param array<string, float> $normalizados
     */
    public static function relevancia(array $normalizados): float
    {
        return array_product($normalizados);
    }
}
