<?php

namespace App\Http\Controllers\Operativo;

use App\Models\EvaluacionFet;
use App\Models\User;
use App\Models\VocacionTuristicaTerritorio;
use App\Models\Zona;

class EvaluacionFetController extends MatrizPonderadaController
{
    protected function criterios(): array
    {
        return [
            'demanda' => ['demanda_flujos', 'demanda_estadia'],
            'super'   => ['super_institucionalidad', 'super_organizacion', 'super_planificacion'],
            'imagen'  => ['imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing'],
        ];
    }

    protected function escala(): array
    {
        return [0, 3];
    }

    /** Peso de cada bloque sobre el total. Suman 1.0. */
    private const PESOS = ['demanda' => 0.20, 'super' => 0.40, 'imagen' => 0.40];

    protected function calcular(array $valores): array
    {
        $resultado = [];
        $total = 0.0;

        foreach ($this->criterios() as $bloque => $campos) {
            $media = array_sum(array_map(fn($c) => $valores[$c], $campos)) / count($campos);
            $ponderado = $media * self::PESOS[$bloque];

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

    protected function despuesDeGuardar($zonaId, User $user): void
    {
        VocacionTuristicaTerritorio::registrar($zonaId, $user->id);
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFet::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fet.form', compact('zona', 'evaluacion'));
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fet  = EvaluacionFet::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_fet.ponderacion', compact('zona', 'fet'));
    }
}
