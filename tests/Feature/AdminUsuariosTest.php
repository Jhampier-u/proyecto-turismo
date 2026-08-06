<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
    }

    private function usuarioCon(string $rol, array $atributos = []): User
    {
        return User::factory()->create($atributos + [
            'role_id' => Role::where('nombre', $rol)->value('id'),
        ]);
    }

    /**
     * El nombre lo elige el propio usuario desde su perfil, así que no puede
     * terminar dentro de un contexto JavaScript en el panel del administrador.
     */
    public function test_el_nombre_de_usuario_no_puede_inyectar_javascript(): void
    {
        $admin = $this->usuarioCon('admin');

        $this->usuarioCon('equipo', ['name' => "'); alert(1); //"]);

        $response = $this->actingAs($admin)->get('/admin/users');

        $response->assertOk();

        // Nunca aparece sin escapar.
        $response->assertDontSee("'); alert(1); //", false);

        // Aparece escapado en un data-*, que se lee vía dataset y no se evalúa.
        $response->assertSee('data-nombre="&#039;); alert(1); //"', false);

        // Y ya no queda ningún manejador inline donde pudiera interpolarse.
        $response->assertDontSee('onsubmit=', false);
    }

    public function test_un_usuario_operativo_no_entra_al_panel_de_usuarios(): void
    {
        $this->actingAs($this->usuarioCon('equipo'))
            ->get('/admin/users')
            ->assertForbidden();
    }
}
