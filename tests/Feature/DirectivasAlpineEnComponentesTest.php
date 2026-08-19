<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Una directiva de Alpine sin valor no puede ir sobre un componente Blade.
 *
 * Blade renderiza `<x-tarjeta x-transition>` como `x-transition="x-transition"`
 * -así normaliza $attributes->merge() los atributos sin valor-, y Alpine
 * intenta interpretar ese texto como el valor de la transición, revienta con
 * un TypeError, y **aborta el recorrido de directivas**: a partir de ahí los
 * x-show de esa página dejan de aplicarse.
 *
 * Pasó de verdad, en `/mis-zonas` y en `/admin/zonas`: el conmutador
 * lista/tarjetas pintaba las dos maquetaciones a la vez y no conmutaba nada.
 * Sobre un <div> normal el mismo atributo sale como `x-transition=""` y no da
 * problema, así que el fallo solo aparece en los componentes.
 *
 * Es estático a propósito. Los seis tests de ConmutadorVistaTest estaban en
 * verde con el conmutador roto, porque comprueban que las dos maquetaciones
 * están en el HTML servido —y estaban: ese era justo el síntoma—. Un test de
 * servidor no puede pulsar un botón, pero sí puede leer el fuente y negarse a
 * dejar pasar el patrón que lo rompe.
 */
class DirectivasAlpineEnComponentesTest extends TestCase
{
    public function test_ningun_componente_blade_lleva_una_directiva_de_alpine_sin_valor(): void
    {
        $vistas = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(resource_path('views'))
        );

        $culpables = [];

        foreach ($vistas as $fichero) {
            if ($fichero->isDir() || ! str_ends_with($fichero->getFilename(), '.blade.php')) {
                continue;
            }

            foreach (file($fichero->getPathname()) as $n => $linea) {
                // Una etiqueta de componente (<x-algo ...) con una directiva
                // x-* que no lleve `=` detrás.
                if (preg_match('/<x-[a-z0-9:.-]+[^>]*\sx-(transition|cloak|ignore|show|collapse)(?![\w:.-])\s*(?![=\w:.-])/i', $linea)) {
                    $ruta = str_replace(resource_path('views') . DIRECTORY_SEPARATOR, '', $fichero->getPathname());
                    $culpables[] = $ruta . ':' . ($n + 1) . ' → ' . trim($linea);
                }
            }
        }

        $this->assertSame(
            [],
            $culpables,
            "Directiva de Alpine sin valor sobre un componente Blade. Blade la "
            . "convierte en x-algo=\"x-algo\" y Alpine aborta el recorrido de "
            . "directivas de esa página:\n" . implode("\n", $culpables)
        );
    }
}
