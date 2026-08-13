<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Ninguna página se queda sin salida.
 *
 * La Fase 1 quita 22 botones «Volver» y los sustituye por migas. El riesgo real
 * no es que una miga esté mal: es que una página se quede sin ninguna de las dos
 * cosas y no haya forma de subir. Abrir las vistas de una en una no lo caza;
 * recorrerlas, sí.
 *
 * Se afirma sobre el fuente y no sobre páginas servidas a propósito: así cubre
 * también las que ninguna prueba renderiza hoy. Mismo patrón y misma razón que
 * TipografiaUnicaTest.
 */
class NavegacionCompletaTest extends TestCase
{
    public function test_toda_pagina_de_zona_trae_migas(): void
    {
        foreach ($this->paginas() as $ruta => $contenido) {
            if (! str_starts_with($ruta, 'operativo/')) {
                continue;
            }

            // «Mis Zonas» es la raíz del rastro operativo: su miga sería un
            // solo tramo apuntándose a sí misma. Mismo motivo por el que las
            // páginas de admin/ quedan fuera del guardián.
            if ($ruta === 'operativo/dashboard.blade.php') {
                continue;
            }

            $this->assertStringContainsString(
                '<x-migas',
                $contenido,
                "{$ruta} es una página y no trae migas. Desde la Fase 1 las migas son la "
                . 'única forma de subir de nivel: sin ellas esta pantalla no tiene salida.'
            );
        }
    }

    /**
     * Escritorio y móvil ofrecen los mismos destinos.
     *
     * No es hipotético: el propio fichero llevaba el comentario de que el
     * bloque móvil llegó a tener solo `dashboard` y «la app era inservible en
     * móvil». Los enlaces estaban escritos dos veces, así que
     * desincronizarlos era cuestión de que alguien tocara uno.
     *
     * Se mira el fuente y no las dos páginas servidas porque el defecto no es
     * que pinten distinto hoy —hoy coinciden—: es que puedan divergir mañana.
     * Lo que hay que fijar es que exista UNA fuente, no que dos copias sean
     * iguales por ahora.
     */
    public function test_los_destinos_del_navbar_se_deciden_en_un_solo_sitio(): void
    {
        $fuente = (string) file_get_contents(
            resource_path('views/layouts/navigation.blade.php')
        );

        $sinComentarios = preg_replace('/\{\{--.*?--\}\}/s', '', $fuente);

        // Se mide la lista, no las comprobaciones de rol. La primera versión de
        // este test contaba `esAdmin()` y se puso roja en cuanto el selector de
        // zona añadió el suyo —que decide si el selector APARECE, no a dónde
        // lleva un enlace—. Contar roles medía una cosa parecida a la que
        // importa, y las cosas parecidas dan falsos positivos.
        $this->assertSame(
            1,
            preg_match_all('/\$secciones\s*=/', $sinComentarios),
            'Hay más de una lista de secciones en el navbar: los destinos han vuelto a '
            . 'estar decididos en dos sitios, que es como el menú móvil se quedó atrás.'
        );

        // Los dos bloques -escritorio y móvil- recorren esa única lista.
        $this->assertSame(
            2,
            preg_match_all('/@foreach\(\$secciones as /', $sinComentarios),
            'Escritorio y móvil no recorren los dos la misma lista de secciones.'
        );
    }

    /**
     * Las zonas del selector se deciden en un solo sitio, por el mismo motivo
     * que los destinos: desde que el menú móvil también las ofrece, hay dos
     * bloques que las recorren, y dos listas escritas aparte es exactamente la
     * forma que tomó el fallo que dejó al móvil con una sola sección.
     */
    public function test_las_zonas_del_selector_se_deciden_en_un_solo_sitio(): void
    {
        $sinComentarios = preg_replace(
            '/\{\{--.*?--\}\}/s',
            '',
            (string) file_get_contents(resource_path('views/layouts/navigation.blade.php'))
        );

        $this->assertSame(
            1,
            preg_match_all('/\$zonasDelSelector\s*=/', $sinComentarios),
            'Hay más de una lista de zonas en el navbar: escritorio y móvil pueden divergir.'
        );

        // Los dos consumidores, nombrados: el desplegable de escritorio la
        // recibe y el menú móvil la recorre.
        $this->assertStringContainsString(
            ':zonas="$zonasDelSelector"',
            $sinComentarios,
            'El desplegable de escritorio no recibe la lista única de zonas.'
        );

        $this->assertSame(
            1,
            preg_match_all('/@foreach\(\$zonasDelSelector as /', $sinComentarios),
            'El menú móvil no recorre la lista única de zonas.'
        );
    }

    /**
     * Una página es una vista que monta el layout. Los componentes y parciales
     * no lo hacen, y no deben traer migas: las pinta la página que los incluye,
     * y repetirlas daría dos rastros en la misma pantalla.
     *
     * Las páginas de `admin/` quedan fuera del guardián a propósito: son
     * secciones de primer nivel a las que se llega desde la barra, así que un
     * rastro de un solo tramo no diría nada que la barra no diga ya.
     *
     * @return array<string, string> ruta relativa => contenido sin comentarios
     */
    private function paginas(): array
    {
        $directorio = resource_path('views');

        $ficheros = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directorio, \FilesystemIterator::SKIP_DOTS)
            ),
            '/\.blade\.php$/'
        );

        $paginas = [];

        foreach ($ficheros as $fichero) {
            $contenido = preg_replace(
                '/\{\{--.*?--\}\}/s',
                '',
                (string) file_get_contents($fichero->getPathname())
            );

            if (! str_contains($contenido, '<x-app-layout')) {
                continue;
            }

            $relativa = str_replace('\\', '/', substr($fichero->getPathname(), strlen($directorio) + 1));
            $paginas[$relativa] = $contenido;
        }

        $this->assertNotEmpty($paginas, 'No se encontró ninguna página que revisar.');

        return $paginas;
    }
}
