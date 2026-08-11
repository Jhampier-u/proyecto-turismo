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
}
