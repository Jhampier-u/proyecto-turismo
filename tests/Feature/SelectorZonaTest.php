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
 * Saltar de zona sin subir a «Mis Zonas».
 *
 * El árbol tiene tres niveles y el salto que de verdad se repite es el primero:
 * estás dentro de una matriz de una zona y quieres la misma matriz de otra.
 * Antes había que subir dos niveles y volver a bajar dos.
 *
 * Es lo que sustituye al `Cmd+K` que la Fase 0 dejó aplazado: una paleta de
 * comandos resuelve saltos arbitrarios en un árbol ancho, y este no lo es —diez
 * matrices por zona y tres niveles—. Además esto funciona en móvil, donde un
 * atajo de teclado no sirve de nada.
 */
class SelectorZonaTest extends TestCase
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

        $this->jefe   = User::factory()->create(['role_id' => Role::where('nombre', 'jefe_zona')->value('id')]);
        $this->equipo = User::factory()->create(['role_id' => Role::where('nombre', 'equipo')->value('id')]);
        $this->admin  = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    public function test_el_jefe_ve_sus_zonas_en_el_selector(): void
    {
        // Desde una página DE zona, no desde la lista: es donde el selector
        // sirve para algo, y es el único sitio donde se pinta.
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="selector-zona"', $html);
        $this->assertStringContainsString(route('operativo.zona.panel', $this->zona->id), $html);
    }

    /**
     * Las zonas del equipo también salen: «mis zonas» es la unión de las dos
     * relaciones, no solo las que uno dirige. Mirando solo `zonasComoJefe`, el
     * equipo vería un selector vacío teniendo zonas asignadas.
     */
    public function test_el_equipo_ve_las_zonas_a_las_que_esta_asignado(): void
    {
        $this->zona->equipo()->attach($this->equipo->id);

        $html = $this->actingAs($this->equipo)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('id="selector-zona"', $html);
        $this->assertStringContainsString('Zona de prueba', $html);
    }

    /**
     * En «Mis Zonas» no se pinta: esa página ES la lista de zonas, así que el
     * desplegable ofrecería en pequeño lo que la pantalla ya enseña entero.
     *
     * Lo destapó `ConmutadorVistaTest`, que cuenta el enlace a la zona
     * esperando uno por maquetación y encontró tres. Tenía razón: el de más
     * era ruido, no cobertura que sobrara.
     */
    public function test_en_mis_zonas_no_se_pinta_porque_la_lista_ya_esta_a_la_vista(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="selector-zona"', $html);
    }

    /**
     * Sin zonas no se pinta nada. Un selector vacío es peor que ausente:
     * promete una navegación que no existe.
     */
    public function test_sin_zonas_asignadas_no_se_pinta_el_selector(): void
    {
        $huerfano = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);

        $html = $this->actingAs($huerfano)
            ->get(route('operativo.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="selector-zona"', $html);
    }

    /**
     * El admin no lo lleva: ya tiene la sección «Zonas» con su buscador, y un
     * desplegable con todas las zonas del sistema crece sin techo. Decidido a
     * propósito en la spec; este test es lo que impide que se cuele por
     * descuido.
     */
    public function test_el_admin_no_lleva_selector_de_zona(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('id="selector-zona"', $html);
    }
}
