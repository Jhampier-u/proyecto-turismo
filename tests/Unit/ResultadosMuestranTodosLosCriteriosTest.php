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
 */
class ResultadosMuestranTodosLosCriteriosTest extends TestCase
{
    /**
     * Instrumento → vista de resultados.
     *
     * Se escribe a mano y no se deriva del registro porque el registro guarda
     * nombres de ruta, no rutas de fichero, y resolverlas exigiría levantar la
     * aplicación entera para una comprobación que es de texto.
     *
     * Potencialidad queda fuera: sus campos activos son configurables por zona,
     * así que «todos sus criterios» significa otra cosa y merece su propia
     * comprobación. Irritación y Concentración también, porque su instrumento
     * no expone `todos()`.
     *
     * @return array<string, array{0: class-string, 1: string}>
     */
    public static function matrices(): array
    {
        $vista = fn(string $dir) => __DIR__ . "/../../resources/views/operativo/{$dir}/ponderacion.blade.php";

        return [
            'fit'                    => [\App\Matrices\Fit::class,                   $vista('evaluacion_fit')],
            'fet'                    => [\App\Matrices\Fet::class,                   $vista('evaluacion_fet')],
            'paisaje'                => [\App\Matrices\Paisaje::class,               $vista('evaluacion_paisaje')],
            'valoracion_territorial' => [\App\Matrices\ValoracionTerritorial::class, $vista('evaluacion_valoracion_territorial')],
            'percepcion'             => [\App\Matrices\Percepcion::class,            $vista('evaluacion_percepcion')],
        ];
    }

    public function test_cada_vista_de_resultados_referencia_todos_sus_criterios(): void
    {
        $problemas = [];

        foreach (self::matrices() as $clave => [$instrumento, $ruta]) {
            $this->assertFileExists($ruta, "{$clave}: la vista de resultados no está donde se esperaba.");

            $fuente = file_get_contents($ruta);
            $campos = array_keys($instrumento::todos());

            $ausentes = array_values(array_filter(
                $campos,
                fn(string $campo) => ! str_contains($fuente, $campo)
            ));

            // Ninguno nombrado significa que la vista recorre su instrumento
            // con un @foreach, y entonces los muestra todos por construcción.
            //
            // No se detecta buscando «@foreach» ni un nombre de variable
            // concreto: cada vista llama al suyo como quiere —$categorias en
            // Paisaje, $grupo en Valoración Territorial— y una heurística sobre
            // esos nombres se rompe con la siguiente matriz. Contar cuántos
            // campos aparecen no depende de cómo estén escritos.
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
