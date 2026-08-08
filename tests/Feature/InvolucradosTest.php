<?php

namespace Tests\Feature;

use App\Matrices\Involucrados;
use App\Models\Involucrado;
use App\Models\InvolucradosConfig;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvolucradosTest extends TestCase
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

    /**
     * Los siete tipos de Mitchell y el caso sin ninguno.
     *
     * La tabla del instrumento original asocia «Exigentes» a legitimidad y
     * «Discrecionales» a urgencia, que es al revés de como los define Mitchell:
     * demanding es el que solo tiene urgencia y discretionary el que solo tiene
     * legitimidad. Se implementa según la fuente, no según la hoja.
     */
    public function test_los_tipos_de_mitchell_salen_de_los_tres_atributos(): void
    {
        $casos = [
            [false, false, false, 'No es actor relevante'],
            [true,  false, false, 'Adormecido'],
            [false, true,  false, 'Discrecional'],
            [false, false, true,  'Exigente'],
            [true,  false, true,  'Peligroso'],
            [true,  true,  false, 'Dominante'],
            [false, true,  true,  'Dependiente'],
            [true,  true,  true,  'Definitivo'],
        ];

        foreach ($casos as [$poder, $legitimidad, $urgencia, $esperado]) {
            $this->assertSame(
                $esperado,
                Involucrados::tipoDe($poder, $legitimidad, $urgencia),
                sprintf('poder=%d legitimidad=%d urgencia=%d', $poder, $legitimidad, $urgencia)
            );
        }
    }

    /** Los once criterios al mismo valor, para no repetir la lista en cada test. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(Involucrados::campos(), $valor);
    }

    public function test_un_actor_esta_completo_solo_con_sus_once_criterios(): void
    {
        $actor = Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $this->assertTrue($actor->estaCompleto());

        // El null se asigna DESPUÉS de crear el actor, y no metido en el
        // array de creación con "+": la unión de arrays con "+" conserva el
        // operando izquierdo cuando la clave se repite en los dos lados, así
        // que un ['leg_sociedad' => null] a la derecha de todosEn(2) nunca
        // llegaría a pisar el 2 que ya está puesto ahí, y este test pasaría
        // sin probar nada.
        $actor->leg_sociedad = null;
        $actor->save();

        $this->assertFalse($actor->fresh()->estaCompleto());
    }

    public function test_grado_suma_los_criterios_del_atributo_o_null_si_falta_alguno(): void
    {
        $actor = Involucrado::create($this->todosEn(3) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor con todo a 3',
        ]);

        // Siete criterios a 3 dan 21 en poder, dos a 3 dan 6 en legitimidad
        // (y en urgencia, que también tiene dos).
        $this->assertSame(21, $actor->grado('poder'));
        $this->assertSame(6, $actor->grado('legitimidad'));
        $this->assertSame(6, $actor->grado('urgencia'));

        $actor->pod_poder = null;
        $actor->save();
        $actor->refresh();

        $this->assertNull($actor->grado('poder'));
        // Un hueco en poder no debería afectar a los otros dos atributos.
        $this->assertSame(6, $actor->grado('legitimidad'));
    }

    public function test_el_scope_incompletos_encuentra_a_los_que_faltan_y_no_a_los_completos(): void
    {
        $completo = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Completo',
        ]);

        $incompleto = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Incompleto',
        ]);
        $incompleto->urg_criticidad = null;
        $incompleto->save();

        $ids = Involucrado::incompletos()->pluck('id');

        $this->assertTrue($ids->contains($incompleto->id));
        $this->assertFalse($ids->contains($completo->id));

        // Filtrado por zona, igual que el test hermano de más abajo: sin el
        // ->where('zona_id', ...) esta aserción solo la sostiene que la
        // tabla esté vacía de cualquier otro actor, cosa que hoy da la
        // transacción de RefreshDatabase pero que un seeder o una ejecución
        // en paralelo tumbarían con un fallo que se leería como un flake.
        $this->assertSame(
            1,
            Involucrado::where('zona_id', $this->zona->id)->incompletos()->count()
        );
    }

    /**
     * Sin el paréntesis que agrupa los orWhereNull en scopeIncompletos(), el
     * primer "or" se saldría del ->where('zona_id', ...) anterior y un actor
     * incompleto de OTRA zona aparecería igual en la consulta filtrada por
     * esta.
     */
    public function test_el_scope_incompletos_no_se_sale_de_un_filtro_previo_por_zona(): void
    {
        $otraZona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Otra zona',
        ]);

        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Completo de esta zona',
        ]);

        $incompletoDeOtraZona = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $otraZona->id,
            'nombre'  => 'Incompleto de otra zona',
        ]);
        $incompletoDeOtraZona->pod_poder = null;
        $incompletoDeOtraZona->save();

        $incompletosDeEstaZona = Involucrado::where('zona_id', $this->zona->id)->incompletos()->count();

        $this->assertSame(0, $incompletosDeEstaZona);
    }

    /**
     * El camino real de la tarea 3 es crear el actor y pintar su tipo en la
     * misma petición, sin pasar por un find() intermedio. Antes de fijar
     * $attributes en el modelo, tiene_poder llegaba en null justo tras
     * create() —los default(false) de la migración no los relee Eloquent
     * tras el insert—, y tipo_mitchell, que exige bool, reventaba con un
     * TypeError. find() enmascaraba el fallo porque una lectura sí trae los
     * false de la base, así que este test crea y no vuelve a consultar.
     */
    public function test_tipo_mitchell_funciona_sobre_un_actor_recien_creado_sin_find(): void
    {
        $actor = Involucrado::create($this->todosEn(0) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor recién creado',
        ]);

        $this->assertFalse($actor->tiene_poder);
        $this->assertSame('No es actor relevante', $actor->tipo_mitchell);

        $actor->tiene_poder    = true;
        $actor->tiene_urgencia = true;

        $this->assertSame('Peligroso', $actor->tipo_mitchell);
    }

    /** Lo mismo vale para un actor construido con new/make, sin guardar todavía. */
    public function test_un_actor_sin_guardar_trae_los_tres_atributos_en_false(): void
    {
        $actor = new Involucrado();

        $this->assertFalse($actor->tiene_poder);
        $this->assertFalse($actor->tiene_legitimidad);
        $this->assertFalse($actor->tiene_urgencia);
        $this->assertSame('No es actor relevante', $actor->tipo_mitchell);
    }

    public function test_involucrados_config_se_crea_y_resuelve_sus_relaciones(): void
    {
        $config = InvolucradosConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'borrador',
        ]);

        $this->assertDatabaseHas('involucrados_config', [
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'borrador',
        ]);

        $this->assertTrue($config->zona->is($this->zona));
        $this->assertTrue($config->user->is($this->jefe));
    }

    /**
     * El unique sobre zona_id es lo que garantiza como mucho una fila de
     * configuración por zona: EstadoZona::filaActores() la busca con
     * ->first() y asume que no hay dos, igual que con las siete matrices de
     * formulario. Sin el índice único, un segundo borrador para la misma
     * zona pasaría en silencio y ->first() devolvería una de las dos al
     * azar según el orden físico de la tabla.
     */
    public function test_solo_puede_haber_una_configuracion_por_zona(): void
    {
        InvolucradosConfig::create(['zona_id' => $this->zona->id]);

        $this->expectException(QueryException::class);

        InvolucradosConfig::create(['zona_id' => $this->zona->id]);
    }

    // ── CRUD por HTTP (Tarea 3) ─────────────────────────────────────────────

    private function urlIndex(Zona $zona): string
    {
        return "/operativo/zona/{$zona->id}/involucrados";
    }

    public function test_se_puede_crear_editar_y_borrar_un_actor(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), [
                'nombre' => 'Municipio de prueba',
            ] + $this->todosEn(2) + ['tiene_poder' => '1'])
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('success');

        $actor = Involucrado::where('zona_id', $this->zona->id)->firstOrFail();
        $this->assertSame('Municipio de prueba', $actor->nombre);
        $this->assertSame(2, $actor->pod_poder);
        $this->assertTrue($actor->tiene_poder);
        $this->assertFalse($actor->tiene_legitimidad);

        $this->actingAs($this->jefe)
            ->get("{$this->urlIndex($this->zona)}/{$actor->id}/editar")
            ->assertOk()
            ->assertSee('Municipio de prueba');

        // Sin el checkbox 'tiene_poder' en el envío: una casilla que se
        // desmarca no llega en la petición, y eso tiene que traducirse en
        // false, no en "se queda como estaba" — es justo el motivo por el
        // que el controlador usa request()->boolean() y no $request->only().
        $this->actingAs($this->jefe)
            ->put("{$this->urlIndex($this->zona)}/{$actor->id}", [
                'nombre' => 'Municipio renombrado',
            ] + $this->todosEn(3))
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('success');

        $actor->refresh();
        $this->assertSame('Municipio renombrado', $actor->nombre);
        $this->assertSame(3, $actor->pod_poder);
        $this->assertFalse($actor->tiene_poder);

        $this->actingAs($this->jefe)
            ->delete("{$this->urlIndex($this->zona)}/{$actor->id}")
            ->assertRedirect($this->urlIndex($this->zona));

        $this->assertDatabaseMissing('involucrados', ['id' => $actor->id]);
    }

    /**
     * old('tiene_poder', $actor->tiene_poder) parecía razonable pero no lo
     * es: una casilla que el usuario acaba de desmarcar no viaja en la
     * petición, así que si OTRO campo falla la validación, old() no
     * encuentra la clave, cae al valor guardado del actor y la casilla
     * reaparece marcada en el formulario repintado sin que nadie la haya
     * vuelto a marcar. El formulario tiene que distinguir "no venía en la
     * petición porque es la primera carga" de "no venía porque se desmarcó".
     */
    public function test_al_repintar_tras_un_error_una_casilla_desmarcada_no_vuelve_a_marcarse(): void
    {
        $actor = Involucrado::create($this->todosEn(1) + [
            'zona_id'     => $this->zona->id,
            'nombre'      => 'Actor con poder',
            'tiene_poder' => true,
        ]);

        $editUrl = "{$this->urlIndex($this->zona)}/{$actor->id}/editar";

        // pod_poder fuera de escala fuerza el error de validación; tiene_poder
        // se omite a propósito, como lo haría el navegador con la casilla
        // desmarcada.
        $this->actingAs($this->jefe)
            ->from($editUrl)
            ->put("{$this->urlIndex($this->zona)}/{$actor->id}", [
                'nombre'    => 'Actor con poder',
                'pod_poder' => 99,
            ])
            ->assertSessionHasErrors('pod_poder');

        $pagina = $this->actingAs($this->jefe)->get($editUrl)->assertOk();

        preg_match('/<input[^>]*name="tiene_poder"[^>]*>/', $pagina->getContent(), $etiqueta);
        $this->assertNotEmpty($etiqueta, 'No se encontró la casilla tiene_poder en el formulario.');
        $this->assertStringNotContainsString('checked', $etiqueta[0]);

        // Y el valor guardado del actor —que sigue en true, porque el
        // envío falló y nunca se guardó— no debe ser lo que decide el
        // repintado.
        $this->assertTrue($actor->fresh()->tiene_poder);
    }

    /**
     * Mismo patrón que AutorizacionZonaTest para los inventarios: el
     * middleware deja pasar la petición porque la URL pertenece a la zona
     * propia, pero el actor de verdad es de otra zona, y el where('zona_id', ...)
     * del controlador tiene que impedir tocarlo.
     */
    public function test_no_se_puede_tocar_un_actor_de_otra_zona(): void
    {
        $estudiante = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($estudiante->id);

        $ajena = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona ajena',
        ]);

        $actorAjeno = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $ajena->id,
            'nombre'  => 'Actor de otra zona',
        ]);

        $this->actingAs($estudiante)
            ->get("{$this->urlIndex($this->zona)}/{$actorAjeno->id}/editar")
            ->assertNotFound();

        $this->actingAs($estudiante)
            ->put("{$this->urlIndex($this->zona)}/{$actorAjeno->id}", [
                'nombre' => 'Secuestrado',
            ] + $this->todosEn(1))
            ->assertNotFound();

        $this->actingAs($estudiante)
            ->delete("{$this->urlIndex($this->zona)}/{$actorAjeno->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('involucrados', [
            'id'     => $actorAjeno->id,
            'nombre' => 'Actor de otra zona',
        ]);
    }

    /**
     * El equipo edita libremente en borrador; en cuanto la lista se
     * confirma, la escritura se cierra para él con un mensaje —no un
     * 403—, igual que EvaluacionZonaController::update() con las siete
     * matrices de formulario.
     */
    public function test_el_equipo_puede_editar_en_borrador_y_no_cuando_esta_confirmada(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $actor = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor en borrador',
        ]);

        $this->actingAs($equipo)
            ->put("{$this->urlIndex($this->zona)}/{$actor->id}", [
                'nombre' => 'Editado en borrador',
            ] + $this->todosEn(1))
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('success');

        $this->assertSame('Editado en borrador', $actor->fresh()->nombre);

        InvolucradosConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
        ]);

        $this->actingAs($equipo)
            ->from($this->urlIndex($this->zona))
            ->put("{$this->urlIndex($this->zona)}/{$actor->id}", [
                'nombre' => 'Intento tras validar',
            ] + $this->todosEn(1))
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        // El nombre no cambió: el bloqueo actuó antes de tocar la base.
        $this->assertSame('Editado en borrador', $actor->fresh()->nombre);

        $this->actingAs($equipo)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlIndex($this->zona), [
                'nombre' => 'Actor nuevo tras validar',
            ] + $this->todosEn(1))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        $this->assertDatabaseMissing('involucrados', ['nombre' => 'Actor nuevo tras validar']);
    }

    public function test_validar_exige_al_menos_un_actor_y_ninguno_incompleto(): void
    {
        $this->actingAs($this->jefe)
            ->from($this->urlIndex($this->zona))
            ->post("{$this->urlIndex($this->zona)}/validar")
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'al menos un actor'));

        $this->assertDatabaseMissing('involucrados_config', ['zona_id' => $this->zona->id]);

        $incompleto = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'A medias',
        ]);
        $incompleto->pod_poder = null;
        $incompleto->save();

        $this->actingAs($this->jefe)
            ->from($this->urlIndex($this->zona))
            ->post("{$this->urlIndex($this->zona)}/validar")
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'sin responder'));

        $this->assertDatabaseMissing('involucrados_config', ['zona_id' => $this->zona->id]);

        $incompleto->pod_poder = 1;
        $incompleto->save();

        $this->actingAs($this->jefe)
            ->post("{$this->urlIndex($this->zona)}/validar")
            ->assertRedirect(route('operativo.involucrados.resultados', $this->zona->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('involucrados_config', [
            'zona_id' => $this->zona->id,
            'estado'  => 'confirmado',
        ]);
    }

    public function test_solo_el_jefe_puede_validar(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $this->actingAs($equipo)
            ->post("{$this->urlIndex($this->zona)}/validar")
            ->assertForbidden();

        $this->assertDatabaseMissing('involucrados_config', ['zona_id' => $this->zona->id]);
    }

    // ── Reapertura: tocar la lista validada la vuelve a borrador ───────────

    /**
     * "Confirmado" significa que ESE conjunto de actores fue validado. El
     * CRUD de un actor no toca InvolucradosConfig, y bloqueoSiCerrada() no
     * detiene al jefe (solo al equipo), así que sin este mecanismo el jefe
     * podía seguir escribiendo actores bajo una lista que seguía diciendo
     * "validada" — justo lo que la normalización por conjunto (grado del
     * actor / suma de todos) no puede permitirse.
     */
    private function validar(): InvolucradosConfig
    {
        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor ya validado',
        ]);

        $this->actingAs($this->jefe)->post("{$this->urlIndex($this->zona)}/validar");

        return InvolucradosConfig::where('zona_id', $this->zona->id)->firstOrFail();
    }

    public function test_crear_un_actor_reabre_la_lista_ya_validada(): void
    {
        $config = $this->validar();
        $this->assertSame('confirmado', $config->estado);

        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), [
                'nombre' => 'Actor nuevo tras validar',
            ] + $this->todosEn(1))
            ->assertSessionHas('success', fn(string $m) => str_contains($m, 'vuelve a borrador'));

        $this->assertSame('borrador', $config->fresh()->estado);
    }

    public function test_editar_un_actor_reabre_la_lista_ya_validada(): void
    {
        $config = $this->validar();
        $actor  = Involucrado::where('zona_id', $this->zona->id)->firstOrFail();

        $this->actingAs($this->jefe)
            ->put("{$this->urlIndex($this->zona)}/{$actor->id}", [
                'nombre' => 'Actor editado tras validar',
            ] + $this->todosEn(2))
            ->assertSessionHas('success', fn(string $m) => str_contains($m, 'vuelve a borrador'));

        $this->assertSame('borrador', $config->fresh()->estado);
    }

    public function test_borrar_un_actor_reabre_la_lista_ya_validada(): void
    {
        $config = $this->validar();
        $actor  = Involucrado::where('zona_id', $this->zona->id)->firstOrFail();

        $this->actingAs($this->jefe)
            ->delete("{$this->urlIndex($this->zona)}/{$actor->id}")
            ->assertSessionHas('success', fn(string $m) => str_contains($m, 'vuelve a borrador'));

        $this->assertSame('borrador', $config->fresh()->estado);
    }

    /** Sin nada validado todavía, escribir un actor no toca InvolucradosConfig. */
    public function test_escribir_sin_lista_validada_no_menciona_ninguna_reapertura(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), [
                'nombre' => 'Actor en borrador',
            ] + $this->todosEn(1))
            ->assertSessionHas('success', fn(string $m) => ! str_contains($m, 'vuelve a borrador'));

        $this->assertDatabaseMissing('involucrados_config', ['zona_id' => $this->zona->id]);
    }

    // ── Vocabulario de la escala (urgencia tiene el suyo propio) ───────────

    public function test_etiquetas_escala_usa_el_vocabulario_propio_de_urgencia(): void
    {
        $this->assertSame(
            ['Nada sensible', 'Poco sensible', 'Sensible', 'Alta sensibilidad'],
            Involucrados::etiquetasEscala('urg_sensibilidad')
        );
        $this->assertSame(
            ['Nada crítico', 'Baja criticidad', 'Media criticidad', 'Alta criticidad'],
            Involucrados::etiquetasEscala('urg_criticidad')
        );
    }

    /**
     * Los otros nueve campos —poder y legitimidad— comparten el mismo
     * vocabulario común. No se recorren los nueve uno a uno: basta con uno
     * de cada atributo para comprobar que no caen en la excepción de
     * urgencia por accidente.
     */
    public function test_etiquetas_escala_usa_el_vocabulario_comun_fuera_de_urgencia(): void
    {
        $comun = ['No lo posee', 'Poca', 'Media', 'Máxima'];

        $this->assertSame($comun, Involucrados::etiquetasEscala('pod_poder'));
        $this->assertSame($comun, Involucrados::etiquetasEscala('leg_territorio'));
    }

    public function test_el_formulario_usa_el_vocabulario_de_urgencia_en_sus_dos_campos(): void
    {
        $pagina = $this->actingAs($this->jefe)
            ->get(route('operativo.involucrados.create', $this->zona->id))
            ->assertOk();

        // Las palabras exclusivas de cada campo de urgencia...
        $pagina->assertSee('Alta sensibilidad');
        $pagina->assertSee('Media criticidad');
        // ...y el vocabulario común, que sigue apareciendo en poder/legitimidad.
        $pagina->assertSee('No lo posee');
        $pagina->assertSee('Máxima');
    }

    /**
     * Pareja de EstadoZonaTest::test_una_entrada_de_actores_sin_empezar_lo_dice,
     * pero por HTTP y con actores de verdad: cubre que la página de zona
     * conoce la entrada 'involucrados' y pinta su recuento real, no solo que
     * el mecanismo genérico de filaActores() funcione en abstracto.
     */
    public function test_la_pagina_de_zona_muestra_la_fila_de_involucrados_con_su_recuento(): void
    {
        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Completo',
        ]);

        $incompleto = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Incompleto',
        ]);
        $incompleto->urg_criticidad = null;
        $incompleto->save();

        $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee('Involucrados turísticos')
            ->assertSee('2 actores, 1 sin completar');
    }
}
