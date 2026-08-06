<?php

namespace Tests\Unit;

use App\Matrices\ValoracionTerritorial;
use PHPUnit\Framework\TestCase;

class ValoracionTerritorialCriteriosTest extends TestCase
{
    public function test_los_pesos_de_cada_dimension_suman_uno(): void
    {
        $this->assertEqualsWithDelta(1.0, array_sum(array_column(ValoracionTerritorial::CT, 'peso')), 0.0001);
        $this->assertEqualsWithDelta(1.0, array_sum(array_column(ValoracionTerritorial::UC, 'peso')), 0.0001);
    }

    public function test_el_numero_de_criterios_coincide_con_el_instrumento(): void
    {
        $this->assertCount(12, ValoracionTerritorial::CT);
        $this->assertCount(9, ValoracionTerritorial::UC);
        $this->assertCount(21, ValoracionTerritorial::todos());
    }

    public function test_cada_criterio_describe_sus_tres_niveles(): void
    {
        foreach (ValoracionTerritorial::todos() as $campo => $criterio) {
            $this->assertArrayHasKey('nombre', $criterio, $campo);
            $this->assertCount(3, $criterio['niveles'], $campo);

            foreach ([0, 1, 2] as $nivel) {
                $this->assertNotEmpty($criterio['niveles'][$nivel], "{$campo} nivel {$nivel}");
            }
        }
    }
}
