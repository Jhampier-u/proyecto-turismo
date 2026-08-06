<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminZonasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->admin = $this->usuarioCon('admin');
    }

    private function usuarioCon(string $rol): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nombre', $rol)->value('id'),
        ]);
    }

    private function datosZona(array $extra = []): array
    {
        return array_merge([
            'nombre'       => 'Zona Nueva',
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->usuarioCon('jefe_zona')->id,
        ], $extra);
    }

    public function test_no_se_puede_nombrar_jefe_a_quien_no_tiene_ese_rol(): void
    {
        $estudiante = $this->usuarioCon('equipo');

        $this->actingAs($this->admin)
            ->post('/admin/zonas', $this->datosZona(['jefe_user_id' => $estudiante->id]))
            ->assertSessionHasErrors('jefe_user_id');

        $this->assertDatabaseCount('zonas', 0);
    }

    public function test_no_se_puede_poner_en_el_equipo_a_un_jefe(): void
    {
        $otroJefe = $this->usuarioCon('jefe_zona');

        $this->actingAs($this->admin)
            ->post('/admin/zonas', $this->datosZona(['equipo' => [$otroJefe->id]]))
            ->assertSessionHasErrors('equipo.0');
    }

    public function test_se_crea_una_zona_valida_sin_descripcion(): void
    {
        // 'descripcion' es nullable: si no viene, validate() no la incluye en el
        // array devuelto y antes se accedía a la clave inexistente.
        $this->actingAs($this->admin)
            ->post('/admin/zonas', $this->datosZona())
            ->assertRedirect(route('admin.zonas.index'));

        $this->assertDatabaseHas('zonas', ['nombre' => 'Zona Nueva', 'descripcion' => null]);
    }

    public function test_el_seeder_de_catalogos_es_idempotente(): void
    {
        $regiones = DB::table('regiones')->count();
        $provincias = DB::table('provincias')->count();
        $categorias = DB::table('categorias_recurso')->count();

        // Antes cada ejecución duplicaba toda la geografía y los catálogos.
        $this->seed(SystemSeeder::class);
        $this->seed(SystemSeeder::class);

        $this->assertSame($regiones, DB::table('regiones')->count());
        $this->assertSame($provincias, DB::table('provincias')->count());
        $this->assertSame($categorias, DB::table('categorias_recurso')->count());
    }

    public function test_el_rol_no_se_puede_asignar_en_masa(): void
    {
        $user = new User(['name' => 'Intruso', 'email' => 'x@y.test', 'role_id' => 1]);

        $this->assertNull($user->role_id);
    }

    public function test_el_admin_asigna_el_rol_al_crear_un_usuario(): void
    {
        $rolEquipo = Role::where('nombre', 'equipo')->value('id');

        $this->actingAs($this->admin)->post('/admin/users', [
            'name'                  => 'Nueva Estudiante',
            'email'                 => 'nueva@uda.edu.ec',
            'password'              => 'clave-larga-123',
            'password_confirmation' => 'clave-larga-123',
            'role_id'               => $rolEquipo,
        ])->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseHas('users', [
            'email'   => 'nueva@uda.edu.ec',
            'role_id' => $rolEquipo,
        ]);
    }

    public function test_el_registro_publico_ya_no_existe(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register', [])->assertNotFound();
    }

    public function test_la_ruta_de_bootstrap_remoto_ya_no_existe(): void
    {
        $this->get('/__bootstrap?token=loquesea')->assertNotFound();
    }
}
