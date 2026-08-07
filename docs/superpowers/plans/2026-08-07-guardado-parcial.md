# Guardado parcial de matrices — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Permitir guardar una matriz a medias sin perder el avance, distinguiendo «sin responder» de «valorado como 0», y exigir la matriz completa solo al validarla.

**Architecture:** Las columnas de criterios pasan a `nullable`. La obligatoriedad se decide por el estado destino: `nullable` al guardar borrador, `required` al confirmar. Si falta algún criterio, las columnas calculadas quedan en `null` en vez de promediar sobre datos incompletos.

**Tech Stack:** Laravel 12, PHP 8.2, SQLite (desarrollo y tests), PostgreSQL 16 (producción), PHPUnit 11.

**Depende de:** `docs/superpowers/plans/2026-08-07-pagina-de-zona.md` completado. La Task 7 usa `App\Servicios\EstadoZona`.

## Global Constraints

- **Solo se exige todo al confirmar.** Regla única, sin excepciones por matriz.
- Un criterio sin responder **nunca** entra en un promedio. Una media parcial parece un resultado y miente.
- Las migraciones se prueban en SQLite **y** en PostgreSQL. `change()` recrea la tabla en SQLite y es donde aparecen las sorpresas.
- Ninguna migración escribe datos: son ensanchamientos de tipo. Las filas existentes ya tienen valor en todas las columnas.
- Comentarios en castellano explicando el *por qué*, siguiendo el estilo del repositorio.
- Suite completa (`php artisan test`) en verde antes de cada commit.
- No se toca ningún contenedor Docker.

## Estructura de ficheros

**Crear:**
- `database/migrations/2026_08_08_000001_criterios_nullable_matrices_ponderadas.php`
- `database/migrations/2026_08_08_000002_criterios_nullable_potencialidad.php`
- `tests/Feature/PotencialidadCalculoTest.php` — caracterización, antes de tocar nada.
- `tests/Feature/GuardadoParcialTest.php`

**Modificar:**
- `app/Http/Controllers/Operativo/EvaluacionZonaController.php`
- `app/Http/Controllers/Operativo/MatrizPonderadaController.php`
- `app/Http/Controllers/Operativo/EvaluacionPaisajeController.php` — solo el nombre del hook.
- `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`
- `resources/views/components/select-0-2.blade.php`
- `resources/views/components/select-0-3.blade.php`
- `resources/views/components/select-percepcion.blade.php`
- `resources/views/components/criterio-escala.blade.php`
- `resources/views/components/criterio-pildoras.blade.php`
- Las cinco vistas `*/ponderacion.blade.php` — aviso de matriz incompleta.
- `app/Servicios/EstadoZona.php` — subtítulo «21 de 34 respondidos».

---

### Task 1: Tests de caracterización de Potencialidad

`calcular()` son 120 líneas de redistribución de pesos con cuatro niveles de
anidamiento y **sin un solo test propio**. Antes de tocarla hay que fijar lo que
hace hoy. Estos tests deben pasar **sin modificar ni una línea de producción**.

Si alguno falla en verde inicial, no lo ajustes para que pase: significa que el
comportamiento real no es el que crees, y eso hay que entenderlo antes de seguir.

**Files:**
- Create: `tests/Feature/PotencialidadCalculoTest.php`

**Interfaces:**
- Consumes: `EvaluacionPotencialidadController::$secciones`, `PotencialidadCamposActivos`.
- Produces: red de seguridad para la Task 5. No expone nada.

- [ ] **Step 1: Escribir los tests de caracterización**

Crear `tests/Feature/PotencialidadCalculoTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Http\Controllers\Operativo\EvaluacionPotencialidadController as Ctrl;
use App\Models\EvaluacionPotencialidad;
use App\Models\PotencialidadCamposActivos;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Caracterización: fija lo que calcular() hace HOY, antes de tocarla.
 *
 * No juzga si el comportamiento es correcto —parte de él no lo es, de ahí el
 * cambio—; solo lo congela para que la modificación posterior no mueva nada
 * que no queramos mover.
 */
class PotencialidadCalculoTest extends TestCase
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

    /** @return list<string> todos los nombres de campo del instrumento */
    private function todosLosCampos(): array
    {
        return collect(Ctrl::$secciones)
            ->flatMap(fn(array $campos) => array_keys($campos))
            ->all();
    }

    /** @return list<string> los campos de una sección concreta */
    private function camposDe(string $seccion): array
    {
        return array_keys(Ctrl::$secciones[$seccion]);
    }

    private function url(): string
    {
        return "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad";
    }

    /**
     * Guarda con los campos indicados en $valores y el resto de campos activos
     * enviados explícitamente al valor $relleno.
     */
    private function guardar(array $valores, array $activos, int $relleno = 0): EvaluacionPotencialidad
    {
        $datos = ['campos' => $activos];

        foreach ($activos as $campo) {
            $datos[$campo] = $valores[$campo] ?? $relleno;
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        return EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();
    }

    public function test_todo_al_maximo_con_todos_los_campos_activos_da_dos(): void
    {
        $eval = $this->guardar([], $this->todosLosCampos(), relleno: 2);

        $this->assertEqualsWithDelta(2.0, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->fx_total, 0.0001);
    }

    public function test_todo_a_cero_da_cero(): void
    {
        $eval = $this->guardar([], $this->todosLosCampos(), relleno: 0);

        $this->assertEqualsWithDelta(0.0, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->fx_total, 0.0001);
    }

    /**
     * FX pondera Afluencia 0.40, Marketing 0.30 y Superestructura 0.30.
     * Solo Afluencia al máximo debe dar 2 * 0.40 = 0.80.
     */
    public function test_los_pesos_de_fx_son_40_30_30(): void
    {
        $valores = array_fill_keys($this->camposDe('Afluencia Turística'), 2);

        $eval = $this->guardar($valores, $this->todosLosCampos(), relleno: 0);

        $this->assertEqualsWithDelta(0.80, $eval->fx_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->val_afluencia, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->val_marketing, 0.0001);
    }

    /**
     * Con solo Afluencia activa, su 0.40 se renormaliza a 1.0: el resultado
     * pasa de 0.80 a 2.0. Es la redistribución de pesos, y es lo más fácil de
     * romper sin darse cuenta.
     */
    public function test_desactivar_grupos_renormaliza_los_pesos_de_fx(): void
    {
        $activos = $this->camposDe('Afluencia Turística');
        $valores = array_fill_keys($activos, 2);

        $eval = $this->guardar($valores, $activos);

        $this->assertEqualsWithDelta(2.0, $eval->fx_total, 0.0001);
    }

    /** FN pondera RT 0.40, PT 0.20, TT 0.20 e Infraestructura 0.20. */
    public function test_los_pesos_de_fn_son_40_20_20_20(): void
    {
        $valores = array_fill_keys($this->camposDe('Infraestructura'), 2);

        $eval = $this->guardar($valores, $this->todosLosCampos(), relleno: 0);

        $this->assertEqualsWithDelta(0.40, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->val_infraestructura, 0.0001);
    }

    /** RT es la media de Recursos Naturales y Culturales al 50 % cada uno. */
    public function test_recursos_turisticos_promedia_naturales_y_culturales(): void
    {
        $valores = array_fill_keys($this->camposDe('RN — Cuerpos de Agua'), 2);

        $eval = $this->guardar($valores, $this->todosLosCampos(), relleno: 0);

        // RN tiene 4 subgrupos; solo uno al máximo → val_rn = 2/4 = 0.5
        $this->assertEqualsWithDelta(0.5, $eval->val_recursos_naturales, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->val_recursos_culturales, 0.0001);
        $this->assertEqualsWithDelta(0.25, $eval->val_recursos_turisticos, 0.0001);
    }

    /** Sin ningún recurso cultural activo, RT es RN a secas, sin promediar con 0. */
    public function test_sin_recursos_culturales_activos_rt_es_solo_rn(): void
    {
        $activos = $this->camposDe('RN — Cuerpos de Agua');
        $valores = array_fill_keys($activos, 2);

        $eval = $this->guardar($valores, $activos);

        $this->assertEqualsWithDelta(2.0, $eval->val_recursos_turisticos, 0.0001);
    }

    /**
     * ESTE es el fallo que el cambio va a corregir, congelado tal cual está.
     *
     * Un campo activo que no se envía se guarda como 0 y cuenta en la media:
     * «no lo he mirado» acaba puntuando igual que «lo he mirado y no hay nada».
     * Cuando la Task 5 lo arregle, este test se reescribe con el comportamiento
     * nuevo — a propósito y de forma visible en el diff.
     */
    public function test_comportamiento_actual_un_campo_no_enviado_cuenta_como_cero(): void
    {
        $activos = $this->camposDe('Afluencia Turística');

        $datos = ['campos' => $activos];
        foreach ($activos as $campo) {
            $datos[$campo] = 2;
        }

        // Se omite uno de los cinco campos de la sección.
        unset($datos['dt_at_estadia']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();

        // 4 campos a 2 y uno a 0 → media 1.6, no 2.
        $this->assertEqualsWithDelta(1.6, $eval->val_afluencia, 0.0001);
        $this->assertSame(0, (int) $eval->dt_at_estadia);
    }

    /** Un campo desactivado conserva el valor que tenía guardado. */
    public function test_desactivar_un_campo_conserva_su_valor_anterior(): void
    {
        $todos = $this->todosLosCampos();
        $this->guardar([], $todos, relleno: 2);

        $activos = $this->camposDe('Afluencia Turística');
        $eval = $this->guardar(array_fill_keys($activos, 1), $activos);

        // 'i_transporte' quedó desactivado pero mantiene el 2 anterior.
        $this->assertSame(2, (int) $eval->i_transporte);
    }

    public function test_la_configuracion_de_campos_activos_se_persiste(): void
    {
        $activos = $this->camposDe('Afluencia Turística');
        $this->guardar(array_fill_keys($activos, 2), $activos);

        $config = PotencialidadCamposActivos::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertSame($activos, $config->campos_activos);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que pasan SIN tocar producción**

```bash
php artisan test --filter=PotencialidadCalculoTest
```

Esperado: PASS, 10 tests, con cero cambios en `app/`.

Si alguno falla, **no ajustes el número esperado para que pase**. Abre
`EvaluacionPotencialidadController::calcular()` y averigua qué hace de verdad;
después corrige el test para reflejarlo. El objetivo es congelar la realidad, no
una suposición.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/PotencialidadCalculoTest.php
git commit -m "test(potencialidad): caracteriza el calculo actual antes de cambiarlo"
```

---

### Task 2: Migración — criterios nullable

Ensanchamiento puro. Después de este task nada cambia de comportamiento: la
validación sigue exigiendo todos los criterios. La suite debe seguir en verde.

**Files:**
- Create: `database/migrations/2026_08_08_000001_criterios_nullable_matrices_ponderadas.php`
- Create: `database/migrations/2026_08_08_000002_criterios_nullable_potencialidad.php`

**Interfaces:**
- Consumes: nada.
- Produces: columnas de criterios `nullable` sin defecto en las seis tablas.

- [ ] **Step 1: Escribir la migración de las cinco matrices ponderadas**

Crear `database/migrations/2026_08_08_000001_criterios_nullable_matrices_ponderadas.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permite guardar una matriz a medias.
 *
 * Las columnas estaban NOT NULL con defecto 0, y 0 es una puntuación con
 * significado —«Afectado», «Desfavorable»—. Sin nullable, un criterio sin
 * responder puntuaría como lo peor. Por eso esto no es cosmética: es lo que
 * impide que «no contestado» se confunda con «pésimo».
 *
 * Las columnas se escriben literalmente en vez de derivarlas de las clases de
 * App\Matrices: una migración es un registro histórico congelado, y regenerar
 * esas clases no debe cambiar lo que hizo una migración ya aplicada.
 *
 * Es un ensanchamiento: las filas existentes tienen valor en todas las
 * columnas, así que no hay datos que rellenar ni riesgo de pérdida.
 */
return new class extends Migration
{
    /** tabla => columnas de criterio */
    private function objetivos(): array
    {
        return [
            'evaluaciones_fit' => [
                'recursos_culturales', 'recursos_naturales',
                'atractivos_manifestaciones', 'atractivos_sitios',
                'prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza',
                'productos_territoriales',
                'infraestructura_basica', 'infraestructura_apoyo',
                'facilidades_senaletica', 'facilidades_recepcion',
                'facilidades_interpretacion', 'facilidades_senderos',
                'facilidades_estacionamientos', 'facilidades_campamentos',
                'facilidades_miradores', 'facilidades_sanitarios',
            ],
            'evaluaciones_fet' => [
                'demanda_flujos', 'demanda_estadia',
                'super_institucionalidad', 'super_organizacion', 'super_planificacion',
                'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing',
            ],
        ];
    }

    public function up(): void
    {
        foreach ($this->objetivos() as $tabla => $columnas) {
            Schema::table($tabla, function (Blueprint $t) use ($columnas) {
                foreach ($columnas as $columna) {
                    $t->tinyInteger($columna)->nullable()->default(null)->change();
                }
            });
        }

        // Percepción, Paisaje y Valoración Territorial declaran sus criterios en
        // clases generadas; aquí se leen los nombres de columna del esquema real
        // para no repetir 71 nombres a mano y arriesgar una errata silenciosa.
        foreach ($this->tablasGeneradas() as $tabla => $prefijos) {
            $columnas = array_values(array_filter(
                Schema::getColumnListing($tabla),
                fn(string $c) => $this->esCriterio($c, $prefijos)
            ));

            Schema::table($tabla, function (Blueprint $t) use ($columnas) {
                foreach ($columnas as $columna) {
                    $t->tinyInteger($columna)->nullable()->default(null)->change();
                }
            });
        }
    }

    public function down(): void
    {
        // Volver a NOT NULL exigiría inventar un valor para las filas con nulos,
        // y ese valor sería una puntuación falsa. Se deja irreversible a
        // propósito: revertir esto es restaurar una copia, no correr un down().
        throw new RuntimeException(
            'Migración irreversible: revertirla inventaría puntuaciones donde hay huecos.'
        );
    }

    /** tabla => prefijos de columna que son criterios */
    private function tablasGeneradas(): array
    {
        return [
            'evaluaciones_percepcion'              => ['ds', 'pl', 'pe', 'no'],
            'evaluaciones_paisaje'                 => ['ep', 'pn', 'pc', 'iv', 'cp'],
            'evaluaciones_valoracion_territorial'  => ['ct', 'uc'],
        ];
    }

    /**
     * Un criterio empieza por un prefijo de categoría y NO es una columna
     * calculada: los promedios y totales conservan su tipo decimal.
     */
    private function esCriterio(string $columna, array $prefijos): bool
    {
        if (str_ends_with($columna, '_promedio') || str_ends_with($columna, '_total')) {
            return false;
        }

        foreach ($prefijos as $prefijo) {
            if (str_starts_with($columna, $prefijo . '_')) {
                return true;
            }
        }

        return false;
    }
};
```

- [ ] **Step 2: Comprobar qué columnas va a tocar antes de aplicarla**

La detección por prefijo es cómoda pero puede coger de más o de menos. Verificar
la lista antes de ejecutar nada:

```bash
php artisan tinker --execute="
foreach ([
  'evaluaciones_percepcion' => ['ds','pl','pe','no'],
  'evaluaciones_paisaje' => ['ep','pn','pc','iv','cp'],
  'evaluaciones_valoracion_territorial' => ['ct','uc'],
] as \$t => \$p) {
  \$cols = array_filter(Schema::getColumnListing(\$t), function(\$c) use (\$p) {
    if (str_ends_with(\$c,'_promedio') || str_ends_with(\$c,'_total')) return false;
    foreach (\$p as \$x) if (str_starts_with(\$c, \$x.'_')) return true;
    return false;
  });
  echo \$t.': '.count(\$cols).PHP_EOL;
}
"
```

Esperado: `evaluaciones_percepcion: 16`, `evaluaciones_paisaje: 34`,
`evaluaciones_valoracion_territorial: 21`.

Si algún número no cuadra, **no sigas**: ajusta `esCriterio()` hasta que
coincida. Una columna calculada convertida a `tinyInteger` perdería decimales.

- [ ] **Step 3: Escribir la migración de Potencialidad**

Crear `database/migrations/2026_08_08_000002_criterios_nullable_potencialidad.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Potencialidad: 156 criterios a nullable.
 *
 * Aquí el problema no era hipotético. La validación no exigía nada, el
 * formulario preseleccionaba «0 - Nulo» y los campos ausentes entraban como 0,
 * así que un criterio que nadie miró bajaba la media de su grupo sin aviso.
 *
 * Los val_* calculados también pasan a nullable: mientras falte algún criterio
 * activo no hay resultado que enseñar, y un 0 ahí sería otra mentira.
 */
return new class extends Migration
{
    public function up(): void
    {
        $criterios = array_values(array_filter(
            Schema::getColumnListing('evaluaciones_potencialidad'),
            fn(string $c) => ! str_starts_with($c, 'val_')
                && ! in_array($c, ['id', 'zona_id', 'user_id', 'estado', 'fn_total', 'fx_total', 'created_at', 'updated_at'], true)
        ));

        Schema::table('evaluaciones_potencialidad', function (Blueprint $t) use ($criterios) {
            foreach ($criterios as $columna) {
                $t->tinyInteger($columna)->nullable()->default(null)->change();
            }
        });

        $calculados = array_values(array_filter(
            Schema::getColumnListing('evaluaciones_potencialidad'),
            fn(string $c) => str_starts_with($c, 'val_') || in_array($c, ['fn_total', 'fx_total'], true)
        ));

        Schema::table('evaluaciones_potencialidad', function (Blueprint $t) use ($calculados) {
            foreach ($calculados as $columna) {
                $t->decimal($columna, 8, 4)->nullable()->default(null)->change();
            }
        });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Migración irreversible: revertirla inventaría puntuaciones donde hay huecos.'
        );
    }
};
```

- [ ] **Step 4: Verificar el recuento de columnas de Potencialidad**

```bash
php artisan tinker --execute="
\$cols = Schema::getColumnListing('evaluaciones_potencialidad');
\$fijas = ['id','zona_id','user_id','estado','fn_total','fx_total','created_at','updated_at'];
\$crit = array_filter(\$cols, fn(\$c) => !str_starts_with(\$c,'val_') && !in_array(\$c,\$fijas,true));
echo 'criterios: '.count(\$crit).PHP_EOL;
echo 'calculados: '.count(array_filter(\$cols, fn(\$c)=>str_starts_with(\$c,'val_'))).PHP_EOL;
"
```

Esperado: `criterios: 156`. Si no, revisar la lista de columnas fijas.

**Antes de aplicar la migración de `decimal`**, comprobar la precisión real que
tienen esas columnas hoy:

```bash
php artisan tinker --execute="print_r(DB::select(\"PRAGMA table_info(evaluaciones_potencialidad)\"));" | grep -i "val_rn_litoral\|fn_total"
```

Ajustar `decimal($columna, 8, 4)` a lo que declare la migración original
(`database/migrations/2025_12_07_000001_create_evaluaciones_potencialidad_table.php`)
para no cambiar la precisión de paso.

- [ ] **Step 5: Aplicar en SQLite y ejecutar la suite**

```bash
php artisan migrate
php artisan test
```

Esperado: PASS. **Nada de comportamiento ha cambiado todavía**: la validación
sigue exigiendo todos los criterios. Si algún test falla aquí, la migración ha
alterado un tipo que no debía.

- [ ] **Step 6: Verificar en PostgreSQL**

SQLite recrea la tabla al hacer `change()` y PostgreSQL emite `ALTER COLUMN`.
Los dos caminos hay que probarlos porque fallan de forma distinta.

```bash
php artisan migrate:fresh --database=pgsql --force
```

(Requiere `DB_CONNECTION=pgsql` configurado con la base de desarrollo. Si no hay
PostgreSQL disponible en local, dejar este paso anotado como pendiente **de forma
visible** y no darlo por hecho.)

- [ ] **Step 7: Commit**

```bash
git add database/migrations/2026_08_08_000001_criterios_nullable_matrices_ponderadas.php \
        database/migrations/2026_08_08_000002_criterios_nullable_potencialidad.php
git commit -m "feat(db): criterios nullable para permitir guardar matrices a medias"
```

---

### Task 3: Obligatoriedad por estado en la clase base

**Files:**
- Modify: `app/Http/Controllers/Operativo/EvaluacionZonaController.php`
- Modify: `app/Http/Controllers/Operativo/MatrizPonderadaController.php`
- Modify: `app/Http/Controllers/Operativo/EvaluacionPaisajeController.php`
- Modify: `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php` (solo la firma)
- Test: `tests/Feature/GuardadoParcialTest.php`

**Interfaces:**
- Consumes: `criterios()`, `escala()`, `calcular()` de cada subclase (sin cambios).
- Produces:
  - `EvaluacionZonaController::prepararDatos(Request $r, $zonaId, ?Model $actual, string $estado): array` — **firma nueva, cuarto parámetro**.
  - `EvaluacionZonaController::estaCompleta(array $datos): bool` — por defecto `true`.
  - `EvaluacionZonaController::mensajeIncompleto(array $datos): string`
  - `MatrizPonderadaController::reglaValor(): string` — regla del valor **sin** obligatoriedad.
  - `MatrizPonderadaController::reglaCriterio(string $estado): string`
  - `MatrizPonderadaController::columnasCalculadasVacias(): array`
  - `MatrizPonderadaController::respondidos(array $valores): int`

- [ ] **Step 1: Escribir los tests de guardado parcial**

Crear `tests/Feature/GuardadoParcialTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Matrices\Paisaje;
use App\Models\EvaluacionPaisaje;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuardadoParcialTest extends TestCase
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

    private function url(string $sufijo = ''): string
    {
        return "/operativo/zona/{$this->zona->id}/paisaje{$sufijo}";
    }

    private function todosEn(int $valor): array
    {
        return array_fill_keys(array_keys(Paisaje::todos()), $valor);
    }

    public function test_se_guarda_un_borrador_con_criterios_en_blanco(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia'], $datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertSame('borrador', $eval->estado);
        $this->assertNull($eval->pn_geologia);
        $this->assertNull($eval->cp_conurbaciones);
        $this->assertSame(3, (int) $eval->ep_cambios_tiempo);
    }

    /**
     * El test que distingue el fallo del arreglo: un 0 elegido a conciencia es
     * un dato, un hueco no lo es, y no pueden guardarse igual.
     */
    public function test_un_cero_respondido_no_se_confunde_con_un_hueco(): void
    {
        $datos = $this->todosEn(3);
        $datos['pn_geologia'] = 0;
        unset($datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertSame(0, (int) $eval->pn_geologia);
        $this->assertNull($eval->cp_conurbaciones);
    }

    public function test_con_criterios_pendientes_no_hay_resultado(): void
    {
        $datos = $this->todosEn(5);
        unset($datos['pn_geologia']);

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertNull($eval->paisaje_total);
        $this->assertNull($eval->pn_promedio);
        $this->assertNull($eval->ep_promedio);
    }

    public function test_completar_la_matriz_calcula_el_resultado(): void
    {
        $parcial = $this->todosEn(5);
        unset($parcial['pn_geologia']);
        $this->actingAs($this->jefe)->post($this->url(), $parcial);

        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(5))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertEqualsWithDelta(5.0, (float) $eval->paisaje_total, 0.0001);
    }

    public function test_confirmar_con_huecos_se_rechaza(): void
    {
        $datos = $this->todosEn(3) + ['accion_estado' => 'confirmado'];
        unset($datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('cp_conurbaciones');

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }

    public function test_confirmar_completa_sigue_funcionando(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(5) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $eval = EvaluacionPaisaje::firstOrFail();

        $this->assertSame('confirmado', $eval->estado);
        $this->assertEqualsWithDelta(5.0, (float) $eval->paisaje_total, 0.0001);
    }

    /** La escala no contigua sigue vigente: 0, 3 y 5, o nada. */
    public function test_un_valor_fuera_de_escala_se_rechaza_tambien_en_borrador(): void
    {
        $datos = $this->todosEn(3);
        $datos['pn_geologia'] = 4;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('pn_geologia');

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }

    public function test_el_mensaje_dice_cuantos_criterios_llevas(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia'], $datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHas('success', fn(string $m) => str_contains($m, '32 de 34'));
    }

    /** Un borrador incompleto vuelve al formulario, no a unos resultados vacíos. */
    public function test_un_borrador_incompleto_no_redirige_a_resultados(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia']);

        $this->actingAs($this->jefe)
            ->from($this->url())
            ->post($this->url(), $datos)
            ->assertRedirect($this->url());
    }

    public function test_el_equipo_tambien_guarda_a_medias(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $datos = $this->todosEn(3);
        unset($datos['pn_geologia']);

        $this->actingAs($equipo)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPaisaje::value('estado'));
        $this->assertNull(EvaluacionPaisaje::value('pn_geologia'));
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=GuardadoParcialTest
```

Esperado: FAIL — los criterios siguen siendo `required`, así que
`assertSessionHasNoErrors()` falla en la mayoría.

- [ ] **Step 3: Cambiar la clase base de nivel 1**

En `app/Http/Controllers/Operativo/EvaluacionZonaController.php`, cambiar la
firma abstracta y `update()`:

```php
    /**
     * Valida la petición y devuelve las columnas a persistir, sin user_id ni
     * estado, de los que se encarga esta clase.
     *
     * Recibe el estado destino porque de él depende la obligatoriedad: en
     * borrador se admiten huecos, al confirmar no.
     *
     * @return array<string, mixed>
     */
    abstract protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array;

    /** ¿Están todos los criterios respondidos? Las matrices lo afinan. */
    protected function estaCompleta(array $datos): bool
    {
        return true;
    }

    protected function mensajeIncompleto(array $datos): string
    {
        return 'Borrador guardado. Faltan criterios por responder.';
    }

    public function update(Request $request, $zonaId)
    {
        $user   = Auth::user();
        $modelo = $this->modelo();

        $actual = $modelo::where('zona_id', $zonaId)->first();

        if ($actual && $actual->estado === 'confirmado' && $user->esEquipo()) {
            return back()->with('error', $this->mensajeCerrada());
        }

        $request->validate(['accion_estado' => 'nullable|in:borrador,confirmado']);

        // El estado se decide ANTES de validar los criterios: es lo que
        // determina si se exigen todos o se admite un borrador a medias.
        // Solo el Jefe de Zona puede confirmar; el equipo siempre borrador.
        $estado = $user->esJefe()
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        $datos = $this->prepararDatos($request, $zonaId, $actual, $estado);

        $modelo::updateOrCreate(
            ['zona_id' => $zonaId],
            $datos + ['user_id' => $user->id, 'estado' => $estado]
        );

        $this->despuesDeGuardar($zonaId, $user);

        // Una matriz incompleta no tiene resultados que enseñar: se vuelve al
        // formulario, que es donde el usuario sigue trabajando.
        if (! $this->estaCompleta($datos)) {
            return back()->with('success', $this->mensajeIncompleto($datos));
        }

        return redirect()
            ->route($this->rutaResultados(), $zonaId)
            ->with('success', $this->mensajeExito($estado, $datos));
    }
```

- [ ] **Step 4: Cambiar la clase base de nivel 2**

Reemplazar en `app/Http/Controllers/Operativo/MatrizPonderadaController.php` desde
`protected function reglaCriterio()` hasta el final de la clase:

```php
    /**
     * Regla del valor, sin obligatoriedad.
     *
     * Por defecto es el rango continuo que declara escala(). Una matriz cuya
     * escala no sea contigua —Paisaje admite 0, 3 y 5, nada más— la
     * sobreescribe: con min/max se colarían el 1, el 2 y el 4.
     */
    protected function reglaValor(): string
    {
        [$min, $max] = $this->escala();

        return "integer|min:{$min}|max:{$max}";
    }

    /**
     * Solo se exige todo al confirmar.
     *
     * Con 34 criterios en Paisaje y 156 en Potencialidad, perder el avance al
     * cerrar la pestaña era lo que más dolía al usar esto de verdad.
     */
    protected function reglaCriterio(string $estado): string
    {
        $obligatoriedad = $estado === 'confirmado' ? 'required' : 'nullable';

        return "{$obligatoriedad}|{$this->reglaValor()}";
    }

    /**
     * Columnas calculadas a partir de las calificaciones validadas.
     *
     * @param array<string, int> $valores
     * @return array<string, float>
     */
    abstract protected function calcular(array $valores): array;

    /** Todos los campos, aplanados en el orden de declaración. */
    protected function campos(): array
    {
        return array_merge(...array_values($this->criterios()));
    }

    protected function estaCompleta(array $datos): bool
    {
        foreach ($this->campos() as $campo) {
            if (($datos[$campo] ?? null) === null) {
                return false;
            }
        }

        return true;
    }

    protected function respondidos(array $valores): int
    {
        return count(array_filter(
            $this->campos(),
            fn(string $campo) => ($valores[$campo] ?? null) !== null
        ));
    }

    protected function mensajeIncompleto(array $datos): string
    {
        $total = count($this->campos());

        return "Borrador guardado. Llevas {$this->respondidos($datos)} de {$total} criterios.";
    }

    /**
     * Los nombres de las columnas que calcula esta matriz, todas a null.
     *
     * Se obtienen llamando a calcular() con ceros en lugar de declararlas en
     * una lista aparte: una lista duplicada se desincroniza en cuanto alguien
     * añade un promedio nuevo, y nada lo detectaría.
     */
    protected function columnasCalculadasVacias(): array
    {
        $ceros = array_fill_keys($this->campos(), 0);

        return array_fill_keys(array_keys($this->calcular($ceros)), null);
    }

    protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array
    {
        $regla = $this->reglaCriterio($estado);

        $reglas = [];
        foreach ($this->campos() as $campo) {
            $reglas[$campo] = $regla;
        }

        $request->validate($reglas);

        // validate() no devuelve las claves ausentes, y aquí hacen falta todas:
        // un campo que el usuario borró tiene que llegar como null a la base
        // para que updateOrCreate lo vacíe en vez de conservar el valor viejo.
        $valores = [];
        foreach ($this->campos() as $campo) {
            $bruto = $request->input($campo);
            $valores[$campo] = $bruto === null ? null : (int) $bruto;
        }

        return $valores + ($this->estaCompleta($valores)
            ? $this->calcular($valores)
            : $this->columnasCalculadasVacias());
    }
}
```

- [ ] **Step 5: Adaptar el override de Paisaje**

En `app/Http/Controllers/Operativo/EvaluacionPaisajeController.php`, sustituir el
método `reglaCriterio()` por:

```php
    /**
     * El instrumento admite 0, 3 y 5, no cualquier valor entre 0 y 5: con la
     * regla de rango por defecto se colarían el 1, el 2 y el 4.
     */
    protected function reglaValor(): string
    {
        return 'integer|in:' . implode(',', Paisaje::VALORES);
    }
```

Es el único override de este hook en todo el proyecto; comprobarlo:

```bash
grep -rn "reglaCriterio" app/
```

Esperado: solo las dos definiciones de `MatrizPonderadaController`.

- [ ] **Step 6: Adaptar la firma de Potencialidad**

En `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`, cambiar
solo la firma para que case con la clase base (la lógica se cambia en la Task 5):

```php
    protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array
```

- [ ] **Step 7: Ejecutar los tests nuevos**

```bash
php artisan test --filter=GuardadoParcialTest
```

Esperado: PASS, 10 tests.

- [ ] **Step 8: Ejecutar la suite completa**

```bash
php artisan test
```

Esperado: PASS. Los tests antiguos que comprobaban
`test_no_se_guarda_con_criterios_sin_responder` en `PaisajeTest` y
`ValoracionTerritorialTest` **van a fallar**: ese comportamiento ha cambiado a
propósito. Reescribirlos así, en `PaisajeTest`:

```php
    public function test_no_se_confirma_con_criterios_sin_responder(): void
    {
        $datos = $this->todosEn(3) + ['accion_estado' => 'confirmado'];
        unset($datos['cp_conurbaciones']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('cp_conurbaciones');

        $this->assertDatabaseCount('evaluaciones_paisaje', 0);
    }
```

Aplicar el equivalente en `ValoracionTerritorialTest` con su propio campo.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Operativo tests/Feature
git commit -m "feat(matrices): guardado parcial en borrador, obligatoriedad al confirmar"
```

---

### Task 4: Vistas de resultados con matriz incompleta

Con totales en `null`, las cinco vistas de ponderación harían
`number_format(null)` y pintarían un 0,00 falso. Cada una necesita el aviso.

**Files:**
- Modify: `resources/views/operativo/evaluacion_paisaje/ponderacion.blade.php`
- Modify: `resources/views/operativo/evaluacion_valoracion_territorial/ponderacion.blade.php`
- Modify: `resources/views/operativo/evaluacion_percepcion/ponderacion.blade.php`
- Modify: `resources/views/operativo/evaluacion_fit/ponderacion.blade.php`
- Modify: `resources/views/operativo/evaluacion_fet/ponderacion.blade.php`
- Test: ampliar `tests/Feature/GuardadoParcialTest.php`

**Interfaces:**
- Consumes: la columna total de cada matriz (`paisaje_total`, `ct_total`,
  `percepcion_total`, `fit`, `fet`).
- Produces: nada nuevo.

- [ ] **Step 1: Añadir el test**

Añadir a `tests/Feature/GuardadoParcialTest.php`:

```php
    public function test_los_resultados_avisan_si_la_matriz_esta_incompleta(): void
    {
        $datos = $this->todosEn(3);
        unset($datos['pn_geologia']);
        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('todavía no está completa')
            ->assertDontSee('0.00');
    }
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=test_los_resultados_avisan_si_la_matriz_esta_incompleta
```

Esperado: FAIL.

- [ ] **Step 3: Ampliar la guarda de Paisaje**

En `resources/views/operativo/evaluacion_paisaje/ponderacion.blade.php`, línea 12,
cambiar la condición y el texto:

```blade
    @if(! $evaluacion || $evaluacion->paisaje_total === null)
        {{-- El admin puede abrir una zona sin la matriz, y ahora también puede
             haberla a medias: sin resultado que enseñar, cualquier número que
             pintáramos aquí sería inventado. --}}
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-6 rounded shadow">
                    <h3 class="font-bold text-lg mb-2">Matriz de Paisaje sin resultados</h3>
                    <p class="text-base">
                        La Matriz de Paisaje de <strong>{{ $zona->nombre }}</strong>
                        todavía no está completa, así que no hay resultado que calcular.
                        Responde los criterios que faltan y volverá a aparecer.
                    </p>
                </div>
                <div class="mt-6 text-center">
                    <a href="{{ $readonly ? route('admin.zonas.index') : route('operativo.evaluacion_paisaje.edit', $zona->id) }}"
                       class="inline-block px-5 py-2 bg-gray-200 text-black font-bold rounded-lg hover:bg-gray-400 shadow">
                        {{ $readonly ? 'Volver' : 'Ir al formulario' }}
                    </a>
                </div>
            </div>
        </div>
    @else
```

- [ ] **Step 4: Aplicar la misma guarda a las otras cuatro vistas**

Para cada una, envolver el contenido en la misma condición con su columna total y
su nombre. Las columnas son:

| Vista | Columna total | Texto del título |
|---|---|---|
| `evaluacion_valoracion_territorial/ponderacion.blade.php` | `ct_total` | Valoración Territorial sin resultados |
| `evaluacion_percepcion/ponderacion.blade.php` | `percepcion_total` | Matriz de Percepción sin resultados |
| `evaluacion_fit/ponderacion.blade.php` | `fit` | Evaluación FIT sin resultados |
| `evaluacion_fet/ponderacion.blade.php` | `fet` | Evaluación FET sin resultados |

En FIT y FET la variable de la evaluación se llama `$fit` y `$fet`
respectivamente, no `$evaluacion`; comprobarlo antes de escribir la condición:

```bash
grep -n "compact(" app/Http/Controllers/Operativo/EvaluacionFitController.php \
                   app/Http/Controllers/Operativo/EvaluacionFetController.php
```

- [ ] **Step 5: Ejecutar la suite completa**

```bash
php artisan test
```

Esperado: PASS.

- [ ] **Step 6: Commit**

```bash
git add resources/views/operativo tests/Feature/GuardadoParcialTest.php
git commit -m "feat(resultados): aviso en vez de ceros falsos cuando la matriz esta a medias"
```

---

### Task 5: Guardado parcial en Potencialidad

Aquí se arregla el fallo vivo. Los tests de caracterización de la Task 1 son la
red: si algo que no debía moverse se mueve, saltan.

**Files:**
- Modify: `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`
- Modify: `tests/Feature/PotencialidadCalculoTest.php` — un test cambia a propósito.
- Test: ampliar `tests/Feature/PotencialidadCalculoTest.php`

**Interfaces:**
- Consumes: `estaCompleta()`, `mensajeIncompleto()` de `EvaluacionZonaController`.
- Produces: `EvaluacionPotencialidadController::camposActivosDe($zonaId): array`

- [ ] **Step 1: Escribir los tests del comportamiento nuevo**

Añadir a `tests/Feature/PotencialidadCalculoTest.php`:

```php
    /**
     * El arreglo del fallo que congelaba
     * test_comportamiento_actual_un_campo_no_enviado_cuenta_como_cero.
     */
    public function test_un_campo_sin_responder_no_baja_la_media(): void
    {
        $activos = $this->camposDe('Afluencia Turística');

        $datos = ['campos' => $activos];
        foreach ($activos as $campo) {
            $datos[$campo] = 2;
        }
        unset($datos['dt_at_estadia']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();

        // Cuatro campos a 2 y uno sin responder: la media de los respondidos
        // es 2, no 1.6. El hueco no puntúa.
        $this->assertEqualsWithDelta(2.0, $eval->val_afluencia, 0.0001);
        $this->assertNull($eval->dt_at_estadia);
    }

    public function test_un_cero_explicito_si_baja_la_media(): void
    {
        $activos = $this->camposDe('Afluencia Turística');

        $datos = ['campos' => $activos];
        foreach ($activos as $campo) {
            $datos[$campo] = 2;
        }
        $datos['dt_at_estadia'] = 0;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertEqualsWithDelta(1.6, $eval->val_afluencia, 0.0001);
        $this->assertSame(0, (int) $eval->dt_at_estadia);
    }

    public function test_un_grupo_entero_sin_responder_no_cuenta(): void
    {
        $activos = array_merge(
            $this->camposDe('Afluencia Turística'),
            $this->camposDe('Marketing Turístico'),
        );

        $datos = ['campos' => $activos];
        foreach ($this->camposDe('Afluencia Turística') as $campo) {
            $datos[$campo] = 2;
        }
        // Marketing queda activo pero entero sin responder.

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();

        // Con Marketing sin responder, FX no está completo: no hay total.
        $this->assertNull($eval->fx_total);
        $this->assertNull($eval->val_marketing);
        $this->assertEqualsWithDelta(2.0, $eval->val_afluencia, 0.0001);
    }

    public function test_confirmar_con_campos_activos_en_blanco_se_rechaza(): void
    {
        $activos = $this->camposDe('Afluencia Turística');

        $datos = ['campos' => $activos, 'accion_estado' => 'confirmado'];
        foreach ($activos as $campo) {
            $datos[$campo] = 2;
        }
        unset($datos['dt_at_estadia']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('dt_at_estadia');

        $this->assertDatabaseCount('evaluaciones_potencialidad', 0);
    }

    public function test_confirmar_completa_calcula_los_totales(): void
    {
        $activos = $this->camposDe('Afluencia Turística');

        $datos = ['campos' => $activos, 'accion_estado' => 'confirmado'];
        foreach ($activos as $campo) {
            $datos[$campo] = 2;
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertSame('confirmado', $eval->estado);
        $this->assertEqualsWithDelta(2.0, $eval->fx_total, 0.0001);
    }
```

- [ ] **Step 2: Eliminar el test que congelaba el fallo**

Borrar `test_comportamiento_actual_un_campo_no_enviado_cuenta_como_cero` de
`tests/Feature/PotencialidadCalculoTest.php`. Su sustituto es
`test_un_campo_sin_responder_no_baja_la_media`, y el diff debe enseñar el cambio
de comportamiento a las claras.

- [ ] **Step 3: Ejecutar y verificar que los nuevos fallan**

```bash
php artisan test --filter=PotencialidadCalculoTest
```

Esperado: FAIL en los cinco nuevos; PASS en los de caracterización que no
cambian.

- [ ] **Step 4: Cambiar prepararDatos**

En `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`,
reemplazar `prepararDatos()` por:

```php
    protected function prepararDatos(Request $request, $zonaId, ?Model $actual, string $estado): array
    {
        $user = Auth::user();

        // La selección de campos se valida contra la lista blanca antes de nada:
        // sus valores se usan como claves de reglas de validación y se serializan
        // a la columna JSON, así que no puede entrar texto arbitrario.
        $request->validate([
            'campos'   => 'nullable|array',
            'campos.*' => ['string', Rule::in($this->getAllCampos())],
        ]);

        $esJefe = $user->esJefe();

        if ($esJefe) {
            $camposActivos = $request->input('campos', []);
        } else {
            // Equipo: conservar la configuración actual del Jefe
            $camposActivos = $this->camposActivosDe($zonaId);
        }

        // Solo se exige responderlo todo al confirmar.
        $obligatoriedad = $estado === 'confirmado' ? 'required' : 'nullable';

        $reglas = [];
        foreach ($camposActivos as $campo) {
            $reglas[$campo] = "{$obligatoriedad}|integer|min:0|max:2";
        }
        $request->validate($reglas);

        // Todo validado: recién ahora se persiste la configuración, para que un
        // error de validación no deje la selección de campos ya modificada.
        if ($esJefe) {
            PotencialidadCamposActivos::updateOrCreate(
                ['zona_id' => $zonaId],
                ['campos_activos' => $camposActivos]
            );
        }

        // Un campo activo sin responder entra como null, no como 0: con 156
        // criterios, «no lo he mirado» puntuando como «Nulo» hundía la media de
        // su grupo sin que nadie lo notara. Los desactivados conservan lo que
        // ya tuvieran, por si vuelven a activarse.
        $valores = [];
        foreach ($this->getAllCampos() as $campo) {
            if (! in_array($campo, $camposActivos, true)) {
                $valores[$campo] = $actual->$campo ?? null;
                continue;
            }

            $bruto = $request->input($campo);
            $valores[$campo] = $bruto === null ? null : (int) $bruto;
        }

        $this->camposActivosEnMemoria = $camposActivos;

        return $valores + $this->calcular($valores, $camposActivos);
    }

    /** Campos activos guardados para la zona, o todos si nunca se configuró. */
    private function camposActivosDe($zonaId): array
    {
        $config = PotencialidadCamposActivos::where('zona_id', $zonaId)->first();

        return $config ? $config->campos_activos : $this->getAllCampos();
    }
```

- [ ] **Step 5: Añadir el estado de completitud**

Añadir a `EvaluacionPotencialidadController`, justo después de `prepararDatos()`:

```php
    /**
     * Campos activos de la última llamada a prepararDatos().
     *
     * estaCompleta() se llama después de guardar y necesita saber qué campos
     * eran obligatorios en ESA petición, no los que hubiera en base antes.
     *
     * @var list<string>|null
     */
    private ?array $camposActivosEnMemoria = null;

    protected function estaCompleta(array $datos): bool
    {
        foreach ($this->camposActivosEnMemoria ?? [] as $campo) {
            if (($datos[$campo] ?? null) === null) {
                return false;
            }
        }

        return true;
    }

    protected function mensajeIncompleto(array $datos): string
    {
        $activos = $this->camposActivosEnMemoria ?? [];

        $respondidos = count(array_filter(
            $activos,
            fn(string $campo) => ($datos[$campo] ?? null) !== null
        ));

        return "Borrador guardado. Llevas {$respondidos} de " . count($activos) . ' criterios activos.';
    }
```

- [ ] **Step 6: Cambiar el cálculo para que ignore los huecos**

En `calcular()`, cambiar la clausura `$avg` y añadir la comprobación de grupo
respondido:

```php
        // Un campo sin responder no entra en el promedio. isset() no vale:
        // un null está declarado pero no respondido, y un 0 sí es una respuesta.
        $avg = function(array $candidatos) use ($v, $camposActivos): ?float {
            $respondidos = array_filter(
                $candidatos,
                fn($c) => in_array($c, $camposActivos, true) && ($v[$c] ?? null) !== null
            );

            if ($respondidos === []) {
                return null;
            }

            return array_sum(array_map(fn($c) => (float) $v[$c], $respondidos)) / count($respondidos);
        };

        // Un grupo cuenta si tiene campos activos Y alguno respondido.
        $hasCampos = fn(array $lista) => $avg($lista) !== null;
```

Con `$avg` devolviendo `null`, las sumas posteriores tienen que filtrarlo. En
cada bloque que hoy hace `array_filter([...], fn($v) => $v !== null)`, la
condición ya sirve. Los que hacen `empty($grupos) ? 0 : …` pasan a devolver
`null`:

```php
        $val_rn = empty($rn_grupos) ? null : array_sum($rn_grupos) / count($rn_grupos);
```

Aplicar el mismo cambio a `$val_rc`, `$val_pt`, y a los cuatro totales
`$fn_total` y `$fx_total`, que pasan a inicializarse a `null` y solo se suman si
hay algún componente:

```php
        $fn_total = $fn_pesos === [] ? null : 0.0;
        // …y cada `if (isset($fn_pesos['xx']))` añade solo cuando su val_ no es null:
        if (isset($fn_pesos['rt']) && $val_rt !== null) {
            $fn_total += $val_rt * ($fn_pesos['rt'] / $fn_sum_pesos);
        }
```

Si algún componente activo está sin responder, `estaCompleta()` ya devolvió
`false` y `update()` no llega a mostrar resultados, así que un total parcial
nunca se enseña. Pero se guarda tal cual para no perder el trabajo.

- [ ] **Step 7: Ejecutar los tests de Potencialidad**

```bash
php artisan test --filter=PotencialidadCalculoTest
```

Esperado: PASS, 14 tests. **Los de caracterización que no cambiaron deben seguir
en verde**: si alguno se ha movido, la redistribución de pesos se ha roto y hay
que averiguar por qué antes de continuar.

- [ ] **Step 8: Suite completa**

```bash
php artisan test
```

Esperado: PASS.

- [ ] **Step 9: Commit**

```bash
git add app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php \
        tests/Feature/PotencialidadCalculoTest.php
git commit -m "fix(potencialidad): un criterio sin responder ya no puntua como Nulo"
```

---

### Task 6: Formularios que distinguen el vacío

**Files:**
- Modify: `resources/views/components/select-0-2.blade.php`
- Modify: `resources/views/components/select-0-3.blade.php`
- Modify: `resources/views/components/select-percepcion.blade.php`
- Modify: `resources/views/components/criterio-escala.blade.php`
- Modify: `resources/views/components/criterio-pildoras.blade.php`

**Interfaces:**
- Consumes: `$val` (puede ser `null`).
- Produces: un `<option value="">` seleccionado cuando el valor es `null`.

- [ ] **Step 1: Cambiar select-0-3**

Reemplazar `resources/views/components/select-0-3.blade.php`:

```blade
@props(['label', 'name', 'val', 'disabled' => false])

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full text-base border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- Sin responder tiene que ser distinguible de un 0 elegido a
             conciencia: 0 es «Nulo», una valoración, no un hueco. Antes este
             desplegable preseleccionaba 0 y los dos casos se guardaban igual. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        <option value="0" @selected($val === 0)>0 - Nulo</option>
        <option value="1" @selected($val === 1)>1 - Bajo</option>
        <option value="2" @selected($val === 2)>2 - Medio</option>
        <option value="3" @selected($val === 3)>3 - Alto</option>
    </select>
    @error($name)
        <span class="text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
```

Ojo con el tipo: `$val` llega de la base como `int` o `null`, pero de un
`old()` llega como `string`. Normalizarlo en el componente antes de comparar:

```blade
@php $val = $val === null || $val === '' ? null : (int) $val; @endphp
```

Colocarlo justo después de `@props`.

- [ ] **Step 2: Cambiar select-0-2**

Es el que usa Potencialidad, o sea el de los 156 campos: el que más importa.
Reemplazar `resources/views/components/select-0-2.blade.php`:

```blade
@props(['label' => null, 'name', 'val', 'disabled' => false])

@php
    // De la base llega int o null; de old() llega string. Sin normalizar, la
    // comparación estricta de @selected fallaría al repintar tras un error.
    $val = ($val === null || $val === '') ? null : (int) $val;
@endphp

<div class="{{ $label ? 'mb-3' : '' }}">
    @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500 text-sm"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- «Sin responder» tiene que ser distinguible de un 0 elegido a
             conciencia: 0 es «Ausencia», una valoración. Antes este desplegable
             preseleccionaba 0 y los dos casos se guardaban igual, así que 156
             campos sin mirar hundían la media sin que nadie lo notara. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        <option value="0" @selected($val === 0)>🔴 0 - Ausencia</option>
        <option value="1" @selected($val === 1)>🟡 1 - Fragilidad</option>
        <option value="2" @selected($val === 2)>🟢 2 - Aprovechable</option>
    </select>
    @error($name)
        <span class="text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
```

- [ ] **Step 3: Ajustar select-percepcion**

Este ya tiene opción vacía, pero la selecciona con `empty($val)`, que también es
cierto para el 0. La escala de Percepción va de 1 a 3, así que hoy no da guerra;
aun así se deja explícito para que no muerda si la escala cambia. Reemplazar
`resources/views/components/select-percepcion.blade.php`:

```blade
@props(['label', 'name', 'val', 'disabled' => false])

@php
    $val = ($val === null || $val === '') ? null : (int) $val;
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full text-base border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- empty() también es cierto para el 0; con una comparación estricta
             esto no depende de que la escala empiece en 1. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        <option value="1" @selected($val === 1)>1 — Negativo</option>
        <option value="2" @selected($val === 2)>2 — Neutral</option>
        <option value="3" @selected($val === 3)>3 — Positivo</option>
    </select>
    @error($name)
        <span class="text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
```

- [ ] **Step 4: Corregir el valor inicial de los formularios de tarjeta**

`criterio-escala` y `criterio-pildoras` usan radios con
`x-model.number="valores['campo']"`, y el estado vive en el `x-data` de la
sección del formulario, no en el componente. **Ya soportan `null`**: cuando el
valor es `null` no hay radio marcado y el contador de respondidos lo filtra.

Lo que falla es el valor inicial. En
`resources/views/operativo/evaluacion_paisaje/form.blade.php` (línea 79):

```php
                        $inicial = collect($categoria['criterios'])->mapWithKeys(
                            fn($c, $campo) => [$campo => $esNueva ? null : (int) $evaluacion->$campo]
                        );
```

Con guardado parcial, una evaluación existente puede tener campos a `null`, y
`(int) null` los convierte en 0. Sustituir por:

```php
                        // Un campo sin responder llega como null y así tiene que
                        // quedarse: (int) null lo convertiría en 0, que aquí es
                        // una puntuación real y no un hueco. $esNueva sobra: una
                        // evaluación nueva ya tiene todos los campos a null.
                        $inicial = collect($categoria['criterios'])->mapWithKeys(
                            fn($c, $campo) => [
                                $campo => $evaluacion->$campo === null ? null : (int) $evaluacion->$campo,
                            ]
                        );
```

Aplicar el mismo cambio en
`resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`.
Localizar el bloque equivalente:

```bash
grep -n "esNueva\|inicial" resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php
```

Si `$esNueva` deja de usarse en el fichero, quitar también su cálculo y su paso
desde el controlador; comprobarlo antes con:

```bash
grep -rn "esNueva" app/ resources/
```

- [ ] **Step 5: Permitir deshacer una respuesta**

Sin esto, un radio marcado por error no se puede desmarcar y volver a «sin
responder» es imposible desde la interfaz.

En `resources/views/components/criterio-escala.blade.php`, justo después del
`</div>` que cierra el `grid` de niveles (línea 89):

```blade
    {{-- Un grupo de radios no se puede desmarcar por sí solo. Sin este botón,
         «sin responder» sería un estado al que nunca se puede volver. --}}
    <div class="mt-2" x-show="valores['{{ $campo }}'] !== null">
        <button type="button"
                @click="valores['{{ $campo }}'] = null"
                @disabled($bloqueado)
                class="text-sm text-gray-500 underline hover:text-gray-700">
            Borrar respuesta
        </button>
    </div>
```

Añadir el mismo bloque en `criterio-pildoras.blade.php`, después del contenedor
de píldoras.

Al no quedar ningún radio marcado, el campo **no se envía** en el POST. Ahí es
donde entra la normalización de `prepararDatos` de la Task 3: lo ausente se
guarda como `null`. No hace falta ningún input oculto.

- [ ] **Step 6: Verificar a mano en el navegador**

```bash
npm run build
```

Abrir una zona con `jefe@local.test` / `password`, entrar en Paisaje, responder
tres criterios, guardar borrador. Comprobar:

1. El mensaje dice «Llevas 3 de 34 criterios».
2. Al volver a abrir el formulario, los 31 restantes siguen en «Sin responder».
3. Los tres respondidos conservan su valor.
4. Poner un criterio en 0 explícito, guardar, y comprobar que al reabrir sigue
   marcado el 0 y no «Sin responder».

El punto 4 es el que se rompe si la normalización de tipo de los pasos 1-3 falla.

Comprobar también, en Paisaje: marcar un criterio, pulsar «Borrar respuesta»,
guardar, y verificar que vuelve a quedar sin responder en vez de conservar el
valor anterior.

- [ ] **Step 7: Suite completa**

```bash
php artisan test
```

Esperado: PASS.

- [ ] **Step 8: Commit**

```bash
git add resources/views/components resources/views/operativo
git commit -m "feat(formularios): opcion sin responder distinta de un cero elegido"
```

---

### Task 7: Progreso real en la página de zona

Con el guardado parcial, «21 de 34 respondidos» pasa a ser información real.

**Files:**
- Modify: `app/Servicios/EstadoZona.php`
- Test: ampliar `tests/Unit/EstadoZonaTest.php`

**Interfaces:**
- Consumes: `Registro::ENTRADAS[$clave]['criterios']`, el modelo de cada matriz.
- Produces: `FilaMatriz::$detalle` con el recuento en las filas en borrador.

- [ ] **Step 1: Añadir el test**

Añadir a `tests/Unit/EstadoZonaTest.php`:

```php
    public function test_una_matriz_en_borrador_dice_cuantos_criterios_van(): void
    {
        $evaluacion = \App\Models\EvaluacionPaisaje::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        $campos = array_keys(\App\Matrices\Paisaje::todos());
        foreach (array_slice($campos, 0, 21) as $campo) {
            $evaluacion->$campo = 3;
        }
        $evaluacion->save();

        $this->assertStringContainsString('21 de 34', $this->filas()['paisaje']->detalle);
    }
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=test_una_matriz_en_borrador_dice_cuantos_criterios_van
```

Esperado: FAIL — el detalle actual dice solo «Borrador».

- [ ] **Step 3: Contar los criterios respondidos**

En `app/Servicios/EstadoZona.php`, dentro de `filaMatriz()`, sustituir la rama de
borrador para que incluya el recuento:

```php
        $respondidos = $this->respondidos($evaluacion, $entrada);

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: "Borrador · {$respondidos} de {$entrada['criterios']} respondidos{$firma}",
            url:     $esAdmin
                ? route($entrada['rutas']['ver'], $this->zona->id)
                : route($entrada['rutas']['editar'], $this->zona->id),
            accion:  $esAdmin ? 'Ver' : 'Continuar',
            puedeValidar:    ! $esAdmin && $this->usuario->esJefe(),
            avisoValidacion: $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
```

Y añadir el método privado:

```php
    /**
     * Criterios ya respondidos de una evaluación.
     *
     * Se cuentan las columnas no nulas del modelo, excluyendo las de control y
     * las calculadas: el registro sabe cuántos criterios hay en total, pero no
     * cuáles, y duplicar esa lista aquí la desincronizaría.
     */
    private function respondidos(Model $evaluacion, array $entrada): int
    {
        $control = ['id', 'zona_id', 'user_id', 'estado', 'created_at', 'updated_at'];

        $respondidos = 0;

        foreach ($evaluacion->getAttributes() as $columna => $valor) {
            if (in_array($columna, $control, true)) {
                continue;
            }

            if (str_contains($columna, '_promedio') || str_contains($columna, '_total')
                || str_starts_with($columna, 'val_') || str_starts_with($columna, 'media_')) {
                continue;
            }

            if ($valor !== null) {
                $respondidos++;
            }
        }

        return min($respondidos, $entrada['criterios']);
    }
```

- [ ] **Step 4: Verificar que el recuento cuadra en las seis matrices**

La exclusión por nombre de columna es frágil: FIT guarda `fit_rtt`, `media_rtt` y
`fit`. Comprobar una por una que el recuento coincide con el número declarado en
el registro cuando la matriz está completa:

```bash
php artisan test --filter=EstadoZonaTest
```

Y añadir este test, que es el que detecta una exclusión mal puesta:

```php
    public function test_una_matriz_completa_cuenta_todos_sus_criterios(): void
    {
        $this->actingAs($this->jefe)->post(
            "/operativo/zona/{$this->zona->id}/paisaje",
            array_fill_keys(array_keys(\App\Matrices\Paisaje::todos()), 3)
        );

        $this->assertStringContainsString('Validada', $this->filas()['paisaje']->detalle);

        // Y en borrador, el recuento llega al total declarado.
        \App\Models\EvaluacionPaisaje::query()->update(['estado' => 'borrador']);

        $this->assertStringContainsString('34 de 34', $this->filas()['paisaje']->detalle);
    }
```

Si `min()` está tapando un recuento de más, este test lo destapa: quita el
`min()` temporalmente y comprueba el número crudo antes de decidir qué columna
falta excluir.

- [ ] **Step 5: Suite completa**

```bash
php artisan test
```

Esperado: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Servicios/EstadoZona.php tests/Unit/EstadoZonaTest.php
git commit -m "feat(zona): la ficha de una matriz en borrador dice cuantos criterios van"
```

---

### Task 8: Revisión final

- [ ] **Step 1: Suite completa y build**

```bash
php artisan test && npm run build
```

- [ ] **Step 2: Recorrido manual de extremo a extremo**

1. Abrir Paisaje en una zona nueva, responder 5 criterios, guardar. El mensaje
   dice «Llevas 5 de 34». La página de zona dice «Borrador · 5 de 34 respondidos».
2. Entrar en los resultados de esa matriz directamente por URL: sale el aviso de
   matriz sin resultados, no un 0,00.
3. Completar los 34 y validar. Los resultados aparecen con el total correcto.
4. En Potencialidad, activar solo «Afluencia Turística», responder 4 de 5,
   guardar. Comprobar en base que el quinto es `null` y `val_afluencia` es la
   media de los cuatro, no de cinco:

```bash
php artisan tinker --execute="
\$e = App\Models\EvaluacionPotencialidad::first();
echo 'dt_at_estadia: '; var_dump(\$e->dt_at_estadia);
echo 'val_afluencia: '; var_dump(\$e->val_afluencia);
"
```

5. Intentar validar esa misma matriz sin completarla: se rechaza nombrando el
   campo que falta.

- [ ] **Step 3: Confirmar que no queda ningún defecto 0 en el esquema**

```bash
php artisan tinker --execute="
foreach (DB::select(\"PRAGMA table_info(evaluaciones_paisaje)\") as \$c) {
  if (\$c->dflt_value !== null && str_starts_with(\$c->name, 'ep_')) {
    echo \$c->name.' tiene defecto '.\$c->dflt_value.PHP_EOL;
  }
}
echo 'fin'.PHP_EOL;
"
```

Esperado: solo `fin`. Un defecto 0 superviviente reintroduce el fallo por la
puerta de atrás en cualquier inserción que no pase por el formulario.

- [ ] **Step 4: Commit final si algo cambió**

```bash
git status --short
```

---

## Fuera de este plan

- **Panel de administración, usuarios y lugares.** Va en
  `docs/superpowers/plans/2026-08-07-vistas-admin.md`.
- **Migrar FIT, FET, Percepción y Potencialidad** a `criterio-escala` /
  `criterio-pildoras`. Este plan solo les añade la opción «sin responder»,
  que es lo imprescindible para que el guardado parcial funcione.
- **Reabrir una matriz validada.** Definido en el spec, sin implementar aquí:
  necesita su propia acción y el aviso de qué resultados se vuelven a bloquear.
