<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\Paisaje;
use App\Models\EvaluacionPaisaje;
use App\Models\Zona;

class EvaluacionPaisajeController extends MatrizPonderadaController
{
    protected function modelo(): string
    {
        return EvaluacionPaisaje::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_paisaje.ponderacion';
    }

    protected function escala(): array
    {
        return [0, Paisaje::MAXIMO];
    }

    /**
     * El instrumento admite 0, 3 y 5, no cualquier valor entre 0 y 5: con la
     * regla de rango por defecto se colarían el 1, el 2 y el 4.
     */
    protected function reglaCriterio(): string
    {
        return 'required|integer|in:' . implode(',', Paisaje::VALORES);
    }

    protected function criterios(): array
    {
        return collect(Paisaje::CATEGORIAS)
            ->map(fn($categoria) => array_keys($categoria['criterios']))
            ->all();
    }

    /**
     * Cada categoría promedia sus criterios y ese promedio se pondera. Los
     * pesos suman 1, así que el total queda en la misma escala 0-5.
     */
    protected function calcular(array $valores): array
    {
        $resultado = [];
        $total = 0.0;

        foreach (Paisaje::CATEGORIAS as $clave => $categoria) {
            $campos = array_keys($categoria['criterios']);

            $promedio = array_sum(array_map(fn($c) => $valores[$c], $campos)) / count($campos);

            $resultado["{$clave}_promedio"] = $promedio;

            $total += $promedio * $categoria['peso'];
        }

        $resultado['paisaje_total'] = $total;

        return $resultado;
    }

    protected function mensajeCerrada(): string
    {
        return 'Esta Matriz de Paisaje ya fue validada por el Jefe de Zona. No puedes editarla.';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        $total = number_format($datos['paisaje_total'], 2);

        return $estado === 'confirmado'
            ? "Matriz de Paisaje VALIDADA. Resultado: {$total} / 5.00"
            : "Borrador guardado. Resultado: {$total} / 5.00";
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionPaisaje::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_paisaje.form', [
            'zona'        => $zona,
            'evaluacion'  => $evaluacion,
            'categorias'  => Paisaje::CATEGORIAS,
        ]);
    }

    public function ponderacion($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionPaisaje::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_paisaje.ponderacion', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'categorias' => Paisaje::CATEGORIAS,
        ]);
    }
}
