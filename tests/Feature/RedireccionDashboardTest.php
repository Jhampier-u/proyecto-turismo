<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * `/dashboard` es el reparto de después del login: no pinta nada, decide a qué
 * panel va cada rol. No tenía ninguna cobertura, y decidía comparando role_id
 * con un 1 escrito a mano.
 */
class RedireccionDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
    }

    /** Los ids salen del seeder: este test no debe saber cuáles son. */
    private function usuarioCon(string $rol): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nombre', $rol)->value('id'),
        ]);
    }

    public function test_el_admin_aterriza_en_el_panel_de_administracion(): void
    {
        $this->actingAs($this->usuarioCon('admin'))
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_el_jefe_de_zona_aterriza_en_sus_zonas(): void
    {
        $this->actingAs($this->usuarioCon('jefe_zona'))
            ->get('/dashboard')
            ->assertRedirect(route('operativo.dashboard'));
    }

    public function test_el_equipo_aterriza_en_sus_zonas(): void
    {
        $this->actingAs($this->usuarioCon('equipo'))
            ->get('/dashboard')
            ->assertRedirect(route('operativo.dashboard'));
    }

    /**
     * El reparto usa `esAdmin()`, y `esAdmin()` compara contra la constante
     * `User::ROL_ADMIN`, que vale 1. O sea que el id sigue siendo un supuesto;
     * lo que cambia es que ahora está en un solo sitio en vez de dos.
     *
     * El supuesto lo sostiene el orden de `SystemSeeder::sembrarRoles()`, que
     * inserta por nombre sin fijar ids. Nada lo comprobaba: si alguien reordena
     * ese array, el admin acaba en el panel operativo y ningún test se entera.
     * Esto lo convierte en un fallo ruidoso.
     */
    public function test_los_ids_que_siembra_el_seeder_son_los_que_asumen_las_constantes(): void
    {
        $porNombre = Role::pluck('id', 'nombre');

        $this->assertSame(User::ROL_ADMIN, $porNombre['admin'], 'El rol admin ya no es ROL_ADMIN.');
        $this->assertSame(User::ROL_JEFE, $porNombre['jefe_zona'], 'El rol jefe_zona ya no es ROL_JEFE.');
        $this->assertSame(User::ROL_EQUIPO, $porNombre['equipo'], 'El rol equipo ya no es ROL_EQUIPO.');
    }
}
