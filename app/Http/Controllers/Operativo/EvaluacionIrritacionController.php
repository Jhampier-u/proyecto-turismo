<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\Irritacion;
use App\Models\EvaluacionIrritacion;
use App\Models\Zona;

/**
 * Índice de Irritación Turística.
 *
 * Dos encuestas paralelas de seis atributos, una a los visitantes y otra a la
 * localidad receptora, con **escala inversa**: 0 es el mejor caso y 10 la
 * irritación crítica. No hay pesos —el instrumento promedia por igual— ni un
 * índice combinado: cruzar los dos bloques mezclaría dos poblaciones distintas
 * en una cifra que no significa nada.
 */
class EvaluacionIrritacionController extends MatrizPonderadaController
{
    protected function modelo(): string
    {
        return EvaluacionIrritacion::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_irritacion.ponderacion';
    }

    protected function escala(): array
    {
        return [Irritacion::ESCALA_MIN, Irritacion::ESCALA_MAX];
    }

    protected function criterios(): array
    {
        return [
            'visitantes' => Irritacion::VISITANTES,
            'residentes' => Irritacion::RESIDENTES,
        ];
    }

    /**
     * Media simple de cada bloque. Sin pesos: el instrumento no los tiene.
     *
     * La clase base solo llama aquí con la matriz completa; con algún atributo
     * sin responder deja los dos promedios en null y no se llega a esta línea.
     */
    protected function calcular(array $valores): array
    {
        $media = fn(array $campos) => array_sum(
            array_map(fn(string $campo) => (float) $valores[$campo], $campos)
        ) / count($campos);

        return [
            'visitantes_promedio' => $media(Irritacion::VISITANTES),
            'residentes_promedio' => $media(Irritacion::RESIDENTES),
        ];
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        $vis = number_format($datos['visitantes_promedio'], 2);
        $res = number_format($datos['residentes_promedio'], 2);

        return $estado === 'confirmado'
            ? "Índice de Irritación VALIDADO. Visitantes: {$vis} | Residentes: {$res}"
            : "Borrador guardado. Visitantes: {$vis} | Residentes: {$res}";
    }

    protected function mensajeCerrada(): string
    {
        return 'Este Índice de Irritación ya fue validado por el Jefe de Zona. No puedes editarlo.';
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionIrritacion::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_irritacion.form', compact('zona', 'evaluacion'));
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        // ->first() y no ->firstOrFail(): se puede llegar a esta URL antes de
        // completar la matriz, y la vista ya contempla ese caso con un aviso.
        $evaluacion = EvaluacionIrritacion::where('zona_id', $zonaId)->first();

        return view('operativo.evaluacion_irritacion.ponderacion', compact('zona', 'evaluacion'));
    }
}
