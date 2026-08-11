<?php

namespace Tests\Feature;

use App\Matrices\Registro;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PestanasMatrizTest extends TestCase
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
     * La pareja del test de integridad del registro: si una matriz nueva no
     * engancha sus pestañas, salta aquí en vez de quedarse sin navegación.
     */
    public function test_todas_las_matrices_muestran_las_pestanas_en_su_formulario(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            if (! isset($entrada['rutas']['editar'], $entrada['rutas']['ver'])) {
                continue;
            }

            $this->actingAs($this->jefe)
                ->get(route($entrada['rutas']['editar'], $this->zona->id))
                ->assertOk()
                ->assertSee('Resultados', false);
        }
    }

    public function test_una_matriz_vacia_no_ofrece_enlace_a_resultados(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id),
            $html
        );
        $this->assertStringContainsString('0 de 34', $html);
    }

    public function test_una_matriz_a_medias_dice_cuantos_faltan(): void
    {
        $evaluacion = \App\Models\EvaluacionPaisaje::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        foreach (array_slice(array_keys(\App\Matrices\Paisaje::todos()), 0, 30) as $campo) {
            $evaluacion->$campo = 3;
        }
        $evaluacion->save();

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee('30 de 34');
    }

    public function test_una_matriz_completa_desbloquea_el_enlace(): void
    {
        $this->actingAs($this->jefe)->post(
            route('operativo.evaluacion_paisaje.update', $this->zona->id),
            array_fill_keys(array_keys(\App\Matrices\Paisaje::todos()), 3)
        );

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id), false);
    }

    public function test_los_resultados_tambien_llevan_pestanas(): void
    {
        $this->actingAs($this->jefe)->post(
            route('operativo.evaluacion_paisaje.update', $this->zona->id),
            array_fill_keys(array_keys(\App\Matrices\Paisaje::todos()), 3)
        );

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }

    /**
     * El recuento de las pestañas y el de la ficha de la zona salen del mismo
     * contador. Este test es lo que impide que se separen.
     */
    public function test_el_recuento_coincide_con_el_de_la_ficha_de_la_zona(): void
    {
        $evaluacion = \App\Models\EvaluacionPaisaje::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        foreach (array_slice(array_keys(\App\Matrices\Paisaje::todos()), 0, 21) as $campo) {
            $evaluacion->$campo = 3;
        }
        $evaluacion->save();

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee('21 de 34');

        $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee('21 de 34');
    }
}
