<?php

namespace Tests\Feature;

use App\Matrices\Fit;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * App\Matrices\Fit es la definición del instrumento que antes solo existía
 * como dimensión => [campos] en EvaluacionFitController, sin ningún texto:
 * ver docs/ESTADO-PROYECTO.md, rama mensajes-validacion. Estos tests cubren
 * lo que esa migración añade: la definición en sí y que el formulario la
 * recorre en vez de tener los 18 criterios tecleados en el Blade.
 *
 * El resto del comportamiento de FIT -cálculo, guardado parcial, bloqueo,
 * mensajes de cierre- ya estaba cubierto en EvaluacionesTest.php y compañía
 * y no se toca aquí.
 */
class FitTest extends TestCase
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
        return "/operativo/zona/{$this->zona->id}/evaluacion-fit";
    }

    public function test_el_instrumento_declara_18_criterios_en_6_bloques(): void
    {
        $this->assertCount(6, Fit::BLOQUES);
        $this->assertCount(18, Fit::todos());

        $esperado = ['rtt' => 2, 'at' => 2, 'pst' => 3, 'ptt' => 1, 'i' => 2, 'ft' => 8];

        foreach ($esperado as $clave => $cuantos) {
            $this->assertCount($cuantos, Fit::BLOQUES[$clave]['criterios'], $clave);
        }
    }

    public function test_los_pesos_de_los_bloques_suman_uno(): void
    {
        $this->assertEqualsWithDelta(
            1.0,
            array_sum(array_column(Fit::BLOQUES, 'peso')),
            0.0001
        );
    }

    /** Los 4 niveles son genéricos e iguales en los 18 criterios, a diferencia de Paisaje. */
    public function test_cada_criterio_declara_los_4_niveles_genericos(): void
    {
        foreach (Fit::todos() as $campo => $criterio) {
            $this->assertNotEmpty($criterio['nombre'], $campo);
            $this->assertSame([0, 1, 2, 3], array_keys($criterio['niveles']), $campo);
            $this->assertSame(['Nulo', 'Bajo', 'Medio', 'Alto'], array_values($criterio['niveles']), $campo);
        }
    }

    /**
     * El formulario pinta los 18 criterios recorriendo Fit::BLOQUES, no
     * tecleados: si alguien borra un campo de la definición, este test lo
     * nota sin que nadie tenga que actualizar una lista aparte a mano.
     */
    public function test_el_formulario_muestra_los_18_criterios_con_sus_etiquetas(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (Fit::todos() as $campo => $criterio) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }

        // Un par de etiquetas concretas: si el formulario mostrara solo los
        // niveles genéricos sin el nombre de cada criterio, este texto no
        // aparecería en ningún sitio de la página.
        $respuesta->assertSee('Señalética');
        $respuesta->assertSee('Baterías Sanitarias');
    }

    public function test_no_se_accede_desde_una_zona_ajena(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }

    /**
     * La barra lateral aparece con el total correcto y con un enlace por
     * bloque -6 bloques en FIT-. No se usa assertSee con el texto suelto
     * "6/18" porque podría coincidir por casualidad con otro número de la
     * página: se cuentan los enlaces de ancla, que solo los pinta la barra
     * lateral.
     *
     * El ancho de la página ya no se comprueba aquí: ahora lo decide
     * <x-contenedor> en el layout, y lo cubre ContenedorTest. Repetir un
     * literal de clase Tailwind en cada vista era justo la duplicación que
     * la fundación visual vino a quitar.
     */
    public function test_el_formulario_muestra_la_barra_lateral_con_sus_seis_bloques(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_fit.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        // Str::between() devuelve la cadena COMPLETA cuando el delimitador
        // no existe -nunca una cadena vacía-, así que assertNotEmpty()
        // sobre el resultado no protegía nada: sin <aside>, $fragmento
        // habría sido la página entera y este assert habría pasado igual.
        // Se comprueba la presencia real del delimitador antes de recortar.
        $this->assertStringContainsString('<aside', $html, 'No se encontró <aside>: la barra lateral no se está pintando.');
        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');

        foreach (array_keys(\App\Matrices\Fit::BLOQUES) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace al bloque '{$clave}'.");
        }
    }

    /**
     * Con 5 de los 18 criterios respondidos, el bloque RTT -2 criterios,
     * ambos respondidos- aparece completo y con marcador; el resto de la
     * barra sigue mostrando su fracción real, no un hueco.
     */
    public function test_la_barra_lateral_desglosa_los_respondidos_por_bloque(): void
    {
        $evaluacion = \App\Models\EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);
        $evaluacion->update(['recursos_culturales' => 2, 'recursos_naturales' => 3]);

        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_fit.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('<aside', $html, 'No se encontró <aside>: la barra lateral no se está pintando.');
        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $rtt = \Illuminate\Support\Str::between($fragmento, 'href="#rtt"', '</a>');

        $this->assertStringContainsString('✓', $rtt);
        $this->assertStringContainsString('2/2', $rtt);
    }
}
