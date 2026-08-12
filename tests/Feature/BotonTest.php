<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-boton> sustituye 12 variantes escritas a mano del mismo botón primario.
 *
 * Con href da un <a> y sin él un <button>, porque hoy la mitad de los
 * «botones» del sistema son enlaces: esa diferencia es de comportamiento, no
 * de estilo, y el estilo no tiene por qué enterarse.
 */
class BotonTest extends TestCase
{
    public function test_sin_href_es_un_boton(): void
    {
        $html = (string) $this->blade('<x-boton>Guardar</x-boton>');

        $this->assertStringContainsString('<button', $html);
        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('Guardar', $html);
    }

    public function test_con_href_es_un_enlace_con_el_mismo_aspecto(): void
    {
        $html = (string) $this->blade('<x-boton href="/zonas">Ver zonas</x-boton>');

        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('href="/zonas"', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    public function test_las_tres_variantes_no_se_parecen(): void
    {
        $clases = [];

        foreach (['primario', 'secundario', 'peligro'] as $variante) {
            $clases[$variante] = (string) $this->blade(
                '<x-boton :variante="$v">Acción</x-boton>',
                ['v' => $variante]
            );
        }

        $this->assertNotSame($clases['primario'], $clases['secundario']);
        $this->assertNotSame($clases['primario'], $clases['peligro']);
        $this->assertNotSame($clases['secundario'], $clases['peligro']);
    }

    public function test_el_tamano_grande_es_mas_grande(): void
    {
        $normal = (string) $this->blade('<x-boton>Acción</x-boton>');
        $grande = (string) $this->blade('<x-boton tamano="grande">Acción</x-boton>');

        $this->assertNotSame($normal, $grande);
        $this->assertStringContainsString('px-6', $grande);
    }

    public function test_una_variante_desconocida_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('variante «morado» desconocida');

        $this->blade('<x-boton variante="morado">Acción</x-boton>');
    }

    public function test_un_tamano_desconocido_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('tamaño «enorme» desconocido');

        $this->blade('<x-boton tamano="enorme">Acción</x-boton>');
    }

    /**
     * El `type="submit"` por defecto es lo que hace que sustituir un
     * `<button type="submit">` por `<x-boton>` no cambie nada, y que sea
     * sobreescribible es lo que permite los `type="button"` de Alpine.
     *
     * Sin esto, un botón dentro de un formulario que solo debía abrir un menú
     * lo enviaría, y ningún test de vista lo vería: el HTML sigue siendo
     * válido.
     */
    public function test_el_tipo_por_defecto_es_submit_y_se_puede_cambiar(): void
    {
        $porDefecto = (string) $this->blade('<x-boton>Guardar</x-boton>');
        $forzado    = (string) $this->blade('<x-boton type="button">Abrir</x-boton>');

        $this->assertStringContainsString('type="submit"', $porDefecto);
        $this->assertStringContainsString('type="button"', $forzado);
        $this->assertStringNotContainsString('type="submit"', $forzado);
    }
}
