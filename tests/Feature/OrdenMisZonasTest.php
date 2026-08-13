<?php

namespace Tests\Feature;

use App\Models\EvaluacionFit;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El orden de «Mis Zonas», que vive en el servidor y viaja en la URL.
 *
 * Se afirma sobre posiciones relativas dentro del HTML servido, no sobre el
 * orden de ninguna colección: lo que el usuario ve es la página, y una
 * colección perfectamente ordenada que la vista recorriera al revés pasaría
 * un test de colección sin despeinarse.
 *
 * Que la ordenación sea de servidor es lo que hace posible este fichero:
 * Playwright no está instalado en esta máquina, así que con Alpine no habría
 * ningún test que pudiera ver esta funcionalidad.
 */
class OrdenMisZonasTest extends TestCase
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

    private function crearZona(string $nombre, ?int $lugarId = null): Zona
    {
        return Zona::create([
            'lugar_id'     => $lugarId ?? DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => $nombre,
        ]);
    }

    /** El seeder trae un solo lugar, y ordenar por lugar necesita dos. */
    private function crearLugar(string $nombre): int
    {
        return DB::table('lugares')->insertGetId([
            'provincia_id' => DB::table('provincias')->value('id'),
            'nombre'       => $nombre,
        ]);
    }

    private function html(string $url = '/mis-zonas'): string
    {
        return $this->actingAs($this->jefe)->get($url)->assertOk()->getContent();
    }

    /**
     * El trozo de HTML de una maquetación, aislado por su id.
     *
     * Aislar no es cosmética: el panel de «siguiente paso» de arriba también
     * lleva el nombre de una zona, y sin recortar falsearía cualquier
     * comparación de posiciones.
     */
    private function maquetacion(string $html, string $id): string
    {
        $inicio = strpos($html, 'id="' . $id . '"');
        $this->assertNotFalse($inicio, "No se encontró la maquetación «{$id}».");

        $fin = $id === 'zonas-lista'
            ? strpos($html, 'id="zonas-tarjetas"')
            : strlen($html);

        return substr($html, $inicio, $fin - $inicio);
    }

    /** Todo lo que va por encima de las dos maquetaciones: el panel y la franja. */
    private function cabecera(string $html): string
    {
        return substr($html, 0, (int) strpos($html, 'id="zonas-lista"'));
    }

    /** @param  list<string>  $nombres  en el orden en que deberían salir */
    private function assertOrden(array $nombres, string $trozo, string $mensaje): void
    {
        $posiciones = [];

        foreach ($nombres as $nombre) {
            $pos = strpos($trozo, $nombre);
            $this->assertNotFalse($pos, "«{$nombre}» no aparece. {$mensaje}");
            $posiciones[] = $pos;
        }

        $esperadas = $posiciones;
        sort($esperadas);

        $this->assertSame($esperadas, $posiciones, $mensaje);
    }

    /** Tres zonas creadas al revés, para que el id no coincida con el alfabeto. */
    private function tresZonas(): void
    {
        $this->crearZona('Charlie');
        $this->crearZona('Bravo');
        $this->crearZona('Alfa');
    }

    public function test_por_defecto_las_zonas_salen_por_nombre_ascendente(): void
    {
        $this->tresZonas();

        $lista = $this->maquetacion($this->html(), 'zonas-lista');

        $this->assertOrden(
            ['Alfa', 'Bravo', 'Charlie'],
            $lista,
            'Sin parámetros, el orden es nombre ascendente y no el id de la base.'
        );
    }

    public function test_dir_desc_invierte_el_orden(): void
    {
        $this->tresZonas();

        $lista = $this->maquetacion($this->html('/mis-zonas?orden=nombre&dir=desc'), 'zonas-lista');

        $this->assertOrden(['Charlie', 'Bravo', 'Alfa'], $lista, 'dir=desc invierte.');
    }

    public function test_se_puede_ordenar_por_lugar(): void
    {
        $zamora = $this->crearLugar('Zamora');
        $azogues = $this->crearLugar('Azogues');

        $this->crearZona('Alfa', $zamora);
        $this->crearZona('Bravo', $azogues);

        $lista = $this->maquetacion($this->html('/mis-zonas?orden=lugar&dir=asc'), 'zonas-lista');

        $this->assertOrden(
            ['Bravo', 'Alfa'],
            $lista,
            'Por lugar, Azogues va antes que Zamora aunque su zona se llame Bravo.'
        );
    }

    public function test_por_progreso_descendente_va_primero_la_mas_avanzada(): void
    {
        $this->crearZona('Alfa');
        $avanzada = $this->crearZona('Bravo');

        EvaluacionFit::create([
            'zona_id' => $avanzada->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);

        $lista = $this->maquetacion($this->html('/mis-zonas?orden=progreso&dir=desc'), 'zonas-lista');

        $this->assertOrden(
            ['Bravo', 'Alfa'],
            $lista,
            'Con una matriz validada, Bravo va por delante pese a ir después en el alfabeto.'
        );
    }

    /**
     * Un `orden` que no está en la lista blanca no rompe la portada de la
     * aplicación: responde 200 y cae al orden por defecto, en silencio. Un
     * enlace viejo compartido no debería enseñar una pantalla de error.
     */
    public function test_un_orden_desconocido_cae_al_de_por_defecto_con_200(): void
    {
        $this->tresZonas();

        $lista = $this->maquetacion(
            $this->html('/mis-zonas?orden=loquesea&dir=arriba'),
            'zonas-lista'
        );

        $this->assertOrden(['Alfa', 'Bravo', 'Charlie'], $lista, 'Cae al orden por defecto.');
    }

    /** Y tampoco lo rompe un parámetro que ni siquiera es una cadena. */
    public function test_un_orden_que_es_un_array_no_rompe_la_pagina(): void
    {
        $this->tresZonas();

        $this->actingAs($this->jefe)->get('/mis-zonas?orden[]=nombre')->assertOk();
    }

    /**
     * Es la misma colección la que se ordena, así que las dos maquetaciones
     * salen igual. Sin test, eso es una casualidad de la implementación de
     * hoy.
     */
    public function test_las_dos_maquetaciones_se_ordenan_igual(): void
    {
        $this->tresZonas();

        $html = $this->html('/mis-zonas?orden=nombre&dir=desc');

        $this->assertOrden(['Charlie', 'Bravo', 'Alfa'], $this->maquetacion($html, 'zonas-lista'), 'La lista.');
        $this->assertOrden(['Charlie', 'Bravo', 'Alfa'], $this->maquetacion($html, 'zonas-tarjetas'), 'Las tarjetas.');
    }

    /**
     * El panel de «siguiente paso» no obedece al orden de la tabla.
     *
     * proximoPaso() recorre las zonas en el orden que recibe y se detiene en
     * la primera con algo pendiente. Pasarle la colección ya ordenada por la
     * URL haría que pulsar una cabecera moviera la recomendación de arriba,
     * que no es una fila de la lista sino un consejo.
     */
    public function test_el_panel_de_siguiente_paso_no_cambia_al_reordenar(): void
    {
        $this->crearZona('Bravo');
        $this->crearZona('Alfa');

        $cabecera = $this->cabecera($this->html('/mis-zonas?orden=nombre&dir=desc'));

        $this->assertStringContainsString('Alfa', $cabecera);
        $this->assertStringNotContainsString(
            'Bravo',
            $cabecera,
            'El panel señala siempre a la misma zona, ordene la tabla como ordene.'
        );
    }
}
