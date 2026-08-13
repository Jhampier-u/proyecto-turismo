<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * El contrato de <x-desglose-estados>, fijado sobre el componente y no sobre
 * el dashboard que lo usa.
 *
 * No necesita base de datos: recibe un array de cuatro cifras, que es
 * exactamente lo que devuelve EstadoZona::progresoDe() por zona.
 */
class DesgloseEstadosTest extends TestCase
{
    private function render(int $hechas, int $borradores, int $sinEmpezar): string
    {
        return (string) $this->blade(
            '<x-desglose-estados :progreso="$progreso" />',
            ['progreso' => [
                'hechas'      => $hechas,
                'borradores'  => $borradores,
                'sin_empezar' => $sinEmpezar,
                'total'       => $hechas + $borradores + $sinEmpezar,
            ]]
        );
    }

    public function test_pinta_los_tres_estados_con_su_numero(): void
    {
        $html = $this->render(3, 1, 6);

        $this->assertStringContainsString('3 validadas', $html);
        $this->assertStringContainsString('1 en borrador', $html);
        $this->assertStringContainsString('6 sin empezar', $html);
    }

    /**
     * Un estado a cero no se pinta: «0 en borrador» ocupa sitio para no decir
     * nada.
     */
    public function test_un_estado_a_cero_no_se_pinta(): void
    {
        $html = $this->render(10, 0, 0);

        $this->assertStringContainsString('10 validadas', $html);
        $this->assertStringNotContainsString('en borrador', $html);
        $this->assertStringNotContainsString('sin empezar', $html);
    }

    /**
     * El orden es fijo —validadas, borrador, sin empezar— y no depende de
     * cuál tenga más. Las tres suman el total, así que leerlas siempre en el
     * mismo sitio es lo que las convierte en un reparto y no en tres cifras
     * sueltas.
     */
    public function test_el_orden_de_las_insignias_es_fijo(): void
    {
        $html = $this->render(1, 2, 7);

        $this->assertLessThan(strpos($html, '2 en borrador'), strpos($html, '1 validadas'));
        $this->assertLessThan(strpos($html, '7 sin empezar'), strpos($html, '2 en borrador'));
    }

    /**
     * Los colores salen de <x-badge>, que los lee de
     * EstadoZona::ESTILOS_ESTADO. Aquí no hay ni un color escrito a mano, y
     * este test es lo que lo sostiene: cada insignia lleva el color de SU
     * estado, que es lo que hace honesto no tener una insignia de «zona
     * terminada».
     */
    public function test_cada_insignia_lleva_el_color_de_su_estado(): void
    {
        $html = $this->render(1, 1, 8);

        $estilos = \App\Servicios\EstadoZona::ESTILOS_ESTADO;

        $this->assertStringContainsString($estilos['validada']['insignia'], $html);
        $this->assertStringContainsString($estilos['borrador']['insignia'], $html);
        $this->assertStringContainsString($estilos['sin_empezar']['insignia'], $html);
    }
}
