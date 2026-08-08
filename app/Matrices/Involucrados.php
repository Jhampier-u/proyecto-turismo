<?php

namespace App\Matrices;

use App\Models\Involucrado;
use Illuminate\Support\Collection;

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
     * Normaliza el grado de UN atributo (poder, legitimidad o urgencia) de
     * cada actor frente al conjunto: (grado del actor / suma de los grados de
     * todos) × número de actores. Es la mitad "por atributo" de la fórmula de
     * relevancia; la otra mitad —el producto de los tres normalizados— vive
     * en relevancias(), que es quien llama a este método tres veces.
     *
     * DELIBERADO — no es un fallo que alguien deba "corregir": el resultado
     * de CADA actor depende de los grados de TODOS los demás, porque se
     * divide por la suma del conjunto. Dar de alta o de baja un actor
     * recalcula los normalizados de los que ya estaban, aunque sus propios
     * criterios no cambien. Está decidido así con el responsable del
     * proyecto: comparar el normalizado de un actor entre dos listados
     * distintos no tiene sentido, solo lo tiene dentro del mismo conjunto.
     * InvolucradosTest fija esta propiedad a propósito con un test que crea
     * un tercer actor y comprueba que los dos primeros cambian.
     *
     * DIVISIÓN POR CERO — "ninguno posee poder" (los siete campos en 0 para
     * todos los actores) es un caso real del instrumento, no un error de
     * captura, y ahí la suma da 0. La fórmula de arriba es en el fondo
     * grado ÷ media(grados) —reescrita para no dividir por partes—, y
     * mientras la suma no sea 0 la media de los normalizados del conjunto da
     * SIEMPRE exactamente 1, sea cual sea la distribución de grados (es el
     * álgebra: la media de (grado_i/suma)×n es n×(suma/suma)/n = 1). Con
     * todos los actores empatados en 0, la continuación natural de "empatados
     * en cualquier valor → todos normalizan a 1" sigue siendo 1.0: es el
     * límite de la fórmula cuando un empate en un valor positivo tiende a
     * cero. Devolver 0.0 en su lugar sería más intuitivo a primera vista,
     * pero anularía la relevancia ENTERA de todos los actores —relevancia es
     * un producto de los tres normalizados—, aunque legitimidad o urgencia sí
     * los diferenciaran.
     *
     * @param Collection<int, Involucrado> $actores
     * @return array<int, float> en el mismo orden que $actores->values()
     */
    private static function normalizarAtributo(Collection $actores, string $atributo): array
    {
        // grado() exige que $actor no tenga ningún criterio en null: es la
        // precondición de relevancias(), documentada ahí. Con un hueco, la
        // suma en int + null lanzaría un TypeError en el map de más abajo en
        // vez de mentir con un cálculo a medias.
        $grados = $actores->values()->map(fn(Involucrado $actor) => $actor->grado($atributo));
        $n      = $grados->count();
        $suma   = $grados->sum();

        if ($suma === 0) {
            return array_fill(0, $n, 1.0);
        }

        return $grados->map(fn(int $grado) => ($grado / $suma) * $n)->all();
    }

    /**
     * Consolida el conjunto de actores para la vista de resultados: por cada
     * actor, sus tres normalizados y la relevancia —poder × legitimidad ×
     * urgencia, ya normalizados—, ordenados de mayor a menor relevancia.
     *
     * Vive aquí y no en el modelo Involucrado porque es lo único en todo el
     * instrumento que cruza actores entre sí: grado() y tipo_mitchell son por
     * actor y el modelo no tiene forma de ver a los demás actores de la zona.
     * Vive aquí y no en la vista para que se pueda probar sin HTTP, igual que
     * pide el diseño de la Tarea 4.
     *
     * Precondición: asume que Involucrado::grado() no devuelve null para
     * ningún actor de la colección, es decir que la lista está completa.
     * Responsabilidad de quien llama —InvolucradosController::resultados()
     * solo la invoca cuando $completa es true—: normalizar sobre un hueco no
     * tiene ningún significado, y llamarlo con un actor a medias falla con un
     * TypeError en vez de devolver un número que parece válido y no lo es.
     *
     * @param Collection<int, Involucrado> $actores
     * @return Collection<int, array{actor: Involucrado, normalizado: array<string, float>, relevancia: float}>
     */
    public static function relevancias(Collection $actores): Collection
    {
        $normalizadosPorAtributo = [];
        foreach (array_keys(self::ATRIBUTOS) as $atributo) {
            $normalizadosPorAtributo[$atributo] = self::normalizarAtributo($actores, $atributo);
        }

        return $actores->values()
            ->map(function (Involucrado $actor, int $indice) use ($normalizadosPorAtributo) {
                $normalizado = [];
                foreach (array_keys(self::ATRIBUTOS) as $atributo) {
                    $normalizado[$atributo] = $normalizadosPorAtributo[$atributo][$indice];
                }

                return [
                    'actor'       => $actor,
                    'normalizado' => $normalizado,
                    'relevancia'  => array_product($normalizado),
                ];
            })
            ->sortByDesc('relevancia')
            ->values();
    }
}
