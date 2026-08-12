<?php

namespace Tests\Feature;

use App\Matrices\Paisaje;
use App\Models\EvaluacionPaisaje;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaisajeTest extends TestCase
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

    /** Rellena los 34 criterios con el mismo valor. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(array_keys(Paisaje::todos()), $valor);
    }

    private function url(string $sufijo = ''): string
    {
        return "/operativo/zona/{$this->zona->id}/paisaje{$sufijo}";
    }

    public function test_los_pesos_de_las_categorias_suman_uno(): void
    {
        $this->assertEqualsWithDelta(
            1.0,
            array_sum(array_column(Paisaje::CATEGORIAS, 'peso')),
            0.0001
        );
    }

    public function test_el_instrumento_declara_34_criterios_en_5_categorias(): void
    {
        $this->assertCount(5, Paisaje::CATEGORIAS);
        $this->assertCount(34, Paisaje::todos());

        $esperado = ['ep' => 6, 'pn' => 9, 'pc' => 5, 'iv' => 7, 'cp' => 7];

        foreach ($esperado as $clave => $cuantos) {
            $this->assertCount($cuantos, Paisaje::CATEGORIAS[$clave]['criterios'], $clave);
        }
    }

    public function test_cada_criterio_declara_sus_tres_niveles(): void
    {
        foreach (Paisaje::todos() as $campo => $criterio) {
            $this->assertNotEmpty($criterio['nombre'], $campo);
            $this->assertSame([0, 3, 5], array_keys($criterio['niveles']), $campo);

            foreach ($criterio['niveles'] as $nivel => $etiqueta) {
                $this->assertNotEmpty($etiqueta, "{$campo} nivel {$nivel}");
            }
        }
    }

    /**
     * Conflictos Paisajísticos es la única categoría que mide un problema en
     * vez de un activo, así que su 5 es «Controlado» y su 0 «Afectado». Si el
     * generador invirtiera el orden, el color del formulario mentiría.
     */
    public function test_conflictos_paisajisticos_puntua_lo_controlado_como_lo_mejor(): void
    {
        foreach (Paisaje::CATEGORIAS['cp']['criterios'] as $campo => $criterio) {
            $this->assertSame('Controlado', $criterio['niveles'][5], $campo);
            $this->assertSame('Afectado', $criterio['niveles'][0], $campo);
        }
    }

    public function test_todo_al_maximo_da_el_tope_de_la_escala(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertEqualsWithDelta(5.0, $eval->paisaje_total, 0.0001);
        $this->assertSame('Eficiente', $eval->escenario['nombre']);

        foreach (array_keys(Paisaje::CATEGORIAS) as $clave) {
            $this->assertEqualsWithDelta(5.0, $eval->{"{$clave}_promedio"}, 0.0001, $clave);
            $this->assertSame('Alto', $eval->lecturaDe($clave), $clave);
        }
    }

    public function test_todo_al_minimo_da_cero_y_escenario_inexistente(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(0))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertEqualsWithDelta(0.0, $eval->paisaje_total, 0.0001);
        $this->assertSame('Inexistente', $eval->escenario['nombre']);
        $this->assertSame('Bajo', $eval->lecturaDe('ep'));
    }

    /**
     * Una sola categoría al máximo debe aportar exactamente su peso × 5.
     * Es el único caso que detecta un peso individual mal asignado: con todos
     * los criterios iguales, cualquier reparto de pesos que sume 1 da lo mismo.
     */
    public function test_cada_categoria_aporta_segun_su_peso(): void
    {
        $datos = $this->todosEn(0);

        foreach (array_keys(Paisaje::CATEGORIAS['pn']['criterios']) as $campo) {
            $datos[$campo] = 5;
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        // Pn pesa 0.25 y su promedio es 5, así que aporta 1.25 y nada más.
        $this->assertEqualsWithDelta(5.0, $eval->pn_promedio, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->ep_promedio, 0.0001);
        $this->assertEqualsWithDelta(1.25, $eval->paisaje_total, 0.0001);
        $this->assertSame('Reactivo', $eval->escenario['nombre']);
    }

    /** La escala admite 0, 3 y 5; nada más. */
    public function test_un_valor_intermedio_fuera_de_la_escala_se_rechaza(): void
    {
        foreach ([1, 2, 4, 6] as $invalido) {
            $datos = $this->todosEn(3);
            $datos['pn_geologia'] = $invalido;

            $this->actingAs($this->jefe)->post($this->url(), $datos)
                ->assertSessionHasErrors('pn_geologia');
        }

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }

    public function test_no_se_confirma_con_criterios_sin_responder(): void
    {
        $datos = $this->todosEn(3) + ['accion_estado' => 'confirmado'];
        unset($datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('cp_conurbaciones');

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }

    public function test_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)->post(
            $this->url(),
            $this->todosEn(5) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPaisaje::value('estado'));
    }

    public function test_el_jefe_confirma_y_queda_cerrada_para_el_equipo(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(5) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('confirmado', EvaluacionPaisaje::value('estado'));

        $this->actingAs($equipo)->post($this->url(), $this->todosEn(0))
            ->assertSessionHas(
                'error',
                'Esta Matriz de Paisaje ya fue validada por el Jefe de Zona. No puedes editarla.'
            );

        $this->assertEqualsWithDelta(5.0, (float) EvaluacionPaisaje::value('paisaje_total'), 0.0001);
    }

    public function test_no_se_accede_desde_una_zona_ajena(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }

    public function test_el_formulario_muestra_los_34_criterios_con_sus_etiquetas(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (Paisaje::todos() as $campo => $criterio) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }

        // Las etiquetas varían por criterio: si el formulario mostrara una
        // escala genérica, este texto concreto no aparecería.
        $respuesta->assertSee('Conurbaciones');
        $respuesta->assertSee('Controlado');
        $respuesta->assertSee('Identitario');
    }

    public function test_la_pagina_de_resultados_se_renderiza(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(3));

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Neutro')
            ->assertSee('Perfil por categoría');
    }

    /**
     * Navega de verdad a la página de resultados como admin, en vez de solo
     * comprobar que el panel enlaza a ella. Antes de este test, un $readonly
     * que nadie ponía se quedaba en false para siempre y nada lo detectaba:
     * el admin veía el pie de página de jefe/equipo con el formulario abierto.
     *
     * Ajustado en volver-a-la-zona: ya no hay "Volver a Zonas" que reservarle
     * al admin en esta vista -x-boton-volver recibe la zona, y con zona el
     * destino es el panel de ESA zona para los tres roles, no el listado de
     * cada uno-, y el enlace "Volver al Formulario" ya no depende del rol:
     * se muestra siempre, porque el admin también puede volver a editar un
     * borrador. Lo que sigue siendo cierto -y lo que este test tiene que
     * seguir comprobando- es que el admin llega a un sitio sensato.
     */
    public function test_el_admin_ve_los_resultados_de_paisaje_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Eficiente')
            ->assertSee(route('operativo.zona.panel', $this->zona->id), false);
    }

    /**
     * Complementa el test anterior: sin este, un arreglo que ocultara el
     * enlace al formulario para todo el mundo (no solo para el admin) pasaría
     * igual el test de arriba.
     */
    public function test_el_jefe_ve_el_enlace_al_formulario_en_los_resultados_de_paisaje(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5));

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }

    /** El admin también ve el enlace: <x-pestanas-matriz> no distingue por rol. */
    public function test_el_admin_ve_el_enlace_al_formulario_en_los_resultados_de_paisaje(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }

    /**
     * Tarea 1: sobre una matriz en borrador -aunque esté completa, como aquí,
     * con sus 34 criterios respondidos- el admin recibe el mismo enlace de
     * editar que el jefe y el equipo. Antes, con la matriz completa, se le
     * mandaba directo a resultados como si ya no pudiera tocarla; ahora sí
     * puede, porque no está validada.
     */
    public function test_el_admin_recibe_el_enlace_de_editar_con_paisaje_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertDontSee('Modo consulta')
            ->assertSee('Paisaje')
            ->assertSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }

    /** Lo único que no cambia: una matriz ya validada lo sigue mandando a resultados. */
    public function test_el_admin_no_recibe_edicion_con_paisaje_validado(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(5) + ['accion_estado' => 'confirmado']
        );

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $html = $this->actingAs($admin)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertDontSee('Modo consulta')
            ->assertSee(route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id), false)
            ->getContent();

        $this->assertStringContainsString('Validada', $html);
    }

    /**
     * El admin escribe borradores desde que se le dio permiso; lo que no puede
     * es validar. La petición no se rechaza, se degrada.
     */
    public function test_el_admin_guarda_borrador_pero_no_valida(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->post(
            $this->url(),
            $this->todosEn(5) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPaisaje::value('estado'));
    }

    /**
     * Invertido en la Tarea 2 de permisos-y-navegación: el admin ya no es de
     * solo lectura, así que un borrador de Paisaje le llega editable, con el
     * botón "Guardar Borrador" y ninguna tarjeta deshabilitada, igual que ve
     * el jefe. Antes este test comprobaba justo lo contrario, y era lo
     * correcto mientras el admin no podía escribir.
     *
     * <x-criterio-pildoras> deshabilita cada radio con @disabled($bloqueado),
     * que solo emite el atributo "disabled" cuando es verdadero: en esta
     * página no aparece en ningún otro sitio.
     */
    public function test_el_admin_recibe_el_formulario_editable_estando_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(3))
            ->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPaisaje::value('estado'));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $respuesta = $this->actingAs($admin)->get($this->url())->assertOk();

        $respuesta->assertSee('Guardar Borrador');
        $respuesta->assertDontSee('disabled', false);
    }

    /**
     * El hermano que conserva la regla que sigue viva: con Paisaje validada,
     * el admin la recibe bloqueada -las tarjetas deshabilitadas-, igual que
     * el equipo.
     */
    public function test_el_admin_recibe_el_formulario_bloqueado_cuando_la_matriz_esta_validada(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(3) + ['accion_estado' => 'confirmado']
        );

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $respuesta = $this->actingAs($admin)->get($this->url())->assertOk();

        $respuesta->assertDontSee('Guardar Borrador');
        $respuesta->assertSee('disabled', false);
    }

    public function test_la_zona_aparece_en_el_dashboard_con_su_progreso(): void
    {
        $this->actingAs($this->jefe)->get('/mis-zonas')
            ->assertOk()
            ->assertSee($this->zona->nombre)
            ->assertSee(route('operativo.zona.panel', $this->zona->id), false)
            ->assertSee('0 / 10');
    }

    public function test_paisaje_es_alcanzable_desde_la_pagina_de_zona(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee('Paisaje')
            ->assertSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }

    /**
     * Igual que en FIT/FET: la barra lateral aparece con un enlace por
     * categoría -5 en Paisaje-. Se cuentan los enlaces de ancla, no el texto
     * suelto de la fracción.
     *
     * El ancho de la página ya no se comprueba aquí: ahora lo decide
     * <x-contenedor> en el layout, y lo cubre ContenedorTest. Repetir un
     * literal de clase Tailwind en cada vista era justo la duplicación que
     * la fundación visual vino a quitar.
     */
    public function test_el_formulario_muestra_la_barra_lateral_con_sus_categorias(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        // Str::between() devuelve la cadena COMPLETA cuando el delimitador
        // no existe -nunca una cadena vacía-, así que assertNotEmpty()
        // sobre el resultado no protegía nada: sin <aside>, $fragmento
        // habría sido la página entera y este assert habría pasado igual.
        // Se comprueba la presencia real del delimitador antes de recortar.
        $this->assertStringContainsString('<aside', $html, 'No se encontró <aside>: la barra lateral no se está pintando.');
        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');

        foreach (array_keys(Paisaje::CATEGORIAS) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace a la categoría '{$clave}'.");
        }
    }

    /**
     * Con 'ep_cambios_tiempo' respondido de los 6 criterios de 'ep', la
     * barra muestra 1/6 para esa categoría, sin marcador de completa.
     */
    public function test_la_barra_lateral_desglosa_los_respondidos_por_categoria(): void
    {
        $evaluacion = EvaluacionPaisaje::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);
        $evaluacion->update(['ep_cambios_tiempo' => 3]);

        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<aside', $html, 'No se encontró <aside>: la barra lateral no se está pintando.');
        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $ep = \Illuminate\Support\Str::between($fragmento, 'href="#ep"', '</a>');

        $this->assertStringContainsString('1/6', $ep);
        $this->assertStringNotContainsString('✓', $ep);
    }
}
