<?php

namespace Tests\Feature;

use App\Servicios\EstadoZona;
use Tests\TestCase;

/**
 * <x-badge> pinta un estado del sistema. Los cinco valores no son inventados:
 * son los que produce EstadoZona y consume <x-fila-matriz>.
 */
class BadgeTest extends TestCase
{
    public function test_pinta_los_cinco_estados_con_su_texto(): void
    {
        $esperado = [
            'sin_empezar' => 'Sin empezar',
            'sin_estado'  => 'Sin estado',
            'borrador'    => 'Borrador',
            'validada'    => 'Validada',
            'bloqueada'   => 'Bloqueada',
        ];

        foreach ($esperado as $estado => $texto) {
            $html = (string) $this->blade(
                '<x-badge :estado="$estado" />',
                ['estado' => $estado]
            );

            $this->assertStringContainsString($texto, $html, "El estado {$estado} no dijo «{$texto}».");
        }
    }

    /**
     * «Listo para validar» no es un estado sino un borrador que además está
     * completo. Se pasa como ranura para que el sistema no acabe con seis
     * estados en la interfaz y cinco en el servicio.
     */
    public function test_la_ranura_sustituye_el_texto_y_conserva_el_color(): void
    {
        $html = (string) $this->blade(
            '<x-badge estado="borrador">Listo para validar</x-badge>'
        );

        $this->assertStringContainsString('Listo para validar', $html);
        $this->assertStringNotContainsString('>Borrador<', $html);
        $this->assertStringContainsString(EstadoZona::ESTILOS_ESTADO['borrador']['insignia'], $html);
    }

    public function test_un_estado_desconocido_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('estado «inventado» desconocido');

        $this->blade('<x-badge estado="inventado" />');
    }

    /**
     * El test que impide que el mapa vuelva a duplicarse: si alguien copia los
     * colores dentro del badge y luego cambia los de fila-matriz, esto falla.
     *
     * Se comprueba el código de los dos componentes y no su salida, a
     * propósito: lo que se quiere garantizar no es «pintan igual» —podrían
     * pintar igual por casualidad, con dos tablas idénticas que mañana se
     * separan— sino que **leen del mismo sitio**, que es lo único que impide
     * la divergencia.
     */
    public function test_el_badge_y_la_fila_leen_el_mismo_mapa(): void
    {
        $fuenteBadge = file_get_contents(resource_path('views/components/badge.blade.php'));
        $fuenteFila  = file_get_contents(resource_path('views/components/fila-matriz.blade.php'));

        foreach ([$fuenteBadge, $fuenteFila] as $fuente) {
            $this->assertStringContainsString('ESTILOS_ESTADO', $fuente);
        }

        // Los dos valores del mapa que NO son grises neutros -el ámbar de
        // borrador y el verde de validada- no pueden estar escritos a mano en
        // ninguno de los dos componentes.
        //
        // Se miran solo esos dos colores a propósito. Prohibir toda la paleta
        // daría falsos positivos: <x-fila-matriz> tiene grises propios que no
        // son del mapa -el `border-gray-200` de su separador, el
        // `text-gray-900` de su título- y son legítimos.
        //
        // Esta comprobación sola no basta, y por eso existe el test de abajo:
        // mira el fuente, y el fuente se puede sortear. Lo que de verdad ata
        // los colores al mapa es renderizar y comparar.
        $colorDeEstado = '/\b(bg|text|border)-(amber|green)-[0-9]{2,3}\b/';

        $this->assertDoesNotMatchRegularExpression($colorDeEstado, $fuenteBadge);
        $this->assertDoesNotMatchRegularExpression($colorDeEstado, $fuenteFila);
    }

    /**
     * <x-fila-matriz> pinta el icono con el color que dice el mapa, en los
     * cinco estados.
     *
     * Es la mitad que faltaba: el test de arriba mira el fuente y el de abajo
     * mira el badge. Sin este, alguien podía copiar el mapa dentro de
     * fila-matriz con los mismos valores de hoy y nada se pondría rojo hasta
     * que los dos se separaran de verdad, que es justo cuando ya es tarde.
     */
    public function test_la_fila_pinta_el_icono_con_el_color_de_su_mapa(): void
    {
        foreach (EstadoZona::ESTILOS_ESTADO as $estado => $estilos) {
            $fila = new \App\Servicios\FilaMatriz(
                clave:   'fit',
                nombre:  'Vocación turística',
                icono:   'documento',
                estado:  $estado,
                detalle: 'Un detalle cualquiera',
                url:     '/algun/sitio',
                accion:  'Continuar',
            );

            $html = (string) $this->blade('<x-fila-matriz :fila="$fila" />', ['fila' => $fila]);

            $this->assertStringContainsString(
                $estilos['icono'],
                $html,
                "La fila en estado {$estado} no pintó el icono con el color de su mapa."
            );
        }
    }

    /**
     * Cada estado sale con las clases que dice su mapa, los cinco.
     *
     * El test de arriba garantiza que los componentes no escriben colores a
     * mano; este garantiza que el mapa llega de verdad a la pantalla. Sin él,
     * una errata en la clave `insignia` de cualquier estado que no sea
     * «borrador» no la veía nadie.
     */
    public function test_cada_estado_pinta_las_clases_de_su_mapa(): void
    {
        foreach (EstadoZona::ESTILOS_ESTADO as $estado => $estilos) {
            $html = (string) $this->blade(
                '<x-badge :estado="$estado" />',
                ['estado' => $estado]
            );

            $this->assertStringContainsString(
                $estilos['insignia'],
                $html,
                "El estado {$estado} no pintó las clases de su mapa."
            );
        }
    }
}
