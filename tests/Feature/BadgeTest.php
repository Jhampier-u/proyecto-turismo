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
     */
    public function test_el_badge_y_la_fila_leen_el_mismo_mapa(): void
    {
        $fuenteBadge = file_get_contents(resource_path('views/components/badge.blade.php'));
        $fuenteFila  = file_get_contents(resource_path('views/components/fila-matriz.blade.php'));

        foreach ([$fuenteBadge, $fuenteFila] as $fuente) {
            $this->assertStringContainsString('ESTILOS_ESTADO', $fuente);
        }

        // Ningún color escrito a mano en ninguno de los dos: los colores
        // vienen del mapa, no del componente.
        $this->assertDoesNotMatchRegularExpression('/text-(amber|green)-[0-9]{3}/', $fuenteBadge);
        $this->assertDoesNotMatchRegularExpression('/text-(amber|green)-[0-9]{3}/', $fuenteFila);
    }
}
