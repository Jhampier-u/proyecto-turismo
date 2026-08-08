<?php

namespace Tests\Feature;

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
