<?php

namespace Tests\Feature;

use App\Models\EvaluacionFit;
use App\Models\EvaluacionPotencialidad;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * «Reabrir una matriz validada» — docs/ESTADO-PROYECTO.md la daba como «en el
 * diseño, sin implementar». No lo estaba: la máquina de estados vive en
 * accion_estado, dentro del propio formulario, y
 * EvaluacionZonaController::update() solo bloquea a quien no sea jefe sobre
 * una matriz confirmada — nada bloquea al jefe, y el botón "Guardar Borrador"
 * ya está en la vista, sin deshabilitar para él, exactamente en ese caso.
 * (Antes de que el admin escribiera, la guarda comprobaba $user->esEquipo();
 * ver PermisosAdminTest para por qué cambió a ! $user->esJefe().)
 *
 * Este fichero no añade la funcionalidad: la fija con tests, para que quien
 * refactorice EvaluacionZonaController en el futuro no la pierda en silencio
 * -el mismo riesgo que ya se vio en el mensaje de "cerrada" de FIT/FET-.
 */
class ReabrirMatrizTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private User $equipo;
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

        $this->equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($this->equipo->id);
    }

    private const CAMPOS_FIT = [
        'recursos_culturales', 'recursos_naturales',
        'atractivos_manifestaciones', 'atractivos_sitios',
        'prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza',
        'productos_territoriales',
        'infraestructura_basica', 'infraestructura_apoyo',
        'facilidades_senaletica', 'facilidades_recepcion', 'facilidades_interpretacion',
        'facilidades_senderos', 'facilidades_estacionamientos', 'facilidades_campamentos',
        'facilidades_miradores', 'facilidades_sanitarios',
    ];

    private const CAMPOS_FET = [
        'demanda_flujos', 'demanda_estadia',
        'super_institucionalidad', 'super_organizacion', 'super_planificacion',
        'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing',
    ];

    /** Subconjunto pequeño de Potencialidad, igual que en EvaluacionesTest. */
    private const CAMPOS_POTENCIALIDAD = ['rn_agua_lagos', 'rn_agua_rios', 'i_transporte'];

    private function todos(array $campos, int $valor): array
    {
        return array_fill_keys($campos, $valor);
    }

    private function confirmarFit(array $valores): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $valores + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();
    }

    // ── 1. El jefe reabre y los valores quedan intactos ───────────────────────

    public function test_el_jefe_reabre_una_matriz_confirmada_y_conserva_sus_valores(): void
    {
        $datos = $this->todos(self::CAMPOS_FIT, 1);
        $datos['recursos_culturales'] = 3; // marca para distinguir de un reseteo

        $this->confirmarFit($datos);
        $this->assertSame('confirmado', EvaluacionFit::where('zona_id', $this->zona->id)->value('estado'));

        // Reenvío del MISMO formulario con accion_estado=borrador: es
        // exactamente lo que hace el botón "Guardar Borrador", visible y sin
        // deshabilitar para el jefe aunque la matriz esté confirmada -ver
        // $bloqueado en evaluacion_fit/form.blade.php-.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $datos + ['accion_estado' => 'borrador']
        )->assertSessionHasNoErrors();

        $fit = EvaluacionFit::where('zona_id', $this->zona->id)->first();

        $this->assertSame('borrador', $fit->estado);
        // Los valores respondidos siguen ahí: reabrir no borra nada.
        $this->assertSame(3, $fit->recursos_culturales);
        $this->assertSame(1, $fit->recursos_naturales);
        $this->assertNotNull($fit->fit);
        $this->assertEqualsWithDelta(1.30, (float) $fit->fit, 0.0001);
    }

    /** Mismo mecanismo en una matriz que no hereda MatrizPonderadaController. */
    public function test_el_jefe_reabre_potencialidad_aunque_no_use_matrizponderadacontroller(): void
    {
        $datos = ['campos' => self::CAMPOS_POTENCIALIDAD] + $this->todos(self::CAMPOS_POTENCIALIDAD, 1);
        $datos['rn_agua_lagos'] = 2;

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad",
            $datos + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame(
            'confirmado',
            EvaluacionPotencialidad::where('zona_id', $this->zona->id)->value('estado')
        );

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad",
            $datos + ['accion_estado' => 'borrador']
        )->assertSessionHasNoErrors();

        $potencialidad = EvaluacionPotencialidad::where('zona_id', $this->zona->id)->first();

        $this->assertSame('borrador', $potencialidad->estado);
        $this->assertSame(2, $potencialidad->rn_agua_lagos);
    }

    // ── 2. El equipo no puede, y el admin tampoco ─────────────────────────────

    public function test_el_equipo_no_puede_reabrir_una_matriz_confirmada(): void
    {
        $datos = $this->todos(self::CAMPOS_FIT, 3);
        $this->confirmarFit($datos);

        $this->actingAs($this->equipo)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 0) + ['accion_estado' => 'borrador']
        )->assertSessionHas('error', 'Evaluación cerrada. No puedes editar.');

        $fit = EvaluacionFit::where('zona_id', $this->zona->id)->first();

        // Sigue confirmada y con los valores del jefe, no los del equipo.
        $this->assertSame('confirmado', $fit->estado);
        $this->assertSame(3, $fit->recursos_culturales);
    }

    public function test_el_admin_no_puede_reabrir_una_matriz_confirmada(): void
    {
        $datos = $this->todos(self::CAMPOS_FIT, 3);
        $this->confirmarFit($datos);

        $admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        // El admin ni siquiera ve un formulario editable: solo lectura.
        $this->actingAs($admin)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit")
            ->assertOk()
            ->assertDontSee('Guardar Borrador');

        // Y si intenta escribir directamente: desde que el admin escribe,
        // el middleware de zona ya no lo corta -entra como cualquier rol
        // operativo-, así que quien cierra el paso es la guarda de
        // EvaluacionZonaController::update() sobre matrices confirmadas,
        // la misma que aplica al equipo.
        $this->actingAs($admin)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 0) + ['accion_estado' => 'borrador']
        )->assertSessionHas('error', 'Evaluación cerrada. No puedes editar.');

        $fit = EvaluacionFit::where('zona_id', $this->zona->id)->first();

        $this->assertSame('confirmado', $fit->estado);
        $this->assertSame(3, $fit->recursos_culturales);
    }

    // ── 3. Los dependientes: Vocación del Territorio exige FIT y FET ─────────

    /**
     * EstadoZona::filaResultado() ya comprueba en vivo el estado de FIT y FET
     * cada vez que se pinta el panel -no memoriza "disponible" en ningún
     * sitio-, y VttController::resultadoFinal() hace la misma comprobación
     * antes de mostrar nada. Reabrir FIT no necesitó ningún cambio para que
     * Vocación deje de estar disponible: ya se defendía sola.
     */
    public function test_reabrir_fit_bloquea_vocacion_del_territorio_hasta_reconfirmarla(): void
    {
        $this->confirmarFit($this->todos(self::CAMPOS_FIT, 3));
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fet",
            $this->todos(self::CAMPOS_FET, 3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        // Con las dos confirmadas, Vocación está disponible.
        $this->actingAs($this->jefe)
            ->get(route('operativo.vtt.final', $this->zona->id))
            ->assertOk();

        $filaVtt = fn() => collect(
            (new EstadoZona($this->zona->refresh(), $this->jefe))->grupos()['vocacion']['filas']
        )->firstWhere('clave', 'vtt');

        $this->assertSame('validada', $filaVtt()->estado);

        // El jefe reabre FIT con valores distintos.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 1) + ['accion_estado' => 'borrador']
        )->assertSessionHasNoErrors();

        // Cara 1: Vocación deja de estar disponible mientras FIT no esté
        // confirmada, y lo dice -no un 404 ni una tabla con datos viejos-.
        $this->actingAs($this->jefe)
            ->get(route('operativo.vtt.final', $this->zona->id))
            ->assertRedirect(route('operativo.evaluacion_fit.edit', $this->zona->id))
            ->assertSessionHas('error');

        $this->assertSame('bloqueada', $filaVtt()->estado);
        $this->assertSame('Se desbloquea al validar: Factores intrínsecos (FIT)', $filaVtt()->detalle);

        // Cara 2: al volver a confirmar FIT, Vocación se recalcula con el
        // valor nuevo -no con la instantánea vieja que quedó en la tabla
        // vocacion_turistica_territorio de la primera confirmación-.
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 1) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('validada', $filaVtt()->estado);

        $respuestaVtt = $this->actingAs($this->jefe)
            ->get(route('operativo.vtt.final', $this->zona->id))
            ->assertOk();

        // FIT=1.0 y FET=3.0 -> vtt = 1.0*0.60 + 3.0*0.40 = 1.80, no el 3.0 de
        // la primera confirmación.
        $respuestaVtt->assertViewHas('vtt', fn($vtt) => abs($vtt->vtt - 1.80) < 0.0001);
    }

    // ── 4. El aviso de bloqueo sigue diciendo la verdad tras reabrir ──────────

    public function test_el_aviso_de_bloqueo_sigue_diciendo_la_verdad_tras_reabrir(): void
    {
        $this->confirmarFit($this->todos(self::CAMPOS_FIT, 3));

        // Antes de reabrir: el equipo ve el motivo de validación, correcto
        // para una matriz confirmada.
        $this->actingAs($this->equipo)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit")
            ->assertOk()
            ->assertSee('Solo el Jefe de Zona puede reabrir o editar una evaluación validada.');

        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/evaluacion-fit",
            $this->todos(self::CAMPOS_FIT, 3) + ['accion_estado' => 'borrador']
        )->assertSessionHasNoErrors();

        // Tras reabrir, esa frase ya no es cierta -la matriz no está
        // validada- y el equipo vuelve a poder editar: no debe verse.
        $this->actingAs($this->equipo)
            ->get("/operativo/zona/{$this->zona->id}/evaluacion-fit")
            ->assertOk()
            ->assertDontSee('Solo el Jefe de Zona puede reabrir o editar una evaluación validada.')
            ->assertSee('Guardar Borrador');
    }
}
