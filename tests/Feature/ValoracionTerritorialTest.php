<?php

namespace Tests\Feature;

use App\Models\EvaluacionValoracionTerritorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValoracionTerritorialTest extends TestCase
{
    use RefreshDatabase;

    public static function cuadrantes(): array
    {
        return [
            'ambos bajos'      => [0.5, 0.5, 'Territorio No Apto para el Turismo'],
            'solo conectado'   => [0.5, 1.5, 'Territorio con Limitación II'],
            'solo contenido'   => [1.5, 0.5, 'Territorio con Limitación III'],
            'ambos altos'      => [1.5, 1.5, 'Territorio a Priorizar para el Turismo IV'],
            'justo en el umbral' => [1.0, 1.0, 'Territorio a Priorizar para el Turismo IV'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cuadrantes')]
    public function test_el_cuadrante_se_deriva_de_los_totales(float $ct, float $uc, string $esperado): void
    {
        $evaluacion = new EvaluacionValoracionTerritorial([
            'ct_total' => $ct,
            'uc_total' => $uc,
        ]);

        $this->assertSame($esperado, $evaluacion->cuadrante);
    }
}
