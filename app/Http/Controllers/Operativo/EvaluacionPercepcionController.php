<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\EvaluacionPercepcion;
use App\Models\Zona;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EvaluacionPercepcionController extends Controller
{
    // Estructura: código → [nombre_campo_db, etiqueta]
    public static array $categorias = [
        'DS' => [
            'nombre' => 'Dimensión Social',
            'peso'   => 0.20,
            'items'  => [
                'ds1_posicion_turistica'  => 'Posición frente a la actividad turística en el lugar',
                'ds2_interes_participar'  => 'Grado de interés por participar en el desarrollo de actividades turísticas',
                'ds3_contribucion_social' => 'Contribución del turismo en los procesos sociales de la localidad',
            ],
        ],
        'PL' => [
            'nombre' => 'Percepción Local',
            'peso'   => 0.40,
            'items'  => [
                'pl1_conoc_recursos'         => 'Conocimiento de la existencia de recursos turísticos en el territorio',
                'pl2_conoc_atractivos'       => 'Conocimiento de la existencia de atractivos turísticos en el territorio',
                'pl3_conoc_motivo_visita'    => 'Conocimiento del motivo de visita hacia el territorio',
                'pl4_conoc_flujo_visitantes' => 'Conocimiento del flujo de visitantes en la comunidad',
                'pl5_sentimiento_visitantes' => 'Percepción o sentimiento causado por la presencia de visitantes',
                'pl6_necesidad_visitantes'   => 'Opinión sobre la necesidad de la llegada de visitantes al lugar',
            ],
        ],
        'PE' => [
            'nombre' => 'Percepción Económica',
            'peso'   => 0.20,
            'items'  => [
                'pe1_incidencia_ingresos'   => 'Incidencia de los ingresos económicos que perciben por su actividad actual',
                'pe2_beneficios_esperados'  => 'Percepción de los beneficios que pueden esperarse por efectos del turismo',
                'pe3_disposicion_inversion' => 'Grado de disposición de la localidad para invertir en actividades turísticas',
            ],
        ],
        'NO' => [
            'nombre' => 'Nivel de Organización',
            'peso'   => 0.20,
            'items'  => [
                'no1_organizacion_colectiva' => 'Nivel de organización colectiva entorno a objetivos comunes',
                'no2_lideres_sociales'       => 'Presencia de líderes sociales al interior de la comunidad',
                'no3_opinion_lideres'        => 'Opinión del trabajo realizado por los líderes',
                'no4_conflictos_sociales'    => 'Presencia de conflictos entre actores y grupos sociales',
            ],
        ],
    ];

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionPercepcion::firstOrNew(['zona_id' => $zonaId]);
        $categorias = self::$categorias;

        return view('operativo.evaluacion_percepcion.form',
            compact('zona', 'evaluacion', 'categorias'));
    }

    public function update(Request $request, $zonaId)
    {
        $user             = Auth::user();
        $evaluacionActual = EvaluacionPercepcion::where('zona_id', $zonaId)->first();

        if ($evaluacionActual && $evaluacionActual->estado === 'confirmado' && $user->role_id == 3) {
            return back()->with('error', 'Evaluación cerrada. No puedes editar.');
        }

        // Armar reglas de validación para los 16 ítems (1=Negativo, 2=Neutral, 3=Positivo)
        $rules = ['acciones_mejora' => 'nullable|string|max:5000'];
        foreach (self::$categorias as $cat) {
            foreach (array_keys($cat['items']) as $campo) {
                $rules[$campo] = 'required|integer|min:1|max:3';
            }
        }
        $validated = $request->validate($rules);

        $calc = $this->calcular($validated);

        $estado = ($user->role_id == 2)
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        EvaluacionPercepcion::updateOrCreate(
            ['zona_id' => $zonaId],
            array_merge($validated, $calc, [
                'user_id' => $user->id,
                'estado'  => $estado,
            ])
        );

        $mensaje = ($estado === 'confirmado')
            ? 'Matriz de Percepción VALIDADA correctamente. Percepción total: ' . number_format($calc['percepcion_total'] * 100, 2) . '%'
            : 'Borrador guardado. Percepción total: ' . number_format($calc['percepcion_total'] * 100, 2) . '%';

        return redirect()
            ->route('operativo.evaluacion_percepcion.ponderacion', $zonaId)
            ->with('success', $mensaje);
    }

    public function ponderacion($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionPercepcion::where('zona_id', $zonaId)->firstOrFail();
        $categorias = self::$categorias;

        return view('operativo.evaluacion_percepcion.ponderacion',
            compact('zona', 'evaluacion', 'categorias'));
    }

    private function calcular(array $v): array
    {
        $result = [];
        $total  = 0;

        $mapaCategorias = [
            'DS' => ['media' => 'media_ds', 'pond' => 'pond_ds'],
            'PL' => ['media' => 'media_pl', 'pond' => 'pond_pl'],
            'PE' => ['media' => 'media_pe', 'pond' => 'pond_pe'],
            'NO' => ['media' => 'media_no', 'pond' => 'pond_no'],
        ];

        foreach (self::$categorias as $codigo => $cat) {
            $campos = array_keys($cat['items']);
            $valores = array_map(fn($c) => (float) ($v[$c] ?? 0), $campos);
            $media   = count($valores) > 0 ? array_sum($valores) / count($valores) : 0;
            // Ponderado normalizado (0-1): (peso * media) / 3
            $pond    = ($cat['peso'] * $media) / 3;

            $result[$mapaCategorias[$codigo]['media']] = round($media, 3);
            $result[$mapaCategorias[$codigo]['pond']]  = round($pond, 3);

            $total += $pond;
        }

        $result['percepcion_total'] = round($total, 3);

        return $result;
    }
}
