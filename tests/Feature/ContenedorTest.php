<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-contenedor> no deriva nada: recibe un ancho por nombre y pinta.
 * Mismo patrón que ResumenListaTest, y por el mismo motivo: un componente
 * que solo pinta se prueba pintándolo.
 */
class ContenedorTest extends TestCase
{
    public function test_el_ancho_normal_llega_a_1440(): void
    {
        $html = (string) $this->blade('<x-contenedor>contenido</x-contenedor>');

        $this->assertStringContainsString('max-w-[1440px]', $html);
        $this->assertStringContainsString('contenido', $html);
    }

    /** Fluido: en pantallas menores que el tope usa todo el ancho disponible. */
    public function test_es_fluido_hasta_el_tope(): void
    {
        $html = (string) $this->blade('<x-contenedor>contenido</x-contenedor>');

        $this->assertStringContainsString('w-full', $html);
        $this->assertStringContainsString('mx-auto', $html);
    }

    public function test_el_estrecho_se_pide_a_proposito(): void
    {
        $html = (string) $this->blade('<x-contenedor ancho="estrecho">contenido</x-contenedor>');

        $this->assertStringContainsString('max-w-2xl', $html);
        $this->assertStringNotContainsString('max-w-[1440px]', $html);
    }

    /**
     * Un ancho mal escrito tiene que reventar, no caer en el ancho por
     * defecto: un contenedor silenciosamente normal donde se pidió estrecho
     * es un fallo que solo se ve mirando la página, y nadie la mira.
     */
    public function test_un_ancho_desconocido_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('ancho «gigante» desconocido');

        $this->blade('<x-contenedor ancho="gigante">contenido</x-contenedor>');
    }

    /** Las clases propias se suman a las del contenedor, no lo sustituyen. */
    public function test_admite_clases_extra_sin_perder_las_suyas(): void
    {
        $html = (string) $this->blade('<x-contenedor class="py-12">contenido</x-contenedor>');

        $this->assertStringContainsString('py-12', $html);
        $this->assertStringContainsString('max-w-[1440px]', $html);
    }
}
