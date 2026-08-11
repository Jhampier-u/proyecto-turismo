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

    /**
     * El admin escribe borradores desde que se le dio permiso; lo que no puede
     * es validar. La petición no se rechaza, se degrada -igual que con el
     * equipo-, y PermisosAdminTest cubre el resto de rutas de escritura.
     */
    public function test_el_admin_puede_consultar_y_escribir_pero_no_validar(): void
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
            ->assertSessionHasNoErrors();

        $this->assertSame(
            'borrador',
            \App\Models\EvaluacionFit::where('zona_id', $zona->id)->value('estado')
        );
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
     * Invertido en la Tarea 2: el admin ya no es de solo lectura, así que
     * tiene que ver los mismos botones de escritura que el jefe. Antes este
     * test comprobaba justo lo contrario -que el admin NO los veía-, y era
     * lo correcto mientras el admin no podía escribir; ese comportamiento
     * cambió por decisión del responsable (ver docs/sdd de esta rama), así
     * que el test tenía que invertirse con él, no borrarse.
     */
    public function test_el_admin_ve_botones_de_escritura_en_el_inventario(): void
    {
        $jefe = $this->usuarioCon('jefe_zona');
        $zona = $this->crearZona($jefe);
        $inventario = $this->crearInventario($zona, $jefe);

        $admin = $this->usuarioCon('admin');

        $respuestaAdmin = $this->actingAs($admin)->get("/operativo/zona/{$zona->id}/inventarios");
        $respuestaAdmin->assertSuccessful();
        $respuestaAdmin->assertSee('Agregar Recurso');
        $respuestaAdmin->assertSee('Editar');
        $respuestaAdmin->assertSee('Eliminar');
        $respuestaAdmin->assertSee(
            route('operativo.inventarios.show', ['zona' => $zona->id, 'inventario' => $inventario->id]),
            false
        );

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
