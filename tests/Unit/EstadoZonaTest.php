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

    /**
     * Un borrador de FIT con sus 18 criterios respondidos.
     *
     * Antes bastaba con crear la fila: un borrador estaba completo por
     * construcción, porque para guardarlo había que responderlo entero. Con el
     * guardado parcial ya no, y «lista para validar» solo se ofrece cuando de
     * verdad se puede validar. Las columnas salen del esquema para no repetir
     * aquí una cuarta copia de la lista de campos.
     */
    private function fitCompleta(): EvaluacionFit
    {
        $criterios = array_filter(
            \Illuminate\Support\Facades\Schema::getColumnListing((new EvaluacionFit())->getTable()),
            fn(string $columna) => EstadoZona::esColumnaDeCriterio($columna)
        );

        return EvaluacionFit::create(
            ['zona_id' => $this->zona->id, 'estado' => 'borrador']
            + array_fill_keys($criterios, 3)
        );
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

    public function test_una_matriz_en_borrador_dice_cuantos_criterios_van(): void
    {
        $evaluacion = \App\Models\EvaluacionPaisaje::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        foreach (array_slice(array_keys(\App\Matrices\Paisaje::todos()), 0, 21) as $campo) {
            $evaluacion->$campo = 3;
        }
        $evaluacion->save();

        $this->assertStringContainsString('21 de 34', $this->filas()['paisaje']->detalle);
    }

    /**
     * La otra cara del guardado parcial: una matriz a medias no se puede
     * validar —el controlador la rechaza al confirmar—, así que la página no
     * debe ofrecerlo. Antes daba igual, porque para guardar un borrador había
     * que responderlo entero.
     */
    public function test_una_matriz_a_medias_no_ofrece_validacion(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        EvaluacionFit::create([
            'zona_id'             => $this->zona->id,
            'estado'              => 'borrador',
            'recursos_culturales' => 3,
        ]);

        $this->assertFalse($this->filas()['fit']->puedeValidar);
        $this->assertNull($this->filas($equipo)['fit']->avisoValidacion);
        $this->assertStringContainsString('1 de 18', $this->filas()['fit']->detalle);
    }

    /**
     * El recuento se apoya en distinguir, por el nombre de la columna, un
     * criterio del instrumento de una columna calculada. Es una heurística: los
     * nombres no siguen un patrón único entre matrices —FIT guarda `fit_rtt`,
     * `media_rtt` y `fit`; Percepción guarda `pond_ds`— y esta misma rama ya se
     * tropezó una vez con un filtro por prefijo que capturaba 0 de 16 columnas.
     *
     * Este test la ata al esquema real: para cada matriz, las columnas que el
     * filtro deja pasar tienen que ser exactamente tantas como criterios declara
     * el registro. Si una columna calculada se cuela, sobra; si se excluye un
     * criterio de verdad, falta. De paso verifica los números del registro para
     * FIT, FET, Percepción y Potencialidad, que hasta ahora solo se habían
     * comprobado a mano.
     *
     * Solo recorre entradas con 'criterios' no nulo, simétrico al filtro de
     * Registro::matrices(): una entrada de tipo 'actores' no tiene denominador
     * fijo —su 'criterios' es null— y su tabla no es una tabla de criterios
     * (lleva zona, usuario y estado, nada más), así que ni assertCount
     * (tipado int) ni Schema::getColumnListing() tendrían sentido sobre ella.
     */
    public function test_el_filtro_de_criterios_cuadra_con_el_esquema_de_las_tablas(): void
    {
        $conCriterios = array_filter(
            \App\Matrices\Registro::matrices(),
            fn(array $entrada) => $entrada['criterios'] !== null
        );

        foreach ($conCriterios as $clave => $entrada) {
            $tabla = (new $entrada['modelo']())->getTable();

            $criterios = array_filter(
                \Illuminate\Support\Facades\Schema::getColumnListing($tabla),
                fn(string $columna) => EstadoZona::esColumnaDeCriterio($columna)
            );

            $this->assertCount(
                $entrada['criterios'],
                $criterios,
                "{$clave}: el filtro deja pasar " . count($criterios) . ' columnas de '
                . "{$tabla} y el registro declara {$entrada['criterios']} criterios."
            );
        }
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
        $this->assertSame(9, $estado->totalMatrices());

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);
        EvaluacionFet::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $estado = new EstadoZona($this->zona, $this->jefe);
        $this->assertSame(1, $estado->validadas());
    }

    public function test_solo_el_jefe_puede_validar(): void
    {
        $this->fitCompleta();

        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->assertTrue($this->filas()['fit']->puedeValidar);
        $this->assertFalse($this->filas($equipo)['fit']->puedeValidar);

        $aviso = $this->filas($equipo)['fit']->avisoValidacion;
        $this->assertStringContainsString($this->jefe->name, $aviso);
    }

    /**
     * Tarea 1 invierte la decisión: el admin deja de ser un observador. Sobre
     * una matriz sin empezar recibe la misma acción "Empezar" que el jefe y
     * el equipo -antes se le negaba con la excusa de que el middleware le
     * cortaba cualquier POST, que dejó de ser cierto en esta misma rama-.
     * Lo único que sigue sin poder es validar.
     */
    public function test_el_admin_recibe_accion_de_empezar_en_una_matriz_sin_empezar(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $estado = new EstadoZona($this->zona, $admin);
        $this->assertSame('admin', $estado->papel());

        $filas = $this->filas($admin);
        $this->assertSame('Empezar', $filas['paisaje']->accion);
        $this->assertSame(
            route('operativo.evaluacion_paisaje.edit', $this->zona->id),
            $filas['paisaje']->url
        );
        $this->assertFalse($filas['paisaje']->puedeValidar);
    }

    /** El admin recibe "Continuar" y el enlace al formulario, igual que jefe y equipo. */
    public function test_el_admin_recibe_continuar_en_una_matriz_en_borrador(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $fila = $this->filas($admin)['fit'];

        $this->assertSame('Continuar', $fila->accion);
        $this->assertSame(route('operativo.evaluacion_fit.edit', $this->zona->id), $fila->url);
    }

    /**
     * Lo único que no cambia: una matriz ya validada lo sigue mandando a los
     * resultados, no a editarla. El admin no puede tocar lo que el jefe ya
     * cerró, igual que el equipo.
     */
    public function test_el_admin_no_recibe_edicion_sobre_una_matriz_validada(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $fila = $this->filas($admin)['fit'];

        $this->assertSame('Ver', $fila->accion);
        $this->assertSame(route('operativo.evaluacion_fit.ponderacion', $this->zona->id), $fila->url);
    }

    /** Mismo trato que sus hermanas de tipo 'matriz': el admin también empieza actores. */
    public function test_el_admin_recibe_accion_de_empezar_en_actores_sin_empezar(): void
    {
        $deActores = array_filter(
            \App\Matrices\Registro::ENTRADAS,
            fn(array $e) => $e['tipo'] === 'actores'
        );

        if ($deActores === []) {
            $this->markTestSkipped('No hay ninguna entrada de tipo actores.');
        }

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $clave = array_key_first($deActores);
        $fila  = $this->filas($admin)[$clave];

        $this->assertSame('Empezar', $fila->accion);
        $this->assertNotNull($fila->url);
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
     * El admin gestiona el inventario como uno más desde que el middleware
     * PerteneceAZona dejó de restringirlo a métodos seguros (Tarea 1):
     * ofrecerle 'Ver' era mentirle, porque el botón que de verdad tenía
     * disponible -crear, editar, borrar- ya funcionaba.
     */
    public function test_el_admin_recibe_abrir_en_el_inventario_igual_que_jefe_y_equipo(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);

        $this->assertSame('Abrir', $this->filas($this->jefe)['inventario']->accion);
        $this->assertSame('Abrir', $this->filas($equipo)['inventario']->accion);
        $this->assertSame('Abrir', $this->filas($admin)['inventario']->accion);
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

    /**
     * grupos() descarta cualquier fase sin matrices declaradas. Con Irritación
     * ya no queda ninguna fase vacía —las cinco de GRUPOS tienen al menos una
     * matriz—, así que la prueba pasa a comprobar que el mecanismo no excluye
     * de más: los grupos devueltos son exactamente los declarados.
     */
    public function test_no_se_devuelven_grupos_sin_filas(): void
    {
        $grupos = (new EstadoZona($this->zona, $this->jefe))->grupos();

        $this->assertSame(array_keys(\App\Matrices\Registro::GRUPOS), array_keys($grupos));
    }

    /**
     * El cuarto tipo de entrada: un CRUD con estado. `inventario` es CRUD sin
     * estado y `matriz` es estado sin lista, así que ninguno servía.
     *
     * Este test usa una entrada declarada de verdad en el registro. Si algún
     * día no hay ninguna de tipo 'actores', se salta en vez de fallar: no es
     * este test quien debe vigilar que exista.
     */
    public function test_una_entrada_de_actores_sin_empezar_lo_dice(): void
    {
        $deActores = array_filter(
            \App\Matrices\Registro::ENTRADAS,
            fn(array $e) => $e['tipo'] === 'actores'
        );

        if ($deActores === []) {
            $this->markTestSkipped('No hay ninguna entrada de tipo actores.');
        }

        $clave = array_key_first($deActores);
        $fila  = $this->filas()[$clave];

        $this->assertSame('sin_empezar', $fila->estado);
        $this->assertStringContainsString('sin actores', $fila->detalle);
    }

    /**
     * El quinto tipo de entrada: un CRUD con estado, hermano de 'actores' y
     * no una reutilización de él. Frecuentación tiene dos cosas que
     * 'actores' no contempla: la Superficie Territorial (un escalar de la
     * zona, no de cada fila) y una relación distinta a
     * $zona->involucrados(). Reutilizar 'actores' habría hecho que esta fila
     * contara actores de Involucrados, no sitios propios -filaActores() y su
     * gemela de pestanas-matriz.blade.php llaman a esa relación a mano-.
     *
     * Igual que su hermano de Involucrados, se salta si todavía no hay
     * ninguna entrada de este tipo: no es este test quien debe vigilar que
     * exista.
     */
    public function test_una_entrada_de_sitios_sin_empezar_lo_dice(): void
    {
        $deSitios = array_filter(
            \App\Matrices\Registro::ENTRADAS,
            fn(array $e) => $e['tipo'] === 'sitios'
        );

        if ($deSitios === []) {
            $this->markTestSkipped('No hay ninguna entrada de tipo sitios.');
        }

        $clave = array_key_first($deSitios);
        $fila  = $this->filas()[$clave];

        $this->assertSame('sin_empezar', $fila->estado);
        $this->assertStringContainsString('sin sitios', $fila->detalle);
    }

    public function test_el_contador_de_criterios_es_reutilizable_desde_fuera(): void
    {
        $evaluacion = \App\Models\EvaluacionPaisaje::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        $this->assertSame(0, \App\Servicios\EstadoZona::criteriosRespondidos($evaluacion));

        $campos = array_keys(\App\Matrices\Paisaje::todos());
        foreach (array_slice($campos, 0, 7) as $campo) {
            $evaluacion->$campo = 3;
        }
        $evaluacion->save();

        $this->assertSame(7, \App\Servicios\EstadoZona::criteriosRespondidos($evaluacion->fresh()));
    }
}
