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

        $html = (string) $this->blade('<x-select-irritacion label="Congestión" name="c" :val="null" />');

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
     * La vista de resultados indexa
     * INTERPRETACIONES[$clave][$evaluacion->clasificacion_...] sin "??",
     * igual que antes indexaba los colores: mismo riesgo, así que se cubre
     * con el mismo tipo de test. todosEn(4) solo ejercita 'Moderado' en las
     * pruebas HTTP, así que sin esto un tramo sin interpretación en un bloque
     * concreto no lo detectaría nada hasta producción.
     */
    public function test_cada_bloque_tiene_interpretacion_para_los_tres_tramos(): void
    {
        foreach (array_keys(Irritacion::BLOQUES) as $clave) {
            foreach (array_keys(Irritacion::TRAMOS) as $clasificacion) {
                $this->assertArrayHasKey(
                    $clasificacion,
                    Irritacion::INTERPRETACIONES[$clave],
                    "INTERPRETACIONES['{$clave}'] no tiene texto para '{$clasificacion}'."
                );
            }
        }
    }

    /**
     * Nada ata las claves de TRAMOS a lo que devuelve clasificar(): la vista
     * de resultados indexa Irritacion::TRAMOS (a través de
     * <x-insignia-clasificacion-irritacion>) con la clasificación de cada
     * bloque sin "??", así que renombrar una clave rompería con un error de clave
     * inexistente cualquier zona 'Bajo' o 'Crítico' sin que ningún test lo
     * note: todosEn(4) —el único dato que abre esa página en el resto de la
     * suite— solo ejercita 'Moderado'.
     */
    public function test_clasificar_devuelve_siempre_una_clave_declarada_en_tramos(): void
    {
        for ($valor = Irritacion::ESCALA_MIN; $valor <= Irritacion::ESCALA_MAX; $valor++) {
            $clasificacion = Irritacion::clasificar((float) $valor);

            $this->assertArrayHasKey(
                $clasificacion,
                Irritacion::TRAMOS,
                "clasificar({$valor}) devolvió '{$clasificacion}', que no está declarado en TRAMOS."
            );
        }
    }

    /**
     * El rango de cada tramo se deriva de los umbrales en el propio
     * Irritacion::TRAMOS (ver su docblock); este test es lo que impediría que
     * la derivación y los umbrales se desincronizaran en el futuro sin que
     * nada lo note.
     */
    public function test_el_rango_declarado_concuerda_con_los_umbrales(): void
    {
        $this->assertSame(
            'menos de ' . Irritacion::UMBRAL_MODERADO,
            Irritacion::TRAMOS['Bajo']['rango']
        );
        $this->assertSame(
            'de ' . Irritacion::UMBRAL_MODERADO . ' a menos de ' . Irritacion::UMBRAL_CRITICO,
            Irritacion::TRAMOS['Moderado']['rango']
        );
        $this->assertSame(
            Irritacion::UMBRAL_CRITICO . ' o más',
            Irritacion::TRAMOS['Crítico']['rango']
        );
    }

    /**
     * ETIQUETAS y BLOQUES describen los mismos doce campos por dos caminos
     * distintos —uno por etiqueta suelta, otro agrupado en bloques— y nada lo
     * comprobaba. Las vistas hacen $etiquetas[$campo] para cada campo de cada
     * bloque sin "??": si un campo apareciera en uno y no en el otro, la
     * página revienta con un error de índice inexistente, y ningún otro test
     * lo detectaría porque todos ejercitan siempre el conjunto completo de
     * los doce.
     */
    public function test_etiquetas_y_bloques_declaran_los_mismos_campos(): void
    {
        $camposDeEtiquetas = array_keys(Irritacion::ETIQUETAS);
        $camposDeBloques   = array_merge(...array_column(Irritacion::BLOQUES, 'campos'));

        sort($camposDeEtiquetas);
        sort($camposDeBloques);

        $this->assertSame($camposDeEtiquetas, $camposDeBloques);
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

        $this->assertSame('borrador', EvaluacionIrritacion::value('estado'));
    }

    /**
     * Invertido en la Tarea 2 de permisos-y-navegación: el admin ya no es de
     * solo lectura, así que un índice en borrador le llega editable, con el
     * botón "Guardar Borrador" y los doce <select> habilitados que ve el
     * jefe. Antes este test comprobaba justo lo contrario, y era lo correcto
     * mientras el admin no podía escribir.
     *
     * select-irritacion.blade.php lleva "disabled:bg-gray-100
     * disabled:text-gray-500" en la clase de forma incondicional, así que la
     * subcadena "disabled" a secas no prueba nada -aparece igual con el
     * formulario abierto de par en par-. "disabled>" sí es inequívoco: solo
     * lo emite {{ $disabled ? 'disabled' : '' }} pegado al cierre de la
     * etiqueta. Se aíslan primero las etiquetas <select ...> una a una y
     * solo entonces se mira cuántas terminan en "disabled>".
     */
    public function test_el_admin_recibe_el_formulario_editable_estando_en_borrador(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4))
            ->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionIrritacion::value('estado'));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $respuesta = $this->actingAs($admin)->get($this->url())->assertOk();

        $respuesta->assertSee('Guardar Borrador');

        preg_match_all('/<select\b[^>]*>/s', $respuesta->getContent(), $selects);
        $selectsDeshabilitados = count(array_filter(
            $selects[0],
            fn(string $tag) => str_ends_with(rtrim($tag), 'disabled>')
        ));

        $this->assertSame(
            0,
            $selectsDeshabilitados,
            'Los doce <select> deberían llegar habilitados al admin en borrador.'
        );
    }

    /**
     * El hermano que conserva la regla que sigue viva: con la matriz
     * validada, el admin la recibe bloqueada -los doce <select>
     * deshabilitados-, igual que el equipo.
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

        $respuesta = $this->actingAs($admin)->get($this->url())->assertOk();

        $respuesta->assertDontSee('Guardar Borrador');

        preg_match_all('/<select\b[^>]*>/s', $respuesta->getContent(), $selects);
        $selectsDeshabilitados = count(array_filter(
            $selects[0],
            fn(string $tag) => str_ends_with(rtrim($tag), 'disabled>')
        ));

        $this->assertSame(
            count(Irritacion::ETIQUETAS),
            $selectsDeshabilitados,
            'Los doce <select> deberían llegar deshabilitados al admin con la matriz validada.'
        );
    }

    /**
     * Motivo de bloqueo por ESTADO, no por rol: el equipo sí puede editar la
     * matriz -solo que esta ya fue validada por el jefe-.
     */
    public function test_el_equipo_ve_el_motivo_de_validacion_en_la_matriz_bloqueada(): void
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
            ->assertSee('Solo el Jefe de Zona puede reabrir o editar una matriz validada.')
            ->assertDontSee('El administrador puede consultar esta matriz, pero no puede modificarla.');
    }

    public function test_el_formulario_muestra_los_doce_atributos(): void
    {
        $pagina = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (array_keys(Irritacion::ETIQUETAS) as $campo) {
            $pagina->assertSee("name=\"{$campo}\"", false);
        }
    }

    /**
     * Quien viene de rellenar Paisaje trae la escala al revés en la cabeza.
     * El aviso no es decoración: es lo que evita doce respuestas invertidas.
     */
    public function test_el_formulario_avisa_de_que_la_escala_es_inversa(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('cuanto más alto, peor');
    }

    /**
     * Navega de verdad a la página de resultados como admin, en vez de solo
     * comprobar que el panel enlaza a ella: es el fallo que esta rama ya
     * corrigió en Paisaje y Valoración Territorial, y esta matriz aterrizó
     * sin la prueba que lo impide.
     *
     * Ajustado en la Tarea 2 de permisos-y-navegación: "Volver a Zonas" ya no
     * es el texto que ve el admin aquí -x-boton-volver se invoca en esta
     * vista con texto="Mis Zonas" fijo-, y el enlace "Volver al Formulario"
     * ya no depende del rol: se muestra siempre, porque el admin también
     * puede volver a editar un borrador. Lo que sigue siendo cierto -y lo
     * que este test tiene que seguir comprobando- es que el botón de volver
     * del admin apunta a SU listado, no al del jefe/equipo.
     */
    public function test_el_admin_ve_los_resultados_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(4));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee(route('admin.zonas.index'), false)
            ->assertDontSee(route('operativo.dashboard'), false);
    }

    /**
     * Si el bucle que pinta la leyenda desaparece del formulario, ningún otro
     * test lo nota: ninguno busca los tres tramos ahí. Se recorre
     * Irritacion::TRAMOS en vez de escribir los tres textos a mano, para que
     * un cambio de rango o de etiqueta no desincronice esta prueba con el
     * instrumento.
     */
    public function test_el_formulario_muestra_los_tres_tramos_de_la_leyenda(): void
    {
        $pagina = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (Irritacion::TRAMOS as $clasificacion => $tramo) {
            $pagina->assertSee("{$tramo['rango']}: {$clasificacion}");
        }
    }

    public function test_los_resultados_muestran_los_dos_bloques_con_su_interpretacion(): void
    {
        $datos = $this->todosEn(1);
        foreach (Irritacion::RESIDENTES as $campo) {
            $datos[$campo] = 8;
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Bajo')
            ->assertSee('Crítico')
            ->assertSee('nivel de aceptación amplio')
            ->assertSee('estado de insatisfacción');
    }

    /** Mismo trato que las otras seis: sin resultado no se pinta un cero. */
    public function test_con_la_matriz_a_medias_no_hay_resultados(): void
    {
        $datos = $this->todosEn(5);
        unset($datos['res_seguridad']);

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('todavía no está completa')
            ->assertDontSee('0.00');
    }
}
