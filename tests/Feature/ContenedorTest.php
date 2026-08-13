<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * <x-contenedor> no deriva nada: recibe un ancho por nombre y pinta.
 * Mismo patrón que ResumenListaTest, y por el mismo motivo: un componente
 * que solo pinta se prueba pintándolo.
 */
class ContenedorTest extends TestCase
{
    use RefreshDatabase;

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

    /**
     * El padding por defecto: es lo que separa el contenido del borde de la
     * ventana en móvil, así que quitarlo sin querer no se ve en escritorio.
     */
    public function test_por_defecto_trae_su_padding_lateral(): void
    {
        $html = (string) $this->blade('<x-contenedor>contenido</x-contenedor>');

        $this->assertStringContainsString('px-4', $html);
        $this->assertStringContainsString('sm:px-6', $html);
        $this->assertStringContainsString('lg:px-8', $html);
    }

    /**
     * Anidado dentro del contenedor del layout, el padding se aplicaría dos
     * veces: 311px de ancho útil en vez de 343 en una pantalla de 375.
     *
     * Pasa en las dos vistas que son estrechas a propósito —un formulario de
     * cuatro campos a 1440px es peor, no mejor—, así que no se arreglan
     * borrando el contenedor interior, que las mandaría a 1440. Se arreglan
     * quitándole el padding, que es lo único que sobra al anidar.
     *
     * Mismo prop y misma razón que <x-tarjeta :padding="false">, que existe
     * desde FV6 para las tarjetas que envuelven una tabla a sangre.
     */
    public function test_sin_padding_no_lo_aplica_dos_veces_al_anidarse(): void
    {
        $html = (string) $this->blade('<x-contenedor :padding="false">contenido</x-contenedor>');

        $this->assertStringNotContainsString('px-4', $html);
        $this->assertStringNotContainsString('sm:px-6', $html);
        $this->assertStringNotContainsString('lg:px-8', $html);

        // El ancho sí se conserva: es lo único que se quería del anidamiento.
        $this->assertStringContainsString('max-w-[1440px]', $html);
        $this->assertStringContainsString('mx-auto', $html);
    }

    /** Las clases propias se suman a las del contenedor, no lo sustituyen. */
    public function test_admite_clases_extra_sin_perder_las_suyas(): void
    {
        $html = (string) $this->blade('<x-contenedor class="py-12">contenido</x-contenedor>');

        $this->assertStringContainsString('py-12', $html);
        $this->assertStringContainsString('max-w-[1440px]', $html);
    }

    /**
     * El contenedor está montado en el layout, no solo escrito.
     *
     * Los tests de arriba prueban el componente en aislado, y con eso bastaba
     * mientras seis tests de matriz comprobaban `max-w-7xl` en la página. Al
     * quitarles esa aserción —afirmaban una clase, no un comportamiento— se
     * quedó un hueco: se podía borrar <x-contenedor> de layouts/app.blade.php
     * y **toda la suite seguía verde**, con la aplicación entera sin ancho ni
     * márgenes laterales, que es lo primero que ve cualquiera al abrirla.
     *
     * Una página cualquiera basta: el contenedor vive en el layout, así que si
     * sale en una, sale en todas.
     */
    public function test_el_layout_monta_el_contenedor_en_una_pagina_de_verdad(): void
    {
        $this->seed(SystemSeeder::class);

        $usuario = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $html = $this->actingAs($usuario)->get('/mis-zonas')->assertOk()->getContent();

        // Se afirma sobre el <main> y no sobre la página entera a propósito:
        // la barra de navegación y la cabecera llevan su propio contenedor,
        // así que buscar la cadena suelta pasaba en verde aunque el del
        // cuerpo no estuviera. Comprobado quitándolo: pasaba igual.
        $this->assertMatchesRegularExpression(
            '/<main[^>]*>\s*<div class="[^"]*max-w-\[1440px\]/',
            $html,
            'El <main> no abre con el contenedor: el cuerpo de la página se quedó sin ancho.'
        );
    }
}
