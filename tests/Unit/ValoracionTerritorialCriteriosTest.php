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

    /**
     * Los pesos suman 1.0 aunque cualquiera de los 21 esté mal (por ejemplo
     * 0.1 en vez de 0.05, compensado por un 0.15 en vez de 0.1 en otro
     * criterio): esa suma no distingue un instrumento correcto de uno con
     * pesos trastocados. Como la razón de generar esta clase desde el Excel
     * era justamente la fidelidad de esos 21 valores, hace falta fijar el
     * mapa completo campo => peso.
     *
     * Los valores de abajo se verificaron a mano contra las hojas 'Valoración
     * CT' y 'Valoración UC' de Documentación/Matriz de Valoración
     * Territorial.xlsx (columnas F=sigla, B=peso), leyendo el Excel con un
     * script independiente del generador (database/matrices/generar_valoracion_territorial.py),
     * no copiados del PHP generado.
     */
    public function test_el_peso_de_cada_criterio_coincide_con_el_instrumento(): void
    {
        $pesosCt = [
            'ct_energia_electrica'     => 0.1,
            'ct_agua_potable'          => 0.1,
            'ct_comunicacion'          => 0.05,
            'ct_recoleccion_basura'    => 0.1,
            'ct_problemas_sociales'    => 0.05,
            'ct_salud'                 => 0.05,
            'ct_seguridad'             => 0.05,
            'ct_conservacion_recursos' => 0.1,
            'ct_actividad_economica'   => 0.1,
            'ct_organizacion_social'   => 0.1,
            'ct_elementos_culturales'  => 0.1,
            'ct_espacios_naturales'    => 0.1,
        ];

        $pesosUc = [
            'uc_vialidad'                      => 0.15,
            'uc_infraestructura_conectividad'  => 0.1,
            'uc_frecuencia_conectividad'       => 0.1,
            'uc_distancia_atractivo'           => 0.1,
            'uc_distancia_sitio_visita'        => 0.1,
            'uc_distancia_destino'             => 0.1,
            'uc_distancia_mercado_emisor'      => 0.1,
            'uc_conglomeracion_oferta'         => 0.15,
            'uc_senalizacion'                  => 0.1,
        ];

        $this->assertSame($pesosCt, array_map(fn($c) => $c['peso'], ValoracionTerritorial::CT));
        $this->assertSame($pesosUc, array_map(fn($c) => $c['peso'], ValoracionTerritorial::UC));
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
