<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El `type` de los dos botones del diálogo de borrar la cuenta.
 *
 * Existe por un riesgo concreto de la conversión de los botones de Breeze a
 * <x-boton>: los dos componentes tienen defaults OPUESTOS.
 * <x-secondary-button> lleva type="button" y <x-boton> lleva type="submit",
 * porque el segundo nace para sustituir <button type="submit"> sin cambiar
 * nada. El «Cancelar» del diálogo vive DENTRO del <form> que borra la cuenta,
 * así que convertirlo sin poner el type a mano hace que cancelar borre la
 * cuenta.
 *
 * Y ningún test de los que ya había lo vería: el HTML resultante es válido,
 * la página responde 200, y el clic solo se da en un navegador de verdad.
 * Es el mismo fallo que FV9 anticipó al añadir un séptimo test a <x-boton>
 * por el type por defecto, aquí en su forma más cara.
 *
 * Se afirma sobre la página servida, no sobre el componente en aislado: lo
 * que importa no es que <x-boton> sepa recibir un type, sino que esta vista
 * se lo pase.
 */
class BotonesPerfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_boton_de_cancelar_no_envia_el_formulario_de_borrado(): void
    {
        $boton = $this->botonDelPerfilConTexto('Cancel');

        $this->assertStringContainsString(
            'type="button"',
            $boton,
            'El «Cancelar» del diálogo de borrar la cuenta no es type="button": '
            . 'está dentro del <form> de borrado, así que cancelar borraría la cuenta.'
        );
    }

    public function test_el_boton_de_confirmar_si_envia_el_formulario_de_borrado(): void
    {
        // La contraparte del anterior. Sin ella, poner type="button" en los dos
        // dejaría el test de arriba en verde y el diálogo sin forma de borrar.
        $boton = $this->botonDelPerfilConTexto('Delete Account', dentroDelDialogo: true);

        $this->assertStringContainsString(
            'type="submit"',
            $boton,
            'El botón de confirmar del diálogo no envía su formulario.'
        );
    }

    /**
     * Devuelve la etiqueta <button> completa cuyo contenido incluye $texto.
     *
     * El diálogo tiene dos «Delete Account» —el que lo abre, fuera de todo
     * formulario, y el que confirma dentro del <form>—; $dentroDelDialogo
     * coge el segundo, que es el que tiene un formulario que enviar.
     */
    private function botonDelPerfilConTexto(string $texto, bool $dentroDelDialogo = false): string
    {
        $usuario = User::factory()->create();

        $html = $this->actingAs($usuario)->get('/profile')->assertOk()->getContent();

        preg_match_all('/<button\b[^>]*>.*?<\/button>/s', $html, $coincidencias);

        $botones = array_values(array_filter(
            $coincidencias[0],
            fn (string $boton): bool => str_contains($boton, $texto)
        ));

        $this->assertNotEmpty(
            $botones,
            "No hay ningún <button> con el texto «{$texto}» en /profile."
        );

        return $dentroDelDialogo ? end($botones) : $botones[0];
    }
}
