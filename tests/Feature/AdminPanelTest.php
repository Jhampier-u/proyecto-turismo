<?php

namespace Tests\Feature;

use App\Models\Lugar;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);
    }

    public function test_el_panel_cuenta_usuarios_por_rol(): void
    {
        User::factory()->count(2)->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
        User::factory()->count(3)->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('2 jefes de zona')
            ->assertSee('3 en equipos');
    }

    /**
     * Una zona sin jefe queda bloqueada en borrador sin explicación: solo el
     * rol jefe_zona puede validar. Es lo único del panel que exige actuar, y
     * hoy no se ve en ninguna pantalla.
     */
    public function test_el_panel_avisa_de_las_zonas_sin_jefe(): void
    {
        $jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
        $lugarId = DB::table('lugares')->value('id');

        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => $jefe->id, 'nombre' => 'Con jefe']);
        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => null,      'nombre' => 'Sin jefe']);

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('1 sin jefe asignado');
    }

    public function test_sin_zonas_huerfanas_no_sale_el_aviso(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('sin jefe asignado');
    }

    public function test_el_panel_cuenta_lugares_y_zonas(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee(Lugar::count() . ' lugares');
    }

    public function test_un_usuario_no_admin_no_entra(): void
    {
        $jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($jefe)->get('/admin/dashboard')->assertForbidden();
    }
}
