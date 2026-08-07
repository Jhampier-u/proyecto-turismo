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
     * Regla de validación de una calificación.
     *
     * Por defecto es el rango continuo que declara escala(). Una matriz cuya
     * escala no sea contigua —Paisaje admite 0, 3 y 5, nada más— la
     * sobreescribe: con min/max se colarían el 1, el 2 y el 4.
     */
    protected function reglaCriterio(): string
    {
        [$min, $max] = $this->escala();

        return "required|integer|min:{$min}|max:{$max}";
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

    protected function prepararDatos(Request $request, $zonaId, ?Model $actual): array
    {
        $regla = $this->reglaCriterio();

        $reglas = [];
        foreach ($this->campos() as $campo) {
            $reglas[$campo] = $regla;
        }

        $valores = $request->validate($reglas);

        return $valores + $this->calcular($valores);
    }
}
