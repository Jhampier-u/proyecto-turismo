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
 * x-boton-volver es el único "volver" de todo el sistema -ver el comentario
 * del propio componente-: matriz → zona → lista de zonas es una jerarquía,
 * y el prop $zona opcional es lo que la respeta. Antes de volver-a-la-zona,
 * el componente solo sabía volver a una lista (por rol), saltándose el
 * nivel de la zona; ahora, con zona, baja un solo nivel para los tres
 * roles. Este fichero fija ese contrato directamente sobre el componente,
 * en vez de depender de que cada vista que lo usa lo compruebe por su
 * cuenta.
 */
class BotonVolverTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private User $equipo;
    private User $admin;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create(['role_id' => Role::where('nombre', 'jefe_zona')->value('id')]);
        $this->admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);
        $this->equipo = User::factory()->create(['role_id' => Role::where('nombre', 'equipo')->value('id')]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
        $this->zona->equipo()->attach($this->equipo->id);
    }

    /**
     * Con zona, los tres roles vuelven al mismo sitio: el panel de esa zona.
     * Se comprueba sobre el formulario de FIT, que pasa la zona al
     * componente sin sobreescribir el destino de ningún otro modo.
     */
    public function test_con_zona_los_tres_roles_vuelven_al_panel_de_la_zona(): void
    {
        $destino = route('operativo.zona.panel', $this->zona->id);

        foreach ([$this->admin, $this->jefe, $this->equipo] as $usuario) {
            $this->actingAs($usuario)
                ->get(route('operativo.evaluacion_fit.edit', $this->zona->id))
                ->assertOk()
                ->assertSee($destino, false);
        }
    }

    /**
     * Con zona, el texto por defecto dice que vuelves a la zona, no a una
     * lista. Se comprueba sobre la Matriz de Percepción a medias -un
     * registro sin percepcion_total, no una zona sin ningún registro:
     * EvaluacionPercepcionController::ponderacion() hace firstOrFail(), así
     * que sin fila no hay página, solo 404-, que cae en la rama sinDatos de
     * la vista, sin sobreescribir $texto -a diferencia del formulario, que
     * lo fija a "Regresar"-. Es el único sitio donde se ve el texto por
     * defecto sin nada más de por medio.
     */
    public function test_con_zona_el_texto_por_defecto_dice_que_vuelves_a_la_zona(): void
    {
        \App\Models\EvaluacionPercepcion::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_percepcion.ponderacion', $this->zona->id))
            ->assertOk()
            ->assertSee('Volver a la Zona');
    }

    /**
     * Sin zona -el panel de la propia zona es lo más alto a lo que "volver"
     * llega desde dentro de operativo, así que es el único sitio donde el
     * componente se invoca sin ella- el destino sigue siendo de rol: admin a
     * su listado de zonas, el resto a Mis Zonas. panel.blade.php fija
     * texto="← Volver" ahí, así que esto se comprueba por el destino
     * (assertSee del href), no por el texto por defecto.
     */
    public function test_sin_zona_el_destino_depende_del_rol(): void
    {
        $this->actingAs($this->admin)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee(route('admin.zonas.index'), false);

        $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee(route('operativo.dashboard'), false);
    }
}
