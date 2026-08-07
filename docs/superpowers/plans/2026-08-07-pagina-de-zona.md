# Página de zona — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sustituir la tarjeta-índice del dashboard por una página propia de zona que lista todas las matrices agrupadas por fase, con una única entrada por matriz, servida a los tres roles desde la misma ruta.

**Architecture:** Un registro estático (`App\Matrices\Registro`) es la única lista de matrices del sistema. Un servicio (`App\Servicios\EstadoZona`) traduce zona + usuario a filas listas para pintar. Las vistas no tienen lógica. Añadir una matriz nueva pasa a ser una entrada en el registro.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, Alpine.js, PHPUnit 11.

## Global Constraints

- Las clases de Tailwind se escriben **completas** en mapas asociativos, nunca construidas por concatenación. El purgado elimina las construidas dinámicamente.
- Nada de texto por debajo de 14 px salvo insignias. Sin `uppercase` ni `tracking-widest` en botones.
- El color codifica **solo el estado**, nunca la identidad de la matriz. La identidad la dan icono y nombre.
- Sin botones desactivados: donde una acción no corresponde, va el texto que dice quién la hace.
- Comentarios en castellano, explicando el *por qué*, siguiendo el estilo del repositorio.
- Los tests se ejecutan con `php artisan test`. Suite completa antes de cada commit.
- No se toca ningún contenedor Docker. El entorno corre en PHP nativo.

## Estructura de ficheros

**Crear:**
- `app/Matrices/Registro.php` — lista de grupos y entradas. Sin lógica.
- `app/Servicios/FilaMatriz.php` — DTO de una fila ya resuelta.
- `app/Servicios/EstadoZona.php` — traduce zona + usuario a filas.
- `app/Http/Controllers/Operativo/ZonaPanelController.php` — una acción, `show`.
- `resources/views/operativo/zona/panel.blade.php` — la página.
- `resources/views/components/icono.blade.php` — SVG en línea por nombre.
- `resources/views/components/fila-matriz.blade.php` — pinta una `FilaMatriz`.
- `tests/Feature/RegistroMatricesTest.php`
- `tests/Unit/EstadoZonaTest.php`
- `tests/Feature/PaginaZonaTest.php`

**Modificar:**
- `routes/web.php` — añadir la ruta del panel, eliminar las cuatro del admin.
- `app/Http/Controllers/Operativo/DashboardController.php` — simplificar.
- `app/Http/Controllers/Admin/ZonaController.php` — eliminar cuatro métodos.
- `resources/views/operativo/dashboard.blade.php` — tarjeta reducida.
- `resources/views/admin/zonas/index.blade.php` — fila de tres botones.
- `tests/Feature/PaisajeTest.php`, `tests/Feature/ValoracionTerritorialTest.php`,
  `tests/Feature/EvaluacionesTest.php`, `tests/Feature/AdminZonasTest.php` — adaptar.

---

### Task 1: Registro de matrices

El registro sustituye el conocimiento repartido por vistas y controladores. El
test que lo recorre es la pieza que impide que vuelva a ocurrir lo de Paisaje:
una matriz mal enganchada rompe la suite en vez de desaparecer de la interfaz.

**Files:**
- Create: `app/Matrices/Registro.php`
- Test: `tests/Feature/RegistroMatricesTest.php`

**Interfaces:**
- Consumes: nada.
- Produces:
  - `Registro::GRUPOS` — `array<string, array{titulo: string, orden: int}>`
  - `Registro::ENTRADAS` — `array<string, array{nombre: string, icono: string, grupo: string, tipo: string, modelo: ?string, criterios: ?int, rutas: array<string, string>, depende_de: list<string>}>`
  - `Registro::deGrupo(string $grupo): array` — entradas de un grupo, conservando claves.
  - `Registro::matrices(): array` — solo las de `tipo === 'matriz'`, las que cuentan para el progreso.

- [ ] **Step 1: Escribir el test de integridad**

Crear `tests/Feature/RegistroMatricesTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Matrices\Registro;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegistroMatricesTest extends TestCase
{
    /**
     * El bug que este test existe para impedir: la ruta admin.zonas.paisaje
     * existía pero ninguna vista la enlazaba, así que la Matriz de Paisaje era
     * inalcanzable para el admin y nadie se enteró.
     */
    public function test_todas_las_rutas_declaradas_existen(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            foreach ($entrada['rutas'] as $papel => $ruta) {
                $this->assertTrue(
                    Route::has($ruta),
                    "{$clave}: la ruta '{$ruta}' ({$papel}) no está registrada."
                );
            }
        }
    }

    public function test_todos_los_modelos_declarados_existen(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            if ($entrada['modelo'] === null) {
                continue;
            }

            $this->assertTrue(
                class_exists($entrada['modelo']),
                "{$clave}: la clase {$entrada['modelo']} no existe."
            );
        }
    }

    public function test_toda_entrada_pertenece_a_un_grupo_declarado(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            $this->assertArrayHasKey(
                $entrada['grupo'],
                Registro::GRUPOS,
                "{$clave}: el grupo '{$entrada['grupo']}' no está declarado."
            );
        }
    }

    public function test_toda_dependencia_apunta_a_una_entrada_existente(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            foreach ($entrada['depende_de'] as $dependencia) {
                $this->assertArrayHasKey(
                    $dependencia,
                    Registro::ENTRADAS,
                    "{$clave}: depende de '{$dependencia}', que no existe."
                );
            }
        }
    }

    /**
     * El progreso de una zona se cuenta sobre las matrices validables.
     * Inventario no tiene estado y Vocación es un resultado derivado: si
     * entraran en el recuento, el denominador mentiría.
     */
    public function test_solo_las_matrices_validables_cuentan_para_el_progreso(): void
    {
        $this->assertCount(6, Registro::matrices());

        foreach (Registro::matrices() as $clave => $entrada) {
            $this->assertSame('matriz', $entrada['tipo'], $clave);
            $this->assertNotNull($entrada['modelo'], $clave);
        }

        $this->assertArrayNotHasKey('inventario', Registro::matrices());
        $this->assertArrayNotHasKey('vtt', Registro::matrices());
    }

    public function test_los_grupos_se_declaran_en_orden_metodologico(): void
    {
        $this->assertSame(
            ['base', 'vocacion', 'valoracion', 'social', 'presion'],
            array_keys(Registro::GRUPOS)
        );
    }

    /**
     * El número de criterios alimenta el «21 de 34 respondidos». Un número mal
     * copiado no rompe nada visible, así que se comprueba solo donde se puede:
     * Paisaje y Valoración Territorial exponen todos() y son verificables.
     *
     * FIT, FET, Percepción y Potencialidad declaran sus criterios en métodos
     * protegidos de sus controladores, sin superficie pública que consultar;
     * los suyos se verifican a mano en el Step 5 de esta tarea.
     */
    public function test_los_criterios_declarados_coinciden_con_el_instrumento(): void
    {
        $verificables = [
            'paisaje'                => \App\Matrices\Paisaje::class,
            'valoracion_territorial' => \App\Matrices\ValoracionTerritorial::class,
        ];

        foreach ($verificables as $clave => $matriz) {
            $this->assertSame(
                count($matriz::todos()),
                Registro::ENTRADAS[$clave]['criterios'],
                "{$clave}: el registro declara un número de criterios que no cuadra con {$matriz}."
            );
        }
    }
}
```

- [ ] **Step 2: Ejecutar el test y verificar que falla**

```bash
php artisan test --filter=RegistroMatricesTest
```

Esperado: FAIL — `Class "App\Matrices\Registro" not found`.

- [ ] **Step 3: Escribir el registro**

Crear `app/Matrices/Registro.php`:

```php
<?php

namespace App\Matrices;

use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
use App\Models\EvaluacionPaisaje;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPotencialidad;
use App\Models\EvaluacionValoracionTerritorial;

/**
 * Única lista de matrices del sistema.
 *
 * Antes, «qué matrices existen» estaba repartido entre el dashboard operativo,
 * la tabla del admin y las rutas, sin nada que lo comprobara. El resultado fue
 * que la Matriz de Paisaje quedó sin enlace en el admin durante meses.
 *
 * Añadir una matriz nueva es una entrada aquí. RegistroMatricesTest recorre
 * este array y falla si algo no encaja.
 */
final class Registro
{
    /**
     * Fases del estudio, en el orden en que se recorren.
     *
     * Los grupos 'social' y 'presion' ya están declarados aunque les falten
     * matrices: sus entradas llegan cuando se implementen Involucrados,
     * Irritación, Concentración y Frecuentación.
     */
    public const GRUPOS = [
        'base'       => ['titulo' => 'Base territorial',        'orden' => 1],
        'vocacion'   => ['titulo' => 'Vocación turística',      'orden' => 2],
        'valoracion' => ['titulo' => 'Valoración del territorio','orden' => 3],
        'social'     => ['titulo' => 'Dimensión social',        'orden' => 4],
        'presion'    => ['titulo' => 'Presión y uso',           'orden' => 5],
    ];

    /**
     * tipo:
     *   'matriz'    — tiene estado borrador/confirmado y cuenta para el progreso
     *   'inventario'— CRUD de recursos, sin estado
     *   'resultado' — derivado de otras entradas, no se rellena
     */
    public const ENTRADAS = [
        'inventario' => [
            'nombre'     => 'Inventario de recursos',
            'icono'      => 'lista',
            'grupo'      => 'base',
            'tipo'       => 'inventario',
            'modelo'     => null,
            'criterios'  => null,
            'rutas'      => ['editar' => 'operativo.inventarios.index'],
            'depende_de' => [],
        ],

        'fit' => [
            'nombre'     => 'Factores intrínsecos (FIT)',
            'icono'      => 'flecha-abajo',
            'grupo'      => 'vocacion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionFit::class,
            'criterios'  => 18,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_fit.edit',
                'ver'    => 'operativo.evaluacion_fit.ponderacion',
            ],
            'depende_de' => [],
        ],

        'fet' => [
            'nombre'     => 'Factores extrínsecos (FET)',
            'icono'      => 'flecha-arriba',
            'grupo'      => 'vocacion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionFet::class,
            'criterios'  => 9,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_fet.edit',
                'ver'    => 'operativo.evaluacion_fet.ponderacion',
            ],
            'depende_de' => [],
        ],

        'vtt' => [
            'nombre'     => 'Vocación del territorio',
            'icono'      => 'diana',
            'grupo'      => 'vocacion',
            'tipo'       => 'resultado',
            'modelo'     => null,
            'criterios'  => null,
            'rutas'      => ['ver' => 'operativo.vtt.final'],
            'depende_de' => ['fit', 'fet'],
        ],

        'potencialidad' => [
            'nombre'     => 'Potencialidad turística',
            'icono'      => 'estrella',
            'grupo'      => 'valoracion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionPotencialidad::class,
            'criterios'  => 156,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_potencialidad.edit',
                'ver'    => 'operativo.evaluacion_potencialidad.ponderacion',
            ],
            'depende_de' => [],
        ],

        'paisaje' => [
            'nombre'     => 'Paisaje',
            'icono'      => 'montana',
            'grupo'      => 'valoracion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionPaisaje::class,
            'criterios'  => 34,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_paisaje.edit',
                'ver'    => 'operativo.evaluacion_paisaje.ponderacion',
            ],
            'depende_de' => [],
        ],

        'valoracion_territorial' => [
            'nombre'     => 'Valoración territorial',
            'icono'      => 'mapa',
            'grupo'      => 'valoracion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionValoracionTerritorial::class,
            'criterios'  => 21,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_valoracion_territorial.edit',
                'ver'    => 'operativo.evaluacion_valoracion_territorial.ponderacion',
            ],
            'depende_de' => [],
        ],

        'percepcion' => [
            'nombre'     => 'Percepción de la localidad',
            'icono'      => 'brujula',
            'grupo'      => 'social',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionPercepcion::class,
            'criterios'  => 16,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_percepcion.edit',
                'ver'    => 'operativo.evaluacion_percepcion.ponderacion',
            ],
            'depende_de' => [],
        ],
    ];

    /** Entradas de un grupo, conservando sus claves y el orden de declaración. */
    public static function deGrupo(string $grupo): array
    {
        return array_filter(
            self::ENTRADAS,
            fn(array $entrada) => $entrada['grupo'] === $grupo
        );
    }

    /** Solo las matrices validables: las que cuentan para el progreso de la zona. */
    public static function matrices(): array
    {
        return array_filter(
            self::ENTRADAS,
            fn(array $entrada) => $entrada['tipo'] === 'matriz'
        );
    }
}
```

- [ ] **Step 4: Ejecutar el test y verificar que pasa**

```bash
php artisan test --filter=RegistroMatricesTest
```

Esperado: PASS, 7 tests.

Si `test_solo_las_matrices_validables_cuentan_para_el_progreso` falla por el
recuento, **no cambies el número esperado**: comprueba que no falte o sobre una
entrada de tipo `matriz`.

- [ ] **Step 5: Verificar el número de criterios declarado**

Los valores de `criterios` se usan para el subtítulo «21 de 34 respondidos». Un
número mal copiado no rompe nada visiblemente, así que se comprueba a mano una
vez:

```bash
php artisan tinker --execute="
echo 'paisaje: '.count(App\Matrices\Paisaje::todos()).PHP_EOL;
echo 'vt: '.count(App\Matrices\ValoracionTerritorial::todos()).PHP_EOL;
"
```

Esperado: `paisaje: 34`, `vt: 21`. Si no coinciden, corrige el registro.

Para FIT, FET, Percepción y Potencialidad, contar los campos declarados en sus
controladores (`criterios()` en FIT/FET/Percepción, `$secciones` en
Potencialidad) y ajustar el registro a lo que haya de verdad.

- [ ] **Step 6: Commit**

```bash
git add app/Matrices/Registro.php tests/Feature/RegistroMatricesTest.php
git commit -m "feat(matrices): registro central de matrices con test de integridad"
```

---

### Task 2: Servicio EstadoZona

Concentra la lógica que hoy está repetida en cada bloque `@php` de las vistas:
qué estado tiene cada matriz, a dónde lleva, y si está bloqueada.

**Files:**
- Create: `app/Servicios/FilaMatriz.php`
- Create: `app/Servicios/EstadoZona.php`
- Test: `tests/Unit/EstadoZonaTest.php`

**Interfaces:**
- Consumes: `Registro::GRUPOS`, `Registro::ENTRADAS`, `Registro::deGrupo()`, `Registro::matrices()`.
- Produces:
  - `FilaMatriz` con propiedades públicas de solo lectura: `clave`, `nombre`, `icono`, `estado`, `detalle`, `url`, `accion`, `puedeValidar`, `avisoValidacion`.
  - `EstadoZona::__construct(Zona $zona, User $usuario)`
  - `EstadoZona::grupos(): array` — `[claveGrupo => ['titulo' => string, 'filas' => list<FilaMatriz>]]`, sin grupos vacíos.
  - `EstadoZona::validadas(): int`
  - `EstadoZona::totalMatrices(): int`
  - `EstadoZona::papel(): string` — `'admin' | 'jefe' | 'equipo'`

Valores de `estado`: `'sin_empezar'`, `'borrador'`, `'validada'`, `'bloqueada'`, `'sin_estado'` (inventario).

- [ ] **Step 1: Escribir los tests unitarios**

Crear `tests/Unit/EstadoZonaTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Models\EvaluacionFet;
use App\Models\EvaluacionFit;
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

    public function test_no_se_devuelven_grupos_sin_filas(): void
    {
        $grupos = (new EstadoZona($this->zona, $this->jefe))->grupos();

        // 'presion' no tiene ninguna matriz implementada todavía.
        $this->assertArrayNotHasKey('presion', $grupos);
        $this->assertArrayHasKey('vocacion', $grupos);
    }
}
```

- [ ] **Step 2: Ejecutar los tests y verificar que fallan**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: FAIL — `Class "App\Servicios\EstadoZona" not found`.

- [ ] **Step 3: Escribir el DTO**

Crear `app/Servicios/FilaMatriz.php`:

```php
<?php

namespace App\Servicios;

/**
 * Una fila de la página de zona, ya resuelta.
 *
 * La vista no decide nada: recibe esto y lo pinta.
 */
final class FilaMatriz
{
    public function __construct(
        public readonly string  $clave,
        public readonly string  $nombre,
        public readonly string  $icono,
        /** sin_empezar | borrador | validada | bloqueada | sin_estado */
        public readonly string  $estado,
        public readonly string  $detalle,
        public readonly ?string $url,
        /** Empezar | Continuar | Ver | Abrir | null cuando no procede */
        public readonly ?string $accion,
        public readonly bool    $puedeValidar = false,
        public readonly ?string $avisoValidacion = null,
    ) {
    }
}
```

- [ ] **Step 4: Escribir el servicio**

Crear `app/Servicios/EstadoZona.php`:

```php
<?php

namespace App\Servicios;

use App\Matrices\Registro;
use App\Models\User;
use App\Models\Zona;
use Illuminate\Database\Eloquent\Model;

/**
 * Traduce una zona y quien la mira a las filas de su página.
 *
 * Toda la lógica de «si está confirmada lleva a resultados, si no al
 * formulario» vivía repetida en los @php de cada vista. Aquí está una vez y
 * se puede probar sin levantar HTTP.
 */
final class EstadoZona
{
    /** @var array<string, ?Model> evaluación cargada por clave de matriz */
    private array $evaluaciones = [];

    public function __construct(
        private readonly Zona $zona,
        private readonly User $usuario,
    ) {
        foreach (Registro::matrices() as $clave => $entrada) {
            $modelo = $entrada['modelo'];

            $this->evaluaciones[$clave] = $modelo::where('zona_id', $this->zona->id)->first();
        }
    }

    public function papel(): string
    {
        return match (true) {
            $this->usuario->esAdmin() => 'admin',
            $this->usuario->esJefe()  => 'jefe',
            default                   => 'equipo',
        };
    }

    public function totalMatrices(): int
    {
        return count(Registro::matrices());
    }

    public function validadas(): int
    {
        return count(array_filter(
            $this->evaluaciones,
            fn(?Model $e) => $e !== null && $e->estado === 'confirmado'
        ));
    }

    /** @return array<string, array{titulo: string, filas: list<FilaMatriz>}> */
    public function grupos(): array
    {
        $grupos = [];

        foreach (Registro::GRUPOS as $clave => $grupo) {
            $entradas = Registro::deGrupo($clave);

            if ($entradas === []) {
                continue;
            }

            $grupos[$clave] = [
                'titulo' => $grupo['titulo'],
                'filas'  => array_values(array_map(
                    fn($entradaClave) => $this->fila($entradaClave),
                    array_keys($entradas)
                )),
            ];
        }

        return $grupos;
    }

    private function fila(string $clave): FilaMatriz
    {
        $entrada = Registro::ENTRADAS[$clave];

        return match ($entrada['tipo']) {
            'inventario' => $this->filaInventario($clave, $entrada),
            'resultado'  => $this->filaResultado($clave, $entrada),
            default      => $this->filaMatriz($clave, $entrada),
        };
    }

    private function filaInventario(string $clave, array $entrada): FilaMatriz
    {
        $cuantos = $this->zona->inventarios()->count();

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'sin_estado',
            detalle: $cuantos === 1 ? '1 recurso registrado' : "{$cuantos} recursos registrados",
            url:     route($entrada['rutas']['editar'], $this->zona->id),
            accion:  'Abrir',
        );
    }

    /**
     * Un resultado derivado no se rellena: o está disponible porque sus
     * dependencias están validadas, o está bloqueado y dice cuáles faltan.
     */
    private function filaResultado(string $clave, array $entrada): FilaMatriz
    {
        $faltan = array_filter(
            $entrada['depende_de'],
            fn(string $dep) => ($this->evaluaciones[$dep] ?? null)?->estado !== 'confirmado'
        );

        if ($faltan !== []) {
            $nombres = array_map(
                fn(string $dep) => Registro::ENTRADAS[$dep]['nombre'],
                $faltan
            );

            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'bloqueada',
                detalle: 'Se desbloquea al validar: ' . implode(' y ', $nombres),
                url:     null,
                accion:  null,
            );
        }

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'validada',
            detalle: 'Disponible',
            url:     route($entrada['rutas']['ver'], $this->zona->id),
            accion:  'Ver',
        );
    }

    private function filaMatriz(string $clave, array $entrada): FilaMatriz
    {
        $evaluacion = $this->evaluaciones[$clave];
        $esAdmin    = $this->usuario->esAdmin();

        if ($evaluacion === null) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'sin_empezar',
                detalle: "{$entrada['criterios']} criterios · sin empezar",
                url:     $esAdmin ? null : route($entrada['rutas']['editar'], $this->zona->id),
                accion:  $esAdmin ? null : 'Empezar',
            );
        }

        $validada = $evaluacion->estado === 'confirmado';
        $firma    = $this->firma($evaluacion);

        if ($validada) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'validada',
                detalle: 'Validada' . $firma,
                url:     route($entrada['rutas']['ver'], $this->zona->id),
                accion:  'Ver',
            );
        }

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: 'Borrador' . $firma,
            url:     $esAdmin
                ? route($entrada['rutas']['ver'], $this->zona->id)
                : route($entrada['rutas']['editar'], $this->zona->id),
            accion:  $esAdmin ? 'Ver' : 'Continuar',
            puedeValidar:    ! $esAdmin && $this->usuario->esJefe(),
            avisoValidacion: $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
    }

    /** «— Ana Pérez, hace 2 días». Se guarda desde siempre y no se enseñaba. */
    private function firma(Model $evaluacion): string
    {
        $quien   = $evaluacion->user?->name;
        $cuando  = $evaluacion->updated_at?->diffForHumans();

        return match (true) {
            $quien !== null && $cuando !== null => " — {$quien}, {$cuando}",
            $cuando !== null                    => " — {$cuando}",
            default                             => '',
        };
    }
}
```

- [ ] **Step 5: Añadir la relación user() que falta en FIT y FET**

`EstadoZona::firma()` hace `$evaluacion->user?->name`. Paisaje, Percepción y
Valoración Territorial declaran esa relación, pero **`EvaluacionFit` y
`EvaluacionFet` no**, así que ahí `?->` no salva nada: Eloquent lanza
`BadMethodCallException` al no encontrar ni el atributo ni el método.

Comprobarlo:

```bash
grep -c "function user()" app/Models/EvaluacionFit.php app/Models/EvaluacionFet.php
```

Esperado hoy: `0` en ambos. Añadir a `app/Models/EvaluacionFit.php` y a
`app/Models/EvaluacionFet.php`, junto a la relación `zona()`:

```php
    public function user()
    {
        return $this->belongsTo(User::class);
    }
```

Y el `use App\Models\User;` si el fichero no lo tiene ya (están en el mismo
namespace `App\Models`, así que probablemente no haga falta ningún `use`).

- [ ] **Step 6: Ejecutar los tests y verificar que pasan**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: PASS, 10 tests.

Si falla `test_solo_el_jefe_puede_validar` por `$this->zona->jefe` nulo,
comprueba que el modelo `Zona` declara la relación `jefe()`; ya existe y la usa
`admin/zonas/index.blade.php`.

- [ ] **Step 7: Ejecutar la suite completa**

```bash
php artisan test
```

Esperado: PASS. Este task no toca nada existente salvo añadir dos relaciones, así
que las 118 pruebas anteriores deben seguir en verde. Si alguna falla, para y
averigua por qué antes de commitear.

- [ ] **Step 8: Commit**

```bash
git add app/Servicios app/Models/EvaluacionFit.php app/Models/EvaluacionFet.php \
        tests/Unit/EstadoZonaTest.php
git commit -m "feat(zona): servicio EstadoZona que resuelve las filas de una zona"
```

---

### Task 3: Componente de icono

Los emoji actuales se dibujan distinto en cada sistema operativo y los lectores
de pantalla los leen mal. Doce SVG en línea, sin dependencias nuevas.

**Files:**
- Create: `resources/views/components/icono.blade.php`
- Test: cubierto indirectamente por `PaginaZonaTest` (Task 5).

**Interfaces:**
- Consumes: los valores de `icono` del registro: `lista`, `flecha-abajo`,
  `flecha-arriba`, `diana`, `estrella`, `montana`, `mapa`, `brujula`.
- Produces: `<x-icono nombre="montana" class="w-6 h-6" />`

- [ ] **Step 1: Escribir el componente**

Crear `resources/views/components/icono.blade.php`:

```blade
@props(['nombre'])

{{--
    SVG de Heroicons (MIT), en línea. Un icono nuevo se añade aquí; si el
    nombre no existe se pinta un círculo, que es visible en la página y por
    tanto se corrige, en vez de dejar un hueco que nadie nota.
--}}

@php
    $trazos = [
        'lista'         => 'M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z',
        'flecha-abajo'  => 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'flecha-arriba' => 'M8.25 6.75 12 3m0 0 3.75 3.75M12 3v18',
        'diana'         => 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582',
        'estrella'      => 'M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z',
        'montana'       => 'm2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Z',
        'mapa'          => 'M9 6.75V15m6-6v8.25m.503 3.498 4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 0 0-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0Z',
        'brujula'       => 'm9 9 6-3-3 6-6 3 3-6Zm12 3a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
        'candado'       => 'M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z',
    ];
@endphp

<svg {{ $attributes->merge(['class' => 'w-6 h-6']) }}
     xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
     stroke-width="1.5" stroke="currentColor" aria-hidden="true">
    <path stroke-linecap="round" stroke-linejoin="round"
          d="{{ $trazos[$nombre] ?? 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Z' }}" />
</svg>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/components/icono.blade.php
git commit -m "feat(ui): componente de icono con SVG en linea, sin emoji"
```

---

### Task 4: Componente de fila

**Files:**
- Create: `resources/views/components/fila-matriz.blade.php`
- Test: cubierto por `PaginaZonaTest` (Task 5).

**Interfaces:**
- Consumes: una instancia de `App\Servicios\FilaMatriz`.
- Produces: `<x-fila-matriz :fila="$fila" />`

- [ ] **Step 1: Escribir el componente**

Crear `resources/views/components/fila-matriz.blade.php`:

```blade
@props(['fila'])

@php
    // Clases completas: Tailwind purga las construidas por concatenación.
    // El color codifica SOLO el estado. La identidad la dan icono y nombre.
    $estilos = [
        'sin_empezar' => ['icono' => 'text-gray-400',  'detalle' => 'text-gray-500'],
        'sin_estado'  => ['icono' => 'text-gray-500',  'detalle' => 'text-gray-600'],
        'borrador'    => ['icono' => 'text-amber-600', 'detalle' => 'text-amber-700'],
        'validada'    => ['icono' => 'text-green-600', 'detalle' => 'text-gray-600'],
        'bloqueada'   => ['icono' => 'text-gray-300',  'detalle' => 'text-gray-400'],
    ][$fila->estado];

    $bloqueada = $fila->estado === 'bloqueada';
@endphp

<div class="flex items-center gap-4 py-4 border-t border-gray-200">
    <x-icono :nombre="$bloqueada ? 'candado' : $fila->icono"
             class="w-6 h-6 shrink-0 {{ $estilos['icono'] }}" />

    <div class="flex-1 min-w-0">
        <p class="text-base {{ $bloqueada ? 'text-gray-400' : 'text-gray-900' }}">
            {{ $fila->nombre }}
        </p>
        <p class="text-sm {{ $estilos['detalle'] }}">{{ $fila->detalle }}</p>

        {{-- Sin botones desactivados: donde el equipo no puede validar, va el
             texto que dice quién lo hace. --}}
        @if($fila->avisoValidacion)
            <p class="text-sm text-amber-700 mt-1">{{ $fila->avisoValidacion }}</p>
        @endif
    </div>

    @if($fila->url && $fila->accion)
        <a href="{{ $fila->url }}"
           class="shrink-0 inline-flex items-center px-4 py-2 rounded-lg border border-gray-300
                  bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
            {{ $fila->accion }}
        </a>
    @endif
</div>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/components/fila-matriz.blade.php
git commit -m "feat(ui): componente de fila de matriz con color por estado"
```

---

### Task 5: Página de zona

**Files:**
- Create: `app/Http/Controllers/Operativo/ZonaPanelController.php`
- Create: `resources/views/operativo/zona/panel.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/PaginaZonaTest.php`

**Interfaces:**
- Consumes: `EstadoZona`, `<x-fila-matriz>`, `<x-icono>`.
- Produces: ruta con nombre `operativo.zona.panel`, con parámetro `{zona}`.

- [ ] **Step 1: Escribir el test de la página**

Crear `tests/Feature/PaginaZonaTest.php`:

```php
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

    public function test_muestra_los_titulos_de_los_grupos_con_matrices(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Base territorial')
            ->assertSee('Vocación turística')
            ->assertSee('Valoración del territorio')
            ->assertDontSee('Presión y uso');
    }

    public function test_muestra_el_progreso_de_la_zona(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('1 de 6');
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

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

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
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=PaginaZonaTest
```

Esperado: FAIL — `Route [operativo.zona.panel] not defined`.

- [ ] **Step 3: Escribir el controlador**

Crear `app/Http/Controllers/Operativo/ZonaPanelController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Illuminate\Support\Facades\Auth;

/**
 * Página de una zona: el índice de su trabajo.
 *
 * La sirven los tres roles por la misma URL. El middleware 'zona' ya limita al
 * admin a métodos seguros, así que no hacen falta rutas de solo lectura
 * aparte —que era justo lo que se desincronizaba.
 */
class ZonaPanelController extends Controller
{
    public function show($zonaId)
    {
        $zona   = Zona::with('lugar', 'jefe')->findOrFail($zonaId);
        $estado = new EstadoZona($zona, Auth::user());

        return view('operativo.zona.panel', compact('zona', 'estado'));
    }
}
```

- [ ] **Step 4: Registrar la ruta**

En `routes/web.php`, dentro del grupo
`Route::prefix('operativo/zona/{zona}')->middleware('zona')->name('operativo.')`,
como **primera** ruta del grupo (antes de `Route::resource('inventarios', ...)`):

```php
        Route::get('/', [ZonaPanelController::class, 'show'])->name('zona.panel');
```

Y añadir el `use` junto a los demás, en orden alfabético:

```php
use App\Http\Controllers\Operativo\ZonaPanelController;
```

- [ ] **Step 5: Escribir la vista**

Crear `resources/views/operativo/zona/panel.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded text-base">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded text-base">
                    {{ session('error') }}
                </div>
            @endif

            @if($estado->papel() === 'admin')
                <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-base">
                    Modo consulta — puedes ver los resultados, no modificarlos.
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-xl border border-gray-200 mb-6 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-base text-gray-600">📍 {{ $zona->lugar->nombre }}</p>
                        <p class="text-sm text-gray-500 mt-1">
                            Jefe de zona: {{ $zona->jefe->name ?? 'sin asignar' }}
                        </p>
                    </div>

                    @php
                        $etiquetaPapel = [
                            'admin'  => ['texto' => 'Administración', 'clase' => 'bg-blue-100 text-blue-800'],
                            'jefe'   => ['texto' => 'Jefe de zona',   'clase' => 'bg-purple-100 text-purple-800'],
                            'equipo' => ['texto' => 'Equipo',         'clase' => 'bg-green-100 text-green-800'],
                        ][$estado->papel()];
                    @endphp
                    <span class="shrink-0 text-sm font-medium px-3 py-1 rounded-full {{ $etiquetaPapel['clase'] }}">
                        {{ $etiquetaPapel['texto'] }} · {{ auth()->user()->name }}
                    </span>
                </div>

                @php
                    $total   = $estado->totalMatrices();
                    $hechas  = $estado->validadas();
                    $porcien = $total > 0 ? round($hechas / $total * 100) : 0;
                @endphp
                <div class="flex items-center gap-4 mt-6">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $porcien }}%"></div>
                    </div>
                    <span class="text-sm text-gray-600 whitespace-nowrap">
                        {{ $hechas }} de {{ $total }} validadas
                    </span>
                </div>
            </div>

            @foreach($estado->grupos() as $grupo)
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 mb-6 px-6 pt-5 pb-2">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $grupo['titulo'] }}</h3>

                    @foreach($grupo['filas'] as $fila)
                        <x-fila-matriz :fila="$fila" />
                    @endforeach
                </div>
            @endforeach

            <div class="flex justify-end">
                <a href="{{ $estado->papel() === 'admin' ? route('admin.zonas.index') : route('operativo.dashboard') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white
                          text-base text-gray-700 hover:bg-gray-50 shadow-sm">
                    ← Volver
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 6: Ejecutar el test y verificar que pasa**

```bash
php artisan test --filter=PaginaZonaTest
```

Esperado: PASS, 8 tests.

- [ ] **Step 7: Ejecutar la suite completa**

```bash
php artisan test
```

Esperado: PASS. Nada existente ha cambiado todavía.

- [ ] **Step 8: Compilar los assets y mirarla**

```bash
npm run build
```

Abrir `http://127.0.0.1:8000/operativo/zona/1` con `jefe@local.test` / `password`.
Comprobar a ojo: los grupos salen en orden, Vocación aparece con candado, ningún
texto se ve más pequeño que el resto.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Operativo/ZonaPanelController.php \
        resources/views/operativo/zona/panel.blade.php \
        routes/web.php tests/Feature/PaginaZonaTest.php
git commit -m "feat(zona): pagina de zona unica para los tres roles"
```

---

### Task 6: Tarjeta reducida en «Mis zonas»

Aquí es donde se rompen tests existentes. Está previsto: la tarjeta deja de
enlazar cada matriz, así que las pruebas que buscaban esos enlaces cambian de
objetivo. Se arreglan en este mismo task, no después.

**Files:**
- Modify: `resources/views/operativo/dashboard.blade.php`
- Modify: `app/Http/Controllers/Operativo/DashboardController.php`
- Modify: `tests/Feature/PaisajeTest.php`, `tests/Feature/ValoracionTerritorialTest.php`
- Test: comprobación previa de qué se rompe.

**Interfaces:**
- Consumes: `EstadoZona`, ruta `operativo.zona.panel`.
- Produces: `$zonas` en la vista, cada una con `$progreso[$zona->id]` = `['hechas' => int, 'total' => int]`.

- [ ] **Step 1: Localizar los tests que van a romperse**

```bash
php artisan test --filter="dashboard|tarjeta"
```

Anotar cuáles pasan ahora. Después del cambio, los que fallen deben ser
exactamente esos y ninguno más.

- [ ] **Step 2: Simplificar el controlador**

Reemplazar el cuerpo de `app/Http/Controllers/Operativo/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // El admin no usa este dashboard — tiene el suyo propio
        if ($user->esAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $zonas = match (true) {
            $user->esJefe()   => $user->zonasComoJefe()->with('lugar')->get(),
            $user->esEquipo() => $user->zonasComoEquipo()->with('lugar')->get(),
            default           => collect(),
        };

        // Antes esto eran cuatro consultas manuales que había que ampliar con
        // cada matriz nueva; una de ellas se olvidó y Paisaje quedó fuera.
        $progreso = $zonas->mapWithKeys(function (Zona $zona) use ($user) {
            $estado = new EstadoZona($zona, $user);

            return [$zona->id => [
                'hechas' => $estado->validadas(),
                'total'  => $estado->totalMatrices(),
            ]];
        });

        return view('operativo.dashboard', compact('zonas', 'progreso'));
    }
}
```

- [ ] **Step 3: Reescribir la tarjeta**

Reemplazar el bloque `<div class="p-6">…</div>` de cada tarjeta en
`resources/views/operativo/dashboard.blade.php` (líneas 39-150 del fichero
actual) por:

```blade
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $zona->nombre }}</h3>
                        <p class="text-sm text-gray-600 mt-1">📍 {{ $zona->lugar->nombre }}</p>

                        <p class="text-sm text-gray-600 mt-3 line-clamp-2">
                            {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
                        </p>

                        @php $p = $progreso[$zona->id]; @endphp
                        <div class="flex items-center gap-3 mt-5">
                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-sm text-gray-600 whitespace-nowrap">
                                {{ $p['hechas'] }} / {{ $p['total'] }}
                            </span>
                        </div>

                        {{-- Dos botones, no siete. El resto vive dentro de la zona.
                             Inventario se queda por ser lo que más se usa a diario. --}}
                        <div class="flex gap-2 mt-5">
                            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
                               class="flex-1 text-center px-4 py-2 rounded-lg bg-indigo-600 text-white
                                      text-sm font-medium hover:bg-indigo-700 shadow-sm">
                                Abrir zona
                            </a>
                            <a href="{{ route('operativo.inventarios.index', $zona->id) }}"
                               class="px-4 py-2 rounded-lg border border-gray-300 bg-white
                                      text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
                                Inventario
                            </a>
                        </div>
                    </div>
```

Mantener intacto el bloque de la imagen (líneas 25-37) y la insignia `#{{ $zona->id }}`
puede eliminarse: no aporta nada al usuario operativo.

- [ ] **Step 4: Ejecutar la suite y ver qué se rompe**

```bash
php artisan test
```

Esperado: FAIL en `PaisajeTest::test_la_tarjeta_aparece_en_el_dashboard` y su
equivalente en `ValoracionTerritorialTest`. Si falla algo más, para y averigua
por qué antes de tocar los tests.

- [ ] **Step 5: Adaptar los tests rotos**

En `tests/Feature/PaisajeTest.php`, sustituir
`test_la_tarjeta_aparece_en_el_dashboard` por:

```php
    public function test_la_zona_aparece_en_el_dashboard_con_su_progreso(): void
    {
        $this->actingAs($this->jefe)->get('/mis-zonas')
            ->assertOk()
            ->assertSee($this->zona->nombre)
            ->assertSee(route('operativo.zona.panel', $this->zona->id), false)
            ->assertSee('0 / 6');
    }

    public function test_paisaje_es_alcanzable_desde_la_pagina_de_zona(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee('Paisaje')
            ->assertSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }
```

Aplicar el mismo cambio al test equivalente de
`tests/Feature/ValoracionTerritorialTest.php`, cambiando `'Paisaje'` por
`'Valoración territorial'` y la ruta por
`operativo.evaluacion_valoracion_territorial.edit`.

- [ ] **Step 6: Ejecutar la suite completa**

```bash
php artisan test
```

Esperado: PASS.

- [ ] **Step 7: Commit**

```bash
git add resources/views/operativo/dashboard.blade.php \
        app/Http/Controllers/Operativo/DashboardController.php \
        tests/Feature/PaisajeTest.php tests/Feature/ValoracionTerritorialTest.php
git commit -m "feat(dashboard): tarjeta reducida con progreso y acceso a la zona"
```

---

### Task 7: Lista de zonas del admin y limpieza de rutas

Al usar el admin la misma página que el resto, las cuatro rutas de resultados y
sus métodos quedan huérfanos. Se eliminan aquí, en el mismo cambio que los deja
de necesitar.

**Files:**
- Modify: `resources/views/admin/zonas/index.blade.php`
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/Admin/ZonaController.php`
- Modify: `tests/Feature/AdminZonasTest.php`

**Interfaces:**
- Consumes: ruta `operativo.zona.panel`, `EstadoZona`.
- Produces: `$progreso` en `admin.zonas.index`, mismo formato que Task 6.

- [ ] **Step 1: Comprobar qué tests cubren las rutas que se van a borrar**

```bash
grep -rn "zonas.potencialidad\|zonas.percepcion\|zonas.paisaje\|zonas.valoracion_territorial" tests/
```

Anotar los resultados: hay que reapuntarlos a `operativo.zona.panel`, no
borrarlos. La cobertura del acceso de solo lectura del admin no se pierde.

- [ ] **Step 2: Añadir el progreso al controlador del admin**

En `app/Http/Controllers/Admin/ZonaController.php`, reemplazar `index()`:

```php
    public function index() {
        $zonas = Zona::with(['lugar', 'jefe'])->withCount('equipo')->paginate(10);

        $user = Auth::user();
        $progreso = $zonas->mapWithKeys(function (Zona $zona) use ($user) {
            $estado = new EstadoZona($zona, $user);

            return [$zona->id => [
                'hechas' => $estado->validadas(),
                'total'  => $estado->totalMatrices(),
            ]];
        });

        return view('admin.zonas.index', compact('zonas', 'progreso'));
    }
```

Añadir los `use` que faltan al principio del fichero:

```php
use App\Servicios\EstadoZona;
use Illuminate\Support\Facades\Auth;
```

- [ ] **Step 3: Eliminar los cuatro métodos huérfanos**

Borrar de `app/Http/Controllers/Admin/ZonaController.php` los métodos
`potencialidad()`, `percepcion()`, `paisaje()` y `valoracionTerritorial()`
completos, y los `use` que dejan de usarse:

```php
use App\Matrices\Paisaje;
use App\Matrices\ValoracionTerritorial;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPaisaje;
use App\Models\EvaluacionPotencialidad;
use App\Models\EvaluacionValoracionTerritorial;
```

`use App\Models\InventarioImagen;` y `use Illuminate\Support\Facades\Storage;`
**se quedan**: los usa `destroy()`.

- [ ] **Step 4: Eliminar las cuatro rutas**

En `routes/web.php`, borrar del grupo admin los cuatro bloques
`Route::get('/zona/{zona}/potencialidad'…)`, `…/percepcion`, `…/paisaje` y
`…/valoracion-territorial`, con sus comentarios.

`admin.vtt.final.admin` **se queda**: la usa `VttController` y no tiene
equivalente en la página de zona.

- [ ] **Step 5: Reescribir la fila de la tabla**

En `resources/views/admin/zonas/index.blade.php`, sustituir la cabecera y el
`<td>` de acciones. La cabecera pasa a:

```blade
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Zona</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Ubicación</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Jefe</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-600">Equipo</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-600">Progreso</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-600">Acciones</th>
```

Antes del `<td>` de acciones, añadir la celda de progreso:

```blade
                            <td class="px-6 py-4 text-center text-sm text-gray-600">
                                @php $p = $progreso[$zona->id]; @endphp
                                {{ $p['hechas'] }} / {{ $p['total'] }}
                            </td>
```

Y el `<td>` de acciones pasa a tener tres botones:

```blade
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('operativo.zona.panel', $zona->id) }}"
                                       class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                                        Abrir zona
                                    </a>
                                    <a href="{{ route('admin.zonas.edit', $zona->id) }}"
                                       class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.zonas.destroy', $zona->id) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar esta zona?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-red-100 text-red-700 text-sm font-medium hover:bg-red-200">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
```

Subir también el resto de textos de la fila de `text-sm`/`text-xs` a `text-base`
donde sean el nombre de la zona, y a `text-sm` el resto.

- [ ] **Step 6: Reapuntar los tests del admin**

En `tests/Feature/PaisajeTest.php`, sustituir `test_el_admin_consulta_los_resultados`
y `test_el_admin_ve_un_aviso_si_la_zona_no_tiene_la_matriz` por:

```php
    public function test_el_admin_consulta_la_zona_en_modo_lectura(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee('Modo consulta')
            ->assertSee('Paisaje')
            ->assertSee(route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id), false);
    }

    public function test_el_admin_no_puede_modificar_la_matriz(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->post($this->url(), $this->todosEn(5))
            ->assertForbidden();

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }
```

Aplicar el mismo tratamiento a los tests equivalentes que la búsqueda del paso 1
haya sacado en `ValoracionTerritorialTest`, `EvaluacionesTest` y `AdminZonasTest`.

- [ ] **Step 7: Ejecutar la suite completa**

```bash
php artisan test
```

Esperado: PASS. Ninguna referencia a las rutas borradas debe quedar viva; si
aparece un `Route [admin.zonas.paisaje] not defined`, queda una vista o un test
sin reapuntar.

- [ ] **Step 8: Verificar que no quedan referencias muertas**

```bash
grep -rn "zonas.potencialidad\|zonas.percepcion\|zonas.paisaje\|zonas.valoracion_territorial" app/ resources/ routes/ tests/
```

Esperado: sin resultados.

- [ ] **Step 9: Commit**

```bash
git add -A
git commit -m "refactor(admin): la lista de zonas usa la pagina comun y elimina rutas duplicadas"
```

---

### Task 8: Revisión final

- [ ] **Step 1: Suite completa y build**

```bash
php artisan test && npm run build
```

Esperado: ambas en verde.

- [ ] **Step 2: Recorrido manual por los tres roles**

Levantar el servidor si no está:

```bash
php artisan serve
```

Comprobar, uno por uno:

1. **Jefe** (`jefe@local.test`): `/mis-zonas` muestra la tarjeta con progreso;
   «Abrir zona» lleva al panel; cada matriz tiene su nombre visible; Vocación
   aparece con candado si FIT o FET no están validadas.
2. **Equipo**: en una matriz en borrador, donde el jefe ve validar, el equipo lee
   «Lista para validar — avísale a …».
3. **Admin**: `/admin/zonas` muestra «Abrir zona»; dentro sale la franja de modo
   consulta y ninguna matriz ofrece editar. **Paisaje es alcanzable** — el bug
   que motivó todo esto.

- [ ] **Step 3: Comprobar que el purgado de Tailwind no se comió nada**

Las clases de estado de `fila-matriz.blade.php` están en un mapa, pero conviene
confirmarlo en el build de producción, no solo en dev:

```bash
grep -c "text-amber-600\|text-green-600\|bg-green-500" public/build/assets/*.css
```

Esperado: al menos 1. Si sale 0, alguna clase se está construyendo por
concatenación en algún sitio.

- [ ] **Step 4: Commit final si algo cambió**

```bash
git status --short
```

Si hay cambios sin commitear, revisarlos y commitearlos con un mensaje
descriptivo. Si no, el plan está terminado.

---

## Fuera de este plan

- **Guardado parcial** de las seis matrices, incluida Potencialidad. Va en
  `docs/superpowers/plans/2026-08-07-guardado-parcial.md`.
- **Panel de administración, usuarios y lugares.** Va en
  `docs/superpowers/plans/2026-08-07-vistas-admin.md`.
- **Migrar FIT, FET, Percepción y Potencialidad** a los componentes de criterio
  nuevos. Sin plan todavía; queda anotado en el spec.
