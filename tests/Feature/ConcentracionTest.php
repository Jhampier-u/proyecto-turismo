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
     *
     * "Volver a Zonas", no "Volver a la zona": esta prueba viene de la Tarea
     * 3, cuando el esqueleto pintaba siempre <x-matriz-sin-resultados> -y su
     * botón de solo lectura dice "Volver a la zona"-. La Tarea 5 sustituye
     * ese esqueleto por las tablas de verdad, y todosEn(4) completa los 113
     * conteos: la matriz deja de estar "sin resultados", así que lo que ve
     * el admin aquí es la página de resultados real, con el mismo botón de
     * solo lectura que Paisaje e Irritación (ver su
     * test_el_admin_ve_los_resultados_en_modo_lectura).
     */
    public function test_el_admin_ve_los_resultados_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Volver a Zonas')
            ->assertSee(route('admin.zonas.index'), false)
            ->assertDontSee('href="' . route('operativo.evaluacion_concentracion.edit', $this->zona->id) . '"', false);
    }

    /**
     * Task 4: el formulario de verdad -no el esqueleto de la Tarea 3- pinta
     * los 113 campos. Recorridos desde Concentracion::campos() y no
     * tecleados aquí: si el instrumento ganara o perdiera un subtipo el día
     * de mañana, este test lo seguiría comprobando sin que nadie lo tocara.
     */
    public function test_el_formulario_pinta_los_113_campos(): void
    {
        $campos = Concentracion::campos();
        $this->assertCount(113, $campos, 'App\Matrices\Concentracion::campos() ya no trae 113 campos.');

        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();

        foreach ($campos as $campo) {
            $this->assertStringContainsString(
                'name="' . $campo . '"',
                $html,
                "El formulario no pinta el campo {$campo}."
            );
        }
    }

    /**
     * NO se usa assertSee('disabled', false): las clases de Tailwind de este
     * proyecto incluyen "disabled:bg-gray-100", así que esa aserción se
     * cumple sola aunque no haya ni un campo bloqueado -ya pasó así en
     * Paisaje, 24 veces, con el test en verde-. Se cuenta 'disabled>', con
     * el cierre de la etiqueta pegado: esa subcadena no aparece dentro de
     * "disabled:bg-gray-100" porque ahí sigue un ':', no un '>'.
     */
    public function test_el_admin_recibe_los_113_campos_deshabilitados(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $html = $this->actingAs($admin)->get($this->url())->assertOk()->getContent();

        $this->assertSame(113, substr_count($html, 'disabled>'));
    }

    /**
     * Un valor tecleado sobrevive al repintado cuando OTRO campo dispara el
     * error de validación. Se comprueba con una expresión regular sobre la
     * etiqueta <input> concreta -no un assertSee('value="7"') suelto, que
     * encontraría un 7 en cualquier parte de una página con 113 campos-,
     * mismo cuidado que InvolucradosTest con la casilla tiene_poder.
     */
    public function test_un_valor_tecleado_sobrevive_a_un_error_de_validacion(): void
    {
        $datos = $this->todosEn(7);
        $datos['at_nat_rios_rio'] = -1;

        $this->actingAs($this->jefe)->from($this->url())->post($this->url(), $datos)
            ->assertSessionHasErrors('at_nat_rios_rio');

        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();

        preg_match('/<input[^>]*name="pt_al_hotel"[^>]*>/', $html, $etiqueta);
        $this->assertNotEmpty($etiqueta, 'No se encontró el campo pt_al_hotel en el formulario.');
        $this->assertStringContainsString('value="7"', $etiqueta[0]);
    }

    // ------------------------------------------------------------------
    // Task 5: los resultados de verdad -las dos tablas que sustituyen el
    // esqueleto de la Tarea 3-.
    // ------------------------------------------------------------------

    /**
     * Los dos bloques se pintan con sus números en el caso completo. Esto es
     * lo importante -no basta con un assertDontSee sobre el caso incompleto,
     * esa aserción de una sola cara ya se coló dos veces en esta serie (ver
     * el plan de esta matriz)-, así que aquí se afirman los NÚMEROS
     * calculados de los dos bloques a la vez: el porcentaje de un subtipo de
     * atractivos sobre el total de SU tabla (no de las dos combinadas), y el
     * Sia, el Pi y el ICT de dos sectores de planta con establecimientos.
     */
    public function test_los_resultados_pintan_atractivos_y_planta_con_sus_numeros(): void
    {
        $datos = $this->todosEn(0);

        // Manifestaciones Culturales (22 subtipos): un único subtipo con 4,
        // el resto en 0 -> 100% para ese subtipo, 0% para todos los demás.
        $datos['at_mc_arquitectura_museos'] = 4;

        // Atractivos Naturales (55 subtipos): mismo truco, con su propio
        // total aparte -mezclarlo con el de arriba daría un porcentaje sobre
        // un total que el instrumento no usa-.
        $datos['at_nat_rios_cascada'] = 6;

        // Planta: Alojamiento con Sia 4 (Pi 50, el mismo caso que fija
        // ConcentracionCalculoTest) y Restauración con Sia 6, sobre un total
        // general de 10 -> ICT 40% y 60%.
        $datos['pt_al_hotel']       = 1;
        $datos['pt_al_hostal']      = 3;
        $datos['pt_rs_restaurante'] = 6;

        $this->actingAs($this->jefe)->post($this->url(), $datos + ['accion_estado' => 'confirmado'])
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($this->jefe)->get($this->url('/resultados'))->assertOk()->getContent();

        // Atractivos: la etiqueta del subtipo con su porcentaje sobre el
        // total de su propia tabla.
        $this->assertStringContainsString('Museos', $html);
        $this->assertStringContainsString('Cascada', $html);
        $this->assertStringContainsString('100.00%', $html);

        // Planta: Sia, Pi e ICT de los dos sectores con establecimientos.
        $this->assertStringContainsString('50.00', $html);  // Pi de Alojamiento: 100/√4
        $this->assertStringContainsString('40.00%', $html); // ICT de Alojamiento: 4/10
        $this->assertStringContainsString('60.00%', $html); // ICT de Restauración: 6/10

        // La nota que avisa de que Pi baja cuando el sector crece. Un comentario
        // de la vista afirmaba que este assertSee existía, y no existía: la
        // frase estaba en una sola línea por costumbre, no por una protección
        // real, y quien la reformateara no habría roto nada. Ahora sí.
        $this->assertStringContainsString(
            'Pi baja cuando el sector tiene más establecimientos',
            $html
        );
    }

    /**
     * El caso central del diseño de esta matriz: un sector sin ningún
     * establecimiento no tiene "un Pi bajo", no tiene Pi. La pantalla lo
     * tiene que decir con palabras, no con un guion suelto que se leería
     * como cero o como error.
     */
    public function test_un_sector_vacio_de_planta_dice_que_no_aplica(): void
    {
        $datos = $this->todosEn(0);
        $datos['pt_al_hotel'] = 5; // Solo Alojamiento tiene establecimientos.

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $html = $this->actingAs($this->jefe)->get($this->url('/resultados'))->assertOk()->getContent();

        // Se aísla la fila entera del sector vacío -Guianza Turística, en 0
        // en este caso-, no una subcadena suelta: así no se confunde con la
        // fila de un sector que sí tiene establecimientos.
        preg_match_all('/<tr[^>]*>.*?<\/tr>/s', $html, $filas);
        $fila = collect($filas[0])->first(fn (string $f) => str_contains($f, 'Guianza Turística (GT)'));

        $this->assertNotNull($fila, 'No se encontró la fila del sector Guianza Turística (GT).');
        $this->assertStringContainsString('No aplica', $fila);
    }

    /**
     * Con la matriz a medias no hay tablas que pintar, solo el aviso
     * compartido. Se comprueba junto con los dos tests de arriba -que sí
     * afirman los números del caso completo-: un assertDontSee suelto en el
     * caso incompleto dejaría pasar una pantalla que no pintase nada en
     * absoluto, que es justo el fallo que ya se coló dos veces en esta
     * serie.
     */
    public function test_la_matriz_incompleta_muestra_el_aviso_y_no_las_tablas(): void
    {
        $datos = $this->todosEn(2);
        unset($datos['pt_al_hotel']); // Un único hueco basta para dejarla a medias.

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('esta matriz todavía no está completa, así que no hay resultado que calcular.')
            ->assertDontSee('Sia')
            ->assertDontSee('% sobre la tabla');
    }
}
