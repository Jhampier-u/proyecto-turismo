<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\Fit;
use App\Models\EvaluacionFit;
use App\Models\User;
use App\Models\VocacionTuristicaTerritorio;
use App\Models\Zona;

class EvaluacionFitController extends MatrizPonderadaController
{
    protected function criterios(): array
    {
        return collect(Fit::BLOQUES)
            ->map(fn(array $bloque) => array_keys($bloque['criterios']))
            ->all();
    }

    protected function escala(): array
    {
        return [Fit::ESCALA_MIN, Fit::ESCALA_MAX];
    }

    /**
     * Las 18 etiquetas del instrumento, tomadas de Fit::todos() y no
     * copiadas: cada criterio trae su 'nombre' ya escrito allí. Antes de
     * esta migración este método no existía -devolvía [] por herencia de
     * MatrizPonderadaController-, y FIT era la única de las nueve matrices
     * sin etiqueta en sus mensajes de validación. Ver
     * docs/ESTADO-PROYECTO.md, rama mensajes-validacion.
     */
    protected function etiquetas(): array
    {
        return array_map(fn(array $criterio) => $criterio['nombre'], Fit::todos());
    }

    /**
     * Cada bloque promedia sus criterios y ese promedio se pondera. El peso
     * de cada bloque vive en Fit::BLOQUES, no en una constante aparte de
     * este controlador: dos listas de pesos para el mismo instrumento se
     * habrían podido desincronizar sin que nada lo notara.
     */
    protected function calcular(array $valores): array
    {
        $resultado = [];
        $total = 0.0;

        foreach (Fit::BLOQUES as $bloque => $datos) {
            $campos = array_keys($datos['criterios']);
            $media = array_sum(array_map(fn($c) => $valores[$c], $campos)) / count($campos);
            $ponderado = $media * $datos['peso'];

            $resultado["media_{$bloque}"] = $media;
            $resultado["fit_{$bloque}"]   = $ponderado;

            $total += $ponderado;
        }

        $resultado['fit'] = $total;

        return $resultado;
    }

    protected function modelo(): string
    {
        return EvaluacionFit::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_fit.ponderacion';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación FIT VALIDADA y CERRADA correctamente.'
            : 'Borrador FIT guardado. Total: ' . number_format($datos['fit'], 2);
    }

    protected function mensajeCerrada(): string
    {
        return 'Evaluación cerrada. No puedes editar.';
    }

    protected function despuesDeGuardar($zonaId, User $user): void
    {
        VocacionTuristicaTerritorio::registrar($zonaId, $user->id);
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFit::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fit.form', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'bloques'    => Fit::BLOQUES,
            'niveles'    => Fit::NIVELES,
        ]);
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fit  = EvaluacionFit::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_fit.ponderacion', compact('zona', 'fit'));
    }
}
