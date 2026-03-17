<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Models\EvaluacionFit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluacionFitController extends Controller
{
    public function edit($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFit::firstOrNew(['zona_id' => $zonaId]);
        return view('operativo.evaluacion_fit.form', compact('zona', 'evaluacion'));
    }

    public function update(Request $request, $zonaId)
    {
        $user = Auth::user();
        $evaluacionActual = EvaluacionFit::where('zona_id', $zonaId)->first();

        if ($evaluacionActual && $evaluacionActual->estado === 'confirmado' && $user->role_id == 3) {
            return back()->with('error', 'Evaluación cerrada. No puedes editar.');
        }

        // 1. VALIDACIÓN DE INPUTS
        $campos = [
            'recursos_culturales', 'recursos_naturales',
            'atractivos_manifestaciones', 'atractivos_sitios',
            'prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza',
            'productos_territoriales',
            'infraestructura_basica', 'infraestructura_apoyo',
            'facilidades_senaletica', 'facilidades_recepcion', 'facilidades_interpretacion',
            'facilidades_senderos', 'facilidades_estacionamientos', 'facilidades_campamentos',
            'facilidades_miradores', 'facilidades_sanitarios'
        ];
        $rules = [];
        foreach ($campos as $campo) {
            $rules[$campo] = 'required|integer|min:0|max:3';
        }
        $validated = $request->validate($rules);


        $rtt = [$validated['recursos_culturales'], $validated['recursos_naturales']];
        $media_rtt = array_sum($rtt) / 2;
        $fit_rtt = $media_rtt * 0.30;

        $at = [$validated['atractivos_manifestaciones'], $validated['atractivos_sitios']];
        $media_at = array_sum($at) / 2;
        $fit_at = $media_at * 0.05;

        $pst = [$validated['prestadores_alojamiento'], $validated['prestadores_restauracion'], $validated['prestadores_guianza']];
        $media_pst = array_sum($pst) / 3;
        $fit_pst = $media_pst * 0.20;

        $media_ptt = $validated['productos_territoriales'];
        $fit_ptt = $media_ptt * 0.05;

        $i = [$validated['infraestructura_basica'], $validated['infraestructura_apoyo']];
        $media_i = array_sum($i) / 2;
        $fit_i = $media_i * 0.20;

        $ft = [
            $validated['facilidades_senaletica'], $validated['facilidades_recepcion'],
            $validated['facilidades_interpretacion'], $validated['facilidades_senderos'],
            $validated['facilidades_estacionamientos'], $validated['facilidades_campamentos'],
            $validated['facilidades_miradores'], $validated['facilidades_sanitarios']
        ];
        $media_ft = array_sum($ft) / 8;
        $fit_ft = $media_ft * 0.20;

        $fit = $fit_rtt + $fit_at + $fit_pst + $fit_ptt + $fit_i + $fit_ft;

        $estado = ($user->role_id == 2) ? $request->input('accion_estado', 'borrador') : 'borrador';

        $datosCalculados = [
            'user_id' => $user->id,
            'estado' => $estado,
            'media_rtt' => $media_rtt, 'fit_rtt' => $fit_rtt,
            'media_at' => $media_at,   'fit_at' => $fit_at,
            'media_pst' => $media_pst, 'fit_pst' => $fit_pst,
            'media_ptt' => $media_ptt, 'fit_ptt' => $fit_ptt,
            'media_i' => $media_i,     'fit_i' => $fit_i,
            'media_ft' => $media_ft,   'fit_ft' => $fit_ft,
            'fit' => $fit
        ];

        EvaluacionFit::updateOrCreate(
            ['zona_id' => $zonaId],
            array_merge($validated, $datosCalculados)
        );

        return redirect()->route('operativo.dashboard')
            ->with('success', 'Evaluación FIT guardada. Total: ' . number_format($fit, 2));
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fit = EvaluacionFit::where('zona_id', $zonaId)->firstOrFail();


        return view('operativo.evaluacion_fit.ponderacion', compact('zona', 'fit'));
    }

    
}