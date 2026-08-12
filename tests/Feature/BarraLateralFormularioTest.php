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
 * <x-barra-lateral-formulario> no deriva su índice de bloques -cada vista
 * se lo pasa ya resuelto-, así que se prueba en aislado con datos de mentira,
 * sin necesitar ninguna de las siete matrices reales que lo van a usar.
 */
class BarraLateralFormularioTest extends TestCase
{
    use RefreshDatabase;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemSeeder::class);

        $jefe = User::factory()->create(['role_id' => Role::where('nombre', 'jefe_zona')->value('id')]);
        $this->zona = Zona::create([
            'lugar_id' => DB::table('lugares')->value('id'),
            'jefe_user_id' => $jefe->id,
            'nombre' => 'Zona de prueba',
        ]);
    }

    private function renderizar(array $secciones, bool $bloqueado = false): string
    {
        return (string) $this->blade(
            '<x-barra-lateral-formulario clave="fit" :zona="$zona" :secciones="$secciones" :bloqueado="$bloqueado" formulario="form-fit" />',
            ['zona' => $this->zona, 'secciones' => $secciones, 'bloqueado' => $bloqueado]
        );
    }

    /** Un 0 respondido se muestra como dato, nunca se omite ni se sustituye. */
    public function test_una_seccion_sin_empezar_muestra_cero_de_su_total(): void
    {
        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos Turísticos', 'respondidos' => 0, 'total' => 2],
        ]);

        $this->assertStringContainsString('0/2', $html);
    }

    /** Una sección completa lleva su marcador, y sigue mostrando la fracción. */
    public function test_una_seccion_completa_lleva_marcador_y_fraccion(): void
    {
        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos Turísticos', 'respondidos' => 2, 'total' => 2],
        ]);

        $fragmento = \Illuminate\Support\Str::between($html, 'href="#rtt"', '</a>');
        $this->assertStringContainsString('✓', $fragmento);
        $this->assertStringContainsString('2/2', $fragmento);
    }

    /** Cada sección enlaza a su propia ancla, no a una genérica. */
    public function test_cada_seccion_enlaza_a_su_propia_ancla(): void
    {
        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos', 'respondidos' => 1, 'total' => 2],
            ['ancla' => 'at', 'etiqueta' => 'Atractivos', 'respondidos' => 0, 'total' => 1],
        ]);

        $this->assertStringContainsString('href="#rtt"', $html);
        $this->assertStringContainsString('href="#at"', $html);
    }

    /**
     * Bloqueada, no ofrece guardar. Se comprueba dentro del propio
     * componente para fijar el contrato antes de integrarlo en ninguna
     * vista real -las Tareas 5-11 vuelven a comprobar esto mismo en
     * contexto, contra el $bloqueado real de cada matriz-.
     */
    public function test_bloqueado_no_ofrece_el_boton_de_guardar(): void
    {
        $html = $this->renderizar([['ancla' => 'rtt', 'etiqueta' => 'R', 'respondidos' => 1, 'total' => 1]], bloqueado: true);

        $this->assertStringNotContainsString('Guardar Borrador', $html);
    }

    public function test_sin_bloquear_ofrece_el_boton_de_guardar_ligado_al_formulario_real(): void
    {
        $html = $this->renderizar([['ancla' => 'rtt', 'etiqueta' => 'R', 'respondidos' => 1, 'total' => 1]], bloqueado: false);

        $this->assertStringContainsString('Guardar Borrador', $html);
        $this->assertStringContainsString('form="form-fit"', $html);
    }

    /**
     * Sin :total ni :respondidos, la cabecera sigue derivándose igual que
     * antes de la Tarea 11 bis -de Registro y EstadoZona::criteriosRespondidos()-.
     * Fija el comportamiento de las siete matrices que no pasan estos props,
     * para que ensancharlos no les mueva un número que nunca se probó.
     */
    public function test_sin_total_ni_respondidos_la_cabecera_se_deriva_como_siempre(): void
    {
        $evaluacion = \App\Models\EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);
        $evaluacion->update(['recursos_culturales' => 2, 'recursos_naturales' => 3]);

        $html = $this->renderizar([['ancla' => 'rtt', 'etiqueta' => 'R', 'respondidos' => 1, 'total' => 1]]);

        // FIT declara 18 criterios en Registro; solo 2 están respondidos.
        $this->assertStringContainsString('2 de 18 respondidos', $html);
    }

    /**
     * Potencialidad es la matriz cuyo denominador se mueve -el Jefe de Zona
     * decide qué criterios aplican-, así que 156 (fijo en Registro) deja de
     * significar nada y EstadoZona::criteriosRespondidos() tampoco sirve: no
     * distingue un campo activo de uno desactivado que conserva una
     * respuesta antigua. :total y :respondidos son la válvula de escape
     * para ESE caso -no un prop genérico "por si acaso"-: si se pasan,
     * sustituyen el cálculo de cabecera entero.
     */
    public function test_con_total_y_respondidos_la_cabecera_usa_esos_valores_en_vez_de_derivarlos(): void
    {
        // clave="fit" sin ninguna EvaluacionFit: el cálculo interno daría
        // "0 de 18". Si la cabecera muestra "60 de 78" en su lugar, es que
        // el override manda, no el cálculo por defecto.
        $html = (string) $this->blade(
            '<x-barra-lateral-formulario clave="fit" :zona="$zona" :secciones="$secciones" :bloqueado="false" formulario="form-fit" :total="$total" :respondidos="$respondidos" />',
            ['zona' => $this->zona, 'secciones' => [], 'total' => 78, 'respondidos' => 60]
        );

        $this->assertStringContainsString('60 de 78 respondidos', $html);
        $this->assertStringNotContainsString('0 de 18', $html);
    }

    /**
     * Numerador y denominador tienen que venir de la MISMA decisión, juntos
     * o ninguno: pasar solo uno mezclaría un valor calculado con uno
     * ajeno -exactamente el "número que no significa nada" que este ensanche
     * existe para evitar-. Falla ruidoso en vez de adivinar cuál falta.
     */
    public function test_pasar_solo_total_sin_respondidos_truena(): void
    {
        // Blade envuelve cualquier excepción lanzada al renderizar un
        // componente en ViewException -no deja pasar la original tal
        // cual-, así que se comprueba el mensaje en vez del tipo original.
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage(':total y :respondidos se pasan juntos o ninguno');

        $this->blade(
            '<x-barra-lateral-formulario clave="fit" :zona="$zona" :secciones="$secciones" :bloqueado="false" formulario="form-fit" :total="78" />',
            ['zona' => $this->zona, 'secciones' => []]
        );
    }

    /** Misma garantía que el test anterior, con el prop que falta al revés. */
    public function test_pasar_solo_respondidos_sin_total_truena(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage(':total y :respondidos se pasan juntos o ninguno');

        $this->blade(
            '<x-barra-lateral-formulario clave="fit" :zona="$zona" :secciones="$secciones" :bloqueado="false" formulario="form-fit" :respondidos="60" />',
            ['zona' => $this->zona, 'secciones' => []]
        );
    }
}
