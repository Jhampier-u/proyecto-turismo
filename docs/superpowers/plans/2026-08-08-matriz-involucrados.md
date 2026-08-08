# Matriz de Involucrados Turísticos Territoriales — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Añadir la octava matriz del sistema —Involucrados Turísticos Territoriales—, que puntúa una lista variable de actores en tres atributos y los clasifica según el modelo de Mitchell.

**Architecture:** Es la primera entrada del registro que es un CRUD **con** estado. `inventario` es CRUD sin estado y `matriz` es estado sin CRUD, así que hace falta un cuarto tipo, `actores`. Eso toca `Registro` y `EstadoZona`, que comparten las siete matrices anteriores, y por eso va primero y solo. Después, dos tablas —la configuración por zona y los actores— y un CRUD con su formulario y sus resultados.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, SQLite en desarrollo y tests, PostgreSQL 16 en producción, PHPUnit 11.

**Diseño:** `docs/superpowers/specs/2026-08-07-matriz-involucrados-design.md`

## Global Constraints

- **La normalización es relativa al conjunto.** Añadir un actor cambia el resultado de todos los demás. Es fiel al instrumento y la pantalla tiene que decirlo; no se corrige por nuestra cuenta.
- **Nada calculado se guarda.** Grados, normalizados y tipo de Mitchell se derivan siempre.
- Los criterios nacen `nullable` y sin defecto: un criterio sin responder no es un 0, y aquí el 0 significa «no posee».
- Nada por debajo de 14 px salvo insignias. Sin `uppercase`. Clases de Tailwind completas, nunca por concatenación.
- Comentarios en castellano explicando el *por qué*.
- Suite completa en verde antes de cada commit.
- No se toca ningún contenedor Docker.

## Estructura de ficheros

**Crear:**
- `app/Matrices/Involucrados.php` — definición del instrumento: los once criterios agrupados por atributo, sus etiquetas, la escala y los siete tipos de Mitchell.
- `database/migrations/2026_08_10_000001_create_involucrados_tables.php`
- `app/Models/InvolucradosConfig.php` y `app/Models/Involucrado.php`
- `app/Http/Controllers/Operativo/InvolucradosController.php`
- `resources/views/operativo/involucrados/index.blade.php`, `form.blade.php`, `resultados.blade.php`
- `tests/Feature/InvolucradosTest.php`

**Modificar:**
- `app/Matrices/Registro.php` — el cuarto tipo y la entrada nueva.
- `app/Servicios/EstadoZona.php` — la rama `filaActores()`.
- `tests/Feature/RegistroMatricesTest.php` y `tests/Unit/EstadoZonaTest.php` — el recuento y el tipo.
- `routes/web.php`.

---

### Task 1: El cuarto tipo de entrada del registro

Va primero y sola. Toca la pieza que comparten las siete matrices anteriores, y
dejarla a medias mientras se construye Involucrados es la forma de no saber, si
algo se rompe, cuál de las dos cosas lo rompió.

Al terminar esta tarea el sistema debe seguir comportándose **exactamente igual**
que antes: es un ensanchamiento, no un cambio.

**Files:**
- Modify: `app/Matrices/Registro.php`
- Modify: `app/Servicios/EstadoZona.php`
- Test: `tests/Unit/EstadoZonaTest.php`, `tests/Feature/RegistroMatricesTest.php`

**Interfaces:**
- Produce: `Registro::TIPOS_VALIDABLES` — los tipos que cuentan para el progreso.
- Produce: `EstadoZona::filaActores()`, con `FilaMatriz::$estado` y su detalle.

- [ ] **Step 1: Escribir el test de la rama nueva**

Añadir a `tests/Unit/EstadoZonaTest.php`:

```php
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
```

- [ ] **Step 2: Ejecutar y verificar que se salta**

```bash
php artisan test --filter=test_una_entrada_de_actores_sin_empezar_lo_dice
```

Esperado: SKIPPED. Todavía no hay ninguna entrada de ese tipo; la habrá en la
Task 3 y entonces este test pasa a comprobar de verdad.

- [ ] **Step 3: Declarar los tipos validables en el registro**

En `app/Matrices/Registro.php`, sustituir el filtro de `matrices()`:

```php
    /**
     * Tipos de entrada que se validan y por tanto cuentan para el progreso de
     * la zona. `inventario` no está porque no tiene estado, y `resultado`
     * porque es derivado: contarlos haría que el denominador mintiera.
     */
    public const TIPOS_VALIDABLES = ['matriz', 'actores'];

    /** Solo las entradas validables: las que cuentan para el progreso. */
    public static function matrices(): array
    {
        return array_filter(
            self::ENTRADAS,
            fn(array $entrada) => in_array($entrada['tipo'], self::TIPOS_VALIDABLES, true)
        );
    }
```

Y documentar el tipo nuevo en el comentario de `ENTRADAS`, junto a los otros tres:

```php
     *   'actores'   — lista variable de actores con estado; cuenta para el progreso
```

- [ ] **Step 4: Añadir la rama a EstadoZona**

**Cuidado con esto:** `EstadoZona::fila()` termina en `default => $this->filaMatriz(...)`. Un tipo nuevo cae ahí **en silencio** y se rompe al leer `$entrada['criterios']`, que en `actores` es `null`. Hay que añadir la rama explícita:

```php
        return match ($entrada['tipo']) {
            'inventario' => $this->filaInventario($clave, $entrada),
            'resultado'  => $this->filaResultado($clave, $entrada),
            'actores'    => $this->filaActores($clave, $entrada),
            default      => $this->filaMatriz($clave, $entrada),
        };
```

Y el método, junto a los otros tres:

```php
    /**
     * Una lista de actores no tiene denominador fijo: son «cinco actores, dos a
     * medias», no «21 de 34 respondidos». Por eso no reutiliza filaMatriz().
     *
     * El modelo de la entrada es la configuración por zona —la que lleva el
     * estado—, no la de cada actor.
     */
    private function filaActores(string $clave, array $entrada): FilaMatriz
    {
        $config  = $this->evaluaciones[$clave];
        $esAdmin = $this->usuario->esAdmin();

        $cuantos = $this->zona->involucrados()->count();

        if ($cuantos === 0) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'sin_empezar',
                detalle: 'Todavía sin actores registrados',
                url:     $esAdmin ? null : route($entrada['rutas']['editar'], $this->zona->id),
                accion:  $esAdmin ? null : 'Empezar',
            );
        }

        $validada = $config?->estado === 'confirmado';
        $firma    = $config ? $this->firma($config) : '';

        if ($validada) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'validada',
                detalle: "Validada · {$cuantos} actores" . $firma,
                url:     route($entrada['rutas']['ver'], $this->zona->id),
                accion:  'Ver',
            );
        }

        $incompletos = $this->zona->involucrados()->incompletos()->count();

        $detalle = $incompletos === 0
            ? "Borrador · {$cuantos} actores, todos completos"
            : "Borrador · {$cuantos} actores, {$incompletos} sin completar";

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: $detalle . $firma,
            url:     $esAdmin
                ? route($entrada['rutas']['ver'], $this->zona->id)
                : route($entrada['rutas']['editar'], $this->zona->id),
            accion:  $esAdmin ? 'Ver' : 'Continuar',
            puedeValidar:    $incompletos === 0 && $this->usuario->esJefe(),
            avisoValidacion: $incompletos === 0 && $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
    }
```

**Nota:** `$this->zona->involucrados()` y el scope `incompletos()` llegan en la Task 2. Hasta entonces este método no se ejecuta, porque no hay ninguna entrada de tipo `actores`. Si el linter se queja, sigue adelante: la Task 2 lo cierra.

- [ ] **Step 5: Ajustar el test del registro**

`RegistroMatricesTest::test_solo_las_matrices_validables_cuentan_para_el_progreso`
afirma que todas las entradas de `matrices()` son de tipo `matriz`. Ya no:

```php
        foreach (Registro::matrices() as $clave => $entrada) {
            $this->assertContains($entrada['tipo'], Registro::TIPOS_VALIDABLES, $clave);
            $this->assertNotNull($entrada['modelo'], $clave);
        }
```

El `assertCount(7, ...)` se queda en 7 hasta la Task 3.

- [ ] **Step 6: Suite completa**

```bash
php artisan test
```

Esperado: PASS, con el test nuevo saltado. **Nada más debe haber cambiado de
comportamiento**: si algún test de otra matriz se mueve, el ensanchamiento no
era tal y hay que averiguar por qué antes de seguir.

- [ ] **Step 7: Commit**

```bash
git add app/Matrices/Registro.php app/Servicios/EstadoZona.php \
        tests/Unit/EstadoZonaTest.php tests/Feature/RegistroMatricesTest.php
git commit -m "feat(registro): cuarto tipo de entrada, un CRUD con estado"
```

---

### Task 2: Tablas, modelos y definición del instrumento

**Files:**
- Create: `app/Matrices/Involucrados.php`
- Create: `database/migrations/2026_08_10_000001_create_involucrados_tables.php`
- Create: `app/Models/InvolucradosConfig.php`, `app/Models/Involucrado.php`
- Modify: `app/Models/Zona.php` — la relación `involucrados()`
- Test: `tests/Feature/InvolucradosTest.php`

**Interfaces:**
- Produce: `Involucrados::ATRIBUTOS` — los tres atributos con sus campos y etiquetas.
- Produce: `Involucrados::tipoDe(bool $poder, bool $legitimidad, bool $urgencia): string`
- Produce: `Involucrado::scopeIncompletos()` y `Involucrado::estaCompleto()`.

- [ ] **Step 1: Escribir los tests de los tipos de Mitchell**

Los siete tipos más el caso sin atributos. **Ojo con la errata del instrumento**,
explicada en el diseño: su tabla asocia «Exigentes» a legitimidad y
«Discrecionales» a urgencia, y en Mitchell es al revés. Se implementa según
Mitchell.

Crear `tests/Feature/InvolucradosTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Matrices\Involucrados;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvolucradosTest extends TestCase
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
     * Los siete tipos de Mitchell y el caso sin ninguno.
     *
     * La tabla del instrumento original asocia «Exigentes» a legitimidad y
     * «Discrecionales» a urgencia, que es al revés de como los define Mitchell:
     * demanding es el que solo tiene urgencia y discretionary el que solo tiene
     * legitimidad. Se implementa según la fuente, no según la hoja.
     */
    public function test_los_tipos_de_mitchell_salen_de_los_tres_atributos(): void
    {
        $casos = [
            [false, false, false, 'No es actor relevante'],
            [true,  false, false, 'Adormecido'],
            [false, true,  false, 'Discrecional'],
            [false, false, true,  'Exigente'],
            [true,  false, true,  'Peligroso'],
            [true,  true,  false, 'Dominante'],
            [false, true,  true,  'Dependiente'],
            [true,  true,  true,  'Definitivo'],
        ];

        foreach ($casos as [$poder, $legitimidad, $urgencia, $esperado]) {
            $this->assertSame(
                $esperado,
                Involucrados::tipoDe($poder, $legitimidad, $urgencia),
                sprintf('poder=%d legitimidad=%d urgencia=%d', $poder, $legitimidad, $urgencia)
            );
        }
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=test_los_tipos_de_mitchell_salen_de_los_tres_atributos
```

Esperado: FAIL, «Class "App\Matrices\Involucrados" not found».

- [ ] **Step 3: Escribir la definición del instrumento**

Crear `app/Matrices/Involucrados.php`:

```php
<?php

namespace App\Matrices;

/**
 * Matriz de Involucrados Turísticos Territoriales.
 *
 * Puntúa una lista variable de actores en tres atributos —poder, legitimidad y
 * urgencia— y los clasifica según el modelo de relevancia de Mitchell, Agle y
 * Wood (1997).
 *
 * A diferencia de las siete matrices anteriores, aquí no hay una lista fija de
 * criterios de la zona: hay una lista de actores, y cada actor lleva estos
 * once criterios.
 */
final class Involucrados
{
    public const ESCALA_MIN = 0;
    public const ESCALA_MAX = 3;

    /** Los tres atributos, con sus criterios y su etiqueta. */
    public const ATRIBUTOS = [
        'poder' => [
            'titulo'  => 'Grado de poder',
            'campos'  => [
                'pod_autoridad'     => 'Autoridad (medios coercitivos)',
                'pod_poder'         => 'Poder (medios coercitivos)',
                'pod_recursos'      => 'Recursos y atractivos',
                'pod_presupuesto'   => 'Presupuesto',
                'pod_tecnologicos'  => 'Medios tecnológicos',
                'pod_cadena_valor'  => 'Cadena de valor',
                'pod_intelectuales' => 'Medios intelectuales',
            ],
        ],
        'legitimidad' => [
            'titulo' => 'Grado de legitimidad',
            'campos' => [
                'leg_territorio' => 'Deseabilidad para el territorio',
                'leg_sociedad'   => 'Deseabilidad para la sociedad',
            ],
        ],
        'urgencia' => [
            'titulo' => 'Grado de urgencia',
            'campos' => [
                'urg_sensibilidad' => 'Sensibilidad temporal',
                'urg_criticidad'   => 'Criticidad',
            ],
        ],
    ];

    /** @return list<string> los once nombres de campo, en orden */
    public static function campos(): array
    {
        return array_merge(...array_map(
            fn(array $a) => array_keys($a['campos']),
            array_values(self::ATRIBUTOS)
        ));
    }

    /**
     * El tipo de Mitchell según qué atributos posee el actor.
     *
     * La tabla del instrumento original intercambia «Exigentes» y
     * «Discrecionales»: asocia el primero a legitimidad y el segundo a
     * urgencia. En Mitchell es al revés —demanding es urgencia sola,
     * discretionary es legitimidad sola— y se implementa según la fuente.
     */
    public static function tipoDe(bool $poder, bool $legitimidad, bool $urgencia): string
    {
        return match (true) {
            $poder && $legitimidad && $urgencia => 'Definitivo',
            $poder && $urgencia                 => 'Peligroso',
            $poder && $legitimidad              => 'Dominante',
            $legitimidad && $urgencia           => 'Dependiente',
            $poder                              => 'Adormecido',
            $legitimidad                        => 'Discrecional',
            $urgencia                           => 'Exigente',
            default                             => 'No es actor relevante',
        };
    }
}
```

- [ ] **Step 4: Escribir la migración**

Crear `database/migrations/2026_08_10_000001_create_involucrados_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Matriz de Involucrados: dos tablas, no una.
 *
 * La configuración lleva el estado del conjunto —es lo que se valida y lo que
 * lee la página de zona— y los actores son filas independientes. Meter el
 * estado en cada actor habría permitido validar medio instrumento, que aquí no
 * significa nada: la normalización del resultado depende de que la lista esté
 * cerrada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('involucrados_config', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('involucrados', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->string('nombre', 200);
            $table->unsignedInteger('orden')->default(0);

            // Los once criterios, nullable y sin defecto: un criterio sin
            // responder no es un 0, y aquí el 0 significa «no posee».
            $table->tinyInteger('pod_autoridad')->nullable();
            $table->tinyInteger('pod_poder')->nullable();
            $table->tinyInteger('pod_recursos')->nullable();
            $table->tinyInteger('pod_presupuesto')->nullable();
            $table->tinyInteger('pod_tecnologicos')->nullable();
            $table->tinyInteger('pod_cadena_valor')->nullable();
            $table->tinyInteger('pod_intelectuales')->nullable();

            $table->tinyInteger('leg_territorio')->nullable();
            $table->tinyInteger('leg_sociedad')->nullable();

            $table->tinyInteger('urg_sensibilidad')->nullable();
            $table->tinyInteger('urg_criticidad')->nullable();

            // El juicio de si el actor posee cada atributo. No se deriva de las
            // puntuaciones: el instrumento nunca dice cómo se pasa de «14 sobre
            // 21» a «tiene poder», y no se inventa aquí ese umbral.
            $table->boolean('tiene_poder')->default(false);
            $table->boolean('tiene_legitimidad')->default(false);
            $table->boolean('tiene_urgencia')->default(false);

            $table->timestamps();

            $table->index('zona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('involucrados');
        Schema::dropIfExists('involucrados_config');
    }
};
```

- [ ] **Step 5: Escribir los modelos y la relación**

Crear `app/Models/InvolucradosConfig.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * El estado del conjunto de actores de una zona.
 *
 * Existe aparte de los actores porque lo que se valida es la lista entera: la
 * normalización del resultado divide por el total, así que media lista no
 * significa nada.
 */
class InvolucradosConfig extends Model
{
    protected $table = 'involucrados_config';

    protected $fillable = ['zona_id', 'user_id', 'estado'];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
```

Crear `app/Models/Involucrado.php`:

```php
<?php

namespace App\Models;

use App\Matrices\Involucrados;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Involucrado extends Model
{
    protected $table = 'involucrados';

    protected $fillable = [
        'zona_id', 'nombre', 'orden',
        ...Involucrados::campos(),
        'tiene_poder', 'tiene_legitimidad', 'tiene_urgencia',
    ];

    protected function casts(): array
    {
        return array_merge(
            array_fill_keys(Involucrados::campos(), 'integer'),
            [
                'tiene_poder'       => 'boolean',
                'tiene_legitimidad' => 'boolean',
                'tiene_urgencia'    => 'boolean',
            ]
        );
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    /** Un actor está completo cuando tiene respondidos sus once criterios. */
    public function estaCompleto(): bool
    {
        foreach (Involucrados::campos() as $campo) {
            if ($this->$campo === null) {
                return false;
            }
        }

        return true;
    }

    /**
     * Los actores a medias, en SQL y no en PHP: la página de zona los cuenta
     * en cada carga y no debe traerse las filas para eso.
     */
    public function scopeIncompletos(Builder $consulta): Builder
    {
        return $consulta->where(function (Builder $q) {
            foreach (Involucrados::campos() as $campo) {
                $q->orWhereNull($campo);
            }
        });
    }

    /** Suma de los criterios de un atributo, o null si le falta alguno. */
    public function grado(string $atributo): ?int
    {
        $suma = 0;

        foreach (array_keys(Involucrados::ATRIBUTOS[$atributo]['campos']) as $campo) {
            if ($this->$campo === null) {
                return null;
            }

            $suma += $this->$campo;
        }

        return $suma;
    }

    public function getTipoMitchellAttribute(): string
    {
        return Involucrados::tipoDe(
            $this->tiene_poder,
            $this->tiene_legitimidad,
            $this->tiene_urgencia
        );
    }
}
```

En `app/Models/Zona.php`, junto a las demás relaciones:

```php
    public function involucrados()
    {
        return $this->hasMany(Involucrado::class)->orderBy('orden')->orderBy('id');
    }
```

- [ ] **Step 6: Añadir los tests del modelo**

Añadir a `tests/Feature/InvolucradosTest.php`:

```php
    public function test_un_actor_esta_completo_solo_con_sus_once_criterios(): void
    {
        $actor = new \App\Models\Involucrado(
            ['zona_id' => $this->zona->id, 'nombre' => 'Municipio']
            + array_fill_keys(Involucrados::campos(), 2)
        );

        $this->assertTrue($actor->estaCompleto());

        $actor->pod_presupuesto = null;

        $this->assertFalse($actor->estaCompleto());
    }

    public function test_el_grado_de_un_atributo_es_null_si_le_falta_un_criterio(): void
    {
        $actor = new \App\Models\Involucrado(
            ['zona_id' => $this->zona->id, 'nombre' => 'Municipio']
            + array_fill_keys(Involucrados::campos(), 3)
        );

        // Siete criterios a 3: el tope de poder es 21.
        $this->assertSame(21, $actor->grado('poder'));
        $this->assertSame(6, $actor->grado('legitimidad'));

        $actor->urg_criticidad = null;

        $this->assertNull($actor->grado('urgencia'));
    }

    /** El scope filtra en SQL: la página de zona lo llama en cada carga. */
    public function test_el_scope_de_incompletos_encuentra_a_los_que_faltan(): void
    {
        \App\Models\Involucrado::create(
            ['zona_id' => $this->zona->id, 'nombre' => 'Completo']
            + array_fill_keys(Involucrados::campos(), 1)
        );

        \App\Models\Involucrado::create(
            ['zona_id' => $this->zona->id, 'nombre' => 'A medias']
            + array_fill_keys(Involucrados::campos(), 1)
            + ['leg_sociedad' => null]
        );

        $this->assertSame(2, $this->zona->involucrados()->count());
        $this->assertSame(1, $this->zona->involucrados()->incompletos()->count());
    }
```

Ojo con el orden de `+` entre arrays: gana el operando izquierdo. En el segundo
caso, `['leg_sociedad' => null]` va a la derecha y **no** pisaría al relleno; hay
que construirlo de forma que sí lo haga, por ejemplo asignando el campo después
de crear el actor. Compruébalo al escribirlo: si el test pasa sin que ningún
actor esté incompleto, es esto.

- [ ] **Step 7: Migrar, ejecutar y commitear**

```bash
php artisan migrate
php artisan test
```

Esperado: PASS.

```bash
git add app/Matrices/Involucrados.php app/Models routes/web.php \
        database/migrations/2026_08_10_000001_create_involucrados_tables.php \
        tests/Feature/InvolucradosTest.php
git commit -m "feat(involucrados): definicion del instrumento, tablas y modelos"
```

---

### Task 3: El CRUD de actores

**Files:**
- Create: `app/Http/Controllers/Operativo/InvolucradosController.php`
- Create: `resources/views/operativo/involucrados/index.blade.php` y `form.blade.php`
- Modify: `routes/web.php`, `app/Matrices/Registro.php`
- Test: ampliar `tests/Feature/InvolucradosTest.php`

Este controlador **no extiende `MatrizPonderadaController`**: aquella clase base
asume un formulario de criterios de la zona, y aquí hay una lista. Sigue el
patrón de `InventarioController`, que es el CRUD que ya existe.

**Rutas**, bajo el mismo prefijo de zona que las demás:

```php
        // Matriz de Involucrados Turísticos Territoriales
        Route::get('/involucrados',                 [InvolucradosController::class, 'index'])->name('involucrados.index');
        Route::get('/involucrados/nuevo',           [InvolucradosController::class, 'create'])->name('involucrados.create');
        Route::post('/involucrados',                [InvolucradosController::class, 'store'])->name('involucrados.store');
        Route::get('/involucrados/{actor}/editar',  [InvolucradosController::class, 'edit'])->name('involucrados.edit');
        Route::put('/involucrados/{actor}',         [InvolucradosController::class, 'update'])->name('involucrados.update');
        Route::delete('/involucrados/{actor}',      [InvolucradosController::class, 'destroy'])->name('involucrados.destroy');
        Route::post('/involucrados/validar',        [InvolucradosController::class, 'validar'])->name('involucrados.validar');
        Route::get('/involucrados/resultados',      [InvolucradosController::class, 'resultados'])->name('involucrados.resultados');
```

**La entrada del registro**, al final de `ENTRADAS`:

```php
        'involucrados' => [
            'nombre'     => 'Involucrados turísticos',
            'icono'      => 'usuarios',
            'grupo'      => 'social',
            'tipo'       => 'actores',
            'modelo'     => InvolucradosConfig::class,
            'criterios'  => null,
            'rutas'      => [
                'editar' => 'operativo.involucrados.index',
                'ver'    => 'operativo.involucrados.resultados',
            ],
            'depende_de' => [],
        ],
```

Hace falta un icono `usuarios` nuevo en `resources/views/components/icono.blade.php`; el test de iconos que ya existe fallará si se repite uno.

- [ ] **Step 1: Escribir los tests del CRUD**

Cubrir: crear un actor, editarlo, borrarlo, que un actor de otra zona no se
puede tocar, que el equipo puede editar mientras esté en borrador y no cuando
está confirmado, y que validar exige que no haya actores incompletos ni la lista
vacía.

Escribir estos tests **antes** del controlador, con la forma de los de
`AutorizacionZonaTest` para los de acceso.

- [ ] **Step 2: Verificar que fallan, escribir el controlador y las dos vistas, y volver a ejecutar**

`index` lista los actores con su grado por atributo y su estado (completo o a
medias), con el botón de validar cuando procede. `form` es el alta y edición de
un actor: su nombre, los once criterios agrupados en tres bloques con el
componente de escala 0-3, y las tres casillas de atributo con su explicación.

El `validar` comprueba que hay al menos un actor y que ninguno está incompleto
antes de poner `confirmado`; si no, vuelve con el error.

- [ ] **Step 3: Ajustar el recuento del registro**

`RegistroMatricesTest` pasa de 7 a 8 matrices, y `EstadoZonaTest::totalMatrices()`
igual. El test de la Task 1 que se saltaba ahora se ejecuta de verdad: compruébalo.

- [ ] **Step 4: Suite completa y commit**

```bash
php artisan test
git commit -m "feat(involucrados): CRUD de actores con validacion del conjunto"
```

---

### Task 4: Resultados

**Files:**
- Create: `resources/views/operativo/involucrados/resultados.blade.php`
- Modify: `app/Http/Controllers/Operativo/InvolucradosController.php`
- Test: ampliar `tests/Feature/InvolucradosTest.php`

- [ ] **Step 1: Escribir los tests de la normalización**

El cálculo del instrumento, con dos actores y números redondos:

```
normalizado = (grado / suma de los grados) × número de actores
relevancia  = producto de los tres normalizados
```

Con dos actores de grado de poder 10 y 5, la suma es 15 y el número de actores 2:
el primero normaliza a `(10/15)×2 = 1.333` y el segundo a `(5/15)×2 = 0.667`.

Escribir un test con esos números y otro que compruebe **la propiedad que
importa**: que añadir un tercer actor cambia el normalizado de los dos primeros.
No es un fallo, es el instrumento, y conviene que quede fijado por test para que
nadie lo «arregle» sin darse cuenta.

- [ ] **Step 2: Escribir la vista**

Tabla de actores ordenados por relevancia descendente, con sus tres grados, sus
tres normalizados, el producto y su tipo de Mitchell.

**Encabezando la tabla, el aviso de que los valores son relativos al conjunto**:
que cambian si se añade o se quita un actor. Es el punto que el diseño marca
como imprescindible.

Con la lista vacía o con actores a medias, `<x-matriz-sin-resultados>`.

- [ ] **Step 3: Suite completa y commit**

---

### Task 5: Revisión final

- [ ] **Step 1: Suite completa y build**

```bash
php artisan test && npm run build
```

- [ ] **Step 2: Las dos migraciones contra PostgreSQL**

Como en las ramas anteriores, con un contenedor desechable y sin tocar los
ajenos:

```bash
docker run --rm -d --name turismo-verif-involucrados -e POSTGRES_PASSWORD=verif \
  -e POSTGRES_USER=turismo -e POSTGRES_DB=turismo -p 15434:5432 postgres:16-alpine
```

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=15434 DB_DATABASE=turismo \
DB_USERNAME=turismo DB_PASSWORD=verif \
php -d extension=pdo_pgsql -d extension=pgsql artisan migrate --force
```

Comprobar que los once criterios quedan nullable sin defecto y que los tres
booleanos tienen su `false`. Parar el contenedor **por nombre explícito** y
comprobar que no queda volumen huérfano.

- [ ] **Step 3: Recorrido manual**

Con `jefe@local.test` / `password`: añadir tres actores, dejar uno a medias, ver
que la página de zona dice «3 actores, 1 sin completar», completarlo, validar, y
comprobar en los resultados que el aviso de que los valores son relativos está
visible y que los tipos de Mitchell salen de las casillas y no de las notas.

- [ ] **Step 4: Actualizar el estado del proyecto**

En `docs/ESTADO-PROYECTO.md`: Involucrados implementada, quedan dos matrices, y
la razón por la que el registro tiene ahora un cuarto tipo de entrada.

---

## Fuera de este plan

- **Índice Espacial de Frecuentación**, bloqueado por una fórmula ambigua en su
  hoja original que hay que aclarar con el autor del instrumento.
- **Índice de Concentración Turística**, bloqueado por una decisión de producto:
  si se profundiza la taxonomía del inventario para calcularlo solo, o si es un
  formulario aparte que recuenta lo ya registrado.
- **Reordenar actores** por arrastre.
- **Migrar FIT, FET, Percepción y Potencialidad** a los componentes de criterio
  nuevos.
