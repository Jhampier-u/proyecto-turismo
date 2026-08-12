<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Una página de resultados tiene que enseñar todos los criterios que entran en
 * su cálculo.
 *
 * Este test existe porque la de FIT enseñaba doce de sus dieciocho: los seis
 * que faltaban —recepción, centros de interpretación, senderos,
 * estacionamientos, campamentos y miradores— sí contaban para `media_ft` y por
 * tanto para la nota, pero no aparecían por ninguna parte. Quien mirara sus
 * resultados no podía cuadrar el número con lo que había respondido.
 *
 * Nadie lo detectó porque las tablas de FIT y FET están escritas a mano fila a
 * fila, mientras las demás matrices recorren su instrumento en un @foreach.
 *
 * **Se comprueba el código de la vista, no su salida**, y a propósito: Blade
 * imprime el *valor* del criterio, no su nombre, así que en el HTML renderizado
 * no queda rastro de qué campo produjo cada celda. Y las etiquetas no sirven de
 * ancla porque FIT las acorta en la tabla —«Básica» bajo el bloque
 * «Infraestructura»— frente al «Infraestructura Básica» del formulario.
 *
 * **Lo que este test NO cubre, dicho para que nadie lo dé por cubierto:** una
 * vista que itera queda fuera de la comprobación, así que si su bucle recorriera
 * un subconjunto del instrumento —porque el controlador le pasara solo una
 * parte— mostraría de menos y esto pasaría en verde igual.
 *
 * Hoy no ocurre: se comprobó que los controladores de Irritación, Concentración
 * y Percepción pasan las constantes completas (`Irritacion::ETIQUETAS`,
 * `Concentracion::ATRACTIVOS`…), no un recorte. Si algún día una vista recibiera
 * un subconjunto a propósito, esa comprobación necesitaría otro mecanismo:
 * renderizar y contar filas, o afirmar sobre lo que el controlador entrega.
 */
class ResultadosMuestranTodosLosCriteriosTest extends TestCase
{
    /**
     * Campos del instrumento → vista de resultados.
     *
     * Cada matriz dice cómo se consulta la suya porque no todas exponen lo
     * mismo: la mayoría tiene `todos()` devolviendo campo => definición, pero
     * Concentración expone `campos()`, una lista plana de nombres. Forzar una
     * interfaz común solo para este test sería mover el problema al código de
     * producción.
     *
     * Las rutas de vista se escriben a mano y no se derivan del registro: el
     * registro guarda nombres de ruta, no rutas de fichero, y resolverlas
     * exigiría levantar la aplicación entera para una comprobación que es de
     * texto.
     *
     * **Fuera de la lista, a propósito:** Involucrados y Frecuentación. No son
     * formularios de criterios sino CRUD de filas de longitud variable, así que
     * «todos sus criterios» no significa nada para ellos.
     *
     * @return array<string, array{0: callable(): list<string>, 1: string}>
     */
    public static function matrices(): array
    {
        $vista = fn(string $dir) => __DIR__ . "/../../resources/views/operativo/{$dir}/ponderacion.blade.php";
        $claves = fn(string $clase) => fn() => array_keys($clase::todos());

        return [
            'fit'                    => [$claves(\App\Matrices\Fit::class),                   $vista('evaluacion_fit')],
            'fet'                    => [$claves(\App\Matrices\Fet::class),                   $vista('evaluacion_fet')],
            'paisaje'                => [$claves(\App\Matrices\Paisaje::class),               $vista('evaluacion_paisaje')],
            'valoracion_territorial' => [$claves(\App\Matrices\ValoracionTerritorial::class), $vista('evaluacion_valoracion_territorial')],
            'percepcion'             => [$claves(\App\Matrices\Percepcion::class),            $vista('evaluacion_percepcion')],
            'irritacion'             => [$claves(\App\Matrices\Irritacion::class),            $vista('evaluacion_irritacion')],
            'potencialidad'          => [$claves(\App\Matrices\Potencialidad::class),         $vista('evaluacion_potencialidad')],
            'concentracion'          => [fn() => \App\Matrices\Concentracion::campos(),       $vista('evaluacion_concentracion')],
        ];
    }

    public function test_cada_vista_de_resultados_referencia_todos_sus_criterios(): void
    {
        $problemas = [];

        foreach (self::matrices() as $clave => [$campos, $ruta]) {
            $this->assertFileExists($ruta, "{$clave}: la vista de resultados no está donde se esperaba.");

            $fuente = file_get_contents($ruta);
            $campos = $campos();

            $this->assertNotEmpty($campos, "{$clave}: el instrumento no devolvió ningún campo.");

            $ausentes = array_values(array_filter(
                $campos,
                fn(string $campo) => ! str_contains($fuente, $campo)
            ));

            // Que no aparezca ninguno es aceptable, y por dos motivos distintos:
            //
            //  - la vista recorre su instrumento con un @foreach y entonces los
            //    muestra todos por construcción (Paisaje, Valoración
            //    Territorial, Percepción, Irritación, Concentración);
            //  - o enseña solo los agregados y ningún criterio suelto, que es
            //    lo que hace Potencialidad: una tabla de 156 filas no se lee, y
            //    sus 23 valores por subgrupo ya dan el desglose. Los valores
            //    individuales se consultan en el formulario.
            //
            // Lo que delata un olvido es el término medio: nombrar algunos y no
            // todos. Así estaba FIT —doce de dieciocho— y así seguiría si esto
            // buscara «@foreach» o un nombre de variable concreto, porque cada
            // vista llama al suyo como quiere. Contar cuántos campos aparecen no
            // depende de cómo estén escritos.
            //
            // Potencialidad entra en la lista aunque hoy no afirme nada: es un
            // hilo de alarma para el día en que alguien le añada un desglose por
            // criterio y se deje la mitad.
            if (count($ausentes) === count($campos)) {
                continue;
            }

            if ($ausentes !== []) {
                $problemas[] = "{$clave}: la vista no muestra "
                    . count($ausentes) . ' de ' . count($campos)
                    . ' criterios: ' . implode(', ', $ausentes);
            }
        }

        // Se acumulan y se afirma una sola vez: con un assert dentro del bucle,
        // la primera matriz rota esconde a las demás. Fue justo lo que pasó al
        // escribir este test —FIT tapaba a Valoración Territorial—.
        $this->assertSame([], $problemas, "\n" . implode("\n", $problemas));
    }
}
