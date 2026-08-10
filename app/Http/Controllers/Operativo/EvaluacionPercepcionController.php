<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\Percepcion;
use App\Models\EvaluacionPercepcion;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EvaluacionPercepcionController extends MatrizPonderadaController
{
    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionPercepcion::firstOrNew(['zona_id' => $zonaId]);
        $categorias = Percepcion::$categorias;
        $niveles    = Percepcion::NIVELES;

        return view('operativo.evaluacion_percepcion.form',
            compact('zona', 'evaluacion', 'categorias', 'niveles'));
    }

    public function ponderacion($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionPercepcion::where('zona_id', $zonaId)->firstOrFail();
        $categorias = Percepcion::$categorias;

        return view('operativo.evaluacion_percepcion.ponderacion',
            compact('zona', 'evaluacion', 'categorias'));
    }

    protected function modelo(): string
    {
        return EvaluacionPercepcion::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_percepcion.ponderacion';
    }

    protected function escala(): array
    {
        return [Percepcion::ESCALA_MIN, Percepcion::ESCALA_MAX];
    }

    protected function criterios(): array
    {
        $criterios = [];
        foreach (Percepcion::$categorias as $codigo => $cat) {
            $criterios[strtolower($codigo)] = array_keys($cat['items']);
        }

        return $criterios;
    }

    /**
     * Las 16 etiquetas del instrumento, tomadas de Percepcion::todos() y no
     * copiadas. Antes de esta migración ya se leían de self::$categorias
     * -Percepción nunca estuvo entre las matrices sin etiqueta, a diferencia
     * de FIT/FET-; lo único que cambia es que la definición ya no vive en
     * este controlador.
     */
    protected function etiquetas(): array
    {
        return Percepcion::todos();
    }

    /** Añade el campo de texto libre, que no es un criterio puntuable. */
    protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array
    {
        $datos = parent::prepararDatos($request, $zonaId, $actual, $estado);

        $extra = $request->validate(
            ['acciones_mejora' => 'nullable|string|max:5000'],
            [],
            ['acciones_mejora' => 'Acciones de mejora']
        );

        return $datos + $extra;
    }

    protected function calcular(array $valores): array
    {
        return $this->calcularPercepcion($valores);
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        $pct = number_format($datos['percepcion_total'] * 100, 2);

        return $estado === 'confirmado'
            ? "Matriz de Percepción VALIDADA correctamente. Percepción total: {$pct}%"
            : "Borrador guardado. Percepción total: {$pct}%";
    }

    protected function mensajeCerrada(): string
    {
        return 'Evaluación cerrada. No puedes editar.';
    }

    private function calcularPercepcion(array $v): array
    {
        $result = [];
        $total  = 0;

        $mapaCategorias = [
            'DS' => ['media' => 'media_ds', 'pond' => 'pond_ds'],
            'PL' => ['media' => 'media_pl', 'pond' => 'pond_pl'],
            'PE' => ['media' => 'media_pe', 'pond' => 'pond_pe'],
            'NO' => ['media' => 'media_no', 'pond' => 'pond_no'],
        ];

        foreach (Percepcion::$categorias as $codigo => $cat) {
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
