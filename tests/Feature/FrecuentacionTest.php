<?php

namespace Tests\Feature;

use App\Models\FrecuentacionConfig;
use App\Models\Role;
use App\Models\SitioFrecuentacion;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FrecuentacionTest extends TestCase
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

    // ── CRUD por HTTP ───────────────────────────────────────────────────────
    //
    // A diferencia de InvolucradosTest, estos tests no visitan las páginas GET
    // de índice/alta/edición: sus vistas llegan en la Tarea 4. Lo que aquí se
    // comprueba es el CRUD y sus reglas de negocio -POST/PUT/DELETE, base de
    // datos y redirecciones-, no el HTML.

    private function urlIndex(Zona $zona): string
    {
        return "/operativo/zona/{$zona->id}/frecuentacion";
    }

    // ── Tarea 6: la franja de resumen ───────────────────────────────────────

    /** Dos sitios, uno sin DET. */
    private function dosSitiosUnoSinDet(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio a medias',
        ]);
    }

    public function test_la_franja_resume_cuantos_sitios_faltan(): void
    {
        $this->dosSitiosUnoSinDet();

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('2 sitios')
            ->assertSee('1 sin DET');
    }

    /**
     * El admin escribe listas pero no las valida, y tampoco es el equipo: no
     * recibe ni el botón ni el aviso de «avísale a tu jefe».
     */
    public function test_el_admin_ve_el_recuento_y_ninguna_accion_de_validar(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        $this->actingAs($this->jefe)->post($this->urlSuperficie(), ['st' => 1200]);

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('1 sitio')
            ->assertDontSee('Validar y Cerrar la Lista')
            ->assertDontSee('avísale a');
    }

    /**
     * El equipo ve el aviso de «avísale a tu jefe» cuando la lista está
     * completa -sitios con DET y ST definida-, pero no recibe el botón de
     * validar, que solo tiene el Jefe de Zona. Mismo hueco que quedó sin
     * cubrir en la tarea de Involucrados: el brief de esta tarea trae tests
     * para el jefe y el admin, pero no para el equipo.
     */
    public function test_el_equipo_ve_el_aviso_de_validar_cuando_la_lista_esta_completa(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        $this->actingAs($this->jefe)->post($this->urlSuperficie(), ['st' => 1200]);

        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);

        // Sin asignarlo a la zona, la petición recibiría un 403 y el test
        // fallaría por el motivo equivocado, no por lo que se quiere probar.
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('1 sitio')
            ->assertSee('avísale a')
            ->assertDontSee('Validar y Cerrar la Lista');
    }

    /**
     * La ST aparece como dato en la franja, pero se sigue editando en su
     * sección: sin ella ningún sitio tiene ÍETP aunque todos tengan DET.
     */
    public function test_la_franja_avisa_si_falta_la_superficie_territorial(): void
    {
        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('falta la Superficie Territorial');
    }

    public function test_con_superficie_definida_la_franja_la_muestra(): void
    {
        // Fija la ST por el camino real -la ruta de superficie-, no escribiendo
        // en la base a mano: así el test también cubre que ese camino funciona.
        $this->actingAs($this->jefe)->post(
            $this->urlSuperficie(),
            ['st' => 1200]
        );

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('ST: 1200')
            ->assertDontSee('falta la Superficie Territorial');
    }

    public function test_el_campo_de_superficie_sigue_siendo_editable(): void
    {
        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('name="st"', false)
            ->assertSee($this->urlSuperficie(), false);
    }

    /**
     * El estado del Hallazgo 1: con todos los sitios completos pero sin ST,
     * la lista sigue bloqueada -Frecuentación exige las dos condiciones, no
     * solo el DET de cada sitio-, así que el jefe tiene que ver el motivo
     * real (la ST) en ámbar, y no el botón de validar. Antes nadie cubría
     * este estado: test_la_franja_avisa_si_falta_la_superficie_territorial
     * prueba la falta de ST con la lista VACÍA, donde esta rama de
     * $stDefinida ni siquiera llega a ejecutarse junto a sitios completos.
     *
     * La frase "falta la Superficie Territorial" la pinta también el candado
     * de la pestaña de Resultados (pestanas-matriz.blade.php) cuando los
     * sitios están completos y falta la ST: un assertSee simple pasaría
     * aunque la FRANJA no dijera nada, con solo la pestaña bastando. Por eso
     * se ata la frase a la clase text-amber-700, que es propia de la franja
     * -el candado usa text-sm sin color-, y así la aserción distingue de
     * verdad quién la está pintando.
     */
    public function test_con_sitios_completos_y_sin_st_el_jefe_ve_el_aviso_ambar_y_no_el_boton(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        $html = $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertDontSee('Validar y Cerrar la Lista')
            ->getContent();

        $this->assertStringContainsString('text-amber-700">falta la Superficie Territorial', $html);
    }

    /**
     * Hallazgo 6b: con la lista ya validada y completa, el jefe no debe ver
     * el botón -aunque $listaCompleta sea cierto-, porque no tiene sentido
     * volver a validar lo que ya está confirmado. El término "! $confirmada"
     * de puedeValidar es justo lo que sostiene esto, y hasta ahora ningún
     * test lo comprobaba: se verificó a mano que quitándolo del controlador
     * la suite entera seguía en verde.
     */
    public function test_el_jefe_no_ve_el_boton_con_la_lista_validada_y_completa(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        FrecuentacionConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
            'st'      => 1200,
        ]);

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertDontSee('Validar y Cerrar la Lista');
    }

    public function test_no_queda_un_segundo_boton_de_validar_al_final(): void
    {
        // El brief de esta tarea trae este test sin datos: con la zona
        // recién creada -sin sitios ni ST- $listaCompleta es siempre falso y
        // el botón nunca se pinta, así que el "1" no se podría cumplir con
        // ninguna implementación. Mismo arreglo que
        // InvolucradosTest::test_no_queda_un_segundo_boton_de_validar_al_final():
        // una lista completa primero, para que puedaValidar sea cierto.
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);
        $this->actingAs($this->jefe)->post($this->urlSuperficie(), ['st' => 1200]);

        $html = $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->getContent();

        // Con la lista completa el botón existe una sola vez. Dos botones que
        // hacen lo mismo es la duplicación que se está quitando.
        $this->assertSame(1, substr_count($html, 'Validar y Cerrar la Lista'));
    }

    public function test_se_puede_crear_editar_y_borrar_un_sitio(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), [
                'nombre' => 'Malecón 2000',
                'det'    => '5.5',
            ])
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('success');

        $sitio = SitioFrecuentacion::where('zona_id', $this->zona->id)->firstOrFail();
        $this->assertSame('Malecón 2000', $sitio->nombre);
        $this->assertEqualsWithDelta(5.5, $sitio->det, 0.0001);

        $this->actingAs($this->jefe)
            ->put("{$this->urlIndex($this->zona)}/{$sitio->id}", [
                'nombre' => 'Malecón 2000 renombrado',
                'det'    => '8.25',
            ])
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('success');

        $sitio->refresh();
        $this->assertSame('Malecón 2000 renombrado', $sitio->nombre);
        $this->assertEqualsWithDelta(8.25, $sitio->det, 0.0001);

        $this->actingAs($this->jefe)
            ->delete("{$this->urlIndex($this->zona)}/{$sitio->id}")
            ->assertRedirect($this->urlIndex($this->zona));

        $this->assertDatabaseMissing('frecuentacion_sitios', ['id' => $sitio->id]);
    }

    /** Un sitio recién creado sin DET no es "cero frecuentación": es "sin responder". */
    public function test_un_sitio_nuevo_sin_det_queda_null_no_cero(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), ['nombre' => 'Cerro las Peñas'])
            ->assertSessionHas('success');

        $sitio = SitioFrecuentacion::where('zona_id', $this->zona->id)->firstOrFail();
        $this->assertNull($sitio->det);
        $this->assertFalse($sitio->estaCompleto());
    }

    public function test_no_se_puede_tocar_un_sitio_de_otra_zona(): void
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

        $sitioAjeno = SitioFrecuentacion::create([
            'zona_id' => $ajena->id,
            'nombre'  => 'Sitio de otra zona',
            'det'     => 1.0,
        ]);

        $this->actingAs($estudiante)
            ->put("{$this->urlIndex($this->zona)}/{$sitioAjeno->id}", [
                'nombre' => 'Secuestrado',
                'det'    => 2.0,
            ])
            ->assertNotFound();

        $this->actingAs($estudiante)
            ->delete("{$this->urlIndex($this->zona)}/{$sitioAjeno->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('frecuentacion_sitios', [
            'id'     => $sitioAjeno->id,
            'nombre' => 'Sitio de otra zona',
        ]);
    }

    /**
     * El equipo edita libremente en borrador; en cuanto la lista se confirma,
     * la escritura se cierra para él con un mensaje -no un 403-, igual que
     * InvolucradosController.
     */
    public function test_el_equipo_puede_editar_en_borrador_y_no_cuando_esta_confirmada(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $sitio = SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio en borrador',
            'det'     => 1.0,
        ]);

        $this->actingAs($equipo)
            ->put("{$this->urlIndex($this->zona)}/{$sitio->id}", [
                'nombre' => 'Editado en borrador',
                'det'    => 2.0,
            ])
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('success');

        $this->assertSame('Editado en borrador', $sitio->fresh()->nombre);

        FrecuentacionConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
            'st'      => 10.0,
        ]);

        $this->actingAs($equipo)
            ->from($this->urlIndex($this->zona))
            ->put("{$this->urlIndex($this->zona)}/{$sitio->id}", [
                'nombre' => 'Intento tras validar',
                'det'    => 3.0,
            ])
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        $this->assertSame('Editado en borrador', $sitio->fresh()->nombre);

        $this->actingAs($equipo)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlIndex($this->zona), ['nombre' => 'Sitio nuevo tras validar'])
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        $this->assertDatabaseMissing('frecuentacion_sitios', ['nombre' => 'Sitio nuevo tras validar']);
    }

    /** Mismo candado que InvolucradosController: solo el Jefe de Zona pasa con la lista confirmada. */
    public function test_el_admin_no_puede_editar_una_lista_de_frecuentacion_validada(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $sitio = SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio ya validado',
            'det'     => 1.0,
        ]);

        FrecuentacionConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
            'st'      => 10.0,
        ]);

        $this->actingAs($admin)
            ->from($this->urlIndex($this->zona))
            ->delete("{$this->urlIndex($this->zona)}/{$sitio->id}")
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validada'));

        $this->assertDatabaseHas('frecuentacion_sitios', ['id' => $sitio->id]);
        $this->assertSame(
            'confirmado',
            FrecuentacionConfig::where('zona_id', $this->zona->id)->value('estado')
        );
    }

    // ── La Superficie Territorial ──────────────────────────────────────────

    private function urlSuperficie(): string
    {
        return "{$this->urlIndex($this->zona)}/superficie";
    }

    public function test_guardar_la_superficie_territorial_acepta_un_numero_positivo(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlSuperficie(), ['st' => '12.5'])
            ->assertRedirect($this->urlIndex($this->zona))
            ->assertSessionHas('success');

        $config = FrecuentacionConfig::where('zona_id', $this->zona->id)->firstOrFail();
        $this->assertEqualsWithDelta(12.5, $config->st, 0.0001);
    }

    /** ST en 0 o negativa: gt:0 la rechaza antes de guardar nada. */
    public function test_guardar_la_superficie_territorial_rechaza_cero_y_negativos(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlSuperficie(), ['st' => '0'])
            ->assertSessionHasErrors('st');

        $this->assertDatabaseMissing('frecuentacion_config', ['zona_id' => $this->zona->id]);

        $this->actingAs($this->jefe)
            ->post($this->urlSuperficie(), ['st' => '-5'])
            ->assertSessionHasErrors('st');

        $this->assertDatabaseMissing('frecuentacion_config', ['zona_id' => $this->zona->id]);
    }

    /** Dejar el campo vacío guarda null, no un cero: la ST puede quedar sin responder. */
    public function test_guardar_la_superficie_territorial_vacia_guarda_null(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlSuperficie(), ['st' => ''])
            ->assertSessionHasNoErrors();

        $config = FrecuentacionConfig::where('zona_id', $this->zona->id)->firstOrFail();
        $this->assertNull($config->st);
    }

    // ── validar() ───────────────────────────────────────────────────────────

    private function urlValidar(): string
    {
        return "{$this->urlIndex($this->zona)}/validar";
    }

    /**
     * El orden observable que exige el diseño: sin sitios, error; con sitios
     * pero alguno sin DET, error; con todos los DET respondidos pero sin ST o
     * con ST en 0, error; con todo completo, confirmado.
     */
    public function test_validar_exige_en_orden_sitios_det_y_st(): void
    {
        $this->actingAs($this->jefe)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlValidar())
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'al menos un sitio'));

        $this->assertDatabaseMissing('frecuentacion_config', ['zona_id' => $this->zona->id]);

        $incompleto = SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio a medias',
        ]);

        $this->actingAs($this->jefe)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlValidar())
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'sin responder'));

        $this->assertDatabaseMissing('frecuentacion_config', ['zona_id' => $this->zona->id]);

        $incompleto->update(['det' => 5.0]);

        // Todos los DET respondidos, pero sin ST todavía.
        $this->actingAs($this->jefe)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlValidar())
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'Superficie Territorial'));

        $this->assertDatabaseMissing('frecuentacion_config', ['zona_id' => $this->zona->id]);

        // ST guardada en 0: mismo motivo de bloqueo que sin ST.
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 0]);

        $this->actingAs($this->jefe)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlValidar())
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'Superficie Territorial'));

        $this->assertSame(
            'borrador',
            FrecuentacionConfig::where('zona_id', $this->zona->id)->value('estado')
        );

        // Con todo completo: confirmado.
        FrecuentacionConfig::where('zona_id', $this->zona->id)->update(['st' => 2.0]);

        $this->actingAs($this->jefe)
            ->post($this->urlValidar())
            ->assertRedirect(route('operativo.frecuentacion.resultados', $this->zona->id))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('frecuentacion_config', [
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

        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio completo',
            'det'     => 1.0,
        ]);
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 5.0]);

        $this->actingAs($equipo)
            ->post($this->urlValidar())
            ->assertForbidden();

        $this->assertDatabaseMissing('frecuentacion_config', [
            'zona_id' => $this->zona->id,
            'estado'  => 'confirmado',
        ]);
    }

    /**
     * El modelo de permisos nuevo (rama permisos-y-navegación): el admin
     * escribe -puede añadir un sitio-, pero validar sigue siendo solo del
     * Jefe de Zona, igual que con Involucrados.
     */
    public function test_el_admin_puede_anadir_un_sitio_pero_no_validar_la_lista(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->post($this->urlIndex($this->zona), ['nombre' => 'Sitio del admin', 'det' => '3.0'])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('frecuentacion_sitios', [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio del admin',
        ]);

        $this->actingAs($admin)
            ->post($this->urlValidar())
            ->assertForbidden();

        $this->assertDatabaseMissing('frecuentacion_config', [
            'zona_id' => $this->zona->id,
            'estado'  => 'confirmado',
        ]);
    }

    // ── Reapertura: tocar la lista o la ST vuelve una lista confirmada a borrador ──
    //
    // El ÍETP de un sitio no depende de los demás sitios, pero el ÍEFT que se
    // valida SÍ es una suma sensible a cada término, y la ST es un divisor
    // compartido por todos: por eso editar/borrar un sitio, o cambiar la ST,
    // sigue reabriendo la lista aunque cada ÍETP individual no cambie con los
    // demás -ver el diseño, punto 3, para el porqué exacto-.

    private function validar(): FrecuentacionConfig
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio ya validado',
            'det'     => 1.0,
        ]);

        $this->actingAs($this->jefe)->post($this->urlSuperficie(), ['st' => '5.0']);
        $this->actingAs($this->jefe)->post($this->urlValidar());

        return FrecuentacionConfig::where('zona_id', $this->zona->id)->firstOrFail();
    }

    public function test_crear_un_sitio_reabre_la_lista_ya_validada(): void
    {
        $config = $this->validar();
        $this->assertSame('confirmado', $config->estado);

        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), ['nombre' => 'Sitio nuevo tras validar'])
            ->assertSessionHas('success', fn(string $m) => str_contains($m, 'vuelve a borrador'));

        $this->assertSame('borrador', $config->fresh()->estado);
    }

    public function test_editar_un_sitio_reabre_la_lista_ya_validada(): void
    {
        $config = $this->validar();
        $sitio  = SitioFrecuentacion::where('zona_id', $this->zona->id)->firstOrFail();

        $this->actingAs($this->jefe)
            ->put("{$this->urlIndex($this->zona)}/{$sitio->id}", [
                'nombre' => 'Sitio editado tras validar',
                'det'    => 9.0,
            ])
            ->assertSessionHas('success', fn(string $m) => str_contains($m, 'vuelve a borrador'));

        $this->assertSame('borrador', $config->fresh()->estado);
    }

    public function test_borrar_un_sitio_reabre_la_lista_ya_validada(): void
    {
        $config = $this->validar();
        $sitio  = SitioFrecuentacion::where('zona_id', $this->zona->id)->firstOrFail();

        $this->actingAs($this->jefe)
            ->delete("{$this->urlIndex($this->zona)}/{$sitio->id}")
            ->assertSessionHas('success', fn(string $m) => str_contains($m, 'vuelve a borrador'));

        $this->assertSame('borrador', $config->fresh()->estado);
    }

    /**
     * El caso que no tiene equivalente en Involucrados: la ST es compartida
     * por todos los sitios, así que cambiarla también reabre una lista ya
     * validada, aunque no se haya tocado ningún sitio.
     */
    public function test_cambiar_la_superficie_territorial_reabre_la_lista_ya_validada(): void
    {
        $config = $this->validar();
        $this->assertSame('confirmado', $config->estado);

        $this->actingAs($this->jefe)
            ->post($this->urlSuperficie(), ['st' => '8.0'])
            ->assertSessionHas('success', fn(string $m) => str_contains($m, 'vuelve a borrador'));

        $this->assertSame('borrador', $config->fresh()->estado);
        $this->assertEqualsWithDelta(8.0, $config->fresh()->st, 0.0001);
    }

    /** Sin nada validado todavía, escribir un sitio no menciona ninguna reapertura. */
    public function test_escribir_sin_lista_validada_no_menciona_ninguna_reapertura(): void
    {
        $this->actingAs($this->jefe)
            ->post($this->urlIndex($this->zona), ['nombre' => 'Sitio en borrador'])
            ->assertSessionHas('success', fn(string $m) => ! str_contains($m, 'vuelve a borrador'));

        $this->assertDatabaseMissing('frecuentacion_config', ['zona_id' => $this->zona->id]);
    }

    // ── Tarea 4: el formulario ──────────────────────────────────────────────

    public function test_el_indice_pinta_la_st_actual_y_los_sitios_con_su_estado(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 5.5,
        ]);
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Cerro las Peñas',
        ]);
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 12.5]);

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('Malecón 2000')
            ->assertSee('Cerro las Peñas')
            // El valor precargado de la ST, en el propio atributo value.
            ->assertSee('value="12.5"', false)
            ->assertSee('Completo')
            ->assertSee('A medias');
    }

    /**
     * El error de validación de la ST se repinta en la propia página, no solo
     * se comprueba a nivel de sesión (ver test_guardar_la_superficie_territorial_rechaza_cero_y_negativos):
     * esta es la cara HTTP completa, siguiendo la redirección.
     */
    public function test_una_st_invalida_repinta_el_error_en_la_pagina_sin_guardar_nada(): void
    {
        $this->actingAs($this->jefe)
            ->from($this->urlIndex($this->zona))
            ->post($this->urlSuperficie(), ['st' => '0'])
            ->assertSessionHasErrors('st');

        // Seguir la redirección: el error solo demuestra que "se repinta en
        // la página" si de verdad se lee en una petición GET posterior, no
        // en la propia respuesta de redirección del POST.
        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('debe ser mayor que 0', false);

        $this->assertDatabaseMissing('frecuentacion_config', ['zona_id' => $this->zona->id]);
    }

    public function test_el_admin_ve_el_formulario_editable_y_no_ve_el_boton_de_validar(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio completo',
            'det'     => 1.0,
        ]);
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 5.0]);

        $html = $this->actingAs($admin)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('+ Nuevo sitio')
            ->getContent();

        // El botón de validar es del Jefe de Zona, aunque la lista esté
        // completa: mismo criterio que Involucrados.
        $this->assertStringNotContainsString('Validar y Cerrar la Lista', $html);

        // El campo lleva la clase Tailwind "disabled:bg-gray-100" en TODOS
        // los casos, activo o no: buscar la palabra "disabled" a secas no
        // distingue nada. Solo la parte de la etiqueta ANTES de "class="
        // puede llevar el atributo HTML real.
        preg_match('/<input[^>]*name="st"[^>]*>/', $html, $campoSt);
        $this->assertNotEmpty($campoSt, 'No se encontró el campo de la Superficie Territorial.');
        [$antesDeClase] = explode('class=', $campoSt[0]);
        $this->assertStringNotContainsString('disabled', $antesDeClase);
    }

    /**
     * Con la configuración confirmada, el equipo no puede tocar nada: ni
     * añadir un sitio, ni editar el existente, ni cambiar la ST. La vista
     * oculta los controles de escritura -mismo trato que Involucrados-.
     */
    public function test_el_equipo_no_puede_tocar_nada_con_la_configuracion_confirmada(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio validado',
            'det'     => 1.0,
        ]);
        FrecuentacionConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
            'st'      => 5.0,
        ]);

        $html = $this->actingAs($equipo)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('+ Nuevo sitio', $html);
        $this->assertStringNotContainsString('Editar', $html);
        $this->assertStringNotContainsString('Eliminar', $html);

        preg_match('/<input[^>]*name="st"[^>]*>/', $html, $campoSt);
        $this->assertNotEmpty($campoSt, 'No se encontró el campo de la Superficie Territorial.');
        [$antesDeClase] = explode('class=', $campoSt[0]);
        $this->assertStringContainsString('disabled', $antesDeClase);
    }

    /**
     * DET puede quedar realmente sin responder: el campo no debe precargarse
     * a 0 -que un evaluador confundiría con "el usuario respondió 0"-, mismo
     * tratamiento vacío-vs-cero que los campos numéricos de Concentración.
     */
    public function test_el_formulario_de_sitio_distingue_det_vacio_de_det_cero(): void
    {
        $sinResponder = SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio sin DET',
        ]);
        $conCero = SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio con DET en cero',
            'det'     => 0.0,
        ]);

        $paginaSinResponder = $this->actingAs($this->jefe)
            ->get(route('operativo.frecuentacion.edit', ['zona' => $this->zona->id, 'sitio' => $sinResponder->id]))
            ->assertOk()
            ->getContent();

        preg_match('/<input[^>]*name="det"[^>]*>/', $paginaSinResponder, $campo);
        $this->assertNotEmpty($campo, 'No se encontró el campo det.');
        $this->assertStringNotContainsString('value="0"', $campo[0]);

        $paginaConCero = $this->actingAs($this->jefe)
            ->get(route('operativo.frecuentacion.edit', ['zona' => $this->zona->id, 'sitio' => $conCero->id]))
            ->assertOk()
            ->assertSee('value="0"', false);
    }

    // ── Tarea 5: resultados ─────────────────────────────────────────────────

    private function urlResultados(): string
    {
        return route('operativo.frecuentacion.resultados', $this->zona->id);
    }

    /**
     * La cara positiva, no solo el assertDontSee del caso incompleto: con
     * datos completos, la tabla pinta el ÍETP real de cada sitio y el ÍEFT
     * del territorio con números concretos que se pueden verificar a mano.
     */
    public function test_resultados_pinta_los_numeros_reales_con_datos_completos(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 10.0,
        ]);
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Cerro las Peñas',
            'det'     => 5.0,
        ]);
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 2.0]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Malecón 2000')
            ->assertSee('Cerro las Peñas')
            // ÍETP de cada sitio: 10/2 = 5.0000, 5/2 = 2.5000.
            ->assertSee('5.0000')
            ->assertSee('2.5000')
            // ÍEFT del territorio: 5.0 + 2.5 = 7.5000.
            ->assertSee('7.5000');
    }

    /**
     * Un tercer sitio con un DET distinto, para que un ensamblado que
     * confundiera el ÍETP de un sitio con el de otro -o que sumara mal- se
     * note: con estos tres valores, ninguna suma parcial da la misma cifra
     * que el total correcto.
     */
    public function test_ieft_es_la_suma_correcta_de_los_ietp_de_tres_sitios(): void
    {
        SitioFrecuentacion::create(['zona_id' => $this->zona->id, 'nombre' => 'A', 'det' => 8.0]);
        SitioFrecuentacion::create(['zona_id' => $this->zona->id, 'nombre' => 'B', 'det' => 3.0]);
        SitioFrecuentacion::create(['zona_id' => $this->zona->id, 'nombre' => 'C', 'det' => 1.0]);
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 4.0]);

        // ÍETP: 8/4=2.0, 3/4=0.75, 1/4=0.25. ÍEFT = 2.0+0.75+0.25 = 3.0.
        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('2.0000')
            ->assertSee('0.7500')
            ->assertSee('0.2500')
            ->assertSee('3.0000');
    }

    /**
     * Sin ningún sitio registrado: la página cae en <x-matriz-sin-resultados>
     * sin reventar, y el motivo es sobre los sitios, no sobre la ST.
     */
    public function test_resultados_con_la_lista_vacia_dice_que_faltan_sitios(): void
    {
        // ST ya guardada, para aislar el motivo: sin esto, una zona nueva
        // fallaría los DOS requisitos a la vez y no probaría nada distinto
        // del test de "los dos motivos a la vez" de más abajo.
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 5.0]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Frecuentación turística sin resultados')
            ->assertSee('Faltan sitios por responder', false)
            ->assertDontSee('falta la Superficie Territorial', false);
    }

    /**
     * ST ausente, o guardada en 0 -por otro camino que no sea el formulario,
     * que ya lo rechaza con gt:0-: la página entera cae en el aviso, con el
     * motivo puesto sobre la ST y no sobre los sitios, y sin ningún
     * DivisionByZeroError.
     */
    public function test_resultados_con_st_ausente_o_en_cero_cae_en_sin_resultados_sin_reventar(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio completo',
            'det'     => 3.0,
        ]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Falta la Superficie Territorial, o es cero', false)
            ->assertDontSee('Faltan sitios por responder', false)
            // Con la ST sin rellenar, ningún ÍETP ni ÍEFT se pinta -aunque el
            // sitio esté completo-: solo el aviso, nunca una tabla con
            // guiones ni un número que parezca un resultado.
            ->assertDontSee('ÍETP')
            ->assertDontSee('ÍEFT');

        // ST guardada en 0 -no debería poder llegar así por el formulario,
        // pero Frecuentacion::ietp() y esta página son la segunda defensa si
        // llega por otro camino (migración, factory, un dato antiguo)-.
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 0]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Falta la Superficie Territorial, o es cero', false)
            ->assertDontSee('ÍETP')
            ->assertDontSee('ÍEFT');
    }

    /** Con un sitio a medias y la ST también sin definir, se citan los dos motivos. */
    public function test_resultados_cita_los_dos_motivos_cuando_los_dos_faltan(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio a medias',
        ]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertSee('Faltan sitios por responder', false)
            ->assertSee('falta la Superficie Territorial, o es cero', false);
    }

    /** No hay anotación "no aplica" fila por fila: el aviso se resuelve una vez, antes de la tabla. */
    public function test_resultados_no_pinta_ninguna_tabla_con_la_lista_incompleta(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio completo',
            'det'     => 1.0,
        ]);
        $incompleto = SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio incompleto',
        ]);
        FrecuentacionConfig::create(['zona_id' => $this->zona->id, 'st' => 5.0]);

        $this->actingAs($this->jefe)
            ->get($this->urlResultados())
            ->assertOk()
            ->assertDontSee('ÍETP')
            ->assertDontSee('ÍEFT');
    }

    /**
     * Mismo defecto que tenía Involucrados, y por el mismo motivo: el aviso de
     * que tocar la lista la reabre es para quien puede tocarla.
     *
     * Con la lista validada `$puedeEditar` solo es cierto para el jefe, así
     * que al admin y al equipo se les anunciaba la consecuencia de una acción
     * que no pueden ejecutar — y que ni siquiera se les pinta: los botones que
     * el párrafo advierte ya iban condicionados.
     *
     * El traspaso listaba este defecto solo en Involucrados. Estaba en las dos
     * pantallas, que son las dos que comparten esta forma de banner.
     */
    public function test_quien_no_puede_tocar_la_lista_validada_no_lee_que_tocarla_la_reabre(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        FrecuentacionConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
            'st'      => 1200,
        ]);

        $this->actingAs($admin)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('Lista de sitios validada')
            ->assertDontSee('vuelve a borrador: hay que validarla de nuevo');
    }

    /** La contraparte positiva: al jefe sí hay que decírselo. */
    public function test_el_jefe_si_lee_que_tocar_la_lista_validada_la_reabre(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        FrecuentacionConfig::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
            'st'      => 1200,
        ]);

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('Lista de sitios validada')
            ->assertSee('vuelve a borrador: hay que validarla de nuevo');
    }
}
