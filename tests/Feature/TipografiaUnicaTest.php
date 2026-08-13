<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
 * cubre las 84 vistas, incluidas las que ninguna prueba renderiza hoy.
 */
class TipografiaUnicaTest extends TestCase
{
    use RefreshDatabase;

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
     * La hoja de estilos de entrada tampoco trae fuente propia.
     *
     * Los dos tests de arriba barren `resources/views`, que es donde estaban
     * los dos casos conocidos. Pero mirar solo ahí deja abierta la puerta más
     * ancha: el sitio natural para añadir una fuente no es una vista, es el
     * CSS de entrada, y un @import ahí no afecta a una pantalla sino a la
     * aplicación ENTERA. Justo el defecto que este guardián existe para
     * impedir, en su versión grande.
     *
     * Hoy el fichero son tres directivas de Tailwind y nada más.
     */
    public function test_la_hoja_de_estilos_de_entrada_no_pide_una_fuente_ajena(): void
    {
        $css = (string) file_get_contents(resource_path('css/app.css'));

        foreach (self::PROVEEDORES_AJENOS as $proveedor) {
            $this->assertStringNotContainsString(
                $proveedor,
                $css,
                "resources/css/app.css pide una fuente a {$proveedor}. La "
                . 'familia la decide tailwind.config.js y la sirve layouts/app '
                . 'desde fonts.bunny.net; traerla aquí la cambia en todas las '
                . 'pantallas a la vez.'
            );
        }

        $this->assertStringNotContainsString(
            'font-family',
            $css,
            'resources/css/app.css declara font-family. La familia se fija en '
            . 'tailwind.config.js (fontFamily.sans), que es el sitio donde el '
            . 'resto del sistema la va a buscar.'
        );
    }

    /**
     * El formulario de Potencialidad, servido de verdad, no pide ninguna
     * fuente.
     *
     * Los dos tests de arriba miran el fuente de las vistas; este mira los
     * bytes que salen por HTTP, que es un paso más cerca de lo que recibe el
     * navegador. Se elige esta página y no otra porque es la que tenía el
     * defecto: un @import dentro de un <style> en el cuerpo del documento.
     *
     * Lo que este test NO puede afirmar, y conviene no darlo por cubierto: qué
     * fuente acaba dibujando el navegador. Eso depende de que fonts.bunny.net
     * responda y de las fuentes instaladas en la máquina, y solo se ve
     * abriendo la página. Lo que sí queda atado es la causa: aquí no se pide
     * ninguna familia, así que la única que gobierna es la del layout.
     */
    public function test_la_pagina_de_potencialidad_servida_no_pide_ninguna_fuente(): void
    {
        $this->seed(SystemSeeder::class);

        $jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);

        $html = $this->actingAs($jefe)
            ->get("/operativo/zona/{$zona->id}/evaluacion-potencialidad")
            ->assertOk()
            ->getContent();

        foreach (self::PROVEEDORES_AJENOS as $proveedor) {
            $this->assertStringNotContainsString($proveedor, $html);
        }

        $this->assertStringNotContainsString('font-family', $html);
        $this->assertStringNotContainsString('@import', $html);

        // La única fuente que se pide en la página es la del layout.
        $this->assertStringContainsString('fonts.bunny.net', $html);
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
