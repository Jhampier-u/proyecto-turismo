<?php

namespace Tests\Feature;

use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPotencialidad;
use App\Models\Inventario;
use App\Models\Role;
use App\Models\User;
use App\Models\VocacionTuristicaTerritorio;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Cubre las restricciones de integridad añadidas:
 *  - unique(zona_id) en las tablas que el código trata como 1:1
 *  - nullOnDelete en las claves foráneas hacia users
 *  - casts a float en las columnas numeric/decimal (AUDITORIA.md M11)
 */
class IntegridadDatosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);
    }

    private function usuarioCon(string $rol): User
    {
        return User::factory()->create([
            'role_id' => Role::where('nombre', $rol)->value('id'),
        ]);
    }

    private function crearZona(User $jefe): Zona
    {
        return Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    /** @return array<string, string> */
    public static function tablasUnicasPorZona(): array
    {
        return [
            'evaluaciones_fit'             => ['evaluaciones_fit'],
            'evaluaciones_fet'             => ['evaluaciones_fet'],
            'evaluaciones_potencialidad'   => ['evaluaciones_potencialidad'],
            'potencialidad_campos_activos' => ['potencialidad_campos_activos'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('tablasUnicasPorZona')]
    public function test_no_se_admiten_dos_filas_para_la_misma_zona(string $tabla): void
    {
        $zona = $this->crearZona($this->usuarioCon('jefe_zona'));

        $fila = ['zona_id' => $zona->id];

        if ($tabla === 'potencialidad_campos_activos') {
            $fila['campos_activos'] = json_encode([]);
        }

        DB::table($tabla)->insert($fila);

        $this->expectException(QueryException::class);

        DB::table($tabla)->insert($fila);
    }

    public function test_borrar_un_usuario_con_actividad_no_falla_y_conserva_los_datos(): void
    {
        $jefe = $this->usuarioCon('jefe_zona');
        $zona = $this->crearZona($jefe);

        $autor = $this->usuarioCon('equipo');

        $inventario = Inventario::create([
            'zona_id'            => $zona->id,
            'categoria_id'       => DB::table('categorias_recurso')->whereNotNull('parent_id')->value('id'),
            'creado_por_user_id' => $autor->id,
            'nombre_recurso'     => 'Laguna de prueba',
        ]);

        $evaluacion = EvaluacionFit::create([
            'zona_id' => $zona->id,
            'user_id' => $autor->id,
            'estado'  => 'borrador',
        ]);

        // Antes lanzaba QueryException por las FK en RESTRICT.
        $autor->delete();

        $this->assertDatabaseMissing('users', ['id' => $autor->id]);

        // El dato de campo sobrevive; solo se pierde la autoría.
        $this->assertNull($inventario->fresh()->creado_por_user_id);
        $this->assertSame('Laguna de prueba', $inventario->fresh()->nombre_recurso);
        $this->assertNull($evaluacion->fresh()->user_id);
    }

    public function test_el_admin_recibe_un_mensaje_en_vez_de_un_error_500(): void
    {
        $admin = $this->usuarioCon('admin');
        $otro  = $this->usuarioCon('equipo');

        $this->actingAs($admin)
            ->delete("/admin/users/{$otro->id}")
            ->assertRedirect(route('admin.users.index'));

        $this->assertDatabaseMissing('users', ['id' => $otro->id]);
    }

    public function test_borrar_la_propia_cuenta_no_resucita_al_usuario(): void
    {
        $user = $this->usuarioCon('equipo');

        $this->actingAs($user)
            ->delete('/profile', ['password' => 'password'])
            ->assertRedirect('/login');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * AUDITORIA.md M11: PostgreSQL devuelve las columnas numeric/decimal como
     * string; sin un cast a float, el mismo código se comporta distinto en
     * local (SQLite) y en producción. Esto fija la declaración del cast en
     * el modelo -no depende del driver- para que quitarla por accidente
     * rompa un test en cualquier entorno, no solo en producción.
     */
    public function test_los_totales_calculados_se_castean_a_float(): void
    {
        $this->assertSame('float', (new EvaluacionFit())->getCasts()['fit'] ?? null);
        $this->assertSame('float', (new EvaluacionFet())->getCasts()['fet'] ?? null);
        $this->assertSame('float', (new EvaluacionPercepcion())->getCasts()['percepcion_total'] ?? null);
        $this->assertSame('float', (new EvaluacionPotencialidad())->getCasts()['fn_total'] ?? null);
        $this->assertSame('float', (new EvaluacionPotencialidad())->getCasts()['fx_total'] ?? null);
        $this->assertSame('float', (new VocacionTuristicaTerritorio())->getCasts()['vtt'] ?? null);
    }
}
