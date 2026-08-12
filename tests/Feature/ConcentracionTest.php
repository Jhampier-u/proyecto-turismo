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

    /**
     * El admin escribe borradores desde que se le dio permiso; lo que no puede
     * es validar. La petición no se rechaza, se degrada.
     */
    public function test_el_admin_guarda_borrador_pero_no_valida(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionConcentracion::value('estado'));
    }

    /**
     * Invertido en la Tarea 2 de permisos-y-navegación: el admin ya no es de
     * solo lectura, así que un índice en borrador le llega editable, con el
     * mismo botón "Guardar Borrador" que ve el jefe. Antes este test
     * comprobaba justo lo contrario -que el botón no aparecía-, y era lo
     * correcto mientras el admin no podía escribir; ese comportamiento
     * cambió por decisión del responsable, así que el test se invierte con
     * él en vez de borrarse.
     */
    public function test_el_admin_recibe_el_formulario_editable_estando_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $this->assertSame('borrador', EvaluacionConcentracion::value('estado'));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url())
            ->assertOk()
            ->assertSee('Guardar Borrador');
    }

    /**
     * El hermano que conserva la regla que sigue viva tras invertir el test
     * de arriba: el admin escribe borradores, pero una matriz ya validada
     * sigue siendo intocable para él -solo el jefe la reabre-. Sin este
     * test, un cambio futuro que abriera el formulario del todo pasaría
     * desapercibido.
     */
    public function test_el_admin_recibe_el_formulario_bloqueado_cuando_la_matriz_esta_validada(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        );

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url())
            ->assertOk()
            ->assertDontSee('Guardar Borrador');
    }

    /**
     * Motivo de bloqueo por ESTADO, no por rol: el equipo sí puede editar el
     * índice -solo que este ya fue validado por el jefe-.
     */
    public function test_el_equipo_ve_el_motivo_de_validacion_en_el_indice_bloqueado(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->get($this->url())
            ->assertOk()
            ->assertSee('Solo el Jefe de Zona puede reabrir o editar una matriz validada.');
    }

    /**
     * Navega de verdad a la página de resultados como admin, no solo
     * comprueba que el panel enlaza a ella: es el fallo que esta misma serie
     * ya corrigió en Paisaje, Valoración Territorial e Irritación.
     *
     * Ajustado en volver-a-la-zona: ya no hay "Volver a Zonas" que reservarle
     * al admin en esta vista -x-boton-volver recibe la zona, y con zona el
     * destino es el panel de ESA zona para los tres roles, no el listado de
     * cada uno-, y el enlace "Volver al Formulario" ya no depende del rol:
     * se muestra siempre, porque el admin también puede volver a editar un
     * borrador. Lo que sigue siendo cierto -y lo que este test tiene que
     * seguir comprobando- es que el admin llega a un sitio sensato.
     */
    public function test_el_admin_ve_los_resultados_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee(route('operativo.zona.panel', $this->zona->id), false);
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
     * Invertido: con la matriz en borrador, los 113 campos del admin llegan
     * habilitados -puede rellenarlos, igual que el jefe y el equipo-. Antes
     * comprobaba que los 113 estaban `disabled`, que era lo correcto
     * mientras el admin no podía editar nunca.
     *
     * NO se usa assertSee('disabled', false): las clases de Tailwind de este
     * proyecto incluyen "disabled:bg-gray-100", así que esa aserción se
     * cumple sola aunque no haya ni un campo bloqueado -ya pasó así en
     * Paisaje, 24 veces, con el test en verde-. Se cuenta 'disabled>', con
     * el cierre de la etiqueta pegado: esa subcadena no aparece dentro de
     * "disabled:bg-gray-100" porque ahí sigue un ':', no un '>'.
     */
    public function test_el_admin_recibe_los_113_campos_habilitados_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $html = $this->actingAs($admin)->get($this->url())->assertOk()->getContent();

        $this->assertSame(0, substr_count($html, 'disabled>'));
    }

    /**
     * El hermano que conserva la regla que sigue viva: con la matriz
     * validada, los 113 campos SÍ llegan deshabilitados para el admin, igual
     * que para el equipo -solo el jefe puede reabrir y editar-.
     */
    public function test_el_admin_recibe_los_113_campos_deshabilitados_cuando_esta_validada(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        );

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

    /**
     * Variante mayor de Concentración (ver Tarea 10 del plan): sin bloques
     * al estilo de las otras seis matrices -113 campos en dos secciones,
     * Atractivos (3 niveles: categoría => tipo => campo) y Planta (2
     * niveles: sector => campo)-, así que el índice de la barra se queda en
     * los DOS grupos de primer nivel, no en cada categoría/tipo/sector.
     */
    public function test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_atractivos_y_planta(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get($this->url())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('max-w-7xl', $html);

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento, 'No se encontró <aside>: la barra lateral no se está pintando.');

        $this->assertStringContainsString('href="#atractivos"', $fragmento);
        $this->assertStringContainsString('href="#planta"', $fragmento);
        $this->assertStringContainsString('/77', $fragmento); // 77 campos de atractivos
        $this->assertStringContainsString('/36', $fragmento); // 36 campos de planta
    }

    /**
     * El total de la barra (77/36) tiene que cuadrar con el total que ya
     * muestra el contador en vivo de cada sección -"Respondidos: X / 77" y
     * "X / 36"-, derivado de la misma estructura ($atractivos/$planta). No
     * son el mismo número por casualidad: ambos cuentan las mismas 113
     * columnas, solo que el contador en vivo las recalcula en el cliente
     * sin guardar, y la barra lateral lee lo ya guardado en el servidor.
     */
    public function test_el_total_de_la_barra_cuadra_con_el_contador_en_vivo_de_cada_seccion(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get($this->url())
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('/ 77', $html); // contador en vivo de Atractivos
        $this->assertStringContainsString('/ 36', $html); // contador en vivo de Planta

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertStringContainsString('/77', $fragmento);
        $this->assertStringContainsString('/36', $fragmento);
    }

    /**
     * Con 'at_mc_arquitectura_museos' respondido -uno de los 77 campos de
     * Atractivos-, la barra muestra 1/77 para esa sección, sin marcador de
     * completa.
     */
    public function test_la_barra_lateral_desglosa_los_respondidos_de_atractivos(): void
    {
        $evaluacion = EvaluacionConcentracion::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);
        $evaluacion->update(['at_mc_arquitectura_museos' => 3]);

        $html = $this->actingAs($this->jefe)
            ->get($this->url())
            ->assertOk()
            ->getContent();

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $atractivos = \Illuminate\Support\Str::between($fragmento, 'href="#atractivos"', '</a>');

        $this->assertStringContainsString('1/77', $atractivos);
        $this->assertStringNotContainsString('✓', $atractivos);
    }
}
