<?php

namespace Tests\Unit;

use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\Inventario;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadoZonaTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    private function filas(?User $usuario = null): array
    {
        $estado = new EstadoZona($this->zona, $usuario ?? $this->jefe);

        $filas = [];
        foreach ($estado->grupos() as $grupo) {
            foreach ($grupo['filas'] as $fila) {
                $filas[$fila->clave] = $fila;
            }
        }

        return $filas;
    }

    public function test_una_zona_recien_creada_tiene_todo_sin_empezar(): void
    {
        $filas = $this->filas();

        $this->assertSame('sin_empezar', $filas['paisaje']->estado);
        $this->assertSame('Empezar', $filas['paisaje']->accion);
        $this->assertStringContainsString('34 criterios', $filas['paisaje']->detalle);
    }

    public function test_una_matriz_en_borrador_lleva_al_formulario(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $fila = $this->filas()['fit'];

        $this->assertSame('borrador', $fila->estado);
        $this->assertSame('Continuar', $fila->accion);
        $this->assertSame(route('operativo.evaluacion_fit.edit', $this->zona->id), $fila->url);
    }

    public function test_una_matriz_validada_lleva_a_los_resultados(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $fila = $this->filas()['fit'];

        $this->assertSame('validada', $fila->estado);
        $this->assertSame('Ver', $fila->accion);
        $this->assertSame(route('operativo.evaluacion_fit.ponderacion', $this->zona->id), $fila->url);
    }

    /**
     * VttController expulsa al formulario FIT si FIT o FET no están confirmadas.
     * La fila tiene que decirlo antes, no después de pulsar.
     */
    public function test_vocacion_esta_bloqueada_hasta_que_fit_y_fet_esten_validadas(): void
    {
        $this->assertSame('bloqueada', $this->filas()['vtt']->estado);
        $this->assertNull($this->filas()['vtt']->url);

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);
        $this->assertSame('bloqueada', $this->filas()['vtt']->estado);

        EvaluacionFet::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $fila = $this->filas()['vtt'];
        $this->assertSame('validada', $fila->estado);
        $this->assertSame(route('operativo.vtt.final', $this->zona->id), $fila->url);
    }

    public function test_un_borrador_de_fit_no_desbloquea_vocacion(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);
        EvaluacionFet::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $this->assertSame('bloqueada', $this->filas()['vtt']->estado);
    }

    public function test_el_bloqueo_nombra_lo_que_falta(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $detalle = $this->filas()['vtt']->detalle;

        $this->assertStringContainsString('Factores extrínsecos (FET)', $detalle);
        $this->assertStringNotContainsString('Factores intrínsecos', $detalle);
    }

    public function test_el_progreso_cuenta_solo_matrices_validadas(): void
    {
        $estado = new EstadoZona($this->zona, $this->jefe);
        $this->assertSame(0, $estado->validadas());
        $this->assertSame(6, $estado->totalMatrices());

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);
        EvaluacionFet::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $estado = new EstadoZona($this->zona, $this->jefe);
        $this->assertSame(1, $estado->validadas());
    }

    public function test_solo_el_jefe_puede_validar(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->assertTrue($this->filas()['fit']->puedeValidar);
        $this->assertFalse($this->filas($equipo)['fit']->puedeValidar);

        $aviso = $this->filas($equipo)['fit']->avisoValidacion;
        $this->assertStringContainsString($this->jefe->name, $aviso);
    }

    public function test_el_admin_no_recibe_acciones_de_edicion(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $estado = new EstadoZona($this->zona, $admin);
        $this->assertSame('admin', $estado->papel());

        $filas = $this->filas($admin);
        $this->assertNull($filas['paisaje']->accion);
        $this->assertFalse($filas['paisaje']->puedeValidar);
    }

    private function crearInventario(): Inventario
    {
        return Inventario::factory()->create([
            'zona_id'            => $this->zona->id,
            'categoria_id'       => DB::table('categorias_recurso')->whereNotNull('parent_id')->value('id'),
            'creado_por_user_id' => $this->jefe->id,
        ]);
    }

    /**
     * El inventario es un CRUD sin flujo de validación: no tiene sentido
     * pintarlo como 'sin_empezar' o 'borrador'.
     */
    public function test_la_fila_de_inventario_no_tiene_estado_de_progreso(): void
    {
        $this->assertSame('sin_estado', $this->filas()['inventario']->estado);
    }

    public function test_el_detalle_de_inventario_pluraliza_segun_cuantos_recursos_hay(): void
    {
        $this->assertSame('0 recursos registrados', $this->filas()['inventario']->detalle);

        $this->crearInventario();
        $this->assertSame('1 recurso registrado', $this->filas()['inventario']->detalle);

        $this->crearInventario();
        $this->assertSame('2 recursos registrados', $this->filas()['inventario']->detalle);
    }

    /**
     * El admin conserva el acceso al inventario (necesita consultar los
     * recursos) pero en solo lectura: el middleware PerteneceAZona ya le
     * corta cualquier POST, así que ofrecerle 'Abrir' sería mentirle.
     */
    public function test_el_admin_recibe_ver_en_el_inventario_mientras_jefe_y_equipo_reciben_abrir(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);

        $this->assertSame('Abrir', $this->filas($this->jefe)['inventario']->accion);
        $this->assertSame('Abrir', $this->filas($equipo)['inventario']->accion);
        $this->assertSame('Ver', $this->filas($admin)['inventario']->accion);
    }

    /**
     * firma() nunca se ejercitaba con user_id puesto: es la ruta por la que
     * se añadieron las relaciones user() a EvaluacionFit/EvaluacionFet.
     */
    public function test_la_firma_muestra_el_nombre_de_quien_evaluo(): void
    {
        EvaluacionFit::create([
            'zona_id' => $this->zona->id,
            'user_id' => $this->jefe->id,
            'estado'  => 'confirmado',
        ]);

        $detalle = $this->filas()['fit']->detalle;

        $this->assertStringContainsString($this->jefe->name, $detalle);
        $this->assertStringContainsString('Validada', $detalle);
    }

    public function test_no_se_devuelven_grupos_sin_filas(): void
    {
        $grupos = (new EstadoZona($this->zona, $this->jefe))->grupos();

        // 'presion' no tiene ninguna matriz implementada todavía.
        $this->assertArrayNotHasKey('presion', $grupos);
        $this->assertArrayHasKey('vocacion', $grupos);
    }
}
