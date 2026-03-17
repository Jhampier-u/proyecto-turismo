<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Models\EvaluacionFet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluacionFetController extends Controller
{
    public function edit($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFet::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fet.form', compact('zona', 'evaluacion'));
    }

    public function update(Request $request, $zonaId)
    {
        $user = Auth::user();
        $evaluacionActual = EvaluacionFet::where('zona_id', $zonaId)->first();

        if ($evaluacionActual && $evaluacionActual->estado === 'confirmado' && $user->role_id == 3) {
            return back()->with('error', 'Esta evaluación FET ya fue validada por el Jefe. No puedes editarla.');
        }

        $campos = [
            'demanda_flujos', 'demanda_estadia',
            'super_institucionalidad', 'super_organizacion', 'super_planificacion',
            'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing'
        ];
        $rules = [];
        foreach ($campos as $campo) {
            $rules[$campo] = 'required|integer|min:0|max:3';
        }
        $validated = $request->validate($rules);


        $demanda = [$validated['demanda_flujos'], $validated['demanda_estadia']];
        $media_demanda = array_sum($demanda) / 2;
        $fit_demanda = $media_demanda * 0.20;

        $super = [$validated['super_institucionalidad'], $validated['super_organizacion'], $validated['super_planificacion']];
        $media_super = array_sum($super) / 3;
        $fit_super = $media_super * 0.40;

        $imagen = [$validated['imagen_apertura'], $validated['imagen_seguridad'], $validated['imagen_percibida'], $validated['imagen_marketing']];
        $media_imagen = array_sum($imagen) / 4;
        $fit_imagen = $media_imagen * 0.40;

        $total_fet = $fit_demanda + $fit_super + $fit_imagen;

       $estado = ($user->role_id == 2) ? $request->input('accion_estado', 'borrador') : 'borrador';

        $datosCalculados = [
            'user_id' => $user->id,
            'estado' => $estado,
            'media_demanda' => $media_demanda, 'fet_demanda' => $fit_demanda,
            'media_super' => $media_super,   'fet_super' => $fit_super,
            'media_imagen' => $media_imagen, 'fet_imagen' => $fit_imagen,
            'fet' => $total_fet
        ];

        EvaluacionFet::updateOrCreate(
            ['zona_id' => $zonaId],
            array_merge($validated, $datosCalculados)
        );

        $mensaje = ($estado == 'confirmado') 
            ? 'Evaluación FET VALIDADA y CERRADA correctamente.' 
            : 'Borrador FET guardado. El Jefe de Zona debe validarlo.';

        return redirect()->route('operativo.dashboard')->with('success', $mensaje);
    }

        public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fet = EvaluacionFet::where('zona_id', $zonaId)->firstOrFail();


        return view('operativo.evaluacion_fet.ponderacion', compact('zona', 'fet'));
    }
}