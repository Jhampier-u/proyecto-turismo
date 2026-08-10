<?php

namespace Tests\Feature;

use App\Matrices\Involucrados;
use App\Matrices\Paisaje;
use App\Matrices\Registro;
use App\Models\EvaluacionFit;
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

    /**
     * Los 18 criterios de FIT, para comprobar que el guardado parcial vive en
     * la clase base y no solo en Paisaje.
     */
    private const CAMPOS_FIT = [
        'recursos_culturales', 'recursos_naturales',
        'atractivos_manifestaciones', 'atractivos_sitios',
        'prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza',
        'productos_territoriales',
        'infraestructura_basica', 'infraestructura_apoyo',
        'facilidades_senaletica', 'facilidades_recepcion', 'facilidades_interpretacion',
        'facilidades_senderos', 'facilidades_estacionamientos', 'facilidades_campamentos',
        'facilidades_miradores', 'facilidades_sanitarios',
    ];

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

    /**
     * El hueco real no llega como clave ausente: un <select> vacío del
     * formulario envía la cadena vacía. Que eso acabe en null y no en 0
     * depende del middleware ConvertEmptyStringsToNull, que nadie de este
     * repositorio configura y cualquiera podría desactivar al tocar
     * bootstrap/app.php. Si se rompe, un criterio sin mirar volvería a
     * puntuar como «0» y sería exactamente el fallo que esta rama arregla,
     * así que conviene tenerlo fijado por test y no por costumbre.
     */
    public function test_un_criterio_vacio_del_formulario_es_un_hueco_no_un_cero(): void
    {
        $datos = $this->todosEn(3);
        $datos['pn_geologia'] = '';

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertNull($eval->pn_geologia);
        $this->assertNull($eval->paisaje_total);
    }

    /**
     * Las otras cuatro matrices ponderadas comparten la clase base, pero solo
     * Paisaje se ejercitaba por este camino. FIT lo recorre entero: criterio
     * en blanco, media de bloque y total sin calcular.
     */
    public function test_el_guardado_parcial_tambien_funciona_en_fit(): void
    {
        $datos = array_fill_keys(self::CAMPOS_FIT, 3);
        unset($datos['recursos_culturales']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fit", $datos)
            ->assertSessionHasNoErrors();

        $fit = EvaluacionFit::firstOrFail();

        $this->assertSame('borrador', $fit->estado);
        $this->assertNull($fit->recursos_culturales);
        $this->assertSame(3, (int) $fit->recursos_naturales);
        $this->assertNull($fit->media_rtt);
        $this->assertNull($fit->fit);
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

        // Que esté en la sesión no basta, y esta segunda mitad falta por algo:
        // ninguna vista de formulario de matriz pintaba session('success'), así
        // que el mensaje se generaba, viajaba y no lo veía nadie. Comprobado en
        // el navegador antes de añadir esto.
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('32 de 34');
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

    /**
     * Con el total en null, number_format() pintaría «0.00»: un resultado
     * inventado, indistinguible de una zona valorada con ceros de verdad.
     */
    public function test_los_resultados_avisan_si_la_matriz_esta_incompleta(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia']);
        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('todavía no está completa')
            ->assertDontSee('0.00');
    }

    /**
     * La invariante de la tarea, matriz por matriz y sin listas de campos: con
     * la evaluación creada y ningún criterio respondido, ninguna vista de
     * resultados puede pintar un número. Recorre el registro para que una
     * matriz futura quede cubierta sin acordarse de este test.
     */
    public function test_ninguna_vista_de_resultados_inventa_ceros(): void
    {
        foreach (Registro::matrices() as $entrada) {
            $entrada['modelo']::create([
                'zona_id' => $this->zona->id,
                'user_id' => $this->jefe->id,
                'estado'  => 'borrador',
            ]);

            $this->actingAs($this->jefe)
                ->get(route($entrada['rutas']['ver'], $this->zona->id))
                ->assertOk()
                ->assertDontSee('0.00')
                ->assertDontSee('0,00');
        }
    }

    /**
     * El formulario tiene que repintar la diferencia, no solo guardarla: un 0
     * que vuelve como «sin responder» se pierde en cuanto el usuario guarda
     * otra vez, y un hueco que vuelve como 0 se convierte en una puntuación que
     * nadie eligió. Es el paso que el plan dejaba en verificación manual.
     *
     * select-involucrados entra en los dos bucles como select-0-3/select-0-2/
     * select-irritacion: también empieza en 0, y es justo la matriz donde ese
     * 0 significa más —"no lo posee", una valoración explícita, no el peor
     * valor de una escala—. Antes no estaba en esta lista y ningún test lo
     * tocaba (un grep por su nombre en tests/ no devolvía nada), así que
     * quedaba fuera del contrato compartido que el resto de desplegables sí
     * cumple. A diferencia de sus hermanos necesita :etiquetas —el
     * vocabulario de la escala no es fijo, cambia por campo—, así que se le
     * pasa aparte en vez de forzarlo en la firma común de los demás.
     */
    public function test_los_desplegables_distinguen_el_hueco_del_cero(): void
    {
        // El bag de errores no existe fuera de una petición y los componentes
        // llevan @error.
        $this->withViewErrors([]);

        $etiquetas = Involucrados::etiquetasEscala('pod_poder');
        $extraDe   = fn(string $componente) => $componente === 'select-involucrados' ? ' :etiquetas="$etiquetas"' : '';

        foreach (['select-0-3', 'select-0-2', 'select-percepcion', 'select-irritacion', 'select-involucrados'] as $componente) {
            $extra = $extraDe($componente);

            $sinResponder = (string) $this->blade(
                "<x-{$componente} label=\"Criterio\" name=\"c\" :val=\"null\"{$extra} />",
                ['etiquetas' => $etiquetas]
            );
            $conValor = (string) $this->blade(
                "<x-{$componente} label=\"Criterio\" name=\"c\" :val=\"\$val\"{$extra} />",
                // Percepción no tiene 0 en su escala; su valor bajo es el 1.
                ['val' => $componente === 'select-percepcion' ? 1 : 0, 'etiquetas' => $etiquetas]
            );

            $this->assertStringContainsString(
                '<option value="" selected>', $sinResponder, "{$componente}: el hueco no se marca"
            );
            $this->assertStringNotContainsString(
                '<option value="" selected>', $conValor, "{$componente}: un valor se repinta como hueco"
            );
        }

        // El caso que de verdad importa, y solo lo tienen las escalas que
        // empiezan en 0: un cero guardado tiene que volver marcado como cero.
        foreach (['select-0-3', 'select-0-2', 'select-irritacion', 'select-involucrados'] as $componente) {
            $extra = $extraDe($componente);

            $this->assertStringContainsString(
                '<option value="0" selected>',
                (string) $this->blade("<x-{$componente} label=\"C\" name=\"c\" :val=\"0\"{$extra} />", ['etiquetas' => $etiquetas]),
                "{$componente}: un cero guardado no vuelve marcado"
            );
        }
    }

    /**
     * Rechazar la validación no puede costarle al usuario lo que acababa de
     * escribir. Confirmar con un hueco no guarda nada —la validación corta
     * antes—, así que si el formulario se repinta desde la base, las respuestas
     * de esa sesión desaparecen. Con 34 criterios en Paisaje y 156 en
     * Potencialidad, es el mismo dolor que esta rama viene a quitar.
     *
     * Encontrado probando la rama en el navegador: 33 respuestas perdidas por
     * olvidar una.
     *
     * FIT migró de <select> a <x-criterio-pildoras> en la rama
     * fit-fet-componentes: la selección ya no viaja en un `<option selected>`
     * del HTML servido, sino en el `x-data` de Alpine que lee old(), así que
     * la comprobación pasó a leer ese JSON -mismo mecanismo que
     * ValoracionTerritorialTest::estadoInicial()-. El comportamiento que
     * importa (old() gana sobre lo guardado) es el mismo; solo cambió cómo se
     * observa desde fuera.
     */
    public function test_un_error_al_validar_no_borra_lo_ya_respondido(): void
    {
        $url = "/operativo/zona/{$this->zona->id}/evaluacion-fit";

        $datos = array_fill_keys(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado'];
        unset($datos['recursos_culturales']);

        $this->actingAs($this->jefe)->from($url)->post($url, $datos)
            ->assertSessionHasErrors('recursos_culturales');

        $this->assertDatabaseCount('evaluaciones_fit', 0);

        $pagina = $this->actingAs($this->jefe)->get($url)->assertOk();

        $estado = $this->estadoInicial($pagina->getContent());

        $this->assertNull($estado['recursos_culturales'], 'el criterio que faltaba debe volver sin responder');

        unset($estado['recursos_culturales']);
        foreach ($estado as $campo => $valor) {
            $this->assertSame(3, $valor, "{$campo} debería conservar la respuesta que el usuario acababa de escribir");
        }
    }

    /**
     * Extrae el estado inicial de Alpine del HTML. Idéntico a
     * ValoracionTerritorialTest::estadoInicial(): Blade emite el x-data como
     * JSON.parse('...') con las comillas escapadas a &quot;, porque
     * Js::from() codifica dos veces.
     *
     * @return array<string, int|null> campo => calificación
     */
    private function estadoInicial(string $html): array
    {
        preg_match_all("/JSON\.parse\('(.+?)'\)/", $html, $coincidencias);

        $valores = [];

        foreach ($coincidencias[1] as $escapado) {
            $json = json_decode('"' . $escapado . '"');

            $valores += json_decode((string) $json, true) ?? [];
        }

        return $valores;
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
