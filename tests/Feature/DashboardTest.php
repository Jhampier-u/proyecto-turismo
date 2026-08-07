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
     * EstadoZona hace seis consultas en su constructor (una por matriz de
     * Registro::matrices()). El dashboard instanciaba un EstadoZona por cada
     * zona del jefe solo para leer validadas()/totalMatrices(), así que el
     * coste crecía 6 consultas por zona además de las fijas de listar zonas.
     *
     * Este test compara el conteo de consultas de /mis-zonas con 1 zona
     * contra el mismo listado con 5 zonas. Si alguien reintroduce el
     * new EstadoZona(...) dentro de un bucle, las 5 zonas dispararán ~4
     * veces más consultas que 1 zona (20 de más, 6 por cada zona extra) y
     * este test lo detecta.
     */
    public function test_el_numero_de_consultas_no_crece_con_el_numero_de_zonas(): void
    {
        $this->crearZona('Zona única');

        DB::enableQueryLog();
        $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk();
        $conUnaZona = count(DB::getQueryLog());
        DB::flushQueryLog();

        for ($i = 1; $i <= 4; $i++) {
            $this->crearZona("Zona extra {$i}");
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
}
