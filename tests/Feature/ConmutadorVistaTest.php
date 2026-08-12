<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El conmutador es Alpine puro, así que un test de servidor no puede pulsarlo.
 * Lo que sí se puede comprobar, y es lo que importa: que las dos maquetaciones
 * viajan en el HTML con los mismos datos y enlaces. Si alguien añade un botón
 * a solo una de las dos, salta.
 */
class ConmutadorVistaTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private User $admin;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
        $this->admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona conmutable',
        ]);
    }

    public function test_mis_zonas_trae_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString("vista === 'lista'", $html);
        $this->assertStringContainsString("vista === 'tarjetas'", $html);
        $this->assertStringContainsString('zonas_vista', $html);
    }

    public function test_el_enlace_a_la_zona_esta_en_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $url = route('operativo.zona.panel', $this->zona->id);

        // La comilla de cierre es a propósito: la URL del panel de zona
        // ('.../operativo/zona/{zona}') es prefijo exacto de la de
        // Inventario ('.../operativo/zona/{zona}/inventarios'). Sin la
        // comilla, substr_count también contaría cada enlace a Inventario
        // -que empieza igual- y el conteo real (2 reales + 2 de rebote) no
        // diría nada sobre si el enlace a la zona está una vez por
        // maquetación, que es lo que este test quiere afirmar.
        $this->assertSame(
            2,
            substr_count($html, $url . '"'),
            'El enlace a la zona debe aparecer una vez en cada maquetación.'
        );
    }

    public function test_las_dos_maquetaciones_de_mis_zonas_llevan_los_mismos_datos(): void
    {
        // Descripción explícita (no el texto de reserva) para que el conteo
        // demuestre que el propio dato viaja en las dos maquetaciones, no
        // solo el "Sin descripción disponible." que saldría igual si el
        // campo faltara en una de las dos.
        $this->zona->update(['descripcion' => 'Zona costera con senderos y miradores.']);
        $this->zona->refresh();

        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        // Mismo cálculo que usa el controlador, para no inventar un número
        // de progreso que luego no coincida con el que pinta la vista.
        $p = EstadoZona::progresoDe(collect([$this->zona]))[$this->zona->id];
        $progreso = "{$p['hechas']} / {$p['total']}";

        // 3, no 2, desde la Tarea 3 del plan de dashboard: una zona recién
        // creada tiene progreso pendiente, así que el panel nuevo de
        // "siguiente paso" pinta una tarjeta "Empieza por aquí" que también
        // lleva el nombre de la zona (<x-fila-matriz :zona="...">), además
        // de las dos maquetaciones de siempre. El resto de asserts de este
        // test no colisiona: el panel no pinta el lugar, la descripción ni
        // la fracción de progreso, solo el nombre.
        $this->assertSame(3, substr_count($html, $this->zona->nombre), 'El nombre debe aparecer una vez por maquetación, más una en el panel de siguiente paso.');
        $this->assertSame(2, substr_count($html, '📍 ' . $this->zona->lugar->nombre), 'El lugar debe aparecer una vez por maquetación.');
        $this->assertSame(2, substr_count($html, 'Zona costera con senderos y miradores.'), 'La descripción debe aparecer una vez por maquetación.');
        $this->assertSame(2, substr_count($html, $progreso), 'El progreso debe aparecer una vez por maquetación.');
    }

    public function test_la_lista_del_admin_trae_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/zonas')->assertOk()->getContent();

        $this->assertStringContainsString("vista === 'lista'", $html);
        $this->assertStringContainsString("vista === 'tarjetas'", $html);
    }

    public function test_el_admin_ve_jefe_y_miembros_en_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/zonas')->assertOk()->getContent();

        // e($this->jefe->name) y no el nombre en crudo: la vista lo pinta con
        // {{ }}, que escapa comillas y ampersanes. El Faker de este proyecto
        // (locale en_US, sin configurar) genera apellidos con apóstrofe
        // -"O'Kon", "O'Keefe"...-, y buscar el nombre crudo dentro del HTML
        // escapado da 0 en vez de 2 justo esas veces: no es un "flake" de
        // orden, es este mismo choque -ya documentado en el proyecto para
        // los correos de Faker- aplicado a un nombre.
        $this->assertSame(2, substr_count($html, e($this->jefe->name)));
        $this->assertSame(2, substr_count($html, 'miembros'));
    }

    public function test_inventario_conserva_su_propia_preferencia(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('operativo.inventarios.index', $this->zona->id))
            ->assertOk()
            ->assertSee('inventario_vista', false)
            ->assertDontSee('zonas_vista', false);
    }
}
