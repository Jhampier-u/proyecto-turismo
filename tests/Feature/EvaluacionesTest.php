<?php

namespace Tests\Feature;

use App\Matrices\Percepcion;
use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPotencialidad;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fija las fórmulas de ponderación de las matrices y el flujo borrador →
 * confirmado. Son el núcleo del sistema y no tenían ninguna cobertura.
 */
class EvaluacionesTest extends TestCase
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

    private const CAMPOS_FIT = [
        'recursos_culturales', 'recursos_naturales',
        'atractivos_manifestaciones', 'atractivos_sitios',
        'prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza',
        'productos_territoriales',
        'infraestructura_basica', 'infraestructura_apoyo',
        'facilidades_senaletica', 'facilidades_recepcion', 'facilidades_interpretacion',
        'facilidades_senderos', 'facilidades_estacionamientos', 'facilidades_campamentos',
        'facilidades_miradores', 'facilidades_sanitarios',
    ];

    private const CAMPOS_FET = [
        'demanda_flujos', 'demanda_estadia',
        'super_institucionalidad', 'super_organizacion', 'super_planificacion',
        'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing',
    ];

    /** Subconjunto pequeño y legible de los campos reales de Potencialidad. */
    private const CAMPOS_POTENCIALIDAD = ['rn_agua_lagos', 'rn_agua_rios', 'i_transporte'];

    /** Rellena todos los campos con el mismo valor. */
    private function todos(array $campos, int $valor): array
    {
        return array_fill_keys($campos, $valor);
    }

    public function test_fit_con_la_puntuacion_maxima_da_el_tope_de_la_escala(): void
    {
        // Los pesos (0.30 + 0.05 + 0.20 + 0.05 + 0.20 + 0.20) suman 1.0,
        // así que todo en 3 debe dar exactamente 3.
        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fit", $this->todos(self::CAMPOS_FIT, 3))
            ->assertSessionHasNoErrors();

        $fit = EvaluacionFit::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(3.0, (float) $fit->fit, 0.0001);
        $this->assertEqualsWithDelta(0.90, (float) $fit->fit_rtt, 0.0001);
        $this->assertEqualsWithDelta(0.15, (float) $fit->fit_at, 0.0001);
        $this->assertEqualsWithDelta(0.60, (float) $fit->fit_pst, 0.0001);
    }

    public function test_fit_promedia_por_bloque_y_no_por_campo(): void
    {
        // recursos_culturales=3 y recursos_naturales=0 → media del bloque 1.5
        $datos = $this->todos(self::CAMPOS_FIT, 0);
        $datos['recursos_culturales'] = 3;

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fit", $datos)
            ->assertSessionHasNoErrors();

        $fit = EvaluacionFit::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(1.5, (float) $fit->media_rtt, 0.0001);
        $this->assertEqualsWithDelta(0.45, (float) $fit->fit_rtt, 0.0001);  // 1.5 * 0.30
        $this->assertEqualsWithDelta(0.45, (float) $fit->fit, 0.0001);
    }

    public function test_fet_pondera_demanda_superestructura_e_imagen(): void
    {
        // Pesos 0.20 + 0.40 + 0.40 = 1.0
        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fet", $this->todos(self::CAMPOS_FET, 3))
            ->assertSessionHasNoErrors();

        $fet = EvaluacionFet::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(3.0, (float) $fet->fet, 0.0001);
        $this->assertEqualsWithDelta(0.60, (float) $fet->fet_demanda, 0.0001);
        $this->assertEqualsWithDelta(1.20, (float) $fet->fet_super, 0.0001);
        $this->assertEqualsWithDelta(1.20, (float) $fet->fet_imagen, 0.0001);
    }

    public function test_el_jefe_confirma_y_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        // El equipo no puede confirmar, aunque lo envíe explícitamente.
        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionFet::where('zona_id', $this->zona->id)->value('estado'));

        // El jefe sí.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('confirmado', EvaluacionFet::where('zona_id', $this->zona->id)->value('estado'));
    }

    public function test_una_evaluacion_confirmada_queda_cerrada_para_el_equipo(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 0)
        );

        // Los valores del jefe siguen intactos.
        $this->assertEqualsWithDelta(
            3.0,
            (float) EvaluacionFet::where('zona_id', $this->zona->id)->value('fet'),
            0.0001
        );
    }

    public function test_el_mensaje_de_evaluacion_fet_cerrada_es_el_especifico_de_fet(): void
    {
        // El refactor que extrajo EvaluacionZonaController (f2514c0) dejó a
        // EvaluacionFetController heredando el mensaje genérico de la base.
        // Este test fija el texto específico de FET para que no se vuelva a
        // perder en silencio en un futuro refactor.
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 0)
        )->assertSessionHas(
            'error',
            'Esta evaluación FET ya fue validada por el Jefe. No puedes editarla.'
        );
    }

    /**
     * Camino real que llevaba al 403: panel de zona como admin → FIT en
     * borrador → «Ver» → resultados → «Ver el Formulario» → edit. Ahí
     * $bloqueado solo miraba si estaba confirmado, así que el admin veía los
     * 18 selects habilitados y el botón "Guardar Borrador", que terminaba en
     * un 403 crudo del middleware al enviarlo.
     */
    public function test_el_admin_recibe_el_formulario_fit_bloqueado_aunque_este_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 2)
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionFit::where('zona_id', $this->zona->id)->value('estado'));

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $respuesta = $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit")
            ->assertOk();

        $respuesta->assertDontSee('Guardar Borrador');
        // FIT migró de <select> a <x-criterio-pildoras> en la rama
        // fit-fet-componentes: ya no hay un único `disabled>` al final de una
        // etiqueta -assertSee('disabled', false) a secas también se
        // arriesgaba a un falso positivo si algún día aparece una clase
        // Tailwind "disabled:" en la página-. Contar los 72 radios
        // deshabilitados (18 criterios × 4 niveles) es la comprobación que de
        // verdad importa: que el formulario entero quedó bloqueado, no solo
        // que la palabra "disabled" aparece en algún sitio.
        $this->assertSame(18 * 4, substr_count($respuesta->getContent(), 'disabled'));
    }

    /**
     * Motivo de bloqueo por ROL, no por validación: la evaluación sigue en
     * borrador y aun así el admin no puede tocarla. Antes de este cambio el
     * formulario decía «Solo el Jefe de Zona puede reabrir o editar...» de
     * todos modos, un motivo que no es el suyo -esta evaluación nunca estuvo
     * validada, y al admin le da igual: nunca edita ninguna-.
     */
    public function test_el_admin_ve_su_propio_motivo_de_bloqueo_en_fit(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 2)
        );

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit")
            ->assertOk()
            ->assertSee('El administrador puede consultar esta evaluación, pero no puede modificarla.')
            ->assertDontSee('Solo el Jefe de Zona puede reabrir o editar una evaluación validada.');
    }

    /**
     * Motivo de bloqueo por ESTADO, no por rol: el equipo sí puede editar
     * evaluaciones -solo esta ya fue validada por el jefe-. Es el motivo
     * correcto para este caso, y el único que debe verse.
     */
    public function test_el_equipo_ve_el_motivo_de_validacion_en_fit_bloqueada(): void
    {
        $equipo = User::factory()->create(['role_id' => Role::where('nombre', 'equipo')->value('id')]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit")
            ->assertOk()
            ->assertSee('Solo el Jefe de Zona puede reabrir o editar una evaluación validada.')
            ->assertDontSee('El administrador puede consultar esta evaluación, pero no puede modificarla.');
    }

    /** Mismo caso que FIT, aplicado a FET. */
    public function test_el_admin_recibe_el_formulario_fet_bloqueado_aunque_este_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 2)
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionFet::where('zona_id', $this->zona->id)->value('estado'));

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $respuesta = $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fet")
            ->assertOk();

        $respuesta->assertDontSee('Guardar Borrador');
        // Ver el comentario equivalente en el test de FIT: 9 criterios × 4
        // niveles = 36 radios deshabilitados.
        $this->assertSame(9 * 4, substr_count($respuesta->getContent(), 'disabled'));
    }

    /** Mismo caso que FIT: Percepción migró de <select> a <x-criterio-pildoras> en esta rama. */
    public function test_el_admin_recibe_el_formulario_percepcion_bloqueado_aunque_este_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 2)
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPercepcion::where('zona_id', $this->zona->id)->value('estado'));

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $respuesta = $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-percepcion")
            ->assertOk();

        $respuesta->assertDontSee('Guardar Borrador');

        // A diferencia de FIT/FET, esta página tiene un <textarea> con la
        // clase Tailwind "disabled:bg-gray-100" -contiene la palabra
        // "disabled" en su nombre, siempre, esté o no bloqueado- y su propio
        // atributo `disabled` condicional. Un substr_count() a secas sobre
        // toda la página los contaría también (50, no 48) y dejaría de medir
        // lo que dice medir. Contar solo dentro de <input type="radio"> evita
        // ese falso positivo: 16 criterios × 3 niveles = 48 radios, todos
        // deshabilitados.
        preg_match_all('/<input type="radio"[^>]*>/', $respuesta->getContent(), $radios);
        $this->assertCount(48, $radios[0], 'deberían existir 48 radios (16 × 3 niveles)');
        $this->assertCount(
            48,
            array_filter($radios[0], fn($tag) => str_contains($tag, 'disabled')),
            'los 48 radios deberían estar deshabilitados'
        );
    }

    /** Mismo caso que FIT, para Percepción: bloqueo por rol, no por validación. */
    public function test_el_admin_ve_su_propio_motivo_de_bloqueo_en_percepcion(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 2)
        );

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-percepcion")
            ->assertOk()
            ->assertSee('El administrador puede consultar esta matriz, pero no puede modificarla.')
            ->assertDontSee('Solo el Jefe de Zona puede reabrir o editar una matriz validada.');
    }

    /** Mismo caso que FIT, para Percepción: bloqueo por validación, no por rol. */
    public function test_el_equipo_ve_el_motivo_de_validacion_en_percepcion_bloqueada(): void
    {
        $equipo = User::factory()->create(['role_id' => Role::where('nombre', 'equipo')->value('id')]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-percepcion")
            ->assertOk()
            ->assertSee('Solo el Jefe de Zona puede reabrir o editar una matriz validada.')
            ->assertDontSee('El administrador puede consultar esta matriz, pero no puede modificarla.');
    }

    /**
     * Potencialidad ya contemplaba al admin en su fórmula ($soloLectura),
     * sin depender de si la matriz estaba confirmada: este test no arranca en
     * rojo, es la red de seguridad para que la unificación del predicado
     * (paso 3 del hallazgo 2) no lo rompa por accidente.
     */
    public function test_el_admin_recibe_el_formulario_potencialidad_bloqueado_aunque_este_en_borrador(): void
    {
        $datos = ['campos' => self::CAMPOS_POTENCIALIDAD] + $this->todos(self::CAMPOS_POTENCIALIDAD, 1);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-potencialidad", $datos)
            ->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPotencialidad::where('zona_id', $this->zona->id)->value('estado'));

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $respuesta = $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-potencialidad")
            ->assertOk();

        // Potencialidad no tiene un botón "Guardar Borrador" con B mayúscula:
        // el suyo es "Guardar borrador" (con b minúscula).
        $respuesta->assertDontSee('Guardar borrador');

        // Hasta la rama potencialidad-componentes, el modo lectura pintaba una
        // insignia («🔴 0», «🟡 1», «🟢 2») en vez de un <select>, y esta
        // aserción comprobaba que no aparecía ningún name="..." -ninguna
        // insignia lo lleva-. El control cambió a <x-criterio-pildoras>, que
        // SÍ lleva name="..." incluso deshabilitado -es el mismo componente,
        // y la misma comprobación, que ya usan FIT/FET/Percepción-, así que
        // la aserción original dejaría de tener sentido tal cual. Se
        // sustituye por una más estricta: cuenta los radios reales y exige
        // que los tres campos activos (3 × 3 niveles = 9) estén deshabilitados,
        // en vez de asumir que la palabra "disabled" en el HTML basta -las
        // clases `disabled:` de Tailwind la contienen sin que nada esté
        // deshabilitado-.
        $contenido = $respuesta->getContent();

        preg_match_all('/<input type="radio"[^>]*>/', $contenido, $radios);
        $this->assertCount(9, $radios[0], 'CAMPOS_POTENCIALIDAD tiene 3 campos activos × 3 niveles.');

        $deshabilitados = array_filter($radios[0], fn(string $radio) => str_contains($radio, 'disabled'));
        $this->assertCount(9, $deshabilitados, 'los 9 radios deben estar deshabilitados para el admin.');
    }

    /**
     * Navega de verdad a la página de resultados de FIT como admin, en vez de
     * solo comprobar que el panel enlaza a ella. El enlace «Ver el
     * Formulario» de esta vista no miraba el rol: era la tercera fuga del
     * mismo bug ya arreglado en Paisaje, Percepción y Valoración Territorial.
     */
    public function test_el_admin_ve_los_resultados_de_fit_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit/ponderacion")
            ->assertOk()
            ->assertSee('Volver a Zonas')
            ->assertSee(route('admin.zonas.index'), false)
            ->assertDontSee(route('operativo.evaluacion_fit.edit', $this->zona->id), false);
    }

    /** Complementa el test anterior: el jefe debe seguir viendo el enlace. */
    public function test_el_jefe_ve_el_enlace_al_formulario_en_los_resultados_de_fit(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit/ponderacion")
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_fit.edit', $this->zona->id), false);
    }

    /** Mismo caso que FIT, para FET. */
    public function test_el_admin_ve_los_resultados_de_fet_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fet/ponderacion")
            ->assertOk()
            ->assertSee('Volver a Zonas')
            ->assertSee(route('admin.zonas.index'), false)
            ->assertDontSee(route('operativo.evaluacion_fet.edit', $this->zona->id), false);
    }

    /** Complementa el test anterior: el jefe debe seguir viendo el enlace. */
    public function test_el_jefe_ve_el_enlace_al_formulario_en_los_resultados_de_fet(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fet/ponderacion")
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_fet.edit', $this->zona->id), false);
    }

    public function test_el_mensaje_de_evaluacion_fit_cerrada_es_el_especifico_de_fit(): void
    {
        // Mismo caso que FET: fija el texto propio de FIT para que un
        // refactor futuro no lo sustituya en silencio por el genérico.
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 0)
        )->assertSessionHas(
            'error',
            'Evaluación cerrada. No puedes editar.'
        );
    }

    public function test_potencialidad_redirige_al_formulario_y_no_a_resultados(): void
    {
        // A diferencia de FIT, FET y Percepción, el formulario de Potencialidad
        // incluye la selección de campos activos: tras guardar debe volver a él
        // (.edit) y no a la página de resultados (.ponderacion), para poder
        // seguir ajustando esa configuración. Ver EvaluacionPotencialidadController::rutaResultados().
        $datos = ['campos' => self::CAMPOS_POTENCIALIDAD] + $this->todos(self::CAMPOS_POTENCIALIDAD, 2);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-potencialidad", $datos)
            ->assertRedirect(route('operativo.evaluacion_potencialidad.edit', $this->zona->id));
    }

    public function test_el_mensaje_de_evaluacion_potencialidad_cerrada_es_el_especifico_de_potencialidad(): void
    {
        // Mismo caso que FIT/FET/Percepción: fija el texto propio de
        // Potencialidad para que un refactor futuro no lo sustituya en
        // silencio por el genérico de la clase base.
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $datos = ['campos' => self::CAMPOS_POTENCIALIDAD] + $this->todos(self::CAMPOS_POTENCIALIDAD, 2);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad",
            $datos + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad",
            $datos
        )->assertSessionHas(
            'error',
            'Evaluación cerrada. No puedes editar.'
        );
    }

    public function test_potencialidad_conserva_el_valor_previo_de_un_campo_que_se_desactiva(): void
    {
        // Esto es justamente lo que impide a Potencialidad usar la clase base
        // MatrizPonderadaController como las otras matrices: al desactivar un
        // campo, prepararDatos() no lo pone a 0, conserva el valor guardado.
        // 1) 'rn_agua_lagos' se guarda activo con calificación 2.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad",
            ['campos' => ['rn_agua_lagos', 'i_transporte'], 'rn_agua_lagos' => 2, 'i_transporte' => 1]
        )->assertSessionHasNoErrors();

        $this->assertSame(
            2,
            (int) EvaluacionPotencialidad::where('zona_id', $this->zona->id)->value('rn_agua_lagos')
        );

        // 2) Se vuelve a guardar sin 'rn_agua_lagos' entre los campos activos
        // (solo 'i_transporte', para que la petición siga siendo válida).
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad",
            ['campos' => ['i_transporte'], 'i_transporte' => 1]
        )->assertSessionHasNoErrors();

        // El valor original de 'rn_agua_lagos' sigue intacto, no se resetea a 0.
        $this->assertSame(
            2,
            (int) EvaluacionPotencialidad::where('zona_id', $this->zona->id)->value('rn_agua_lagos')
        );
    }

    public function test_potencialidad_pone_a_cero_un_campo_inactivo_sin_evaluacion_previa(): void
    {
        // Contraparte del test anterior: si no hay evaluación previa (zona
        // nueva), un campo que no está entre los activos sí debe guardarse
        // como 0, porque no hay `$actual` del que conservar un valor.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad",
            ['campos' => ['i_transporte'], 'i_transporte' => 2]
        )->assertSessionHasNoErrors();

        $this->assertSame(
            0,
            (int) EvaluacionPotencialidad::where('zona_id', $this->zona->id)->value('rn_agua_lagos')
        );
    }

    public function test_el_mensaje_de_evaluacion_percepcion_cerrada_es_el_especifico_de_percepcion(): void
    {
        // Percepción migró a MatrizPonderadaController conservando el texto
        // que ya usaba antes del refactor; este test lo fija.
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3) + ['accion_estado' => 'confirmado']
        );

        // El mínimo de la escala de Percepción es 1, no 0.
        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 1)
        )->assertSessionHas(
            'error',
            'Evaluación cerrada. No puedes editar.'
        );
    }

    /**
     * Las páginas de resultados construyen las gráficas con datos interpolados
     * desde Blade. Un error ahí no rompe la petición pero deja el <canvas> en
     * blanco, así que conviene al menos verificar que renderizan.
     */
    public function test_las_paginas_de_resultados_se_renderizan(): void
    {
        $zonaId = $this->zona->id;

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3)
        )->assertSessionHasNoErrors();

        foreach ([
            "/operativo/zona/{$zonaId}/evaluacion-fit/ponderacion",
            "/operativo/zona/{$zonaId}/evaluacion-fet/ponderacion",
            "/operativo/zona/{$zonaId}/evaluacion-percepcion/resultados",
            "/operativo/zona/{$zonaId}/resultado-vtt",
        ] as $url) {
            $this->actingAs($this->jefe)->get($url)->assertOk();
        }
    }

    public function test_el_vtt_se_guarda_al_confirmar_y_no_al_consultarlo(): void
    {
        $zonaId = $this->zona->id;

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        );

        // Con solo FIT confirmada todavía no hay instantánea.
        $this->assertDatabaseCount('vocacion_turistica_territorio', 0);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        );

        // Al confirmar la segunda, sí. Antes hacía falta abrir la página.
        $this->assertDatabaseHas('vocacion_turistica_territorio', ['zona_id' => $zonaId]);

        // Y el admin puede consultarla sin haberla generado nadie por él.
        // La ruta operativo.vtt.final sirve a los tres roles y su vista ya
        // distingue al admin; la antigua admin.vtt.final.admin (bajo
        // ['auth','admin'], sin el middleware zona) se eliminó por ser una
        // tercera vía sin comprobar hacia el formulario editable de FIT.
        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->actingAs($admin)
            ->get(route('operativo.vtt.final', $zonaId))
            ->assertOk();
    }

    public function test_un_accion_estado_invalido_se_rechaza_con_validacion(): void
    {
        // Antes llegaba a la columna enum y provocaba un error 500.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 2) + ['accion_estado' => 'no-existe']
        )->assertSessionHasErrors('accion_estado');

        $this->assertDatabaseCount('evaluaciones_fet', 0);
    }

    /** Los 16 ítems, tomados de App\Matrices\Percepcion, no tecleados. */
    private function itemsPercepcion(): array
    {
        return array_keys(Percepcion::todos());
    }

    public function test_percepcion_normaliza_el_total_entre_cero_y_uno(): void
    {
        // Pesos 0.20 + 0.40 + 0.20 + 0.20 = 1.0 y normalización /3,
        // así que todo en el máximo (3) debe dar exactamente 1.0.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3)
        )->assertSessionHasNoErrors();

        $percepcion = EvaluacionPercepcion::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(1.0, (float) $percepcion->percepcion_total, 0.0001);
        $this->assertEqualsWithDelta(0.40, (float) $percepcion->pond_pl, 0.0001);
    }

    public function test_percepcion_en_el_minimo_da_cero(): void
    {
        // El mínimo de la escala es 1, no 0: el total no puede ser 0 sino 1/3.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 1)
        )->assertSessionHasNoErrors();

        $total = (float) EvaluacionPercepcion::where('zona_id', $this->zona->id)->value('percepcion_total');

        $this->assertEqualsWithDelta(1 / 3, $total, 0.001);
    }

    /**
     * Navega de verdad a la página de resultados como admin, en vez de solo
     * comprobar que el panel enlaza a ella. Mismo caso que Paisaje y
     * Valoración Territorial: un $readonly que nadie ponía se quedaba en
     * false para siempre y nada lo detectaba.
     */
    public function test_el_admin_ve_los_resultados_de_percepcion_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3)
        )->assertSessionHasNoErrors();

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-percepcion/resultados")
            ->assertOk()
            ->assertSee('Volver a Zonas')
            ->assertSee(route('admin.zonas.index'), false)
            ->assertDontSee(route('operativo.evaluacion_percepcion.edit', $this->zona->id), false);
    }

    /**
     * Complementa el test anterior: sin este, un arreglo que ocultara el
     * enlace al formulario para todo el mundo (no solo para el admin) pasaría
     * igual el test de arriba.
     */
    public function test_el_jefe_ve_el_enlace_al_formulario_en_los_resultados_de_percepcion(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3)
        )->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-percepcion/resultados")
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_percepcion.edit', $this->zona->id), false);
    }
}
