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

class GuardadoParcialTest extends TestCase
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
        return "/operativo/zona/{$this->zona->id}/paisaje{$sufijo}";
    }

    private function todosEn(int $valor): array
    {
        return array_fill_keys(array_keys(Paisaje::todos()), $valor);
    }

    public function test_se_guarda_un_borrador_con_criterios_en_blanco(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia'], $datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertSame('borrador', $eval->estado);
        $this->assertNull($eval->pn_geologia);
        $this->assertNull($eval->cp_conurbaciones);
        $this->assertSame(3, (int) $eval->ep_cambios_tiempo);
    }

    /**
     * El test que distingue el fallo del arreglo: un 0 elegido a conciencia es
     * un dato, un hueco no lo es, y no pueden guardarse igual.
     */
    public function test_un_cero_respondido_no_se_confunde_con_un_hueco(): void
    {
        $datos = $this->todosEn(3);
        $datos['pn_geologia'] = 0;
        unset($datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertSame(0, (int) $eval->pn_geologia);
        $this->assertNull($eval->cp_conurbaciones);
    }

    public function test_con_criterios_pendientes_no_hay_resultado(): void
    {
        $datos = $this->todosEn(5);
        unset($datos['pn_geologia']);

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertNull($eval->paisaje_total);
        $this->assertNull($eval->pn_promedio);
        $this->assertNull($eval->ep_promedio);
    }

    public function test_completar_la_matriz_calcula_el_resultado(): void
    {
        $parcial = $this->todosEn(5);
        unset($parcial['pn_geologia']);
        $this->actingAs($this->jefe)->post($this->url(), $parcial);

        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertEqualsWithDelta(5.0, (float) $eval->paisaje_total, 0.0001);
    }

    public function test_confirmar_con_huecos_se_rechaza(): void
    {
        $datos = $this->todosEn(3) + ['accion_estado' => 'confirmado'];
        unset($datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('cp_conurbaciones');

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }

    public function test_confirmar_completa_sigue_funcionando(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(5) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertSame('confirmado', $eval->estado);
        $this->assertEqualsWithDelta(5.0, (float) $eval->paisaje_total, 0.0001);
    }

    /** La escala no contigua sigue vigente: 0, 3 y 5, o nada. */
    public function test_un_valor_fuera_de_escala_se_rechaza_tambien_en_borrador(): void
    {
        $datos = $this->todosEn(3);
        $datos['pn_geologia'] = 4;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('pn_geologia');

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }

    public function test_el_mensaje_dice_cuantos_criterios_llevas(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia'], $datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHas('success', fn(string $m) => str_contains($m, '32 de 34'));
    }

    /** Un borrador incompleto vuelve al formulario, no a unos resultados vacíos. */
    public function test_un_borrador_incompleto_no_redirige_a_resultados(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia']);

        $this->actingAs($this->jefe)
            ->from($this->url())
            ->post($this->url(), $datos)
            ->assertRedirect($this->url());
    }

    public function test_el_equipo_tambien_guarda_a_medias(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $datos = $this->todosEn(3);
        unset($datos['pn_geologia']);

        $this->actingAs($equipo)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPaisaje::value('estado'));
        $this->assertNull(EvaluacionPaisaje::value('pn_geologia'));
    }
}
