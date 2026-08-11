<?php

namespace Tests\Feature;

use App\Matrices\ValoracionTerritorial;
use App\Models\EvaluacionValoracionTerritorial;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ValoracionTerritorialTest extends TestCase
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

    /** Rellena los 21 criterios con el mismo valor. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(array_keys(ValoracionTerritorial::todos()), $valor);
    }

    private function url(string $sufijo = ''): string
    {
        return "/operativo/zona/{$this->zona->id}/valoracion-territorial{$sufijo}";
    }

    public function test_todos_los_criterios_al_maximo_dan_el_tope_de_la_escala(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionValoracionTerritorial::firstOrFail();

        $this->assertEqualsWithDelta(2.0, $eval->ct_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->uc_total, 0.0001);
        $this->assertSame('Territorio a Priorizar para el Turismo IV', $eval->cuadrante);
    }

    public function test_todos_los_criterios_en_uno_dan_exactamente_uno(): void
    {
        // Los pesos suman 1, así que este es el caso del umbral.
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(1))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionValoracionTerritorial::firstOrFail();

        $this->assertEqualsWithDelta(1.0, $eval->ct_total, 0.0001);
        $this->assertEqualsWithDelta(1.0, $eval->uc_total, 0.0001);
        $this->assertSame('Territorio a Priorizar para el Turismo IV', $eval->cuadrante);
    }

    public function test_cada_criterio_aporta_segun_su_peso(): void
    {
        // Vialidad pesa 0.15 en UC; con calificación 2 aporta 0.30 y nada más.
        $datos = $this->todosEn(0);
        $datos['uc_vialidad'] = 2;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionValoracionTerritorial::firstOrFail();

        $this->assertEqualsWithDelta(0.30, $eval->uc_total, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->ct_total, 0.0001);
    }

    public function test_una_calificacion_fuera_de_escala_se_rechaza(): void
    {
        $datos = $this->todosEn(1);
        $datos['ct_salud'] = 3;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('ct_salud');

        $this->assertDatabaseCount('evaluaciones_valoracion_territorial', 0);
    }

    public function test_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)->post(
            $this->url(),
            $this->todosEn(2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionValoracionTerritorial::value('estado'));
    }

    public function test_el_jefe_con_accion_estado_confirmado_deja_la_evaluacion_confirmada(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('confirmado', EvaluacionValoracionTerritorial::value('estado'));
    }

    public function test_una_evaluacion_confirmada_queda_cerrada_para_el_equipo(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(2) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->post($this->url(), $this->todosEn(0))
            ->assertSessionHas(
                'error',
                'Esta evaluación de Valoración Territorial ya fue validada por el Jefe de Zona. No puedes editarla.'
            );

        // Los valores del jefe siguen intactos: el intento del equipo no escribió nada.
        $this->assertEqualsWithDelta(
            2.0,
            (float) EvaluacionValoracionTerritorial::value('ct_total'),
            0.0001
        );
    }

    public function test_no_se_accede_desde_una_zona_ajena(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }

    /**
     * Tarea 1: sobre una matriz en borrador -aunque esté completa, como aquí-
     * el admin recibe el mismo enlace de editar que el jefe y el equipo, y ya
     * no la franja de "Modo consulta". Ver el comentario equivalente en
     * PaisajeTest::test_el_admin_recibe_el_enlace_de_editar_con_paisaje_en_borrador.
     */
    public function test_el_admin_recibe_el_enlace_de_editar_con_valoracion_territorial_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertDontSee('Modo consulta')
            ->assertSee('Valoración territorial')
            ->assertSee(route('operativo.evaluacion_valoracion_territorial.edit', $this->zona->id), false);
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
            $this->todosEn(2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionValoracionTerritorial::value('estado'));
    }

    /**
     * Invertido en la Tarea 2 de permisos-y-navegación: el admin ya no es de
     * solo lectura, así que un borrador de Valoración Territorial le llega
     * editable, con el botón "Guardar Borrador" y ninguna tarjeta
     * deshabilitada, igual que ve el jefe. Antes este test comprobaba justo
     * lo contrario, y era lo correcto mientras el admin no podía escribir.
     *
     * <x-criterio-escala> deshabilita cada radio con @disabled($bloqueado),
     * que solo emite el atributo "disabled" cuando es verdadero: en esta
     * página no aparece en ningún otro sitio.
     */
    public function test_el_admin_recibe_el_formulario_editable_estando_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(1))
            ->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionValoracionTerritorial::value('estado'));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $respuesta = $this->actingAs($admin)->get($this->url())->assertOk();

        $respuesta->assertSee('Guardar Borrador');
        $respuesta->assertDontSee('disabled', false);
    }

    /**
     * El hermano que conserva la regla que sigue viva: con Valoración
     * Territorial validada, el admin la recibe bloqueada -las tarjetas
     * deshabilitadas-, igual que el equipo.
     */
    public function test_el_admin_recibe_el_formulario_bloqueado_cuando_la_matriz_esta_validada(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(1) + ['accion_estado' => 'confirmado']
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
            ->assertSee('0 / 9');
    }

    public function test_valoracion_territorial_es_alcanzable_desde_la_pagina_de_zona(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee('Valoración territorial')
            ->assertSee(route('operativo.evaluacion_valoracion_territorial.edit', $this->zona->id), false);
    }

    public static function cuadrantes(): array
    {
        return [
            'ambos bajos'      => [0.5, 0.5, 'Territorio No Apto para el Turismo'],
            'solo conectado'   => [0.5, 1.5, 'Territorio con Limitación II'],
            'solo contenido'   => [1.5, 0.5, 'Territorio con Limitación III'],
            'ambos altos'      => [1.5, 1.5, 'Territorio a Priorizar para el Turismo IV'],
            'justo en el umbral' => [1.0, 1.0, 'Territorio a Priorizar para el Turismo IV'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cuadrantes')]
    public function test_el_cuadrante_se_deriva_de_los_totales(float $ct, float $uc, string $esperado): void
    {
        $evaluacion = new EvaluacionValoracionTerritorial([
            'ct_total' => $ct,
            'uc_total' => $uc,
        ]);

        $this->assertSame($esperado, $evaluacion->cuadrante);
    }

    public function test_el_formulario_se_renderiza_con_los_21_criterios(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url());

        $respuesta->assertOk();

        foreach (array_keys(ValoracionTerritorial::todos()) as $campo) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }
    }

    /**
     * Una evaluación nueva no debe traer nada marcado. Antes se preseleccionaba
     * 0 en los 21 criterios, así que el formulario podía enviarse sin leer uno
     * solo y quedaba como una valoración válida de puros ceros.
     */
    /**
     * Extrae el estado inicial de Alpine del HTML.
     *
     * La selección no viaja en el atributo `checked` —la lleva x-model—, así
     * que hay que leer el `x-data`. Blade lo emite como JSON.parse('...') con
     * las comillas escapadas a ", porque Js::from() codifica dos veces.
     *
     * @return array<string, int|null> campo => calificación
     */
    private function estadoInicial(string $html): array
    {
        preg_match_all("/JSON\.parse\('(.+?)'\)/", $html, $coincidencias);

        $valores = [];

        foreach ($coincidencias[1] as $escapado) {
            // Js::from() codifica dos veces, así que el contenido de JSON.parse
            // es el cuerpo de una cadena JSON. Decodificarlo como tal deshace el
            // escapado sin tener que manipular las secuencias a mano.
            $json = json_decode('"' . $escapado . '"');

            $valores += json_decode((string) $json, true) ?? [];
        }

        return $valores;
    }

    public function test_una_evaluacion_nueva_no_trae_ningun_nivel_preseleccionado(): void
    {
        $this->assertDatabaseCount('evaluaciones_valoracion_territorial', 0);

        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();

        $estado = $this->estadoInicial($html);

        $this->assertCount(21, $estado, 'deben venir los 21 criterios en el estado');
        $this->assertSame(
            array_keys(ValoracionTerritorial::todos()),
            array_keys($estado)
        );

        foreach ($estado as $campo => $valor) {
            $this->assertNull($valor, "{$campo} no debería venir preseleccionado");
        }
    }

    /** Al editar una guardada sí se recuperan las calificaciones registradas. */
    public function test_una_evaluacion_guardada_recupera_sus_calificaciones(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2))
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();

        $estado = $this->estadoInicial($html);

        $this->assertCount(21, $estado);

        foreach ($estado as $campo => $valor) {
            $this->assertSame(2, $valor, "{$campo} debería recuperar su calificación");
        }
    }

    /** Dejar un criterio sin responder no puede confirmarse. */
    public function test_no_se_confirma_con_criterios_sin_responder(): void
    {
        $datos = $this->todosEn(1) + ['accion_estado' => 'confirmado'];
        unset($datos['uc_senalizacion']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('uc_senalizacion');

        $this->assertDatabaseCount('evaluaciones_valoracion_territorial', 0);
    }

    /**
     * El test anterior solo comprueba que existan los 21 `name="..."`, lo que
     * pasaría igual con un <select> de etiquetas genéricas (0/1/2). La
     * decisión de diseño de este formulario es que la descripción completa de
     * cada nivel sea la opción, así que se verifica que el texto literal de
     * un nivel concreto llegue al HTML.
     */
    public function test_el_formulario_muestra_la_descripcion_completa_de_los_niveles(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url());

        $respuesta->assertOk();
        $respuesta->assertSee(
            'Las vías de acceso al territorio cuentan con un mantenimiento adecuado y son de primer orden'
        );
    }

    public function test_la_pagina_de_resultados_se_renderiza(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2));

        $this->actingAs($this->jefe)
            ->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Territorio a Priorizar para el Turismo IV');
    }

    /**
     * Navega de verdad a la página de resultados como admin, en vez de solo
     * comprobar que el panel enlaza a ella. Antes de este test, un $readonly
     * que nadie ponía se quedaba en false para siempre y nada lo detectaba:
     * el admin veía el pie de página de jefe/equipo con el formulario abierto.
     *
     * Ajustado en la Tarea 2 de permisos-y-navegación: "Volver a Zonas" ya no
     * es el texto que ve el admin aquí -x-boton-volver se invoca en esta
     * vista con texto="Mis Zonas" fijo-, y el enlace al formulario ya no
     * depende del rol: se muestra siempre, porque el admin también puede
     * volver a editar un borrador. Lo que sigue siendo cierto -y lo que este
     * test tiene que seguir comprobando- es que el botón de volver del admin
     * apunta a SU listado, no al del jefe/equipo.
     */
    public function test_el_admin_ve_los_resultados_de_valoracion_territorial_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Territorio a Priorizar para el Turismo IV')
            ->assertSee(route('admin.zonas.index'), false)
            ->assertDontSee(route('operativo.dashboard'), false);
    }

    /**
     * Complementa el test anterior: sin este, un arreglo que ocultara el
     * enlace al formulario para todo el mundo (no solo para el admin) pasaría
     * igual el test de arriba.
     */
    public function test_el_jefe_ve_el_enlace_al_formulario_en_los_resultados_de_valoracion_territorial(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2));

        $this->actingAs($this->jefe)
            ->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_valoracion_territorial.edit', $this->zona->id), false);
    }

    /** El admin también ve el enlace: mismo caso que Paisaje, FIT, FET y Percepción. */
    public function test_el_admin_ve_el_enlace_al_formulario_en_los_resultados_de_valoracion_territorial(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_valoracion_territorial.edit', $this->zona->id), false);
    }

    /**
     * La tabla de desglose es la que explica por qué la zona cayó en su
     * cuadrante; no basta con ver el nombre del cuadrante. Vialidad pesa
     * 0.15 en UC, así que con calificación 2 su aporte es 2 * 0.15 = 0.300.
     */
    public function test_la_pagina_de_resultados_muestra_el_aporte_de_un_criterio(): void
    {
        $datos = $this->todosEn(0);
        $datos['uc_vialidad'] = 2;

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)
            ->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Vialidad')
            ->assertSee('0.300');
    }

    /**
     * El mapa de estilos por cuadrante tiene cuatro entradas; el test de
     * renderizado solo cubre la de máxima puntuación. Sin verificar otro
     * cuadrante, tres de las cuatro entradas del mapa podrían tener la clave
     * o el texto mal y ningún test lo notaría.
     *
     * No basta con `assertSee('Territorio con Limitación II')`: ese texto
     * aparece siempre en la leyenda estática de los cuatro cuadrantes,
     * independientemente del resultado. En cambio la lectura ("Bien
     * conectado...") solo se imprime una vez, en la tarjeta que usa la
     * entrada del mapa seleccionada según `$evaluacion->cuadrante`.
     */
    public function test_la_pagina_de_resultados_refleja_un_cuadrante_distinto(): void
    {
        // CT bajo (todos en 0) y UC alto (todos en 2): Territorio con Limitación II.
        $datos = $this->todosEn(0);
        foreach (array_keys(ValoracionTerritorial::UC) as $campo) {
            $datos[$campo] = 2;
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)
            ->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Bien conectado, pero sin base territorial suficiente.');
    }
}
