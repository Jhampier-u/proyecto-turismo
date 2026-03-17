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
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFet::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fet.form', compact('zona', 'evaluacion'));
    }

    public function update(Request $request, $zonaId)
    {
        $user            = Auth::user();
        $evaluacionActual = EvaluacionFet::where('zona_id', $zonaId)->first();

        if ($evaluacionActual && $evaluacionActual->estado === 'confirmado' && $user->role_id == 3) {
            return back()->with('error', 'Esta evaluación FET ya fue validada por el Jefe. No puedes editarla.');
        }

        // 1. Validación de inputs
        $campos = [
            'demanda_flujos', 'demanda_estadia',
            'super_institucionalidad', 'super_organizacion', 'super_planificacion',
            'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing',
        ];
        $rules = [];
        foreach ($campos as $campo) {
            $rules[$campo] = 'required|integer|min:0|max:3';
        }
        $validated = $request->validate($rules);

        // 2. Cálculos ponderados
        // ✅ FIX: renombradas de $fit_* a $fet_* para evitar confusión con EvaluacionFit
        $demanda      = [$validated['demanda_flujos'], $validated['demanda_estadia']];
        $media_demanda = array_sum($demanda) / 2;
        $fet_demanda   = $media_demanda * 0.20;

        $super      = [$validated['super_institucionalidad'], $validated['super_organizacion'], $validated['super_planificacion']];
        $media_super = array_sum($super) / 3;
        $fet_super   = $media_super * 0.40;

        $imagen      = [$validated['imagen_apertura'], $validated['imagen_seguridad'], $validated['imagen_percibida'], $validated['imagen_marketing']];
        $media_imagen = array_sum($imagen) / 4;
        $fet_imagen   = $media_imagen * 0.40;

        $total_fet = $fet_demanda + $fet_super + $fet_imagen;

        $estado = ($user->role_id == 2)
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        $datosCalculados = [
            'user_id'      => $user->id,
            'estado'       => $estado,
            'media_demanda' => $media_demanda, 'fet_demanda' => $fet_demanda,
            'media_super'   => $media_super,   'fet_super'   => $fet_super,
            'media_imagen'  => $media_imagen,  'fet_imagen'  => $fet_imagen,
            'fet'           => $total_fet,
        ];

        EvaluacionFet::updateOrCreate(
            ['zona_id' => $zonaId],
            array_merge($validated, $datosCalculados)
        );

        $mensaje = ($estado === 'confirmado')
            ? 'Evaluación FET VALIDADA y CERRADA correctamente.'
            : 'Borrador FET guardado. El Jefe de Zona debe validarlo.';

        // ✅ FIX: redirige a la ponderación de la misma zona, consistente con EvaluacionFitController
        return redirect()
            ->route('operativo.evaluacion_fet.ponderacion', $zonaId)
            ->with('success', $mensaje);
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fet  = EvaluacionFet::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_fet.ponderacion', compact('zona', 'fet'));
    }
}
