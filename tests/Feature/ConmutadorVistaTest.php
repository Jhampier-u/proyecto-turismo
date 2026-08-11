<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
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

    public function test_la_lista_del_admin_trae_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/zonas')->assertOk()->getContent();

        $this->assertStringContainsString("vista === 'lista'", $html);
        $this->assertStringContainsString("vista === 'tarjetas'", $html);
    }

    public function test_el_admin_ve_jefe_y_miembros_en_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/zonas')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, $this->jefe->name));
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
