<?php

namespace Tests\Feature;

use App\Matrices\Concentracion;
use App\Matrices\Fet;
use App\Matrices\Fit;
use App\Matrices\Involucrados;
use App\Matrices\Irritacion;
use App\Matrices\Paisaje;
use App\Matrices\Percepcion;
use App\Matrices\Potencialidad;
use App\Matrices\Registro;
use App\Matrices\ValoracionTerritorial;
use App\Models\Involucrado;
use App\Models\PotencialidadCamposActivos;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class PermisosAdminTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $jefe;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
        $this->admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    /** Paisaje sirve de matriz representativa: escala 0/3/5 y 34 criterios. */
    private function criteriosDePaisaje(int $valor): array
    {
        return array_fill_keys(array_keys(\App\Matrices\Paisaje::todos()), $valor);
    }

    private function urlPaisaje(): string
    {
        return route('operativo.evaluacion_paisaje.update', $this->zona->id);
    }

    public function test_el_admin_guarda_un_borrador(): void
    {
        $this->actingAs($this->admin)
            ->post($this->urlPaisaje(), $this->criteriosDePaisaje(3))
            ->assertSessionHasNoErrors();

        $eval = \App\Models\EvaluacionPaisaje::firstOrFail();

        $this->assertSame('borrador', $eval->estado);
        $this->assertSame($this->admin->id, $eval->user_id);
    }

    /**
     * El admin puede escribir, pero confirmar sigue siendo del jefe. La
     * petición no se rechaza: se degrada a borrador, igual que con el equipo.
     */
    public function test_el_admin_no_puede_validar(): void
    {
        $this->actingAs($this->admin)->post(
            $this->urlPaisaje(),
            $this->criteriosDePaisaje(5) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', \App\Models\EvaluacionPaisaje::value('estado'));
    }

    public function test_el_admin_no_puede_editar_una_matriz_validada(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->urlPaisaje(),
            $this->criteriosDePaisaje(5) + ['accion_estado' => 'confirmado']
        );

        $this->assertSame('confirmado', \App\Models\EvaluacionPaisaje::value('estado'));

        $this->actingAs($this->admin)
            ->post($this->urlPaisaje(), $this->criteriosDePaisaje(0))
            ->assertSessionHas('error');

        // Ni el estado ni los datos se movieron.
        $eval = \App\Models\EvaluacionPaisaje::firstOrFail();
        $this->assertSame('confirmado', $eval->estado);
        $this->assertEqualsWithDelta(5.0, (float) $eval->paisaje_total, 0.0001);
    }

    public function test_el_admin_crea_y_borra_recursos_del_inventario(): void
    {
        // InventarioController::store() exige más campos que nombre y
        // categoria_id -y la categoría debe ser una hoja, no la raíz-, así
        // que el payload se ajusta a lo que el controlador realmente pide.
        $categoria = DB::table('categorias_recurso')->whereNotNull('parent_id')->value('id');
        $propietario = DB::table('tipos_propietario')->value('id');

        $this->actingAs($this->admin)->post(
            route('operativo.inventarios.store', $this->zona->id),
            [
                'nombre_recurso' => 'Recurso del admin',
                'categoria_id' => $categoria,
                'propietario_id' => $propietario,
                'descripcion' => 'Descripción de prueba',
                'estado_conservacion' => 'Bueno',
            ]
        )->assertSessionHasNoErrors();

        $inventario = \App\Models\Inventario::where('zona_id', $this->zona->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->delete(route('operativo.inventarios.destroy', [$this->zona->id, $inventario->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseMissing('inventarios', ['id' => $inventario->id]);
    }

    /** La única ruta de escritura del grupo que sigue cerrada para el admin. */
    public function test_el_admin_no_puede_validar_involucrados(): void
    {
        $this->actingAs($this->admin)
            ->post(route('operativo.involucrados.validar', $this->zona->id))
            ->assertForbidden();
    }

    /**
     * Lista blanca de las rutas de escritura del grupo `operativo/zona`.
     *
     * El filtro anterior («¿el nombre contiene 'validar'?») dejaba fuera
     * `evaluacion_potencialidad.reconfigurar` -que no valida nada, pero solo
     * el Jefe de Zona puede tocarla, ver Hallazgo 1- sin que nada lo avisara.
     * Una lista blanca por nombre, comprobada contra TODAS las rutas de
     * escritura reales del grupo, no tiene ese punto ciego: una ruta nueva
     * que no esté en ninguna de las dos listas hace fallar el test de
     * clasificación de abajo en vez de colarse por omisión.
     *
     * @return array{permitidas: list<string>, prohibidas: list<string>}
     */
    private function rutasDeEscrituraClasificadas(): array
    {
        return [
            // Las ocho matrices, el inventario y el CRUD de actores: el admin
            // rellena formularios y gestiona recursos/actores como el equipo.
            'permitidas' => [
                'operativo.evaluacion_fit.update',
                'operativo.evaluacion_fet.update',
                'operativo.evaluacion_potencialidad.update',
                'operativo.evaluacion_percepcion.update',
                'operativo.evaluacion_paisaje.update',
                'operativo.evaluacion_valoracion_territorial.update',
                'operativo.evaluacion_irritacion.update',
                'operativo.evaluacion_concentracion.update',
                'operativo.inventarios.store',
                'operativo.inventarios.update',
                'operativo.inventarios.destroy',
                'operativo.involucrados.store',
                'operativo.involucrados.update',
                'operativo.involucrados.destroy',
            ],
            // Validar, y la única acción de Potencialidad que no pasa por
            // update(): reconfigurar reescribe en silencio la selección de
            // campos del jefe si no se guarda igual que las demás (Hallazgo 1).
            'prohibidas' => [
                'operativo.involucrados.validar',
                'operativo.evaluacion_potencialidad.reconfigurar',
            ],
        ];
    }

    /**
     * El guardián del riesgo que abre esta tarea: al quitar la restricción del
     * middleware, cualquier ruta de escritura nueva del grupo queda permitida
     * al admin por omisión. Este test no confía en que el nombre de la ruta
     * diga lo que hace -ese fue el punto ciego que dejó pasar `reconfigurar`-:
     * recorre TODAS las rutas de escritura reales (POST/PUT/PATCH/DELETE) del
     * grupo y exige que cada una esté, a mano, en una de las dos listas.
     */
    public function test_toda_ruta_de_escritura_del_grupo_zona_esta_clasificada(): void
    {
        ['permitidas' => $permitidas, 'prohibidas' => $prohibidas] = $this->rutasDeEscrituraClasificadas();

        $rutasDeEscritura = collect(Route::getRoutes())
            ->filter(fn($r) => str_starts_with($r->getName() ?? '', 'operativo.'))
            ->filter(fn($r) => array_intersect($r->methods(), ['POST', 'PUT', 'PATCH', 'DELETE']) !== []);

        $this->assertNotEmpty(
            $rutasDeEscritura,
            'No se encontró ninguna ruta de escritura; el filtro de este test se ha quedado obsoleto.'
        );

        foreach ($rutasDeEscritura as $ruta) {
            $nombre = $ruta->getName();

            $this->assertTrue(
                in_array($nombre, $permitidas, true) || in_array($nombre, $prohibidas, true),
                "La ruta de escritura «{$nombre}» no está clasificada: decide si el admin puede "
                . 'usarla y añádela a la lista que corresponda en rutasDeEscrituraClasificadas().'
            );
        }

        // Y al revés: que ninguna de las dos listas cargue un nombre que ya
        // no existe, o el test estaría comprobando una ruta fantasma.
        $nombresReales = $rutasDeEscritura->map(fn($r) => $r->getName())->all();
        foreach ([...$permitidas, ...$prohibidas] as $nombre) {
            $this->assertContains(
                $nombre,
                $nombresReales,
                "«{$nombre}» está en la lista blanca de este test pero ya no existe como ruta de escritura."
            );
        }
    }

    /** Las ocho matrices, el inventario y el CRUD de actores aceptan la escritura del admin. */
    public function test_el_admin_puede_usar_todas_las_rutas_de_escritura_permitidas(): void
    {
        foreach ($this->instrumentosDeMatriz() as $clave => $info) {
            $this->actingAs($this->admin)
                ->post(route($info['guardar'], $this->zona->id), $info['criterios'])
                ->assertSessionHasNoErrors("La matriz «{$clave}» debería aceptar la escritura del admin.");
        }

        $categoria = DB::table('categorias_recurso')->whereNotNull('parent_id')->value('id');
        $propietario = DB::table('tipos_propietario')->value('id');
        $payloadInventario = [
            'nombre_recurso' => 'Recurso del admin',
            'categoria_id' => $categoria,
            'propietario_id' => $propietario,
            'descripcion' => 'Descripción de prueba',
            'estado_conservacion' => 'Bueno',
        ];

        $this->actingAs($this->admin)
            ->post(route('operativo.inventarios.store', $this->zona->id), $payloadInventario)
            ->assertSessionHasNoErrors();
        $inventario = \App\Models\Inventario::where('zona_id', $this->zona->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('operativo.inventarios.update', [$this->zona->id, $inventario->id]), $payloadInventario)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->delete(route('operativo.inventarios.destroy', [$this->zona->id, $inventario->id]))
            ->assertSessionHasNoErrors();

        $payloadActor = ['nombre' => 'Actor del admin'] + array_fill_keys(Involucrados::campos(), 1);

        $this->actingAs($this->admin)
            ->post(route('operativo.involucrados.store', $this->zona->id), $payloadActor)
            ->assertSessionHasNoErrors();
        $actor = Involucrado::where('zona_id', $this->zona->id)->firstOrFail();

        $this->actingAs($this->admin)
            ->put(route('operativo.involucrados.update', [$this->zona->id, $actor->id]), $payloadActor)
            ->assertSessionHasNoErrors();

        $this->actingAs($this->admin)
            ->delete(route('operativo.involucrados.destroy', [$this->zona->id, $actor->id]))
            ->assertSessionHasNoErrors();
    }

    /**
     * Las dos rutas prohibidas siguen exigiendo Jefe de Zona.
     *
     * No responden igual: `involucrados.validar` aborta con 403 (guarda
     * dedicada, ver InvolucradosController::validar()); `reconfigurar`
     * degrada como el resto de guardas de rol de este controlador -un
     * back()->with('error'), no un 403-, así que la prueba de que el admin
     * no pudo es que la configuración del jefe sigue intacta después.
     */
    public function test_el_admin_no_puede_usar_ninguna_ruta_prohibida(): void
    {
        $this->actingAs($this->admin)
            ->post(route('operativo.involucrados.validar', $this->zona->id))
            ->assertForbidden();

        $seleccionDelJefe = array_slice(array_keys(Potencialidad::todos()), 0, 3);
        PotencialidadCamposActivos::create([
            'zona_id' => $this->zona->id,
            'campos_activos' => $seleccionDelJefe,
        ]);

        $this->actingAs($this->admin)
            ->post(route('operativo.evaluacion_potencialidad.reconfigurar', $this->zona->id));

        $this->assertSame(
            $seleccionDelJefe,
            PotencialidadCamposActivos::where('zona_id', $this->zona->id)->value('campos_activos'),
            'El admin no puede reconfigurar los campos activos: solo el Jefe de Zona.'
        );
    }

    public function test_el_equipo_conserva_su_comportamiento(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)->post(
            $this->urlPaisaje(),
            $this->criteriosDePaisaje(3) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', \App\Models\EvaluacionPaisaje::value('estado'));
    }

    public function test_un_usuario_ajeno_a_la_zona_sigue_sin_entrar(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)
            ->post($this->urlPaisaje(), $this->criteriosDePaisaje(3))
            ->assertForbidden();
    }

    public function test_el_admin_ve_el_formulario_editable(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Guardar Borrador', $html);
    }

    public function test_el_admin_no_ve_el_boton_de_validar(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('accion_estado', $html);
    }

    public function test_el_jefe_si_ve_el_boton_de_validar(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee('accion_estado', false);
    }

    public function test_el_admin_ve_los_botones_del_inventario(): void
    {
        $this->actingAs($this->admin)
            ->get(route('operativo.inventarios.index', $this->zona->id))
            ->assertOk()
            ->assertSee('Agregar Recurso');
    }

    /** El único resto del antiguo readonly: a dónde vuelve cada rol. */
    public function test_el_boton_volver_lleva_a_cada_rol_a_su_listado(): void
    {
        $this->actingAs($this->admin)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee(route('admin.zonas.index'), false);

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee(route('operativo.dashboard'), false);
    }

    public function test_la_matriz_dice_quien_la_edito_por_ultima_vez(): void
    {
        $this->actingAs($this->admin)->post($this->urlPaisaje(), $this->criteriosDePaisaje(3));

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee($this->admin->name);
    }

    /**
     * Añadido a la revisión de la Tarea 1: InvolucradosController::index()
     * calculaba 'puedeEditar' con esEquipo(), que excluye al admin. Es una
     * bandera de vista -consumida solo por index.blade.php-, sin efecto sobre
     * la autorización real de escritura (bloqueoSiCerrada() ya la cubre), así
     * que este test comprueba el botón "+ Nuevo actor", no una ruta.
     */
    public function test_el_admin_ve_el_boton_de_nuevo_actor_mientras_la_lista_no_este_confirmada(): void
    {
        $this->actingAs($this->admin)
            ->get(route('operativo.involucrados.index', $this->zona->id))
            ->assertOk()
            ->assertSee('+ Nuevo actor');
    }

    /**
     * Payload de criterios que deja completo cada instrumento de tipo
     * 'matriz' de Registro::ENTRADAS, para poder validarlo con un POST del
     * jefe (mismo patrón que $this->criteriosDePaisaje(), generalizado).
     *
     * Sin 'default' en el match a propósito: si algún día se añade un
     * instrumento de tipo 'matriz' a Registro::ENTRADAS sin enseñar aquí
     * cómo rellenarlo, este test tiene que fallar con un error explícito en
     * vez de saltárselo en silencio -mismo principio que ya usa
     * RegistroMatricesTest con el propio Registro::ENTRADAS-.
     *
     * @return array<string, int|array<int, string>>
     */
    private function criteriosCompletosDe(string $clave): array
    {
        return match ($clave) {
            'paisaje'                => array_fill_keys(array_keys(Paisaje::todos()), 5),
            'fit'                    => array_fill_keys(array_keys(Fit::todos()), 3),
            'fet'                    => array_fill_keys(array_keys(Fet::todos()), 3),
            'valoracion_territorial' => array_fill_keys(array_keys(ValoracionTerritorial::todos()), 2),
            'percepcion'             => array_fill_keys(array_keys(Percepcion::todos()), 3),
            'irritacion'             => array_fill_keys(array_keys(Irritacion::todos()), 5),
            'concentracion'          => array_fill_keys(Concentracion::campos(), 1),
            // Potencialidad decide sus campos activos en el propio POST (ver
            // EvaluacionPotencialidadController::prepararDatos()): sin
            // 'campos', el jefe activaría cero criterios y la evaluación se
            // daría por completa sin haber respondido nada.
            'potencialidad'          => array_fill_keys(array_keys(Potencialidad::todos()), 2)
                + ['campos' => array_keys(Potencialidad::todos())],
        };
    }

    /**
     * Los instrumentos de tipo 'matriz' de Registro::ENTRADAS -los únicos con
     * formulario de criterios y estado borrador/confirmado-, con sus rutas de
     * editar/guardar y un payload que los deja completos.
     *
     * Recorrer este mapa en vez de escribir el caso de Paisaje a mano es lo
     * que hace que una regresión en cualquiera de los ocho formularios la
     * note esta suite: los dos tests que reemplaza este método solo cubrían
     * un formulario (Paisaje) y un rol (jefe), así que un copia-pega mal
     * hecho en cualquiera de los otros siete habría pasado desapercibido.
     *
     * @return array<string, array{editar: string, guardar: string, criterios: array}>
     */
    private function instrumentosDeMatriz(): array
    {
        $instrumentos = [];

        foreach (Registro::ENTRADAS as $clave => $entrada) {
            if ($entrada['tipo'] !== 'matriz') {
                continue;
            }

            $rutaEditar = $entrada['rutas']['editar'];

            $instrumentos[$clave] = [
                'editar' => $rutaEditar,
                // Los ocho instrumentos siguen el mismo patrón .edit/.update
                // en routes/web.php: no hace falta una segunda lista de rutas.
                'guardar' => str_replace('.edit', '.update', $rutaEditar),
                'criterios' => $this->criteriosCompletosDe($clave),
            ];
        }

        return $instrumentos;
    }

    public function test_el_jefe_ve_el_aviso_de_reapertura_en_todas_las_matrices_validadas(): void
    {
        foreach ($this->instrumentosDeMatriz() as $clave => $info) {
            $this->actingAs($this->jefe)->post(
                route($info['guardar'], $this->zona->id),
                $info['criterios'] + ['accion_estado' => 'confirmado']
            )->assertSessionHasNoErrors();

            $html = $this->actingAs($this->jefe)
                ->get(route($info['editar'], $this->zona->id))
                ->assertOk()
                ->getContent();

            $this->assertStringContainsString(
                'la devolverá a borrador',
                $html,
                "El jefe debería ver el aviso de reapertura en «{$clave}» validada."
            );
        }
    }

    public function test_el_equipo_y_el_admin_no_ven_el_aviso_en_una_matriz_validada(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        foreach ($this->instrumentosDeMatriz() as $clave => $info) {
            $this->actingAs($this->jefe)->post(
                route($info['guardar'], $this->zona->id),
                $info['criterios'] + ['accion_estado' => 'confirmado']
            )->assertSessionHasNoErrors();

            foreach (['equipo' => $equipo, 'admin' => $this->admin] as $rol => $usuario) {
                $html = $this->actingAs($usuario)
                    ->get(route($info['editar'], $this->zona->id))
                    ->assertOk()
                    ->getContent();

                $this->assertStringNotContainsString(
                    'la devolverá a borrador',
                    $html,
                    "El {$rol} no debería ver el aviso de reapertura en «{$clave}» validada: para él el formulario ya está bloqueado."
                );
            }
        }
    }

    public function test_nadie_ve_el_aviso_de_reapertura_con_una_matriz_en_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        foreach ($this->instrumentosDeMatriz() as $clave => $info) {
            $this->actingAs($this->jefe)
                ->post(route($info['guardar'], $this->zona->id), $info['criterios'])
                ->assertSessionHasNoErrors();

            foreach (['jefe' => $this->jefe, 'equipo' => $equipo, 'admin' => $this->admin] as $rol => $usuario) {
                $html = $this->actingAs($usuario)
                    ->get(route($info['editar'], $this->zona->id))
                    ->assertOk()
                    ->getContent();

                $this->assertStringNotContainsString(
                    'la devolverá a borrador',
                    $html,
                    "El {$rol} no debería ver el aviso de reapertura en «{$clave}» en borrador: guardar no la reabre porque no está validada."
                );
            }
        }
    }
}
