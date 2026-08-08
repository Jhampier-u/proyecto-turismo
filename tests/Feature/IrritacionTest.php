<?php

namespace Tests\Feature;

use App\Matrices\Irritacion;
use App\Models\EvaluacionIrritacion;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IrritacionTest extends TestCase
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
     * La escala es inversa y el desplegable tiene que decirlo: un 7 suelto no
     * significa nada, «7 — Crítico» sí. Es lo que evita tener que consultar la
     * tabla de rangos del instrumento aparte.
     */
    public function test_el_desplegable_etiqueta_cada_valor_con_su_clasificacion(): void
    {
        $this->withViewErrors([]);

        $html = (string) $this->blade('<x-select-0-10 label="Congestión" name="c" :val="null" />');

        $this->assertStringContainsString('0 — Bajo', $html);
        $this->assertStringContainsString('2 — Bajo', $html);
        $this->assertStringContainsString('3 — Moderado', $html);
        $this->assertStringContainsString('6 — Moderado', $html);
        $this->assertStringContainsString('7 — Crítico', $html);
        $this->assertStringContainsString('10 — Crítico', $html);

        // Un "<=" mal escrito en el bucle del componente pasaría igual las
        // aserciones de arriba (contains, no cuenta); esto lo delata. Once
        // valores más el hueco, ni uno más: el servidor validará 0..10 y una
        // opción de sobra dejaría elegir algo que el backend rechazaría sin
        // explicar por qué.
        $this->assertSame(12, substr_count($html, '<option '), 'La escala no tiene once valores más el hueco.');
    }

    /**
     * Los tres tramos en sus bordes exactos. El instrumento se contradice a sí
     * mismo en una tabla —dice «De 3 a 5» en un lado y «De 3 a 6» en el otro—
     * pero todas sus fórmulas usan >=3, y eso es lo que se implementa.
     */
    public function test_la_clasificacion_respeta_los_umbrales_del_instrumento(): void
    {
        // Pares y no un array asociativo: PHP trunca las claves float a
        // entero, así que 2.9 pisaría a 2.0 y los dos casos con decimales
        // —los que de verdad distinguen >= de >— nunca se llegarían a probar.
        $casos = [
            [0.0, 'Bajo'], [2.0, 'Bajo'], [2.9, 'Bajo'],
            [3.0, 'Moderado'], [6.0, 'Moderado'], [6.9, 'Moderado'],
            [7.0, 'Crítico'], [10.0, 'Crítico'],
        ];

        foreach ($casos as [$valor, $esperada]) {
            $this->assertSame(
                $esperada,
                Irritacion::clasificar($valor),
                "El promedio {$valor} no se clasificó como {$esperada}."
            );
        }
    }

    /** Sin promedio no hay clasificación: la matriz está a medias. */
    public function test_sin_promedio_no_hay_clasificacion(): void
    {
        $this->assertNull(Irritacion::clasificar(null));
    }

    /**
     * Nada instancia hoy el modelo. Una errata en el nombre del atributo
     * dentro de un accesorio devolvería null en silencio, y no se vería
     * hasta la Task 3, a través de un POST, donde diagnosticarlo cuesta
     * mucho más. Construir con make() en vez de con datos ya guardados
     * también comprueba que $fillable use los mismos nombres que las
     * columnas: una errata ahí dejaría el atributo sin asignar y la
     * clasificación en null, no en el valor esperado.
     */
    public function test_los_accesorios_de_clasificacion_leen_su_propio_promedio(): void
    {
        $eval = EvaluacionIrritacion::make([
            'visitantes_promedio' => 7.0,
            'residentes_promedio' => 1.0,
        ]);

        $this->assertSame('Crítico', $eval->clasificacion_visitantes);
        $this->assertSame('Bajo',    $eval->clasificacion_residentes);
    }

    private function url(string $sufijo = ''): string
    {
        return "/operativo/zona/{$this->zona->id}/irritacion{$sufijo}";
    }

    /** Los doce atributos al mismo valor. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(
            array_merge(Irritacion::VISITANTES, Irritacion::RESIDENTES),
            $valor
        );
    }

    public function test_cada_bloque_promedia_solo_sus_seis_atributos(): void
    {
        $datos = $this->todosEn(0);

        // Visitantes a 6 de media: 10, 8, 6, 4, 2, 6.
        $valores = [10, 8, 6, 4, 2, 6];
        foreach (Irritacion::VISITANTES as $i => $campo) {
            $datos[$campo] = $valores[$i];
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionIrritacion::firstOrFail();

        $this->assertEqualsWithDelta(6.0, $eval->visitantes_promedio, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->residentes_promedio, 0.0001);
        $this->assertSame('Moderado', $eval->clasificacion_visitantes);
        $this->assertSame('Bajo', $eval->clasificacion_residentes);
    }

    /** La escala más ancha del sistema hasta ahora era 0-5. */
    public function test_el_diez_se_acepta_y_el_once_se_rechaza(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(10))
            ->assertSessionHasNoErrors();

        $datos = $this->todosEn(5);
        $datos['vis_congestion'] = 11;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('vis_congestion');
    }

    /** Heredado de la clase base, pero es la primera matriz que nace con ello. */
    public function test_un_atributo_sin_responder_no_baja_la_media(): void
    {
        $datos = $this->todosEn(6);
        unset($datos['vis_congestion']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionIrritacion::firstOrFail();

        $this->assertNull($eval->vis_congestion);
        $this->assertNull($eval->visitantes_promedio);
        $this->assertNull($eval->residentes_promedio);
    }

    public function test_el_jefe_confirma_y_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionIrritacion::value('estado'));

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('confirmado', EvaluacionIrritacion::value('estado'));
    }

    /**
     * La clase base no responde 403 aquí: devuelve al formulario con el
     * mensaje de cerrada. Lo que hay que comprobar es que los valores del jefe
     * siguen intactos, igual que en EvaluacionesTest y PaisajeTest.
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

        $eval = EvaluacionIrritacion::firstOrFail();

        $this->assertSame('confirmado', $eval->estado);
        $this->assertSame(4, $eval->vis_congestion);
    }
}
