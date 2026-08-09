<?php

namespace Tests\Feature;

use App\Matrices\Registro;
use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaginaZonaTest extends TestCase
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

    private function url(): string
    {
        return route('operativo.zona.panel', $this->zona->id);
    }

    /**
     * Un borrador de FIT con sus 18 criterios respondidos.
     *
     * Antes bastaba con crear la fila: un borrador estaba completo por
     * construcción, porque para guardarlo había que responderlo entero. Con el
     * guardado parcial ya no, y la pista de validar solo aparece cuando de
     * verdad se puede validar. Las columnas salen del esquema para no repetir
     * aquí la lista de campos.
     */
    private function fitCompleta(): EvaluacionFit
    {
        $criterios = array_filter(
            \Illuminate\Support\Facades\Schema::getColumnListing((new EvaluacionFit())->getTable()),
            fn(string $columna) => \App\Servicios\EstadoZona::esColumnaDeCriterio($columna)
        );

        return EvaluacionFit::create(
            ['zona_id' => $this->zona->id, 'estado' => 'borrador']
            + array_fill_keys($criterios, 3)
        );
    }

    /**
     * La pareja del test de integridad del registro: si una matriz se declara
     * pero la página no la pinta, salta aquí. Es lo que faltaba cuando Paisaje
     * quedó sin enlace en el admin.
     */
    public function test_la_pagina_muestra_todas_las_entradas_del_registro(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (Registro::ENTRADAS as $clave => $entrada) {
            $respuesta->assertSee($entrada['nombre'], false);
        }
    }

    /**
     * Simétrico al test anterior pero para el admin: comprueba que la página
     * pinta la ruta 'ver' de cada entrada del registro que la declare, con
     * las seis matrices ya confirmadas. Es el punto ciego que dejó la Matriz
     * de Paisaje inalcanzable durante meses, trasladado un nivel más arriba
     * — recorre Registro::ENTRADAS para que falle solo cuando alguien añada
     * una séptima matriz y olvide probarla para el admin.
     */
    public function test_la_pagina_muestra_la_ruta_ver_de_cada_entrada_para_el_admin(): void
    {
        foreach (Registro::matrices() as $entrada) {
            $modelo = $entrada['modelo'];
            $modelo::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);
        }

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $respuesta = $this->actingAs($admin)->get($this->url())->assertOk();

        foreach (Registro::ENTRADAS as $clave => $entrada) {
            if (! isset($entrada['rutas']['ver'])) {
                continue;
            }

            $respuesta->assertSee(route($entrada['rutas']['ver'], $this->zona->id), false);
        }
    }

    /** Con Irritación, 'presion' deja de estar vacío: ya tiene título que pintar. */
    public function test_muestra_los_titulos_de_los_grupos_con_matrices(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Base territorial')
            ->assertSee('Vocación turística')
            ->assertSee('Valoración del territorio')
            ->assertSee('Presión y uso');
    }

    public function test_muestra_el_progreso_de_la_zona(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('1 de 9');
    }

    public function test_vocacion_aparece_bloqueada_y_luego_disponible(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Se desbloquea al validar');

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);
        EvaluacionFet::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertDontSee('Se desbloquea al validar')
            ->assertSee(route('operativo.vtt.final', $this->zona->id), false);
    }

    public function test_el_equipo_ve_quien_valida_en_vez_de_un_boton(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->fitCompleta();

        $this->actingAs($equipo)->get($this->url())
            ->assertOk()
            ->assertSee('avísale a ' . $this->jefe->name);
    }

    public function test_el_admin_entra_en_modo_consulta(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->get($this->url())
            ->assertOk()
            ->assertSee('Modo consulta')
            ->assertDontSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }

    public function test_un_usuario_ajeno_a_la_zona_recibe_403(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }

    public function test_la_cabecera_dice_quien_eres_en_esta_zona(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Jefe de zona')
            ->assertSee($this->jefe->name);
    }

    /**
     * Decisión del responsable del proyecto: la tabla de roles del diseño
     * prometía un «Validar» en la fila del jefe, pero no existe ruta para
     * validar desde la fila —la máquina de estados vive en el formulario de
     * cada matriz (accion_estado=confirmado). En vez de un botón que no lleva
     * a ningún sitio, el jefe recibe la misma pista textual que ya tenía el
     * equipo con avisoValidacion: «Lista para validar».
     *
     * El aviso del equipo ya empezaba con esa misma frase ("Lista para
     * validar — avísale a ..."), así que un simple assertDontSee no sirve
     * para el equipo: la frase está legítimamente en su propio aviso. Lo que
     * no debe pasar es que, además de su aviso, se le pinte la pista suelta
     * del jefe por duplicado.
     */
    public function test_el_jefe_ve_la_pista_de_validar_y_el_equipo_no(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->fitCompleta();

        $paginaJefe = $this->actingAs($this->jefe)->get($this->url())->assertOk();
        $paginaJefe->assertSee('Lista para validar');
        // El aviso del equipo nunca debe colarse en la vista del jefe.
        $paginaJefe->assertDontSee('avísale a');

        $paginaEquipo = $this->actingAs($equipo)->get($this->url())->assertOk();
        $paginaEquipo->assertSee('Lista para validar — avísale a ' . $this->jefe->name);
        $this->assertSame(
            1,
            substr_count($paginaEquipo->getContent(), 'Lista para validar'),
            'La pista suelta del jefe no debe pintarse además del aviso propio del equipo.'
        );
    }
}
