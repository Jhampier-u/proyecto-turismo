<?php

namespace Tests\Feature;

use App\Models\Inventario;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cubre el middleware PerteneceAZona y el escopado por zona de los inventarios.
 *
 * Antes de estos arreglos, cualquier usuario con rol operativo podía leer,
 * editar y borrar los datos de cualquier zona cambiando el ID en la URL.
 */
class AutorizacionZonaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles, geografía y catálogos: los necesita todo lo demás.
        $this->seed(SystemSeeder::class);
    }

    private function usuarioCon(string $rol): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nombre', $rol)->value('id'),
        ]);
    }

    private function crearZona(User $jefe, ?User $miembro = null): Zona
    {
        $zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);

        if ($miembro) {
            $zona->equipo()->attach($miembro->id);
        }

        return $zona;
    }

    private function crearInventario(Zona $zona, User $autor): Inventario
    {
        return Inventario::create([
            'zona_id'            => $zona->id,
            'categoria_id'       => DB::table('categorias_recurso')->whereNotNull('parent_id')->value('id'),
            'creado_por_user_id' => $autor->id,
            'nombre_recurso'     => 'Cascada de prueba',
            'estado_conservacion' => 'Bueno',
        ]);
    }

    public function test_un_miembro_de_equipo_no_accede_a_una_zona_ajena(): void
    {
        $estudiante = $this->usuarioCon('equipo');
        $propia     = $this->crearZona($this->usuarioCon('jefe_zona'), $estudiante);
        $ajena      = $this->crearZona($this->usuarioCon('jefe_zona'));

        $this->actingAs($estudiante)
            ->get("/operativo/zona/{$propia->id}/inventarios")
            ->assertSuccessful();

        $this->actingAs($estudiante)
            ->get("/operativo/zona/{$ajena->id}/inventarios")
            ->assertForbidden();
    }

    public function test_un_jefe_no_accede_a_una_zona_que_no_dirige(): void
    {
        $jefe  = $this->usuarioCon('jefe_zona');
        $ajena = $this->crearZona($this->usuarioCon('jefe_zona'));

        $this->actingAs($jefe)
            ->get("/operativo/zona/{$ajena->id}/evaluacion-fit")
            ->assertForbidden();
    }

    public function test_un_jefe_no_puede_confirmar_evaluaciones_de_zonas_ajenas(): void
    {
        $jefe  = $this->usuarioCon('jefe_zona');
        $ajena = $this->crearZona($this->usuarioCon('jefe_zona'));

        $this->actingAs($jefe)
            ->post("/operativo/zona/{$ajena->id}/evaluacion-fit", [
                'accion_estado' => 'confirmado',
            ])
            ->assertForbidden();
    }

    public function test_el_admin_puede_consultar_pero_no_escribir(): void
    {
        $admin = $this->usuarioCon('admin');
        $zona  = $this->crearZona($this->usuarioCon('jefe_zona'));

        $this->actingAs($admin)
            ->get("/operativo/zona/{$zona->id}/inventarios")
            ->assertSuccessful();

        $this->actingAs($admin)
            ->post("/operativo/zona/{$zona->id}/evaluacion-fit", [
                'accion_estado' => 'confirmado',
            ])
            ->assertForbidden();
    }

    public function test_no_se_puede_borrar_un_inventario_de_otra_zona(): void
    {
        $estudiante = $this->usuarioCon('equipo');
        $propia     = $this->crearZona($this->usuarioCon('jefe_zona'), $estudiante);
        $ajena      = $this->crearZona($this->usuarioCon('jefe_zona'));

        $victima = $this->crearInventario($ajena, $estudiante);

        // Pasa el middleware (pertenece a $propia) pero el inventario es de otra zona.
        $this->actingAs($estudiante)
            ->delete("/operativo/zona/{$propia->id}/inventarios/{$victima->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('inventarios', ['id' => $victima->id]);
    }

    public function test_no_se_puede_ver_un_inventario_de_otra_zona(): void
    {
        $estudiante = $this->usuarioCon('equipo');
        $propia     = $this->crearZona($this->usuarioCon('jefe_zona'), $estudiante);
        $ajena      = $this->crearZona($this->usuarioCon('jefe_zona'));

        $inventarioAjeno = $this->crearInventario($ajena, $estudiante);

        $this->actingAs($estudiante)
            ->get("/operativo/zona/{$propia->id}/inventarios/{$inventarioAjeno->id}")
            ->assertNotFound();
    }

    /**
     * EstadoZona ya le manda 'Ver' en vez de 'Abrir', pero la vista pintaba
     * los tres botones de escritura sin mirar el rol: el admin llegaba aquí
     * y los pulsaba para toparse con un 403 del middleware. Los botones
     * tienen que desaparecer, no solo fallar al pulsarlos.
     */
    public function test_el_admin_no_ve_botones_de_escritura_en_el_inventario(): void
    {
        $jefe = $this->usuarioCon('jefe_zona');
        $zona = $this->crearZona($jefe);
        $this->crearInventario($zona, $jefe);

        $admin = $this->usuarioCon('admin');

        $respuestaAdmin = $this->actingAs($admin)->get("/operativo/zona/{$zona->id}/inventarios");
        $respuestaAdmin->assertSuccessful();
        $respuestaAdmin->assertDontSee('Agregar Recurso');
        $respuestaAdmin->assertDontSee('Editar');
        $respuestaAdmin->assertDontSee('Eliminar');

        $respuestaJefe = $this->actingAs($jefe)->get("/operativo/zona/{$zona->id}/inventarios");
        $respuestaJefe->assertSee('Agregar Recurso');
        $respuestaJefe->assertSee('Editar');
        $respuestaJefe->assertSee('Eliminar');
    }

    public function test_un_usuario_sin_rol_no_entra_al_modulo_operativo(): void
    {
        $sinRol = User::factory()->create(['role_id' => null]);
        $zona   = $this->crearZona($this->usuarioCon('jefe_zona'));

        $this->actingAs($sinRol)
            ->get("/operativo/zona/{$zona->id}/inventarios")
            ->assertForbidden();
    }
}
