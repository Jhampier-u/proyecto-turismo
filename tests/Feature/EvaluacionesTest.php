<?php

namespace Tests\Feature;

use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Http\Controllers\Operativo\EvaluacionPercepcionController;
use App\Models\EvaluacionPercepcion;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Fija las fórmulas de ponderación de las matrices y el flujo borrador →
 * confirmado. Son el núcleo del sistema y no tenían ninguna cobertura.
 */
class EvaluacionesTest extends TestCase
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

    private const CAMPOS_FET = [
        'demanda_flujos', 'demanda_estadia',
        'super_institucionalidad', 'super_organizacion', 'super_planificacion',
        'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing',
    ];

    /** Rellena todos los campos con el mismo valor. */
    private function todos(array $campos, int $valor): array
    {
        return array_fill_keys($campos, $valor);
    }

    public function test_fit_con_la_puntuacion_maxima_da_el_tope_de_la_escala(): void
    {
        // Los pesos (0.30 + 0.05 + 0.20 + 0.05 + 0.20 + 0.20) suman 1.0,
        // así que todo en 3 debe dar exactamente 3.
        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fit", $this->todos(self::CAMPOS_FIT, 3))
            ->assertSessionHasNoErrors();

        $fit = EvaluacionFit::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(3.0, (float) $fit->fit, 0.0001);
        $this->assertEqualsWithDelta(0.90, (float) $fit->fit_rtt, 0.0001);
        $this->assertEqualsWithDelta(0.15, (float) $fit->fit_at, 0.0001);
        $this->assertEqualsWithDelta(0.60, (float) $fit->fit_pst, 0.0001);
    }

    public function test_fit_promedia_por_bloque_y_no_por_campo(): void
    {
        // recursos_culturales=3 y recursos_naturales=0 → media del bloque 1.5
        $datos = $this->todos(self::CAMPOS_FIT, 0);
        $datos['recursos_culturales'] = 3;

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fit", $datos)
            ->assertSessionHasNoErrors();

        $fit = EvaluacionFit::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(1.5, (float) $fit->media_rtt, 0.0001);
        $this->assertEqualsWithDelta(0.45, (float) $fit->fit_rtt, 0.0001);  // 1.5 * 0.30
        $this->assertEqualsWithDelta(0.45, (float) $fit->fit, 0.0001);
    }

    public function test_fet_pondera_demanda_superestructura_e_imagen(): void
    {
        // Pesos 0.20 + 0.40 + 0.40 = 1.0
        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fet", $this->todos(self::CAMPOS_FET, 3))
            ->assertSessionHasNoErrors();

        $fet = EvaluacionFet::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(3.0, (float) $fet->fet, 0.0001);
        $this->assertEqualsWithDelta(0.60, (float) $fet->fet_demanda, 0.0001);
        $this->assertEqualsWithDelta(1.20, (float) $fet->fet_super, 0.0001);
        $this->assertEqualsWithDelta(1.20, (float) $fet->fet_imagen, 0.0001);
    }

    public function test_el_jefe_confirma_y_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        // El equipo no puede confirmar, aunque lo envíe explícitamente.
        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionFet::where('zona_id', $this->zona->id)->value('estado'));

        // El jefe sí.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('confirmado', EvaluacionFet::where('zona_id', $this->zona->id)->value('estado'));
    }

    public function test_una_evaluacion_confirmada_queda_cerrada_para_el_equipo(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 0)
        );

        // Los valores del jefe siguen intactos.
        $this->assertEqualsWithDelta(
            3.0,
            (float) EvaluacionFet::where('zona_id', $this->zona->id)->value('fet'),
            0.0001
        );
    }

    public function test_el_mensaje_de_evaluacion_fet_cerrada_es_el_especifico_de_fet(): void
    {
        // El refactor que extrajo EvaluacionZonaController (f2514c0) dejó a
        // EvaluacionFetController heredando el mensaje genérico de la base.
        // Este test fija el texto específico de FET para que no se vuelva a
        // perder en silencio en un futuro refactor.
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 0)
        )->assertSessionHas(
            'error',
            'Esta evaluación FET ya fue validada por el Jefe. No puedes editarla.'
        );
    }

    /**
     * Las páginas de resultados construyen las gráficas con datos interpolados
     * desde Blade. Un error ahí no rompe la petición pero deja el <canvas> en
     * blanco, así que conviene al menos verificar que renderizan.
     */
    public function test_las_paginas_de_resultados_se_renderizan(): void
    {
        $zonaId = $this->zona->id;

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3)
        )->assertSessionHasNoErrors();

        foreach ([
            "/operativo/zona/{$zonaId}/evaluacion-fit/ponderacion",
            "/operativo/zona/{$zonaId}/evaluacion-fet/ponderacion",
            "/operativo/zona/{$zonaId}/evaluacion-percepcion/resultados",
            "/operativo/zona/{$zonaId}/resultado-vtt",
        ] as $url) {
            $this->actingAs($this->jefe)->get($url)->assertOk();
        }
    }

    public function test_el_vtt_se_guarda_al_confirmar_y_no_al_consultarlo(): void
    {
        $zonaId = $this->zona->id;

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'confirmado']
        );

        // Con solo FIT confirmada todavía no hay instantánea.
        $this->assertDatabaseCount('vocacion_turistica_territorio', 0);

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$zonaId}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        );

        // Al confirmar la segunda, sí. Antes hacía falta abrir la página.
        $this->assertDatabaseHas('vocacion_turistica_territorio', ['zona_id' => $zonaId]);

        // Y el admin puede consultarla sin haberla generado nadie por él.
        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->actingAs($admin)
            ->get("/admin/zona/{$zonaId}/resultado-vtt")
            ->assertOk();
    }

    public function test_un_accion_estado_invalido_se_rechaza_con_validacion(): void
    {
        // Antes llegaba a la columna enum y provocaba un error 500.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 2) + ['accion_estado' => 'no-existe']
        )->assertSessionHasErrors('accion_estado');

        $this->assertDatabaseCount('evaluaciones_fet', 0);
    }

    /** Los 16 ítems, tomados de la propia definición del controlador. */
    private function itemsPercepcion(): array
    {
        return collect(EvaluacionPercepcionController::$categorias)
            ->flatMap(fn($cat) => array_keys($cat['items']))
            ->all();
    }

    public function test_percepcion_normaliza_el_total_entre_cero_y_uno(): void
    {
        // Pesos 0.20 + 0.40 + 0.20 + 0.20 = 1.0 y normalización /3,
        // así que todo en el máximo (3) debe dar exactamente 1.0.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 3)
        )->assertSessionHasNoErrors();

        $percepcion = EvaluacionPercepcion::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(1.0, (float) $percepcion->percepcion_total, 0.0001);
        $this->assertEqualsWithDelta(0.40, (float) $percepcion->pond_pl, 0.0001);
    }

    public function test_percepcion_en_el_minimo_da_cero(): void
    {
        // El mínimo de la escala es 1, no 0: el total no puede ser 0 sino 1/3.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-percepcion",
            $this->todos($this->itemsPercepcion(), 1)
        )->assertSessionHasNoErrors();

        $total = (float) EvaluacionPercepcion::where('zona_id', $this->zona->id)->value('percepcion_total');

        $this->assertEqualsWithDelta(1 / 3, $total, 0.001);
    }
}
