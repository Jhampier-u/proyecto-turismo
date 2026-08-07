<?php

namespace App\Http\Controllers\Operativo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Matrices de criterios fijos con peso y escala fija.
 *
 * La validación se deriva de la declaración de criterios, de modo que cada
 * matriz solo aporta sus criterios, su escala y su cálculo.
 */
abstract class MatrizPonderadaController extends EvaluacionZonaController
{
    /**
     * Campos agrupados por dimensión o bloque de cálculo.
     *
     * Solo los nombres: los pesos viven donde se usan, en calcular(), porque
     * no todas las matrices ponderan criterio a criterio. FIT y FET promedian
     * por bloque y ponderan el bloque, así que un peso por criterio sería un
     * dato decorativo que nadie lee.
     *
     * @return array<string, list<string>> dimensión => [campo, ...]
     */
    abstract protected function criterios(): array;

    /** @return array{0: int, 1: int} [mínimo, máximo] de la escala */
    abstract protected function escala(): array;

    /**
     * Regla del valor, sin obligatoriedad.
     *
     * Por defecto es el rango continuo que declara escala(). Una matriz cuya
     * escala no sea contigua —Paisaje admite 0, 3 y 5, nada más— la
     * sobreescribe: con min/max se colarían el 1, el 2 y el 4.
     */
    protected function reglaValor(): string
    {
        [$min, $max] = $this->escala();

        return "integer|min:{$min}|max:{$max}";
    }

    /**
     * Solo se exige todo al confirmar.
     *
     * Con 34 criterios en Paisaje y 156 en Potencialidad, perder el avance al
     * cerrar la pestaña era lo que más dolía al usar esto de verdad.
     */
    protected function reglaCriterio(string $estado): string
    {
        $obligatoriedad = $estado === 'confirmado' ? 'required' : 'nullable';

        return "{$obligatoriedad}|{$this->reglaValor()}";
    }

    /**
     * Columnas calculadas a partir de las calificaciones validadas.
     *
     * @param array<string, int> $valores
     * @return array<string, float>
     */
    abstract protected function calcular(array $valores): array;

    /** Todos los campos, aplanados en el orden de declaración. */
    protected function campos(): array
    {
        return array_merge(...array_values($this->criterios()));
    }

    protected function estaCompleta(array $datos): bool
    {
        foreach ($this->campos() as $campo) {
            if (($datos[$campo] ?? null) === null) {
                return false;
            }
        }

        return true;
    }

    protected function respondidos(array $valores): int
    {
        return count(array_filter(
            $this->campos(),
            fn(string $campo) => ($valores[$campo] ?? null) !== null
        ));
    }

    protected function mensajeIncompleto(array $datos): string
    {
        $total = count($this->campos());

        return "Borrador guardado. Llevas {$this->respondidos($datos)} de {$total} criterios.";
    }

    /**
     * Los nombres de las columnas que calcula esta matriz, todas a null.
     *
     * Se obtienen llamando a calcular() con ceros en lugar de declararlas en
     * una lista aparte: una lista duplicada se desincroniza en cuanto alguien
     * añade un promedio nuevo, y nada lo detectaría.
     */
    protected function columnasCalculadasVacias(): array
    {
        $ceros = array_fill_keys($this->campos(), 0);

        return array_fill_keys(array_keys($this->calcular($ceros)), null);
    }

    protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array
    {
        $regla = $this->reglaCriterio($estado);

        $reglas = [];
        foreach ($this->campos() as $campo) {
            $reglas[$campo] = $regla;
        }

        $request->validate($reglas);

        // validate() no devuelve las claves ausentes, y aquí hacen falta todas:
        // un campo que el usuario borró tiene que llegar como null a la base
        // para que updateOrCreate lo vacíe en vez de conservar el valor viejo.
        $valores = [];
        foreach ($this->campos() as $campo) {
            $bruto = $request->input($campo);
            $valores[$campo] = $bruto === null ? null : (int) $bruto;
        }

        return $valores + ($this->estaCompleta($valores)
            ? $this->calcular($valores)
            : $this->columnasCalculadasVacias());
    }
}
