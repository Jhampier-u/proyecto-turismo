<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\Zona;
use App\Models\VocacionTuristicaTerritorio;
use Illuminate\Support\Facades\Auth;

class VttController extends Controller
{
    public function resultadoFinal($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $user = Auth::user();

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

        $fit_score = $evaluacion_fit->fit;
        $fet_score = $evaluacion_fet->fet;

        $vtt_score = ($fit_score * 0.60 + $fet_score * 0.40);
        $vocacion_texto = $this->determinarVocacion($vtt_score);

        $vtt = VocacionTuristicaTerritorio::updateOrCreate(
            ['zona_id' => $zonaId],
            [
                'user_id' => $user->id,
                'fit' => $fit_score,
                'fet' => $fet_score,
                'vtt' => $vtt_score,
                'vocacion_texto' => $vocacion_texto,
            ]
        );
                
        return view('operativo.vtt.resultado', compact('zona', 'vtt'));
    }

    private function determinarVocacion($score)
    {
        if ($score >= 2.1) {
            return 'Alta Vocación Turística';
        } elseif ($score >= 1.1) {
            return 'Mediana Vocación Turística';
        } else {
            return 'Baja Vocación Turística';
        }
    }
}