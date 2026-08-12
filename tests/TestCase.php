<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Cuántos campos de la página están deshabilitados de verdad.
     *
     * Existe porque cuatro tests contaban `substr_count($html, 'disabled')`,
     * que cuenta la palabra y no el atributo. Funcionó mientras ninguna clase
     * de Tailwind la contuviera; en cuanto `<x-boton>` trajo
     * `disabled:opacity-50`, el recuento se infló y los cuatro se pusieron
     * rojos con la página perfectamente correcta.
     *
     * El patrón exige un espacio delante —para no casar con `x-bind:disabled`—
     * y prohíbe que detrás venga `:` o un guion, que es lo que distingue el
     * atributo `disabled` de las variantes `disabled:algo` de Tailwind.
     */
    protected function contarDeshabilitados(string $html): int
    {
        return preg_match_all('/\sdisabled(?![:\w-])/', $html);
    }
}
