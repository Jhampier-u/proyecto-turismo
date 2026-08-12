<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-tarjeta> es la caja blanca sobre el fondo. Sustituye 52 repeticiones a
 * mano de «bg-white shadow-sm sm:rounded-lg», cada una con su variación.
 */
class TarjetaTest extends TestCase
{
    public function test_es_una_caja_blanca_con_borde_y_sombra_suave(): void
    {
        $html = (string) $this->blade('<x-tarjeta>contenido</x-tarjeta>');

        $this->assertStringContainsString('bg-white', $html);
        $this->assertStringContainsString('border-gray-200/80', $html);
        $this->assertStringContainsString('rounded-xl', $html);
        $this->assertStringContainsString('shadow-sm', $html);
        $this->assertStringContainsString('contenido', $html);
    }

    public function test_trae_su_propio_espaciado(): void
    {
        $html = (string) $this->blade('<x-tarjeta>contenido</x-tarjeta>');

        $this->assertStringContainsString('p-6', $html);
    }

    /**
     * Sin padding para las tarjetas que envuelven una tabla a sangre: con él,
     * la cabecera gris de la tabla queda flotando dentro de un marco blanco.
     */
    public function test_el_espaciado_se_puede_quitar(): void
    {
        $html = (string) $this->blade('<x-tarjeta :padding="false">contenido</x-tarjeta>');

        $this->assertStringNotContainsString('p-6', $html);
        $this->assertStringContainsString('bg-white', $html);
    }

    public function test_admite_clases_extra_sin_perder_las_suyas(): void
    {
        $html = (string) $this->blade('<x-tarjeta class="mb-6">contenido</x-tarjeta>');

        $this->assertStringContainsString('mb-6', $html);
        $this->assertStringContainsString('rounded-xl', $html);
    }
}
