<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Una sola tipografía para la aplicación, y un solo sitio que la decide.
 *
 * `tailwind.config.js` fija `fontFamily.sans` en Inter y `layouts/app` la
 * sirve desde fonts.bunny.net. Ese es el mecanismo, y una vista que traiga su
 * propia fuente lo esquiva entero.
 *
 * Este guardián existe porque ya pasó dos veces, y las dos en silencio:
 *
 * 1. `layouts/guest` —las cinco páginas de autenticación— siguió pidiendo
 *    Figtree después de FV4. Lo cazó una revisión, no un test.
 * 2. `evaluacion_potencialidad/form` traía un `@import` a fonts.googleapis.com
 *    con DM Sans y un `font-family` en su CSS propio. La matriz más grande del
 *    sistema, la única pantalla con otra letra, y nadie lo vio en cuatro ramas.
 *
 * Ningún test de los que había podía verlo: los tests miran HTML, y el HTML de
 * una página con otra tipografía es idéntico al de una con la correcta. Lo que
 * sí se puede mirar es si una vista declara una fuente, que es la causa.
 *
 * Se afirma sobre el fuente y no sobre una página servida a propósito: así
 * cubre las 80 vistas, incluidas las que ninguna prueba renderiza hoy.
 */
class TipografiaUnicaTest extends TestCase
{
    /** Proveedores de fuentes que no son el del sistema. */
    private const PROVEEDORES_AJENOS = [
        'fonts.googleapis.com',
        'fonts.gstatic.com',
        'use.typekit.net',
        'cdn.jsdelivr.net/npm/@fontsource',
    ];

    public function test_ninguna_vista_pide_una_fuente_a_otro_proveedor(): void
    {
        foreach ($this->vistas() as $ruta => $contenido) {
            foreach (self::PROVEEDORES_AJENOS as $proveedor) {
                $this->assertStringNotContainsString(
                    $proveedor,
                    $contenido,
                    "{$ruta} pide una fuente a {$proveedor}. La aplicación sirve Inter "
                    . 'desde fonts.bunny.net en layouts/app; una vista con su propia '
                    . 'fuente es una segunda tipografía que ningún otro test puede ver.'
                );
            }
        }
    }

    public function test_ninguna_vista_declara_su_propia_familia_tipografica(): void
    {
        foreach ($this->vistas() as $ruta => $contenido) {
            $this->assertStringNotContainsString(
                'font-family',
                $contenido,
                "{$ruta} declara font-family. La familia la decide "
                . 'tailwind.config.js (fontFamily.sans), y el body la aplica con '
                . '`font-sans`: declararla en una vista la sustituye solo ahí.'
            );
        }
    }

    /**
     * Las vistas Blade con los comentarios ya quitados.
     *
     * Quitarlos importa: este mismo repositorio documenta en
     * `evaluacion_potencialidad/form` qué fuente se le retiró y por qué, y sin
     * este paso el guardián fallaría contra la explicación de su propio
     * hallazgo. Blade tampoco los sirve, así que mirar sin ellos es mirar lo
     * que llega al navegador.
     *
     * @return array<string, string> ruta relativa => contenido
     */
    private function vistas(): array
    {
        $directorio = resource_path('views');

        $ficheros = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directorio, \FilesystemIterator::SKIP_DOTS)
            ),
            '/\.blade\.php$/'
        );

        $vistas = [];

        foreach ($ficheros as $fichero) {
            $relativa = str_replace('\\', '/', substr($fichero->getPathname(), strlen($directorio) + 1));

            $vistas[$relativa] = preg_replace(
                '/\{\{--.*?--\}\}/s',
                '',
                (string) file_get_contents($fichero->getPathname())
            );
        }

        $this->assertNotEmpty($vistas, 'No se encontró ninguna vista que revisar.');

        return $vistas;
    }
}
