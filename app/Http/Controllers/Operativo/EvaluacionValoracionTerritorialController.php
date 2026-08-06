<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\ValoracionTerritorial;
use App\Models\EvaluacionValoracionTerritorial;
use App\Models\Zona;

class EvaluacionValoracionTerritorialController extends MatrizPonderadaController
{
    protected function modelo(): string
    {
        return EvaluacionValoracionTerritorial::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_valoracion_territorial.ponderacion';
    }

    protected function escala(): array
    {
        return [ValoracionTerritorial::ESCALA_MIN, ValoracionTerritorial::ESCALA_MAX];
    }

    protected function criterios(): array
    {
        return [
            'ct' => array_keys(ValoracionTerritorial::CT),
            'uc' => array_keys(ValoracionTerritorial::UC),
        ];
    }

    /**
     * Suma ponderada por dimensión, con los pesos del instrumento original.
     * Como cada dimensión suma 1 y la escala es 0-2, cada total va de 0 a 2.
     */
    protected function calcular(array $valores): array
    {
        $dimensiones = ['ct' => ValoracionTerritorial::CT, 'uc' => ValoracionTerritorial::UC];

        $totales = [];

        foreach ($dimensiones as $dimension => $criterios) {
            $totales["{$dimension}_total"] = array_sum(array_map(
                fn($campo, $criterio) => $valores[$campo] * $criterio['peso'],
                array_keys($criterios),
                $criterios
            ));
        }

        return $totales;
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        $ct = number_format($datos['ct_total'], 2);
        $uc = number_format($datos['uc_total'], 2);

        return $estado === 'confirmado'
            ? "Valoración Territorial VALIDADA. CT: {$ct} | UC: {$uc}"
            : "Borrador guardado. CT: {$ct} | UC: {$uc}";
    }

    protected function mensajeCerrada(): string
    {
        return 'Esta evaluación de Valoración Territorial ya fue validada por el Jefe de Zona. No puedes editarla.';
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionValoracionTerritorial::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_valoracion_territorial.form', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'ct'         => ValoracionTerritorial::CT,
            'uc'         => ValoracionTerritorial::UC,
        ]);
    }

    public function ponderacion($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        // ->first() y no ->firstOrFail(): la vista ya contempla el caso sin
        // evaluación (ver ZonaController::valoracionTerritorial(), que sirve la
        // misma vista al admin), así que ambas rutas comparten el mismo trato en
        // vez de que una dé un 404 crudo y la otra un aviso legible.
        $evaluacion = EvaluacionValoracionTerritorial::where('zona_id', $zonaId)->first();

        return view('operativo.evaluacion_valoracion_territorial.ponderacion', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'ct'         => ValoracionTerritorial::CT,
            'uc'         => ValoracionTerritorial::UC,
        ]);
    }
}
