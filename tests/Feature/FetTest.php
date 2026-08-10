<?php

namespace Tests\Feature;

use App\Matrices\Fet;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Análoga a FitTest.php: App\Matrices\Fet es la definición del instrumento
 * que antes solo existía como dimensión => [campos] en
 * EvaluacionFetController, sin ningún texto. El resto del comportamiento de
 * FET ya estaba cubierto en EvaluacionesTest.php y compañía.
 */
class FetTest extends TestCase
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
        return "/operativo/zona/{$this->zona->id}/evaluacion-fet";
    }

    public function test_el_instrumento_declara_9_criterios_en_3_bloques(): void
    {
        $this->assertCount(3, Fet::BLOQUES);
        $this->assertCount(9, Fet::todos());

        $esperado = ['demanda' => 2, 'super' => 3, 'imagen' => 4];

        foreach ($esperado as $clave => $cuantos) {
            $this->assertCount($cuantos, Fet::BLOQUES[$clave]['criterios'], $clave);
        }
    }

    public function test_los_pesos_de_los_bloques_suman_uno(): void
    {
        $this->assertEqualsWithDelta(
            1.0,
            array_sum(array_column(Fet::BLOQUES, 'peso')),
            0.0001
        );
    }

    public function test_cada_criterio_declara_los_4_niveles_genericos(): void
    {
        foreach (Fet::todos() as $campo => $criterio) {
            $this->assertNotEmpty($criterio['nombre'], $campo);
            $this->assertSame([0, 1, 2, 3], array_keys($criterio['niveles']), $campo);
            $this->assertSame(['Nulo', 'Bajo', 'Medio', 'Alto'], array_values($criterio['niveles']), $campo);
        }
    }

    public function test_el_formulario_muestra_los_9_criterios_con_sus_etiquetas(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (Fet::todos() as $campo => $criterio) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }

        $respuesta->assertSee('Flujos Turísticos');
        $respuesta->assertSee('Marketing Turístico');
    }

    public function test_no_se_accede_desde_una_zona_ajena(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }
}
