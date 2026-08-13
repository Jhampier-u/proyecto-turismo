<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
    }

    private function crearZona(string $nombre): Zona
    {
        return Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => $nombre,
        ]);
    }

    /**
     * Una zona con una matriz validada y otra en borrador.
     *
     * El desglose de progresoDe() tiene dos ramas -confirmado y todo lo
     * demás- y sobre una zona vacía no se recorre ninguna. Las mediciones de
     * coste que usan este helper lo hacen para contar consultas de verdad,
     * no de un camino que no se pisa.
     */
    private function crearZonaConProgreso(string $nombre): Zona
    {
        $zona = $this->crearZona($nombre);

        \App\Models\EvaluacionFit::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);
        \App\Models\EvaluacionFet::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        return $zona;
    }

    /**
     * EstadoZona hace seis consultas en su constructor (una por matriz de
     * Registro::matrices()). El dashboard instanciaba un EstadoZona por cada
     * zona del jefe solo para leer validadas()/totalMatrices(), así que el
     * coste crecía 6 consultas por zona además de las fijas de listar zonas.
     *
     * Este test compara el conteo de consultas de /mis-zonas con 1 zona
     * contra el mismo listado con 5 zonas. Si alguien reintroduce el
     * new EstadoZona(...) dentro de un bucle, las 5 zonas dispararán ~4
     * veces más consultas que 1 zona (24 de más, 6 por cada zona extra) y
     * este test lo detecta.
     *
     * Las cinco zonas llevan datos, y las dos mediciones la misma forma: una
     * zona vacía y otra con evaluaciones no recorren los mismos caminos
     * -proximoPaso() instancia un EstadoZona más cuando encuentra "última
     * tocada"-, así que comparar una contra otras cuatro mediría esa
     * diferencia y no el N+1 que este test vigila.
     */
    public function test_el_numero_de_consultas_no_crece_con_el_numero_de_zonas(): void
    {
        $this->crearZonaConProgreso('Zona única');

        DB::enableQueryLog();
        $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk();
        $conUnaZona = count(DB::getQueryLog());
        DB::flushQueryLog();

        for ($i = 1; $i <= 4; $i++) {
            $this->crearZonaConProgreso("Zona extra {$i}");
        }
        // Las inserciones de las zonas extra no son parte de la petición que
        // se mide: se descartan del log antes de la segunda medición.
        DB::flushQueryLog();

        $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk();
        $conCincoZonas = count(DB::getQueryLog());
        DB::disableQueryLog();

        // Con coste fijo, pasar de 1 a 5 zonas no debería añadir consultas
        // por el camino de EstadoZona (6 por zona). Se deja un margen de 3
        // para no acoplar el test a detalles de implementación ajenos
        // (por ejemplo una consulta extra al pintar más filas en la vista),
        // pero un N+1 de matrices dispararía +24 consultas, muy por encima.
        $this->assertLessThanOrEqual(
            $conUnaZona + 3,
            $conCincoZonas,
            "Con 1 zona hubo {$conUnaZona} consultas y con 5 zonas {$conCincoZonas}. " .
            'El número de consultas no debería crecer con el número de zonas.'
        );
    }

    /**
     * Cero zonas: el aviso de siempre, y NADA de panel nuevo -ver el punto 2
     * del plan: un panel de "continuar" vacío sería peor que no tenerlo-.
     */
    public function test_sin_zonas_no_se_pinta_el_panel_de_siguiente_paso(): void
    {
        $sinZona = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($sinZona)
            ->get('/mis-zonas')
            ->assertOk()
            ->assertDontSee('Sigue por aquí')
            ->assertDontSee('Empieza por aquí');
    }

    /**
     * Zona nueva, sin ninguna evaluación: el panel ofrece "Empieza por
     * aquí" señalando a FIT -la primera matriz declarada-, y NO ofrece
     * "Sigue por aquí" -no hay nada que continuar todavía-.
     */
    public function test_una_zona_recien_creada_ofrece_empezar_por_fit(): void
    {
        $this->crearZona('Zona nueva');

        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString('Empieza por aquí', $html);
        $this->assertStringNotContainsString('Sigue por aquí', $html);
        $this->assertStringContainsString(
            route('operativo.evaluacion_fit.edit', \App\Models\Zona::where('nombre', 'Zona nueva')->value('id')),
            $html
        );
    }

    /**
     * Con un borrador de FET guardado por el jefe, "Sigue por aquí" señala
     * a FET y "Todavía sin empezar" señala a FIT: dos tarjetas, no una.
     */
    public function test_una_matriz_tocada_que_no_es_la_primera_ofrece_las_dos_tarjetas(): void
    {
        $zona = $this->crearZona('Zona con FET a medias');

        \App\Models\EvaluacionFet::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString('Sigue por aquí', $html);
        $this->assertStringContainsString('Todavía sin empezar', $html);
    }

    /**
     * Tocar FIT y no tener nada más pendiente por delante: se fusiona en
     * una sola tarjeta -"Sigue por aquí"-, sin repetir FIT como "Todavía
     * sin empezar" justo debajo. Es la trampa concreta de las tarjetas
     * duplicadas, fijada por test.
     */
    public function test_la_matriz_tocada_y_la_siguiente_sin_terminar_no_se_repiten(): void
    {
        $zona = $this->crearZona('Zona con FIT a medias');

        \App\Models\EvaluacionFit::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString('Sigue por aquí', $html);
        $this->assertStringNotContainsString('Todavía sin empezar', $html);
        $this->assertSame(1, substr_count($html, route('operativo.evaluacion_fit.edit', $zona->id)));
    }

    /**
     * El panel muestra en qué zona está cada tarjeta -imprescindible con
     * más de una zona-, reutilizando la prop nueva de <x-fila-matriz>.
     */
    public function test_el_panel_dice_a_que_zona_pertenece_cada_tarjeta(): void
    {
        $zona = $this->crearZona('Zona identificable');

        \App\Models\EvaluacionFit::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $this->actingAs($this->jefe)
            ->get('/mis-zonas')
            ->assertOk()
            ->assertSee('Zona identificable');
    }

    /** El trozo de la franja de cifras, aislado por su id. */
    private function franja(string $html): string
    {
        $inicio = strpos($html, 'id="zonas-kpis"');
        $this->assertNotFalse($inicio, 'No se encontró la franja de cifras.');

        return substr($html, $inicio, (int) strpos($html, 'id="zonas-lista"') - $inicio);
    }

    /**
     * Con dos zonas, la franja suma: las matrices validadas de las dos sobre
     * el total de las dos. Es la cifra que hoy obliga a sumar barras a ojo.
     */
    public function test_la_franja_suma_las_cifras_de_todas_las_zonas(): void
    {
        $primera = $this->crearZona('Zona primera');
        $segunda = $this->crearZona('Zona segunda');

        \App\Models\EvaluacionFit::create([
            'zona_id' => $primera->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);
        \App\Models\EvaluacionFet::create([
            'zona_id' => $primera->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);
        \App\Models\EvaluacionFit::create([
            'zona_id' => $segunda->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);

        $franja = $this->franja($this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent());

        $this->assertStringContainsString('Zonas asignadas', $franja);
        $this->assertStringContainsString('>2</p>', $franja);
        $this->assertStringContainsString('Matrices validadas', $franja);
        $this->assertStringContainsString('>3</p>', $franja);
        $this->assertStringContainsString('de 20 en total', $franja);
    }

    /**
     * Con una sola zona no hay franja: repetiría lo que su propia tarjeta ya
     * dice, y ocupando el sitio de lo accionable.
     */
    public function test_con_una_sola_zona_no_se_pinta_la_franja(): void
    {
        $this->crearZona('Zona única');

        $this->actingAs($this->jefe)
            ->get('/mis-zonas')
            ->assertOk()
            ->assertDontSee('id="zonas-kpis"', false)
            ->assertDontSee('Zonas asignadas');
    }

    /**
     * «Terminada» es la zona cuyas diez matrices están validadas, y se cuenta
     * sobre el desglose, no sobre una insignia inventada en la tarjeta.
     */
    public function test_la_franja_cuenta_las_zonas_terminadas(): void
    {
        $terminada = $this->crearZona('Zona terminada');
        $this->crearZona('Zona a medias');

        // Mismo patrón que EstadoZonaTest::test_con_todo_validado_no_hay_siguiente,
        // y por el mismo motivo: las columnas de criterio salen del esquema
        // para no repetir aquí una lista de campos que se desincronizaría, y
        // rellenarlas evita chocar con cualquier NOT NULL de las diez tablas.
        foreach (\App\Matrices\Registro::matrices() as $entrada) {
            $modelo = $entrada['modelo'];

            $columnas = array_filter(
                \Illuminate\Support\Facades\Schema::getColumnListing((new $modelo())->getTable()),
                fn(string $columna) => \App\Servicios\EstadoZona::esColumnaDeCriterio($columna)
            );

            $modelo::create(
                ['zona_id' => $terminada->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado']
                + array_fill_keys($columnas, 3)
            );
        }

        $franja = $this->franja($this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent());

        $this->assertStringContainsString('Zonas terminadas', $franja);
        $this->assertStringContainsString('>1</p>', $franja);
    }
}
