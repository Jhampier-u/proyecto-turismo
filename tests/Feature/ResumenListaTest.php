<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-resumen-lista> no deriva nada —cada vista le pasa sus números ya
 * resueltos—, así que se prueba en aislado con datos de mentira, sin necesitar
 * ni Involucrados ni Frecuentación.
 *
 * Mismo patrón que BarraLateralFormularioTest, y por el mismo motivo: un
 * componente que solo pinta se prueba pintándolo.
 */
class ResumenListaTest extends TestCase
{
    private function renderizar(array $props = [], string $ranura = ''): string
    {
        $props = array_merge([
            'sustantivo'      => 'sitio',
            'plural'          => 'sitios',
            'faltante'        => 'sin DET',
            'total'           => 5,
            'incompletos'     => 2,
            'puedeValidar'    => false,
            'rutaValidar'     => '/validar',
            'avisoValidacion' => false,
            'jefe'            => 'Ana Pérez',
        ], $props);

        return (string) $this->blade(
            '<x-resumen-lista :sustantivo="$sustantivo" :plural="$plural" :faltante="$faltante"
                              :total="$total" :incompletos="$incompletos"
                              :puede-validar="$puedeValidar" :ruta-validar="$rutaValidar"
                              :aviso-validacion="$avisoValidacion" :jefe="$jefe">'
            . $ranura .
            '</x-resumen-lista>',
            $props
        );
    }

    public function test_cuenta_el_total_y_lo_que_falta(): void
    {
        $html = $this->renderizar();

        $this->assertStringContainsString('5 sitios', $html);
        $this->assertStringContainsString('2 sin DET', $html);
    }

    public function test_con_todo_completo_lo_dice_en_vez_de_callar(): void
    {
        $html = $this->renderizar(['incompletos' => 0]);

        $this->assertStringContainsString('5 sitios', $html);
        $this->assertStringNotContainsString('sin DET', $html);
        $this->assertStringContainsString('todos completos', $html);
    }

    /**
     * Con la lista vacía no se dice «todos completos»: no hay nada completo, y
     * afirmarlo invitaría a validar algo que no se puede.
     */
    public function test_con_la_lista_vacia_no_dice_que_este_todo_completo(): void
    {
        $html = $this->renderizar(['total' => 0, 'incompletos' => 0]);

        $this->assertStringContainsString('0 sitios', $html);
        $this->assertStringNotContainsString('todos completos', $html);
    }

    public function test_el_singular_no_dice_1_sitios(): void
    {
        $html = $this->renderizar(['total' => 1, 'incompletos' => 0]);

        $this->assertStringContainsString('1 sitio', $html);
        $this->assertStringNotContainsString('1 sitios', $html);
    }

    /**
     * total viaja como cadena cuando una vista lo pasa por un atributo Blade
     * sin ":" -"total="1"" en vez de ":total="1""-, y en PHP '1' === 1 es
     * falso: sin convertir a entero antes de comparar, el singular se
     * perdería en silencio y nadie lo notaría hasta verlo en pantalla. El
     * test anterior no lo cubre porque pasa 1 ya como entero.
     */
    public function test_el_singular_no_dice_1_sitios_con_total_en_cadena(): void
    {
        $html = $this->renderizar(['total' => '1', 'incompletos' => 0]);

        $this->assertStringContainsString('1 sitio', $html);
        $this->assertStringNotContainsString('1 sitios', $html);
    }

    /** El plural del castellano no es añadir una s: actor da actores. */
    public function test_el_plural_se_puede_dar_a_mano(): void
    {
        $html = $this->renderizar([
            'sustantivo' => 'actor',
            'plural'     => 'actores',
            'total'      => 3,
        ]);

        $this->assertStringContainsString('3 actores', $html);
        $this->assertStringNotContainsString('3 actors', $html);
    }

    public function test_faltante_vale_sin_completar_por_defecto(): void
    {
        $html = (string) $this->blade(
            '<x-resumen-lista sustantivo="actor" plural="actores" :total="3" :incompletos="1" />'
        );

        $this->assertStringContainsString('1 sin completar', $html);
    }

    public function test_el_jefe_ve_el_boton_de_validar(): void
    {
        $html = $this->renderizar(['puedeValidar' => true, 'incompletos' => 0]);

        $this->assertStringContainsString('Validar y Cerrar la Lista', $html);
        $this->assertStringContainsString('/validar', $html);
    }

    /**
     * Hallazgo 4: con puedeValidar cierto y sin rutaValidar, el formulario
     * saldría con action="" -la URL del propio índice, que solo tiene GET-,
     * y el POST moriría en un 405 silencioso. Mismo patrón de guardia que
     * <x-barra-lateral-formulario>: revienta aquí en vez de adivinar.
     */
    public function test_puede_validar_sin_ruta_revienta(): void
    {
        // Blade envuelve cualquier excepción lanzada al renderizar un
        // componente en ViewException -no deja pasar la original tal
        // cual-, así que se comprueba el mensaje en vez del tipo original.
        // Mismo patrón que BarraLateralFormularioTest.
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage(':ruta-validar es obligatoria cuando :puede-validar es cierto');

        $this->renderizar(['puedeValidar' => true, 'incompletos' => 0, 'rutaValidar' => null]);
    }

    public function test_sin_permiso_no_hay_boton(): void
    {
        $html = $this->renderizar(['puedeValidar' => false]);

        $this->assertStringNotContainsString('Validar y Cerrar la Lista', $html);
    }

    /**
     * El equipo no recibe un botón gris: recibe el texto que dice quién valida.
     * Es la regla global de «sin botones desactivados».
     */
    public function test_el_equipo_ve_a_quien_avisar_en_vez_de_un_boton(): void
    {
        $html = $this->renderizar([
            'puedeValidar'    => false,
            'avisoValidacion' => true,
            'incompletos'     => 0,
        ]);

        $this->assertStringNotContainsString('Validar y Cerrar la Lista', $html);
        $this->assertStringContainsString('avísale a Ana Pérez', $html);
    }

    public function test_la_ranura_se_pinta_cuando_se_le_da_algo(): void
    {
        $this->assertStringContainsString('ST: 1.200', $this->renderizar([], 'ST: 1.200'));
    }

    /** Sin ranura no queda un separador suelto ni un hueco. */
    public function test_sin_ranura_no_queda_rastro(): void
    {
        $html = $this->renderizar();

        $this->assertStringNotContainsString('ST:', $html);
    }
}
