<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\Fet;
use App\Models\EvaluacionFet;
use App\Models\User;
use App\Models\VocacionTuristicaTerritorio;
use App\Models\Zona;

class EvaluacionFetController extends MatrizPonderadaController
{
    protected function criterios(): array
    {
        return collect(Fet::BLOQUES)
            ->map(fn(array $bloque) => array_keys($bloque['criterios']))
            ->all();
    }

    protected function escala(): array
    {
        return [Fet::ESCALA_MIN, Fet::ESCALA_MAX];
    }

    /**
     * Las 9 etiquetas del instrumento, tomadas de Fet::todos() y no
     * copiadas. Ver EvaluacionFitController::etiquetas(): mismo motivo,
     * mismo instrumento sin etiqueta hasta esta migración.
     */
    protected function etiquetas(): array
    {
        return array_map(fn(array $criterio) => $criterio['nombre'], Fet::todos());
    }

    /**
     * Ver EvaluacionFitController::calcular(): el peso de cada bloque vive
     * en Fet::BLOQUES, no en una constante aparte de este controlador.
     */
    protected function calcular(array $valores): array
    {
        $resultado = [];
        $total = 0.0;

        foreach (Fet::BLOQUES as $bloque => $datos) {
            $campos = array_keys($datos['criterios']);
            $media = array_sum(array_map(fn($c) => $valores[$c], $campos)) / count($campos);
            $ponderado = $media * $datos['peso'];

            $resultado["media_{$bloque}"] = $media;
            $resultado["fet_{$bloque}"]   = $ponderado;

            $total += $ponderado;
        }

        $resultado['fet'] = $total;

        return $resultado;
    }

    protected function modelo(): string
    {
        return EvaluacionFet::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_fet.ponderacion';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación FET VALIDADA y CERRADA correctamente.'
            : 'Borrador FET guardado. El Jefe de Zona debe validarlo.';
    }

    protected function mensajeCerrada(): string
    {
        return 'Esta evaluación FET ya fue validada por el Jefe. No puedes editarla.';
    }

    protected function despuesDeGuardar($zonaId, User $user): void
    {
        VocacionTuristicaTerritorio::registrar($zonaId, $user->id);
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFet::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fet.form', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'bloques'    => Fet::BLOQUES,
            'niveles'    => Fet::NIVELES,
        ]);
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fet  = EvaluacionFet::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_fet.ponderacion', compact('zona', 'fet'));
    }
}
