<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\Zona;
use App\Models\VocacionTuristicaTerritorio;

class VttController extends Controller
{
    public function resultadoFinal($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        $evaluacion_fit = EvaluacionFit::where('zona_id', $zonaId)->first();
        $evaluacion_fet = EvaluacionFet::where('zona_id', $zonaId)->first();

        if (!$evaluacion_fit || $evaluacion_fit->estado !== 'confirmado') {
            return redirect()->route('operativo.evaluacion_fit.edit', $zonaId)
                ->with('error', 'El reporte no está disponible: La Evaluación FIT debe ser VALIDADA primero.');
        }

        if (!$evaluacion_fet || $evaluacion_fet->estado !== 'confirmado') {
            return redirect()->route('operativo.evaluacion_fet.edit', $zonaId)
                ->with('error', 'El reporte no está disponible: La Evaluación FET debe ser VALIDADA primero.');
        }

        // Esta acción es de solo lectura. La instantánea se guarda al confirmar
        // la evaluación (VocacionTuristicaTerritorio::registrar), no al abrir
        // esta página: antes un simple GET escribía en la base de datos.
        //
        // Los valores se recalculan siempre para que reflejen el FIT y el FET
        // actuales aunque la instantánea guardada haya quedado desfasada.
        $vtt = VocacionTuristicaTerritorio::where('zona_id', $zonaId)->first()
            ?? new VocacionTuristicaTerritorio(['zona_id' => $zonaId]);

        $vtt->fill(VocacionTuristicaTerritorio::calcular(
            (float) $evaluacion_fit->fit,
            (float) $evaluacion_fet->fet,
        ));

        return view('operativo.vtt.resultado', compact('zona', 'vtt'));
    }
}
