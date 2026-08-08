<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminBusquedasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->admin = User::factory()->create(['role_id' => $this->rol('admin')]);
    }

    /**
     * Los ids de rol los crea el seeder y no son constantes del dominio: se
     * piden por nombre para que reordenarlo no convierta estos tests en otra
     * cosa sin que nada avise.
     */
    private function rol(string $nombre): int
    {
        return Role::where('nombre', $nombre)->value('id');
    }

    public function test_el_buscador_de_usuarios_filtra_por_nombre(): void
    {
        User::factory()->create(['name' => 'Ana Pérez', 'role_id' => $this->rol('equipo')]);
        User::factory()->create(['name' => 'Luis Gómez', 'role_id' => $this->rol('equipo')]);

        $this->actingAs($this->admin)->get('/admin/users?buscar=Ana')
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertDontSee('Luis Gómez');
    }

    public function test_el_buscador_de_usuarios_filtra_por_correo(): void
    {
        User::factory()->create([
            'name' => 'Ana Pérez', 'email' => 'ana@ejemplo.test', 'role_id' => $this->rol('equipo'),
        ]);
        User::factory()->create([
            'name' => 'Luis Gómez', 'email' => 'luis@otro.test', 'role_id' => $this->rol('equipo'),
        ]);

        $this->actingAs($this->admin)->get('/admin/users?buscar=ejemplo')
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertDontSee('Luis Gómez');
    }

    public function test_se_filtra_por_rol(): void
    {
        $idJefe = $this->rol('jefe_zona');

        User::factory()->create(['name' => 'Jefa Marta', 'role_id' => $idJefe]);
        User::factory()->create(['name' => 'Equipo Pedro', 'role_id' => $this->rol('equipo')]);

        $this->actingAs($this->admin)->get("/admin/users?rol={$idJefe}")
            ->assertOk()
            ->assertSee('Jefa Marta')
            ->assertDontSee('Equipo Pedro');
    }

    public function test_la_lista_muestra_las_zonas_de_cada_persona(): void
    {
        $jefe = User::factory()->create([
            'name'    => 'Jefa Marta',
            'role_id' => $this->rol('jefe_zona'),
        ]);

        Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $jefe->id,
            'nombre'       => 'Zona El Cajas',
        ]);

        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('Zona El Cajas');
    }

    public function test_quien_no_tiene_zonas_lo_dice(): void
    {
        User::factory()->create(['name' => 'Suelto Juan', 'role_id' => $this->rol('equipo')]);

        $this->actingAs($this->admin)->get('/admin/users?buscar=Suelto')
            ->assertOk()
            ->assertSee('Sin zonas');
    }

    /**
     * La columna de zonas se pinta por fila, así que sin carga anticipada
     * serían dos consultas por usuario listado. El fallo que este test caza no
     * es hipotético: la carga anticipada de `zonasComoJefe` con columnas
     * sueltas se dejaba fuera la clave foránea, y la relación volvía vacía sin
     * dar ningún error —se veía «Sin zonas» en todo el mundo—.
     */
    public function test_el_numero_de_consultas_no_crece_con_el_numero_de_usuarios(): void
    {
        $lugarId = DB::table('lugares')->value('id');

        $crearJefeConZona = function (int $n) use ($lugarId) {
            $jefe = User::factory()->create([
                'name'    => "Jefe {$n}",
                'role_id' => $this->rol('jefe_zona'),
            ]);

            Zona::create([
                'lugar_id'     => $lugarId,
                'jefe_user_id' => $jefe->id,
                'nombre'       => "Zona {$n}",
            ]);
        };

        $crearJefeConZona(1);

        DB::enableQueryLog();
        $this->actingAs($this->admin)->get('/admin/users')->assertOk();
        $conUno = count(DB::getQueryLog());

        for ($i = 2; $i <= 6; $i++) {
            $crearJefeConZona($i);
        }
        // Las inserciones no son parte de la petición que se mide.
        DB::flushQueryLog();

        $this->actingAs($this->admin)->get('/admin/users')->assertOk();
        $conSeis = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            $conUno + 2,
            $conSeis,
            "Con 1 jefe hubo {$conUno} consultas y con 6 {$conSeis}: la lista consulta por fila."
        );
    }

    public function test_el_buscador_de_lugares_filtra_por_nombre(): void
    {
        $this->actingAs($this->admin)->get('/admin/lugares?buscar=zzzzinexistente')
            ->assertOk()
            ->assertSee('No hay lugares que coincidan');
    }

    public function test_la_lista_de_lugares_cuenta_sus_zonas(): void
    {
        $lugarId = DB::table('lugares')->value('id');
        $nombre  = DB::table('lugares')->where('id', $lugarId)->value('nombre');

        $jefe = User::factory()->create(['role_id' => $this->rol('jefe_zona')]);

        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => $jefe->id, 'nombre' => 'Zona A']);
        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => $jefe->id, 'nombre' => 'Zona B']);

        $this->actingAs($this->admin)->get('/admin/lugares?buscar=' . urlencode($nombre))
            ->assertOk()
            ->assertSee('2 zonas');
    }
}
