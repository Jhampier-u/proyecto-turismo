<?php

namespace App\Http\Controllers\Operativo;

use App\Models\EvaluacionFit;
use App\Models\User;
use App\Models\VocacionTuristicaTerritorio;
use App\Models\Zona;

class EvaluacionFitController extends MatrizPonderadaController
{
    protected function criterios(): array
    {
        return [
            'rtt' => ['recursos_culturales', 'recursos_naturales'],
            'at'  => ['atractivos_manifestaciones', 'atractivos_sitios'],
            'pst' => ['prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza'],
            'ptt' => ['productos_territoriales'],
            'i'   => ['infraestructura_basica', 'infraestructura_apoyo'],
            'ft'  => [
                'facilidades_senaletica', 'facilidades_recepcion',
                'facilidades_interpretacion', 'facilidades_senderos',
                'facilidades_estacionamientos', 'facilidades_campamentos',
                'facilidades_miradores', 'facilidades_sanitarios',
            ],
        ];
    }

    protected function escala(): array
    {
        return [0, 3];
    }

    /** Peso de cada bloque sobre el total. Suman 1.0. */
    private const PESOS = [
        'rtt' => 0.30, 'at' => 0.05, 'pst' => 0.20,
        'ptt' => 0.05, 'i'  => 0.20, 'ft'  => 0.20,
    ];

    protected function calcular(array $valores): array
    {
        $resultado = [];
        $total = 0.0;

        foreach ($this->criterios() as $bloque => $campos) {
            $media = array_sum(array_map(fn($c) => $valores[$c], $campos)) / count($campos);
            $ponderado = $media * self::PESOS[$bloque];

            $resultado["media_{$bloque}"] = $media;
            $resultado["fit_{$bloque}"]   = $ponderado;

            $total += $ponderado;
        }

        $resultado['fit'] = $total;

        return $resultado;
    }

    protected function modelo(): string
    {
        return EvaluacionFit::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_fit.ponderacion';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación FIT VALIDADA y CERRADA correctamente.'
            : 'Borrador FIT guardado. Total: ' . number_format($datos['fit'], 2);
    }

    protected function mensajeCerrada(): string
    {
        return 'Evaluación cerrada. No puedes editar.';
    }

    protected function despuesDeGuardar($zonaId, User $user): void
    {
        VocacionTuristicaTerritorio::registrar($zonaId, $user->id);
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFit::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fit.form', compact('zona', 'evaluacion'));
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fit  = EvaluacionFit::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_fit.ponderacion', compact('zona', 'fit'));
    }
}
