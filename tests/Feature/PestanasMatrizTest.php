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

            $html = $this->actingAs($this->jefe)
                ->get(route($entrada['rutas']['editar'], $this->zona->id))
                ->assertOk()
                ->getContent();

            // Buscar solo el literal "Resultados" pasaría en verde aunque
            // <x-pestanas-matriz> no existiera -la palabra podría aparecer por
            // cualquier otro motivo en la vista-. Con la zona recién creada
            // ninguna matriz tiene evaluación, así que la pestaña tiene que
            // estar bloqueada: se comprueba el texto de candado que solo pinta
            // el componente, y la ausencia del enlace a resultados.
            $this->assertStringNotContainsString(
                route($entrada['rutas']['ver'], $this->zona->id),
                $html,
                "«{$clave}»: con la zona recién creada no debería haber enlace a resultados."
            );
            // 'sitios' es hermano de 'actores', no una reutilización: mismo
            // candado de conjunto variable, texto propio ("sin sitios
            // completos") en vez del recuento "N de M criterios" que no tiene
            // sentido para una lista de longitud variable.
            $textoCandado = match ($entrada['tipo']) {
                'actores' => 'sin actores completos',
                'sitios'  => 'sin sitios completos',
                default   => "0 de {$entrada['criterios']} criterios",
            };

            $this->assertStringContainsString(
                $textoCandado,
                $html,
                "«{$clave}»: no se encontró el texto de candado de <x-pestanas-matriz>."
            );
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
     * El caso concreto del Hallazgo 5: Potencialidad es configurable -el jefe
     * puede activar solo una parte de sus 156 criterios-, así que "completa"
     * no puede seguir siendo solo un recuento contra el total declarado. Con
     * 20 de 156 activados, respondidos y validados, el recuento por sí solo
     * seguiría pintando candado sobre una matriz confirmada, con resultados
     * ya calculados: el estado tiene que ganarle al recuento.
     */
    public function test_potencialidad_validada_con_campos_desactivados_ofrece_el_enlace_a_resultados(): void
    {
        $campos = array_slice(array_keys(\App\Matrices\Potencialidad::todos()), 0, 20);

        $this->actingAs($this->jefe)->post(
            route('operativo.evaluacion_potencialidad.update', $this->zona->id),
            array_fill_keys($campos, 2) + ['campos' => $campos, 'accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $eval = \App\Models\EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();
        $this->assertSame('confirmado', $eval->estado);

        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_potencialidad.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(
            route('operativo.evaluacion_potencialidad.ponderacion', $this->zona->id),
            $html,
            'Una Potencialidad validada, aunque tenga campos desactivados, debe ofrecer el enlace a resultados.'
        );
        $this->assertStringNotContainsString('de 156 criterios', $html);
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

    /**
     * La rama de actores del candado no tenía ningún test con actores de por
     * medio: los existentes dejaban la lista vacía, que cae en la misma rama
     * pero por el otro motivo ("cuantos === 0"). Este crea un actor a medias
     * -con algún criterio sin responder- para ejercer el motivo que faltaba:
     * "hay actores, pero no todos completos".
     */
    public function test_actores_incompletos_no_ofrecen_enlace_a_resultados(): void
    {
        \App\Models\Involucrado::create([
            'zona_id'       => $this->zona->id,
            'nombre'        => 'Actor a medias',
            'pod_autoridad' => 2,
            // El resto de los once criterios queda sin responder (null).
        ]);

        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.involucrados.index', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            route('operativo.involucrados.resultados', $this->zona->id),
            $html
        );
        $this->assertStringContainsString('sin actores completos', $html);
    }
}
