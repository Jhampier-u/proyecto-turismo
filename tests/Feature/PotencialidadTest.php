<?php

namespace Tests\Feature;

use App\Matrices\Potencialidad;
use App\Models\EvaluacionPotencialidad;
use App\Models\PotencialidadCamposActivos;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * App\Matrices\Potencialidad es la definición del instrumento que antes vivía
 * en EvaluacionPotencialidadController::$secciones -ver
 * docs/ESTADO-PROYECTO.md, rama potencialidad-componentes-. A diferencia de
 * FIT, FET y Percepción, Potencialidad no hereda de MatrizPonderadaController
 * -tiene su propio prepararDatos(), calcular() y la configuración de campos
 * activos-, así que esta clase solo cubre lo que necesitan el formulario y
 * los mensajes de validación: el catálogo de 156 criterios y su escala. El
 * cálculo, con sus cuatro niveles de anidamiento, se queda donde estaba y
 * sigue cubierto por PotencialidadCalculoTest, que esta migración no toca.
 *
 * El resto del comportamiento de Potencialidad -cálculo, campos activos,
 * bloqueo, mensajes de cierre- ya estaba cubierto en PotencialidadCalculoTest.php
 * y EvaluacionesTest.php y no se toca aquí.
 */
class PotencialidadTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    private function url(): string
    {
        return "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad";
    }

    private function contarRadios(string $html): int
    {
        return preg_match_all('/<input type="radio"[^>]*>/', $html);
    }

    public function test_el_instrumento_declara_156_criterios_en_sus_secciones(): void
    {
        $this->assertCount(156, Potencialidad::todos());

        // Un par de secciones concretas, para detectar un recuento que
        // cuadre en total pero esté mal repartido entre secciones.
        $this->assertCount(6, Potencialidad::SECCIONES['RN — Zonas de Litoral']);
        $this->assertCount(37, Potencialidad::SECCIONES['Tipologías de Turismo']);
        $this->assertCount(5, Potencialidad::SECCIONES['Afluencia Turística']);

        $this->assertSame(
            156,
            array_sum(array_map('count', Potencialidad::SECCIONES)),
            'la suma de las secciones debe cuadrar con todos().'
        );
    }

    /** Los 3 niveles son genéricos e iguales en los 156 criterios: Ausencia/Fragilidad/Aprovechable. */
    public function test_los_3_niveles_son_genericos_y_van_de_0_a_2(): void
    {
        $this->assertSame([0, 1, 2], array_keys(Potencialidad::NIVELES));
        $this->assertSame(['Ausencia', 'Fragilidad', 'Aprovechable'], array_values(Potencialidad::NIVELES));
        $this->assertSame(0, Potencialidad::ESCALA_MIN);
        $this->assertSame(2, Potencialidad::ESCALA_MAX);
    }

    /**
     * El formulario pinta los 156 criterios recorriendo
     * Potencialidad::SECCIONES, no tecleados: si alguien borra un campo de
     * la definición, este test lo nota sin que nadie tenga que actualizar
     * una lista aparte a mano. El Jefe ve los 156 sin filtrar por campos
     * activos -puede calificar y reactivar cualquiera-, a diferencia del
     * equipo o el admin (ver test_el_equipo_solo_ve_pildoras_de_los_campos_activos).
     */
    public function test_el_formulario_del_jefe_muestra_los_156_criterios_con_pildoras(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (Potencialidad::todos() as $campo => $etiqueta) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }

        // Un par de etiquetas concretas: si el formulario mostrara solo los
        // niveles genéricos sin el texto de cada criterio, esto no aparecería.
        $respuesta->assertSee('Playas');
        $respuesta->assertSee('Política Pública de Turismo');

        // Prueba de verdad de que el control es <x-criterio-pildoras> y no el
        // <select> anterior: 156 criterios × 3 niveles = 468 radios. Un simple
        // assertSee del nombre del campo no lo distinguiría, porque un
        // <select name="..."> también lo cumpliría.
        $this->assertSame(468, $this->contarRadios($respuesta->getContent()));
    }

    public function test_no_se_accede_desde_una_zona_ajena(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }

    /**
     * A diferencia de FIT/FET/Percepción, Potencialidad tiene configuración
     * de campos activos: el Jefe ve los 156 siempre, pero el equipo solo ve
     * -y solo puede calificar- los que están activos. Este test ata esa
     * regla al control nuevo: si <x-criterio-pildoras> se pintara para todos
     * los campos sin filtrar, el recuento de radios lo delataría.
     */
    public function test_el_equipo_solo_ve_pildoras_de_los_campos_activos(): void
    {
        $activos = array_keys(Potencialidad::SECCIONES['Afluencia Turística']);

        PotencialidadCamposActivos::create([
            'zona_id'        => $this->zona->id,
            'campos_activos' => $activos,
        ]);

        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $respuesta = $this->actingAs($equipo)->get($this->url())->assertOk();

        // 5 campos activos × 3 niveles = 15 radios, ni uno más.
        $this->assertSame(15, $this->contarRadios($respuesta->getContent()));

        foreach ($activos as $campo) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }

        // Un campo inactivo conocido no debe aparecer con su propio control.
        $respuesta->assertDontSee('name="i_transporte"', false);
    }

    /**
     * Cabo 5(a) de cabos-sueltos: el toggle de campos activos ahora también
     * deshabilita el control en vivo, no solo funde la fila a opacidad 0.55.
     * Antes de este cambio las píldoras de un campo inactivo seguían
     * respondiendo al clic -sin bug de datos, porque prepararDatos() ya
     * descartaba esa calificación sin importar qué se enviara, pero sí un
     * control que reacciona cuando no debería-.
     *
     * PHPUnit no ejecuta Alpine, así que esto no simula el clic: fija que
     * el HTML servido al Jefe lleva el x-bind:disabled correcto -la mitad
     * que sí puede verificarse sin un navegador-, apuntando al mismo
     * `states` que ya gobierna el toggle. El otro half -que un campo
     * inactivo termina con disabled=true de verdad, que un clic sobre él no
     * cambia la selección, y que reactivarlo lo devuelve a responder- se
     * verificó a mano en el navegador (Alpine, DevTools) durante esta rama.
     */
    public function test_el_toggle_de_campos_activos_deshabilita_el_control_en_vivo(): void
    {
        $inactivo = 'rn_litoral_playas';
        $activo   = 'rn_litoral_arrecifes';
        $activos  = array_diff(array_keys(Potencialidad::todos()), [$inactivo]);

        PotencialidadCamposActivos::create([
            'zona_id'        => $this->zona->id,
            'campos_activos' => array_values($activos),
        ]);

        // html_entity_decode: Blade escapa la comilla simple del literal
        // JS a &#039; al pasar por {{ }} -el navegador la decodifica solo al
        // parsear el atributo, pero getContent() devuelve el HTML crudo tal
        // cual sale del servidor-.
        $html = html_entity_decode(
            $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent()
        );

        // El Jefe ve los 156 campos siempre -incluido el inactivo, fundido a
        // opacidad pero visible, para poder reactivarlo-, así que su radio
        // también lleva el binding.
        $this->assertStringContainsString(
            "x-bind:disabled=\"!(states['{$inactivo}'])\"",
            $html,
            'el campo inactivo debe llevar el binding reactivo de disabled'
        );

        // Uno activo también lo lleva -el binding es incondicional, la
        // expresión decide en vivo si el toggle cambia sin recargar-, no
        // solo el que empezó inactivo.
        $this->assertStringContainsString(
            "x-bind:disabled=\"!(states['{$activo}'])\"",
            $html,
            'un campo activo tambien debe llevar el binding, para que responda si se desactiva sin recargar'
        );
    }

    /**
     * Garantía de que :activo-expr es aditivo de verdad: sin él -el caso de
     * Paisaje, FIT, FET y Percepción, que nunca lo pasan- el HTML que sale
     * de <x-criterio-pildoras> no cambia ni un atributo, bloqueado o no.
     *
     * Se verifica sobre el componente directamente, no releyendo una de
     * esas cuatro vistas, para que la garantía cubra también a cualquier
     * consumidor futuro que tampoco lo pase -no solo a los cuatro de hoy-.
     * Las suites completas de FitTest, FetTest, PercepcionTest y
     * PaisajeTest, sin ningún cambio, son la otra mitad de esta garantía:
     * si esto rompiera algo suyo, habría fallado ahí.
     */
    public function test_sin_activo_expr_el_componente_no_cambia(): void
    {
        $this->withViewErrors([]);

        $criterio = ['nombre' => 'Criterio', 'niveles' => Potencialidad::NIVELES];

        foreach ([false, true] as $bloqueado) {
            $html = (string) $this->blade(
                '<x-criterio-pildoras :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />',
                ['campo' => 'c', 'criterio' => $criterio, 'bloqueado' => $bloqueado]
            );

            $this->assertStringNotContainsString(
                'x-bind:disabled',
                $html,
                'sin :activo-expr no debe aparecer ningún binding reactivo de disabled (bloqueado=' . ($bloqueado ? 'true' : 'false') . ')'
            );
        }
    }

    /**
     * Tarea 11 bis: Potencialidad migra su `.pt-sidebar` a
     * <x-barra-lateral-formulario>. Este bloque cubre solo lo que cambia de
     * presentación -la barra nueva-; el cálculo, la validación y el orden de
     * persistencia de campos_activos (M6) siguen en PotencialidadCalculoTest
     * y en el resto de este fichero, sin tocar.
     */

    /** El índice de la barra lateral tiene una entrada por cada una de las 6 áreas. */
    public function test_la_barra_lateral_indexa_las_seis_areas(): void
    {
        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();

        $fragmento = Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento, 'No se encontró <aside>: la barra lateral no se está pintando.');

        foreach ([
            'Recursos Naturales', 'Recursos Culturales', 'Planta Turística',
            'Tipologías de Turismo', 'Infraestructura', 'Factores Exógenos',
        ] as $area) {
            $this->assertStringContainsString(
                'href="#area-' . Str::slug($area) . '"',
                $fragmento,
                "Falta el enlace al área '{$area}'."
            );
        }
    }

    /**
     * El hallazgo que abrió la disyuntiva de esta tarea: un campo
     * desactivado conserva su valor a propósito -prepararDatos() lo guarda
     * "por si vuelve a activarse"-, así que un recuento ingenuo sobre las
     * 156 columnas de la tabla lo seguiría contando como respondido aunque
     * ya no cuente para nada. La cabecera tiene que ver solo los campos
     * activos, no el histórico completo de la fila.
     */
    public function test_la_cabecera_no_cuenta_respuestas_de_campos_ya_desactivados(): void
    {
        $activos = array_keys(Potencialidad::SECCIONES['Afluencia Turística']); // 5 campos

        PotencialidadCamposActivos::create([
            'zona_id'        => $this->zona->id,
            'campos_activos' => $activos,
        ]);

        $evaluacion = EvaluacionPotencialidad::create(
            ['zona_id' => $this->zona->id, 'estado' => 'borrador']
            + array_fill_keys($activos, 2)
        );

        // Campo de OTRA sección, ya desactivado -no está en $activos-, con
        // una respuesta antigua que prepararDatos() habría conservado tal
        // cual en vez de vaciarla.
        $evaluacion->update(['rn_litoral_playas' => 1]);

        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();
        $fragmento = Str::between($html, '<aside', '</aside>');

        // Honesto: 5 de 5 activos. Un recuento ingenuo sobre toda la fila
        // habría dicho "6 de 156" -contando el campo inactivo y el
        // denominador fijo del registro, ninguno de los dos correcto aquí-.
        $this->assertStringContainsString('5 de 5 respondidos', $fragmento);
    }

    /** Caso explícito del plan: con la mitad de los 156 desactivados, el denominador es la mitad, no 156. */
    public function test_el_denominador_de_la_barra_lateral_es_el_de_los_campos_activos_no_156(): void
    {
        $todos = array_keys(Potencialidad::todos());
        $mitad = array_slice($todos, 0, intdiv(count($todos), 2));

        PotencialidadCamposActivos::create([
            'zona_id'        => $this->zona->id,
            'campos_activos' => $mitad,
        ]);

        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();
        $fragmento = Str::between($html, '<aside', '</aside>');

        $this->assertStringContainsString('de ' . count($mitad) . ' respondidos', $fragmento);
        $this->assertStringNotContainsString('de 156 respondidos', $fragmento);
    }

    /**
     * Mismo motivo por el que las siete matrices hermanas atan el botón de
     * la barra a $bloqueado: una matriz confirmada, vista por quien no es
     * Jefe, no debe ofrecer un "Guardar Borrador" en la barra que no exista
     * también abajo.
     */
    public function test_la_barra_lateral_no_ofrece_guardar_si_la_evaluacion_esta_confirmada_y_no_es_el_jefe(): void
    {
        $todos = array_keys(Potencialidad::todos());

        EvaluacionPotencialidad::create(
            ['zona_id' => $this->zona->id, 'estado' => 'confirmado']
            + array_fill_keys($todos, 2)
        );

        $equipo = User::factory()->create(['role_id' => Role::where('nombre', 'equipo')->value('id')]);
        $this->zona->equipo()->attach($equipo->id);

        $html = $this->actingAs($equipo)->get($this->url())->assertOk()->getContent();
        $fragmento = Str::between($html, '<aside', '</aside>');

        $this->assertStringNotContainsString('Guardar Borrador', $fragmento);
    }

    /** El Jefe siempre puede seguir guardando, aunque la evaluación ya esté confirmada. */
    public function test_la_barra_lateral_ofrece_guardar_al_jefe_ligado_al_formulario_real(): void
    {
        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();
        $fragmento = Str::between($html, '<aside', '</aside>');

        $this->assertStringContainsString('Guardar Borrador', $fragmento);
        $this->assertStringContainsString('form="pt-form"', $fragmento);
    }
}
