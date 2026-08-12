<?php

namespace Tests\Feature;

use App\Matrices\Percepcion;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * App\Matrices\Percepcion es la definición del instrumento que antes vivía en
 * EvaluacionPercepcionController::$categorias -ver docs/ESTADO-PROYECTO.md,
 * rama percepcion-componentes-. A diferencia de FIT y FET, Percepción ya daba
 * su etiqueta en los mensajes de validación antes de esta migración: lo que
 * cambia aquí es solo dónde vive la definición y que el formulario la recorre
 * con <x-criterio-pildoras> en vez de <select>.
 *
 * El resto del comportamiento de Percepción -cálculo, guardado parcial,
 * bloqueo, mensajes de cierre- ya estaba cubierto en EvaluacionesTest.php y
 * compañía y no se toca aquí.
 */
class PercepcionTest extends TestCase
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
        return "/operativo/zona/{$this->zona->id}/evaluacion-percepcion";
    }

    public function test_el_instrumento_declara_16_criterios_en_4_categorias(): void
    {
        $this->assertCount(4, Percepcion::$categorias);
        $this->assertCount(16, Percepcion::todos());

        $esperado = ['DS' => 3, 'PL' => 6, 'PE' => 3, 'NO' => 4];

        foreach ($esperado as $codigo => $cuantos) {
            $this->assertCount($cuantos, Percepcion::$categorias[$codigo]['items'], $codigo);
        }
    }

    public function test_los_pesos_de_las_categorias_suman_uno(): void
    {
        $this->assertEqualsWithDelta(
            1.0,
            array_sum(array_column(Percepcion::$categorias, 'peso')),
            0.0001
        );
    }

    /** Los 3 niveles son genéricos e iguales en los 16 criterios: Negativo/Neutral/Positivo. */
    public function test_los_3_niveles_son_genericos_y_van_de_1_a_3(): void
    {
        $this->assertSame([1, 2, 3], array_keys(Percepcion::NIVELES));
        $this->assertSame(['Negativo', 'Neutral', 'Positivo'], array_values(Percepcion::NIVELES));
    }

    /**
     * El formulario pinta los 16 criterios recorriendo Percepcion::$categorias,
     * no tecleados: si alguien borra un campo de la definición, este test lo
     * nota sin que nadie tenga que actualizar una lista aparte a mano.
     */
    public function test_el_formulario_muestra_los_16_criterios_con_sus_etiquetas(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (Percepcion::todos() as $campo => $etiqueta) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }

        // Un par de etiquetas concretas: si el formulario mostrara solo los
        // niveles genéricos sin el texto de cada atributo, esto no aparecería.
        $respuesta->assertSee('Posición frente a la actividad turística en el lugar');
        $respuesta->assertSee('Presencia de conflictos entre actores y grupos sociales');

        // Prueba de verdad de que el control es <x-criterio-pildoras> y no el
        // <select> anterior: 16 criterios × 3 niveles = 48 radios. Un simple
        // assertSee del nombre del campo no lo distinguiría, porque un
        // <select name="..."> también lo cumpliría.
        $this->assertSame(48, substr_count($respuesta->getContent(), 'type="radio"'));
    }

    public function test_no_se_accede_desde_una_zona_ajena(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }

    /**
     * El guardado parcial vive en MatrizPonderadaController y ya se ejercita
     * con Paisaje y FIT en GuardadoParcialTest.php; esto confirma que sigue
     * funcionando en Percepción tras cambiar de <select> a <x-criterio-pildoras>
     * -el control cambia, pero un hueco sigue sin ser un cero-.
     */
    public function test_el_guardado_parcial_tambien_funciona_en_percepcion(): void
    {
        $datos = array_fill_keys(array_keys(Percepcion::todos()), 3);
        unset($datos['ds1_posicion_turistica']);

        $this->actingAs($this->jefe)
            ->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $percepcion = \App\Models\EvaluacionPercepcion::firstOrFail();

        $this->assertSame('borrador', $percepcion->estado);
        $this->assertNull($percepcion->ds1_posicion_turistica);
        $this->assertNull($percepcion->percepcion_total);
        $this->assertSame(3, (int) $percepcion->pl3_conoc_motivo_visita);
    }

    /**
     * Percepción ya está en max-w-7xl -no se toca el ancho, solo se añade
     * la barra lateral-. Un enlace por categoría -4 en Percepción-, cada
     * una con su clave 'items' en vez de 'criterios'.
     */
    public function test_el_formulario_muestra_la_barra_lateral_con_sus_categorias(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get($this->url())
            ->assertOk()
            ->getContent();

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento, 'No se encontró <aside>: la barra lateral no se está pintando.');

        foreach (array_keys(Percepcion::$categorias) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace a la categoría '{$clave}'.");
        }
    }

    /**
     * Con 'ds1_posicion_turistica' respondido de los 3 items de 'DS', la
     * barra muestra 1/3 para esa categoría, sin marcador de completa.
     */
    public function test_la_barra_lateral_desglosa_los_respondidos_por_categoria(): void
    {
        $evaluacion = \App\Models\EvaluacionPercepcion::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);
        $evaluacion->update(['ds1_posicion_turistica' => 3]);

        $html = $this->actingAs($this->jefe)
            ->get($this->url())
            ->assertOk()
            ->getContent();

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $ds = \Illuminate\Support\Str::between($fragmento, 'href="#DS"', '</a>');

        $this->assertStringContainsString('1/3', $ds);
        $this->assertStringNotContainsString('✓', $ds);
    }
}
