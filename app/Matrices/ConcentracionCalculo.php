<?php

namespace App\Matrices;

use InvalidArgumentException;

/**
 * Cálculo del Índice de Concentración Turística.
 *
 * VIVE APARTE DE Concentracion.php A PROPÓSITO: ese fichero está GENERADO
 * (ver su cabecera y database/matrices/generar_concentracion.py) y una
 * regeneración lo reescribe entero -solo ATRACTIVOS, PLANTA y campos()-, sin
 * avisar de que había algo más ahí. Cualquier método añadido a mano dentro
 * de Concentracion.php desaparecería en el próximo
 * `python database/matrices/generar_concentracion.py`. Esta clase, en un
 * fichero que el generador no toca, sobrevive a esa regeneración.
 *
 * Aritmética pura sobre arrays de escalares -sin Eloquent, sin recibir
 * modelos, sin importar nada de App\Models-, en la línea de
 * Involucrados::normalizar(): quien reúne los conteos de una zona en estos
 * arrays es el controlador o la vista, no esta clase. Esto evita repetir el
 * hallazgo de la revisión de Involucrados, donde el cálculo había acabado
 * recibiendo colecciones de Eloquent dentro del instrumento y creó la
 * primera dependencia circular del sistema -el instrumento importando el
 * modelo que ya lo importaba a él-.
 */
final class ConcentracionCalculo
{
    /**
     * Valida que un array de conteos esté completo, sin huecos (null). Los
     * conteos nacen nullable en la base de datos -un subtipo o subcategoría
     * sin contar todavía no es un cero-, así que sumarlos directamente con
     * array_sum() tomaría cada hueco como 0 en silencio ("0 + null" no
     * lanza nada en PHP). Se exige el bloque completo, igual que
     * Involucrados::validarGradosCompletos(): un porcentaje o un Sia
     * calculado sobre una tabla o un sector a medias no tiene ninguna
     * lectura razonable, así que esto falla ruidoso en vez de devolver un
     * número que parece válido y no lo es.
     *
     * @param array<int|string, mixed> $conteos
     */
    private static function validarConteosCompletos(array $conteos): void
    {
        foreach ($conteos as $conteo) {
            if (! is_int($conteo)) {
                throw new InvalidArgumentException(
                    'El cálculo de Concentración exige un bloque de conteos completo, sin huecos (null): un subtipo o sector sin contar no tiene un porcentaje, un Sia ni un Pi razonables.'
                );
            }
        }
    }

    /**
     * Porcentaje de cada subtipo sobre el total de SU tabla. El instrumento
     * trae dos tablas de atractivos -manifestaciones culturales y
     * atractivos naturales- y cada una se calcula por separado, una llamada
     * por tabla: mezclar las dos perdería el "sobre el total de SU tabla"
     * que pide el instrumento.
     *
     * TABLA ENTERA A CERO -> 0.0, NO null: si nadie ha contado ningún
     * atractivo de la tabla, el total da 0 y el porcentaje de cada subtipo
     * sería 0/0, que no tiene una lectura matemática. Pero a diferencia de
     * Pi -donde 100/√0 es una fórmula sin sentido y por eso Pi devuelve
     * null, ver pi()-, aquí el NUMERADOR de cada subtipo es también 0
     * (nadie cuenta nada, ni la tabla ni ningún subtipo suyo), así que "0%
     * de una tabla vacía" es una lectura razonable y no una invención.
     * Devolver 0.0 en vez de null también mantiene el tipo de retorno
     * uniforme (float en todos los casos), así que quien pinte esto no
     * tiene que distinguir "sin porcentaje" de "0%": aquí sí hay un 0%
     * legítimo.
     *
     * @param array<string, int> $conteos campo => cantidad, todos los subtipos de UNA tabla, completo (sin huecos)
     * @return array<string, float> campo => porcentaje (0-100) sobre el total de esa tabla
     */
    public static function porcentajesAtractivos(array $conteos): array
    {
        self::validarConteosCompletos($conteos);

        $total = array_sum($conteos);

        if ($total === 0) {
            return array_map(static fn () => 0.0, $conteos);
        }

        return array_map(static fn (int $conteo) => (float) ($conteo / $total * 100), $conteos);
    }

    /**
     * Sia: la suma de las subcategorías de UN sector de planta turística.
     *
     * @param array<string, int> $conteos campo => cantidad, todas las subcategorías del sector, completo (sin huecos)
     */
    public static function sia(array $conteos): int
    {
        self::validarConteosCompletos($conteos);

        return array_sum($conteos);
    }

    /**
     * Pi = 100/√Sia.
     *
     * NO ES LINEAL, Y BAJA CUANDO Sia SUBE: un sector con MÁS
     * establecimientos tiene un Pi MENOR, no mayor. Puede parecer al revés
     * de lo esperado -"más" debería dar "más"-, pero es la fórmula del
     * instrumento (Calle Lituma y Chaca Espinoza, sobre Illingworth 2011),
     * no un error de esta clase: Pi pondera en sentido inverso a cuánta
     * planta turística ya tiene el sector. Que quede dicho aquí para que el
     * próximo lector que lo encuentre contraintuitivo no lo "corrija".
     *
     * SIA CERO -> null, NO 0.0: un sector sin ningún establecimiento no
     * tiene "un Pi bajo" -Pi bajo significaría mucha planta concentrada
     * ahí, justo lo contrario de lo que pasa en un sector vacío-, tiene un
     * Pi que directamente no existe: 100/√0 es una división por cero, no un
     * 0.0 disfrazado.
     */
    public static function pi(int $sia): ?float
    {
        if ($sia < 0) {
            throw new InvalidArgumentException('Sia no puede ser negativo: es una suma de conteos de establecimientos.');
        }

        return $sia === 0 ? null : 100 / sqrt($sia);
    }

    /**
     * ICT de un sector: su Sia sobre el total general (la suma de los Sia
     * de TODOS los sectores de planta turística).
     *
     * TOTAL GENERAL CERO -> 0.0, NO UNA DIVISIÓN POR CERO: si la planta
     * turística entera está a cero (los 36 campos, en los diez sectores) no
     * hay nada que repartir entre sectores. Igual que en
     * porcentajesAtractivos(), el numerador (el Sia de este sector) también
     * es 0 en ese caso, así que "0.0 de reparto" es la lectura razonable, no
     * un valor inventado.
     */
    public static function ict(int $sia, int $totalGeneral): float
    {
        if ($sia < 0 || $totalGeneral < 0) {
            throw new InvalidArgumentException('Sia y el total general no pueden ser negativos: son sumas de conteos de establecimientos.');
        }

        if ($totalGeneral === 0) {
            return 0.0;
        }

        return (float) ($sia / $totalGeneral);
    }

    /**
     * Sia, Pi e ICT de TODOS los sectores de planta turística a la vez.
     *
     * A diferencia de porcentajesAtractivos() -donde cada tabla se basta a
     * sí misma-, el ICT de un sector necesita el total general de TODOS los
     * sectores, así que aquí sí hace falta ver el bloque completo de planta
     * en una sola llamada: es el equivalente, para esta matriz, de que la
     * clase base solo llame a calcular() con la matriz completa. sia(),
     * pi() e ict() siguen siendo públicos y utilizables sueltos aparte de
     * esta función -una vista que ya tenga el Sia de un sector calculado en
     * otro sitio no tiene que reconstruir el array de planta entero solo
     * para conseguir su Pi-.
     *
     * @param array<string, array<string, int>> $conteosPorSector sector => [campo => cantidad], con TODOS los sectores y TODAS sus subcategorías, completo (sin huecos)
     * @return array<string, array{sia: int, pi: float|null, ict: float}>
     */
    public static function planta(array $conteosPorSector): array
    {
        $sias = array_map(
            static fn (array $conteos) => self::sia($conteos),
            $conteosPorSector
        );

        $totalGeneral = array_sum($sias);

        return array_map(
            static fn (int $sia) => [
                'sia' => $sia,
                'pi'  => self::pi($sia),
                'ict' => self::ict($sia, $totalGeneral),
            ],
            $sias
        );
    }
}
