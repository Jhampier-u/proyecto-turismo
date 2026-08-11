<?php

namespace App\Matrices;

use InvalidArgumentException;

/**
 * Índice Espacial de Frecuentación Turística.
 *
 * Reparte la frecuentación de una zona entre sus sitios: por sitio,
 * ÍETP = DET ÷ ST; para el territorio, ÍEFT = Σ ÍETP. A diferencia de
 * Involucrados::normalizar(), el ÍETP de un sitio NO depende de los demás
 * sitios -su único divisor es ST, un dato de la zona, no una función de los
 * propios DET-. Ver el diseño para la comparación completa con Involucrados.
 */
final class Frecuentacion
{
    /**
     * ÍETP de un sitio: DET ÷ ST.
     *
     * ST NULA O CERO -> null, NUNCA una excepción ni un número inventado:
     * en PHP 8 la división por cero con el operador `/` lanza
     * DivisionByZeroError, así que hay que interceptarla antes. Un sitio sin
     * Superficie Territorial no tiene "un ÍETP bajo": no tiene ÍETP. Misma
     * jurisprudencia que ConcentracionCalculo::pi() con un sector vacío,
     * aplicada aquí a un divisor que es de la ZONA, no de cada fila -ver el
     * diseño para por qué eso cambia cómo se presenta, no cómo se calcula-.
     *
     * DET es obligatorio en esta función: un sitio sin DET no debe llegar
     * aquí -la completitud se comprueba antes, en el controlador o la
     * vista-, igual que Concentración e Involucrados exigen sus arrays de
     * entrada completos antes de calcular nada.
     */
    public static function ietp(float $det, ?float $st): ?float
    {
        if ($det < 0) {
            throw new InvalidArgumentException('DET no puede ser negativo.');
        }

        if ($st !== null && $st < 0) {
            throw new InvalidArgumentException('ST no puede ser negativa.');
        }

        return ($st === null || $st === 0.0) ? null : $det / $st;
    }

    /**
     * ÍEFT del territorio: la suma de los ÍETP de todos sus sitios.
     *
     * Exige el array completo, sin huecos y con al menos un elemento: un
     * sitio sin ÍETP (DET sin responder, o ST ausente/cero) no tiene una
     * suma parcial razonable -descontar el término que falta daría un
     * número medido sobre otra escala, la misma lección que dejó GP5-.
     * Mismo principio que ConcentracionCalculo::validarConteosCompletos() e
     * Involucrados::validarGradosCompletos(): la completitud se comprueba
     * ANTES de llamar aquí -en la vista de resultados, con
     * <x-matriz-sin-resultados> para el caso incompleto-, así que un hueco
     * que llegue hasta esta función es un fallo de quien llama, y debe
     * fallar ruidoso, no devolver un número que parece un resultado.
     *
     * @param array<int, float|null> $ietps
     */
    public static function ieft(array $ietps): float
    {
        if ($ietps === []) {
            throw new InvalidArgumentException('ÍEFT no está definido para un territorio sin sitios.');
        }

        foreach ($ietps as $valor) {
            if (! is_float($valor) && ! is_int($valor)) {
                throw new InvalidArgumentException(
                    'ÍEFT exige que todos los sitios tengan su ÍETP calculado: ninguno puede faltar.'
                );
            }
        }

        return (float) array_sum($ietps);
    }
}
