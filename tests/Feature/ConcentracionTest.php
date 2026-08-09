<?php

namespace Tests\Feature;

use App\Matrices\Concentracion;
use App\Models\EvaluacionConcentracion;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Task 3 del Índice de Concentración Turística: tabla, modelo, controlador,
 * rutas y registro. No cubre el formulario ni los resultados de verdad -son
 * las Tareas 4 y 5, y sus vistas aquí son esqueletos mínimos-, solo que la
 * máquina de estados y la validación funcionan igual que en el resto de
 * matrices, con la salvedad de que esta no tiene tope.
 */
class ConcentracionTest extends TestCase
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

    private function url(string $sufijo = ''): string
    {
        return "/operativo/zona/{$this->zona->id}/concentracion{$sufijo}";
    }

    /** Los 113 campos al mismo valor. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(Concentracion::campos(), $valor);
    }

    public function test_se_guarda_un_borrador_con_huecos(): void
    {
        $datos = $this->todosEn(2);
        unset($datos['pt_al_hotel'], $datos['at_mc_arquitectura_museos']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionConcentracion::firstOrFail();

        $this->assertSame('borrador', $eval->estado);
        $this->assertNull($eval->pt_al_hotel);
        $this->assertNull($eval->at_mc_arquitectura_museos);
        $this->assertSame(2, $eval->pt_al_hostal);
    }

    /**
     * La escala no tiene tope: un conteo de 500 es tan válido como uno de 3
     * -una zona puede tener trescientos hoteles-. Con la regla por defecto de
     * MatrizPonderadaController (min/max a partir de escala()) esto
     * reventaría, porque aquí escala() no tiene un máximo real que dar; es
     * justo lo que reglaValor() sobreescrito evita.
     */
    public function test_un_conteo_grande_se_acepta(): void
    {
        $datos = $this->todosEn(0);
        $datos['pt_rs_restaurante'] = 500;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $this->assertSame(500, EvaluacionConcentracion::firstOrFail()->pt_rs_restaurante);
    }

    /** El único límite real de esta matriz: no hay cantidades negativas de nada. */
    public function test_un_negativo_se_rechaza(): void
    {
        $datos = $this->todosEn(3);
        $datos['at_nat_rios_rio'] = -1;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('at_nat_rios_rio');

        $this->assertDatabaseCount('evaluaciones_concentracion', 0);
    }

    public function test_el_jefe_confirma_y_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)->post(
            $this->url(),
            $this->todosEn(1) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionConcentracion::value('estado'));

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(1) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('confirmado', EvaluacionConcentracion::value('estado'));
    }

    /**
     * La clase base no responde 403 aquí: devuelve al formulario con el
     * mensaje de cerrada, igual que en IrritacionTest y PaisajeTest. Lo que
     * hay que comprobar es que los conteos del jefe siguen intactos.
     */
    public function test_una_evaluacion_confirmada_queda_cerrada_para_el_equipo(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->from($this->url())
            ->post($this->url(), $this->todosEn(9))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validado'));

        $eval = EvaluacionConcentracion::firstOrFail();

        $this->assertSame('confirmado', $eval->estado);
        $this->assertSame(4, $eval->pt_al_hotel);
    }

    public function test_el_admin_no_puede_modificar_la_matriz(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->post($this->url(), $this->todosEn(4))
            ->assertForbidden();

        $this->assertDatabaseCount('evaluaciones_concentracion', 0);
    }

    /**
     * El middleware deja pasar al admin en cualquier GET, confirmada o no la
     * matriz: sin este predicado el admin vería el formulario abierto con
     * "Guardar Borrador" habilitado, que terminaría en un 403 crudo al
     * enviarlo -el mismo caso que ya cubre IrritacionTest-.
     */
    public function test_el_admin_recibe_el_formulario_bloqueado_aunque_este_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $this->assertSame('borrador', EvaluacionConcentracion::value('estado'));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url())
            ->assertOk()
            ->assertDontSee('Guardar Borrador');
    }

    /**
     * Navega de verdad a la página de resultados como admin, no solo
     * comprueba que el panel enlaza a ella: es el fallo que esta misma serie
     * ya corrigió en Paisaje, Valoración Territorial e Irritación.
     */
    public function test_el_admin_ve_los_resultados_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Volver a la zona');
    }
}
