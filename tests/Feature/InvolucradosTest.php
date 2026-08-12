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

    /** Dos actores, uno de ellos a medias. */
    private function dosActoresUnoIncompleto(): void
    {
        Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $medias = Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor a medias',
        ]);

        // El null se asigna DESPUÉS de crear, no dentro del array: la unión
        // con «+» conserva el operando izquierdo cuando la clave se repite,
        // así que un null a la derecha de todosEn(2) nunca pisaría el 2.
        $medias->leg_sociedad = null;
        $medias->save();
    }

    public function test_la_franja_resume_cuantos_actores_faltan(): void
    {
        $this->dosActoresUnoIncompleto();

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('2 actores')
            ->assertSee('1 sin completar');
    }

    public function test_el_boton_de_validar_esta_arriba_y_no_al_final(): void
    {
        Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $html = $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->getContent();

        // Con la lista completa el botón existe una sola vez. Dos botones que
        // hacen lo mismo es la duplicación que se está quitando.
        $this->assertSame(1, substr_count($html, 'Validar y Cerrar la Lista'));
    }

    /**
     * El admin escribe listas pero no las valida, y tampoco es el equipo: no
     * recibe ni el botón ni el aviso de «avísale a tu jefe».
     */
    public function test_el_admin_ve_el_recuento_y_ninguna_accion_de_validar(): void
    {
        Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('1 actor')
            ->assertDontSee('Validar y Cerrar la Lista')
            ->assertDontSee('avísale a');
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
     * Recorrido completo por HTTP, y no vía Involucrado::create() directo
     * como los otros cuarenta usos de valores en 0 de este archivo: sin este
     * test el camino "el usuario elige 0 → llega por POST → se guarda → se
     * repinta el desplegable con el 0 marcado" no se ejecuta nunca de punta
     * a punta. select-involucrados es justo el desplegable donde ese 0
     * significa más —"no lo posee", una valoración explícita, no un hueco—,
     * así que perder ese 0 al repintar sería el fallo que este instrumento
     * menos se puede permitir.
     */
    public function test_un_cero_elegido_por_el_usuario_llega_por_post_y_se_repinta_marcado(): void
    {
        // pod_poder va en el array de la izquierda a propósito: "+" entre
        // arrays conserva el valor del lado izquierdo en un choque de
        // claves, y pod_poder SÍ es una de las once claves que todosEn(2)
        // ya trae. Ponerlo después de todosEn(2), como con tiene_poder en
        // otros tests, lo habría dejado en 2 en vez de en 0 sin que ningún
        // error lo avisara.
        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), [
                'nombre'    => 'Actor con un cero real',
                'pod_poder' => '0',
            ] + $this->todosEn(2))
            ->assertSessionHas('success');

        $actor = Involucrado::where('zona_id', $this->zona->id)->firstOrFail();
        $this->assertSame(0, $actor->pod_poder);

        $pagina = $this->actingAs($this->jefe)
            ->get("{$this->urlIndex($this->zona)}/{$actor->id}/editar")
            ->assertOk();

        preg_match('/<select[^>]*name="pod_poder".*?<\/select>/s', $pagina->getContent(), $bloque);
        $this->assertNotEmpty($bloque, 'No se encontró el desplegable pod_poder.');
        $this->assertStringContainsString('<option value="0" selected>', $bloque[0]);
    }

    /**
     * Invertido en la Tarea 2: el admin ya no es de solo lectura, así que
     * ve el mismo formulario abierto que el jefe mientras la lista de
     * actores siga en borrador. Antes este test comprobaba justo lo
     * contrario -que el admin NO lo veía editable-, y era lo correcto
     * mientras el admin no podía escribir; ese comportamiento cambió por
     * decisión del responsable (ver docs/sdd de esta rama), así que el test
     * tenía que invertirse con él, no borrarse. El caso "lista validada"
     * -que sigue bloqueado, para todos menos el jefe- se cubre aparte en
     * test_el_admin_no_ve_el_formulario_de_actor_editable_con_la_lista_validada().
     */
    public function test_el_admin_ve_el_formulario_de_actor_editable(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $actor = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor existente',
        ]);

        $nuevo = $this->actingAs($admin)->get("{$this->urlIndex($this->zona)}/nuevo");
        $nuevo->assertOk();
        $nuevo->assertSee('Guardar');

        // Los desplegables llevan la clase Tailwind "disabled:bg-gray-100" en
        // TODOS los casos, activos o no: buscar la palabra "disabled" a
        // secas no distingue nada. El atributo HTML real va al final de la
        // etiqueta, así que "disabled>" solo aparecería si de verdad
        // estuviera desactivado.
        preg_match('/<select[^>]*name="pod_poder"[^>]*>/', $nuevo->getContent(), $select);
        $this->assertNotEmpty($select, 'No se encontró el desplegable pod_poder.');
        $this->assertStringNotContainsString('disabled>', $select[0]);

        // En la casilla el atributo disabled va ANTES de class="...", así
        // que basta con mirar la parte de la etiqueta anterior a "class=".
        preg_match('/<input[^>]*name="tiene_poder"[^>]*>/', $nuevo->getContent(), $checkbox);
        $this->assertNotEmpty($checkbox, 'No se encontró la casilla tiene_poder.');
        [$antesDeClase] = explode('class=', $checkbox[0]);
        $this->assertStringNotContainsString('disabled', $antesDeClase);

        $this->actingAs($admin)
            ->get("{$this->urlIndex($this->zona)}/{$actor->id}/editar")
            ->assertOk()
            ->assertSee('Guardar');

        // El jefe conserva el mismo formulario abierto de par en par que
        // siempre tuvo: el arreglo no debe cambiarle nada.
        $this->actingAs($this->jefe)
            ->get("{$this->urlIndex($this->zona)}/nuevo")
            ->assertSee('Guardar');
    }

    /**
     * El hermano del test anterior: la regla que sí sobrevive es que, con la
     * lista VALIDADA, el admin vuelve a quedar bloqueado -igual que el
     * equipo-. InvolucradosController::bloqueoSiCerrada() corta create()/edit()
     * antes de que lleguen a pintar el formulario, así que "bloqueado" aquí
     * es un redirect con el aviso de "ya fue validada", no un formulario
     * disabled -a diferencia de las siete matrices de formulario, esta lista
     * no se puede ni siquiera abrir para editar una vez cerrada-.
     */
    public function test_el_admin_no_ve_el_formulario_de_actor_editable_con_la_lista_validada(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $actor = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor validado',
        ]);

        InvolucradosConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
        ]);

        $this->actingAs($admin)
            ->get("{$this->urlIndex($this->zona)}/nuevo")
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        $this->actingAs($admin)
            ->get("{$this->urlIndex($this->zona)}/{$actor->id}/editar")
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        // El jefe, en cambio, sigue entrando al formulario aunque la lista
        // esté validada: es el único rol al que bloqueoSiCerrada() no cierra
        // el paso.
        $this->actingAs($this->jefe)
            ->get("{$this->urlIndex($this->zona)}/nuevo")
            ->assertOk()
            ->assertSee('Guardar');
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

    /**
     * bloqueoSiCerrada() solo comprobaba esEquipo(): desde que el middleware
     * de zona deja escribir al admin (PermisosAdminTest), ese guardián lo
     * dejaba pasar igual que al jefe, pudiendo borrar o crear actores de una
     * lista ya validada y reabrirla de paso -mismo patrón que el fallo que
     * ReabrirMatrizTest fija para las siete matrices de formulario-. Ahora
     * comprueba ! esJefe(), así que cierra al admin igual que al equipo.
     */
    public function test_el_admin_no_puede_editar_una_lista_de_involucrados_validada(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $actor = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor ya validado',
        ]);

        InvolucradosConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
        ]);

        $this->actingAs($admin)
            ->from($this->urlIndex($this->zona))
            ->delete("{$this->urlIndex($this->zona)}/{$actor->id}")
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        // Ni el actor se borró ni la lista se reabrió.
        $this->assertDatabaseHas('involucrados', ['id' => $actor->id]);
        $this->assertSame(
            'confirmado',
            InvolucradosConfig::where('zona_id', $this->zona->id)->value('estado')
        );

        $this->actingAs($admin)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlIndex($this->zona), [
                'nombre' => 'Actor colado por el admin',
            ] + $this->todosEn(1))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        $this->assertDatabaseMissing('involucrados', ['nombre' => 'Actor colado por el admin']);
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

    /**
     * Al jefe no se le puede avisar de la reapertura recién DESPUÉS de
     * provocarla: con la lista validada, "+ Nuevo actor", "Editar" y
     * "Eliminar" siguen activos justo debajo de este banner, así que el
     * aviso tiene que estar aquí, antes de que pulse cualquiera de los tres,
     * no solo en el flash que sale después de guardar.
     */
    public function test_el_banner_de_lista_validada_avisa_que_modificarla_la_reabre(): void
    {
        $this->validar();

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('vuelve a borrador: hay que validarla de nuevo');
    }

    /**
     * Mismo aviso en el diálogo nativo de "¿Borrar este actor?", y solo
     * cuando de verdad aplica: con la lista en borrador, borrar un actor no
     * reabre nada, y anunciarlo igual sería un aviso falso.
     */
    public function test_el_dialogo_de_borrar_avisa_de_la_reapertura_solo_si_la_lista_esta_validada(): void
    {
        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor en borrador',
        ]);

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertDontSee('validada: al borrarlo');

        $this->validar();

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('validada: al borrarlo');
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

    // ── Vista de resultados: ensamblado del ranking (Tarea 4) ──────────────
    //
    // La aritmética pura (normalizar/esAtributoDegenerado/relevancia) se
    // prueba sin base de datos en tests/Unit/InvolucradosCalculoTest.php. Lo
    // que hace falta probar aquí, con actores de verdad, es el ENSAMBLADO de
    // InvolucradosController::relevanciasDe(): que lee los grados correctos
    // de cada actor para cada atributo, sin cruzar columnas entre sí, y que
    // ordena y pinta lo que calculó.

    private function urlResultados(): string
    {
        return route('operativo.involucrados.resultados', $this->zona->id);
    }

    public function test_resultados_muestra_los_actores_sus_tipos_y_el_aviso_de_valores_relativos(): void
    {
        Involucrado::create($this->todosEn(1) + [
            'zona_id'     => $this->zona->id,
            'nombre'      => 'Municipio de prueba',
            'tiene_poder' => true,
        ]);
        Involucrado::create($this->todosEn(1) + [
            'zona_id'           => $this->zona->id,
            'nombre'            => 'Operadora turística',
            'tiene_legitimidad' => true,
            'tiene_urgencia'    => true,
        ]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Municipio de prueba')
            ->assertSee('Operadora turística')
            ->assertSee('Adormecido')
            ->assertSee('Dependiente')
            // El aviso de que los normalizados son relativos al conjunto:
            // el punto que el diseño marca como imprescindible.
            ->assertSee('relativos a este conjunto de actores', false)
            // Positivo, no solo la ausencia de estos textos en el caso
            // incompleto (ver el test de abajo): que en la rama COMPLETA la
            // tabla de números de verdad se pinta. Con los dos actores
            // empatados en 1 en los once criterios, los tres atributos
            // normalizan a (1/2)*2 = 1.0 y la relevancia a 1×1×1 = 1.0.
            ->assertSee('Relevancia')
            ->assertSee('Tipo de Mitchell')
            ->assertSee('(norm.)')
            ->assertSee('1.000')
            ->assertSee('1.0000');
    }

    /**
     * Grados deliberadamente distintos y cruzados entre los tres atributos
     * -A gana en poder y legitimidad pero pierde en urgencia, B al revés-
     * para que un error de ensamblado en relevanciasDe() que mezclara la
     * columna de un atributo con la de otro (itera los tres por separado y
     * arma cada fila indexando con la misma posición) se note: si dos
     * atributos compartieran la misma pareja de valores, ese error pasaría
     * desapercibido.
     */
    public function test_resultados_calcula_normalizados_y_relevancia_para_los_tres_atributos(): void
    {
        $actorA = Involucrado::create([
            'zona_id'          => $this->zona->id,
            'nombre'           => 'Actor A',
            'pod_autoridad'    => 3, 'pod_poder' => 3, 'pod_recursos' => 3, 'pod_presupuesto' => 1,
            'leg_territorio'   => 3, 'leg_sociedad' => 2,
            'urg_sensibilidad' => 1,
        ] + $this->todosEn(0));
        $actorB = Involucrado::create([
            'zona_id'          => $this->zona->id,
            'nombre'           => 'Actor B',
            'pod_autoridad'    => 3, 'pod_poder' => 2,
            'leg_territorio'   => 1,
            'urg_sensibilidad' => 2, 'urg_criticidad' => 1,
        ] + $this->todosEn(0));

        $this->assertSame(10, $actorA->grado('poder'));
        $this->assertSame(5, $actorA->grado('legitimidad'));
        $this->assertSame(1, $actorA->grado('urgencia'));
        $this->assertSame(5, $actorB->grado('poder'));
        $this->assertSame(1, $actorB->grado('legitimidad'));
        $this->assertSame(3, $actorB->grado('urgencia'));

        $html = $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->getContent();

        // Poder: suma 15 → (10/15)*2 = 1.333, (5/15)*2 = 0.667.
        $this->assertStringContainsString('1.333', $html);
        $this->assertStringContainsString('0.667', $html);
        // Legitimidad: suma 6 → (5/6)*2 = 1.667, (1/6)*2 = 0.333.
        $this->assertStringContainsString('1.667', $html);
        $this->assertStringContainsString('0.333', $html);
        // Urgencia: suma 4 → (1/4)*2 = 0.500, (3/4)*2 = 1.500.
        $this->assertStringContainsString('0.500', $html);
        $this->assertStringContainsString('1.500', $html);

        // Relevancia A = (4/3)×(5/3)×(1/2) = 20/18 = 1.1111...
        // Relevancia B = (2/3)×(1/3)×(3/2) = 6/18  = 0.3333...
        $this->assertStringContainsString('1.1111', $html);
        $this->assertStringContainsString('0.3333', $html);
    }

    /**
     * DELIBERADO, no un fallo: normalizar() divide por la suma del conjunto,
     * así que el normalizado de un actor depende, en general, de los grados
     * de todos los demás. Dar de alta un tercer actor recalcula a los dos que
     * ya estaban, sin que sus propios criterios cambien un ápice. Está
     * decidido con el responsable del proyecto que se queda así: este test
     * lo fija a propósito, en los TRES atributos (no solo en poder), para que
     * nadie lo "corrija" dentro de seis meses pensando que el normalizado de
     * un actor debería ser estable entre altas.
     *
     * Los grados de A y B son simétricos entre los tres atributos (4/2 en los
     * tres) a propósito: si relevanciasDe() dejara de recalcular alguno de
     * los tres al añadir el actor C —por ejemplo por un fallo que solo
     * recorriera dos de las tres claves de ATRIBUTOS—, el valor "viejo"
     * seguiría apareciendo en la página y el assertDontSee de más abajo lo
     * detectaría igual, sea cual sea el atributo afectado.
     */
    public function test_anadir_un_actor_cambia_los_normalizados_renderizados_en_los_tres_atributos(): void
    {
        $actorA = Involucrado::create([
            'zona_id'          => $this->zona->id,
            'nombre'           => 'Actor A',
            'pod_autoridad'    => 3, 'pod_poder' => 1,
            'leg_territorio'   => 2, 'leg_sociedad' => 2,
            'urg_sensibilidad' => 2, 'urg_criticidad' => 2,
        ] + $this->todosEn(0));
        Involucrado::create([
            'zona_id'          => $this->zona->id,
            'nombre'           => 'Actor B',
            'pod_autoridad'    => 2,
            'leg_territorio'   => 1, 'leg_sociedad' => 1,
            'urg_sensibilidad' => 1, 'urg_criticidad' => 1,
        ] + $this->todosEn(0));

        $this->assertSame(4, $actorA->grado('poder'));
        $this->assertSame(4, $actorA->grado('legitimidad'));
        $this->assertSame(4, $actorA->grado('urgencia'));

        // Antes de C: suma 6 en los tres atributos, dos actores →
        // (4/6)*2 = 1.333, (2/6)*2 = 0.667 en los tres.
        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('1.333')
            ->assertSee('0.667');

        Involucrado::create([
            'zona_id'          => $this->zona->id,
            'nombre'           => 'Actor C',
            'pod_autoridad'    => 3, 'pod_poder' => 2,
            'leg_territorio'   => 3, 'leg_sociedad' => 2,
            'urg_sensibilidad' => 3, 'urg_criticidad' => 2,
        ] + $this->todosEn(0));

        // Después de C: suma 4+2+5 = 11, tres actores →
        // A: (4/11)*3 = 1.091, B: (2/11)*3 = 0.545, C: (5/11)*3 = 1.364.
        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            // Los normalizados "viejos" de A y B ya no deben aparecer en
            // NINGUNO de los tres atributos: si solo hubieran cambiado uno o
            // dos, alguno de estos dos valores seguiría en la página.
            ->assertDontSee('1.333')
            ->assertDontSee('0.667')
            ->assertSee('1.091')
            ->assertSee('0.545')
            ->assertSee('1.364');
    }

    /**
     * "Ninguno puntúa nada en urgencia" —los dos campos en 0 para todos los
     * actores— es un caso real del instrumento, no un error de captura, y no
     * debe pintarse como un 1.00 indistinguible de "está justo en la media":
     * Involucrados::esAtributoDegenerado() lo marca, y la vista lo pinta con
     * "—" y una nota, sin afectar a las otras dos columnas, que sí tienen
     * datos.
     */
    public function test_resultados_marca_con_guion_un_atributo_sin_ningun_puntaje_en_el_conjunto(): void
    {
        $actorA = Involucrado::create([
            'zona_id'        => $this->zona->id,
            'nombre'         => 'Actor A',
            'pod_autoridad'  => 2,
            'leg_territorio' => 1,
        ] + $this->todosEn(0));
        $actorB = Involucrado::create([
            'zona_id'        => $this->zona->id,
            'nombre'         => 'Actor B',
            'pod_autoridad'  => 1,
            'leg_territorio' => 2,
        ] + $this->todosEn(0));

        $this->assertSame(0, $actorA->grado('urgencia'));
        $this->assertSame(0, $actorB->grado('urgencia'));

        $contenido = $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('no diferencia a ningún actor', false)
            // Poder sí tiene datos: suma 3 → (2/3)*2 = 1.333, (1/3)*2 = 0.667.
            ->assertSee('1.333')
            ->assertSee('0.667')
            ->getContent();

        // Exactamente dos celdas marcadas con guión -una por actor-, ni una
        // de más (que contagiaría a poder o legitimidad) ni de menos.
        $this->assertSame(2, substr_count($contenido, 'class="text-gray-400"'));
    }

    /**
     * Con un único actor, normalizar() da 1.0 en cualquier atributo sea cual
     * sea su grado (grado/grado siempre es 1, y si ese grado es 0 la rama de
     * suma-cero también da 1.0): los tres atributos cuentan como degenerados
     * y ninguno debe pintar un 1.000 desnudo, aunque el actor sí tenga
     * criterios variados.
     */
    public function test_resultados_con_un_solo_actor_marca_los_tres_atributos_como_degenerados(): void
    {
        Involucrado::create([
            'zona_id'         => $this->zona->id,
            'nombre'          => 'Actor único',
            'pod_autoridad'   => 3,
            'leg_territorio'  => 2,
            'urg_criticidad'  => 1,
        ] + $this->todosEn(0));

        $contenido = $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Actor único')
            ->getContent();

        $this->assertSame(3, substr_count($contenido, 'class="text-gray-400"'));
    }

    /**
     * Con un actor a medias la matriz entera se trata como sin resultados
     * —igual que estaCompleto()/scopeIncompletos() ya hacen en el resto del
     * sistema—, así que no debe pintarse ningún normalizado ni relevancia:
     * calcularlos sobre un conjunto con un hueco no tiene significado, y
     * InvolucradosController::relevanciasDe() ni siquiera llega a invocarse
     * (ver la precondición en su docblock).
     */
    public function test_resultados_con_un_actor_incompleto_no_pinta_ningun_numero(): void
    {
        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $incompleto = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor incompleto',
        ]);
        $incompleto->pod_poder = null;
        $incompleto->save();

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Involucrados turísticos sin resultados')
            // Las cabeceras de la tabla de números solo existen en la rama
            // "completa" de la vista: su ausencia es la prueba de que no se
            // pintó ningún normalizado ni relevancia.
            ->assertDontSee('Relevancia')
            ->assertDontSee('Tipo de Mitchell')
            ->assertDontSee('(norm.)');
    }

    /**
     * Invertido en la Tarea 2: Involucrados no tiene, ni tuvo nunca, un
     * botón x-boton-volver en resultados.blade.php -a diferencia de las
     * otras matrices-, y desde que el admin deja de ser de solo lectura esa
     * vista dejó de distinguir el rol del todo: ya no hay un "Volver a
     * Zonas" reservado para él ni ningún aviso de solo lectura. El test
     * anterior comprobaba justo lo contrario -que el admin NO veía el
     * enlace al listado editable-, y era lo correcto mientras lo era; ahora
     * comprueba que ve el mismo enlace que el jefe (ver el test hermano
     * inmediatamente debajo, para el jefe).
     */
    public function test_el_admin_ve_el_enlace_al_listado_en_los_resultados_de_involucrados(): void
    {
        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor único',
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee(route('operativo.involucrados.index', $this->zona->id), false);
    }

    /**
     * Complementa el test anterior: sin este, un arreglo que ocultara el
     * enlace al listado para todo el mundo (no solo para el admin) pasaría
     * igual el test de arriba.
     */
    public function test_el_jefe_ve_el_enlace_al_listado_en_los_resultados_de_involucrados(): void
    {
        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor único',
        ]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee(route('operativo.involucrados.index', $this->zona->id), false);
    }
}
