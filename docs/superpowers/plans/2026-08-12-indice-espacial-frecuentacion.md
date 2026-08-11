# Índice Espacial de Frecuentación Turística — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Añadir la décima y última matriz del sistema —el Índice Espacial de Frecuentación Turística—, que reparte la frecuentación turística de una zona entre sus sitios: `ÍETP = DET ÷ ST` por sitio, `ÍEFT = Σ ÍETP` para el territorio.

**Architecture:** Un CRUD de sitios de longitud variable con un estado de conjunto aparte, como Involucrados — pero no es la misma forma exacta: la Superficie Territorial (ST) es un dato de la zona que ninguna otra matriz tiene, y la fórmula por sitio no depende de los demás sitios (a diferencia de la normalización de Involucrados). Eso obliga a un quinto tipo de entrada del registro, `sitios`, hermano de `actores` y no una reutilización de él: `EstadoZona::filaActores()` y la rama gemela de `pestanas-matriz.blade.php` tienen la relación `involucrados()` escrita a mano, no genérica.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, SQLite en desarrollo y tests, PostgreSQL 16 en producción, PHPUnit 11.

**Diseño:** `docs/superpowers/specs/2026-08-12-indice-espacial-frecuentacion-design.md`

## Global Constraints

- **ST es una por zona, no por sitio.** Vive en `frecuentacion_config`, junto al estado del conjunto — no en cada fila de sitio.
- **ST nula o cero → ningún ÍETP existe, para ningún sitio.** `Frecuentacion::ietp()` devuelve `null`, nunca lanza `DivisionByZeroError` ni devuelve un número inventado. No es una anotación fila a fila como el `Pi` de Concentración: es una condición del territorio entero, y se resuelve una vez, a nivel de página, con `<x-matriz-sin-resultados>`.
- **`validar()` exige ST > 0**, además de lo que ya exige Involucrados (al menos un sitio, ninguno incompleto).
- **El ÍETP de un sitio no depende de los otros sitios** — a diferencia de la normalización de Involucrados. Pero el CRUD **sí** reabre una matriz validada al tocarla, porque lo que se certifica es ÍEFT, una suma sensible a cada término, y todos los sitios comparten la misma ST. No confundir «no depende de los demás» con «no hace falta reabrir»: sí hace falta, por un motivo distinto al de Involucrados.
- **`sitios` es un tipo de registro nuevo, no una reutilización de `actores`.** `EstadoZona::filaActores()` y `pestanas-matriz.blade.php` llaman a `$zona->involucrados()` a mano; reutilizar `actores` haría que la fila y la pestaña de Frecuentación contaran actores de Involucrados, no sitios propios.
- **DET y ST son `decimal`, no `integer`.** No hay unidad confirmada y ST es casi con toda seguridad una superficie fraccionaria. Dígitos generosos: no repetir el aprieto de `decimal(5,3)` que dejó anotado Irritación.
- **`decimal`/`numeric` vuelve como cadena desde PostgreSQL.** `st` y `det` llevan `'campo' => 'float'` explícito en sus modelos, como ya hace `EvaluacionFit`.
- **Nada calculado se guarda.** ÍETP e ÍEFT se derivan siempre de `det` y `st`.
- **Registra la entrada del registro antes de escribir el controlador.** Sin ella, `RegistroMatricesTest::test_toda_ruta_de_matriz_pertenece_a_una_entrada_del_registro` falla en cuanto existan las rutas.
- Nada por debajo de 14 px salvo insignias. Sin `uppercase`. Clases de Tailwind completas, nunca por concatenación.
- Comentarios en castellano explicando el *por qué*.
- Suite completa en verde antes de cada commit. No se toca ningún contenedor Docker. UTF-8 sin BOM.

## Estructura de ficheros

**Crear:**
- `app/Matrices/Frecuentacion.php` — definición del instrumento y cálculo puro (`ietp()`, `ieft()`).
- `database/migrations/2026_08_12_000001_create_frecuentacion_tables.php`
- `app/Models/FrecuentacionConfig.php`, `app/Models/SitioFrecuentacion.php`
- `app/Http/Controllers/Operativo/FrecuentacionController.php`
- `resources/views/operativo/frecuentacion/index.blade.php`, `form.blade.php`, `resultados.blade.php`
- `tests/Unit/FrecuentacionCalculoTest.php`
- `tests/Feature/FrecuentacionTest.php`

**Modificar:**
- `app/Matrices/Registro.php` — el quinto tipo y la entrada nueva.
- `app/Servicios/EstadoZona.php` — la rama `filaSitios()`.
- `app/Models/Zona.php` — la relación `frecuentacionSitios()`.
- `resources/views/components/pestanas-matriz.blade.php` — la rama `sitios`.
- `resources/views/components/icono.blade.php` — icono `ubicacion`.
- `routes/web.php`.
- `tests/Unit/EstadoZonaTest.php`, `tests/Feature/RegistroMatricesTest.php`, `tests/Feature/PermisosAdminTest.php`.
- `docs/ESTADO-PROYECTO.md` — al final, Task 6.

---

### Task 1: El quinto tipo de entrada del registro

Va primero y sola, igual que hizo Involucrados con `actores`. Al terminar, el
sistema debe comportarse **exactamente igual** que antes: es un ensanchamiento,
no un cambio, y la entrada real de Frecuentación todavía no existe.

**Files:**
- Modify: `app/Matrices/Registro.php`, `app/Servicios/EstadoZona.php`, `resources/views/components/pestanas-matriz.blade.php`
- Test: `tests/Unit/EstadoZonaTest.php`

**Interfaces:**
- Produce: `Registro::TIPOS_VALIDABLES` con `'sitios'` añadido.
- Produce: `EstadoZona::filaSitios()`.

- [ ] **Step 1: Escribir el test de la rama nueva, y verificar que se salta**

Añadir a `tests/Unit/EstadoZonaTest.php`, junto a
`test_una_entrada_de_actores_sin_empezar_lo_dice`:

```php
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
```

```bash
php artisan test --filter=test_una_entrada_de_sitios_sin_empezar_lo_dice
```

Esperado: SKIPPED.

- [ ] **Step 2: Declarar el tipo en el registro**

En `app/Matrices/Registro.php`:

```php
    /**
     * tipo:
     *   'matriz'    — tiene estado borrador/confirmado y cuenta para el progreso
     *   'inventario'— CRUD de recursos, sin estado
     *   'resultado' — derivado de otras entradas, no se rellena
     *   'actores'   — lista variable de actores con estado; cuenta para el progreso
     *   'sitios'    — lista variable de sitios con estado Y un escalar de zona
     *                 (la Superficie Territorial); cuenta para el progreso
     */
    public const TIPOS_VALIDABLES = ['matriz', 'actores', 'sitios'];
```

- [ ] **Step 3: Añadir la rama a `EstadoZona::fila()`**

```php
        return match ($entrada['tipo']) {
            'inventario' => $this->filaInventario($clave, $entrada),
            'resultado'  => $this->filaResultado($clave, $entrada),
            'actores'    => $this->filaActores($clave, $entrada),
            'sitios'     => $this->filaSitios($clave, $entrada),
            default      => $this->filaMatriz($clave, $entrada),
        };
```

Y el método, junto a `filaActores()`. **No reutiliza `filaActores()` a
propósito**: su condición de completitud tiene una segunda parte —la ST—
que Involucrados no tiene, y forzar las dos formas dentro del mismo método
sería el mismo riesgo que ya se evitó al no forzar `Potencialidad::SECCIONES`
dentro de la forma de `Fit::BLOQUES`.

```php
    /**
     * Una lista de sitios no tiene denominador fijo, igual que la de
     * actores, pero con una condición de más: la Superficie Territorial (ST)
     * es un escalar de la ZONA, no de cada sitio, y sin ella ningún ÍETP
     * existe aunque todos los sitios tengan su DET respondido. Por eso no
     * reutiliza filaActores(): esa rama no sabe nada de un segundo dato de
     * completitud aparte de la lista.
     *
     * El modelo de la entrada es FrecuentacionConfig -la configuración por
     * zona, que lleva el estado Y la ST-, no el de cada sitio.
     */
    private function filaSitios(string $clave, array $entrada): FilaMatriz
    {
        $config = $this->evaluaciones[$clave];

        $cuantos = $this->zona->frecuentacionSitios()->count();

        if ($cuantos === 0) {
            return new FilaMatriz(
                clave:   $clave,
                nombre:  $entrada['nombre'],
                icono:   $entrada['icono'],
                estado:  'sin_empezar',
                detalle: 'Todavía sin sitios registrados',
                url:     route($entrada['rutas']['editar'], $this->zona->id),
                accion:  'Empezar',
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
                detalle: "Validada · {$cuantos} sitios" . $firma,
                url:     route($entrada['rutas']['ver'], $this->zona->id),
                accion:  'Ver',
            );
        }

        $incompletos = $this->zona->frecuentacionSitios()->incompletos()->count();
        $stDefinida  = ($config?->st ?? null) !== null && $config->st > 0;

        // Dos motivos de bloqueo, no uno: sitios sin DET, y una ST sin
        // definir o en cero. Son causas distintas -una es "faltan datos de
        // sitio", la otra "falta un dato de la zona"- y conviene que el
        // detalle las distinga en vez de fundirlas en una frase que no dice
        // cuál de las dos hace falta resolver.
        $detalle = match (true) {
            $incompletos > 0 && ! $stDefinida => "Borrador · {$cuantos} sitios, {$incompletos} sin DET, falta la Superficie Territorial",
            $incompletos > 0                  => "Borrador · {$cuantos} sitios, {$incompletos} sin DET",
            ! $stDefinida                      => "Borrador · {$cuantos} sitios completos, falta la Superficie Territorial",
            default                            => "Borrador · {$cuantos} sitios, todos completos",
        };

        $listaCompleta = $incompletos === 0 && $stDefinida;

        return new FilaMatriz(
            clave:   $clave,
            nombre:  $entrada['nombre'],
            icono:   $entrada['icono'],
            estado:  'borrador',
            detalle: $detalle . $firma,
            url:     route($entrada['rutas']['editar'], $this->zona->id),
            accion:  'Continuar',
            puedeValidar:    $listaCompleta && $this->usuario->esJefe(),
            avisoValidacion: $listaCompleta && $this->usuario->esEquipo()
                ? 'Lista para validar — avísale a ' . ($this->zona->jefe?->name ?? 'tu Jefe de Zona')
                : null,
        );
    }
```

**Nota:** `$this->zona->frecuentacionSitios()`, su scope `incompletos()` y la
columna `st` de `FrecuentacionConfig` llegan en la Task 2. Hasta entonces
este método no se ejecuta —no hay ninguna entrada de tipo `sitios`—; si el
linter se queja, sigue adelante.

- [ ] **Step 4: Añadir la rama gemela a `pestanas-matriz.blade.php`**

Junto al `elseif($entrada['tipo'] === 'actores')` ya existente:

```blade
    } elseif ($entrada['tipo'] === 'actores') {
        $actores  = $zona->involucrados();
        $completa = $actores->count() > 0 && ! $actores->incompletos()->exists();
    } elseif ($entrada['tipo'] === 'sitios') {
        // Misma idea que la rama de actores, con una segunda condición: la
        // ST vive en $evaluacion (el modelo de la entrada es
        // FrecuentacionConfig, ya cargado arriba), no en cada sitio.
        $sitios   = $zona->frecuentacionSitios();
        $completa = $sitios->count() > 0
            && ! $sitios->incompletos()->exists()
            && ($evaluacion?->st ?? null) !== null
            && $evaluacion->st > 0;
    } else {
```

Y en la rama `@elseif($entrada['tipo'] === 'actores')` del candado de abajo,
añadir la gemela para `sitios` con el mismo texto «— sin sitios completos».

- [ ] **Step 5: Suite completa**

```bash
php artisan test
```

Esperado: PASS, con el test nuevo saltado (SKIPPED). Nada más debe haber
cambiado de comportamiento: si algún test de otra matriz se mueve, el
ensanchamiento no era tal y hay que averiguar por qué antes de seguir.

- [ ] **Step 6: Commit**

```bash
git add app/Matrices/Registro.php app/Servicios/EstadoZona.php \
        resources/views/components/pestanas-matriz.blade.php \
        tests/Unit/EstadoZonaTest.php
git commit -m "feat(registro): quinto tipo de entrada, sitios con un escalar de zona"
```

---

### Task 2: Tablas, modelos y definición del instrumento

**Files:**
- Create: `app/Matrices/Frecuentacion.php`
- Create: `database/migrations/2026_08_12_000001_create_frecuentacion_tables.php`
- Create: `app/Models/FrecuentacionConfig.php`, `app/Models/SitioFrecuentacion.php`
- Modify: `app/Models/Zona.php`
- Test: `tests/Unit/FrecuentacionCalculoTest.php`

**Interfaces:**
- Produce: `Frecuentacion::ietp(float $det, ?float $st): ?float`
- Produce: `Frecuentacion::ieft(array $ietps): float`
- Produce: `SitioFrecuentacion::scopeIncompletos()`, `SitioFrecuentacion::estaCompleto()`.

- [ ] **Step 1: Los tests del cálculo, primero**

Crear `tests/Unit/FrecuentacionCalculoTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Matrices\Frecuentacion;
use InvalidArgumentException;
use Tests\TestCase;

class FrecuentacionCalculoTest extends TestCase
{
    public function test_ietp_es_det_entre_st(): void
    {
        $this->assertEqualsWithDelta(2.5, Frecuentacion::ietp(5.0, 2.0), 0.0001);
    }

    /**
     * ST nula o cero -> null, NUNCA una excepción ni INF: en PHP 8, $a / 0
     * lanza DivisionByZeroError, y la plantilla real del instrumento llega
     * vacía (I6:I14 y J6 sin valor), así que este no es un caso de esquina.
     */
    public function test_ietp_es_null_si_st_falta_o_es_cero(): void
    {
        $this->assertNull(Frecuentacion::ietp(5.0, null));
        $this->assertNull(Frecuentacion::ietp(5.0, 0.0));
    }

    public function test_ietp_con_det_cero_es_cero_no_null(): void
    {
        // Un sitio sin visitas es un dato real, 0.0, no "sin responder": esa
        // distinción la hace el controlador antes de llamar aquí (un DET
        // null no debe llegar a ietp()), no esta función.
        $this->assertSame(0.0, Frecuentacion::ietp(0.0, 10.0));
    }

    public function test_ietp_rechaza_det_o_st_negativos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Frecuentacion::ietp(-1.0, 10.0);
    }

    public function test_ieft_suma_los_ietp_de_todos_los_sitios(): void
    {
        $this->assertEqualsWithDelta(6.0, Frecuentacion::ieft([1.0, 2.0, 3.0]), 0.0001);
    }

    /**
     * Un ÍETP que falta (null) no entra en ningún total, ni con un cero
     * disfrazado: mismo principio que ConcentracionCalculo::validarConteosCompletos()
     * e Involucrados::validarGradosCompletos(). Quien llama a ieft() ya
     * decidió mostrar resultados solo con la lista completa; si esta función
     * recibe un hueco es que esa comprobación no se hizo.
     */
    public function test_ieft_exige_el_conjunto_completo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Frecuentacion::ieft([1.0, null, 3.0]);
    }

    public function test_ieft_exige_al_menos_un_sitio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Frecuentacion::ieft([]);
    }
}
```

```bash
php artisan test --filter=FrecuentacionCalculoTest
```

Esperado: FAIL, «Class "App\Matrices\Frecuentacion" not found».

- [ ] **Step 2: Escribir `app/Matrices/Frecuentacion.php`**

Cálculo puro sobre escalares —sin Eloquent, sin recibir modelos—, en la línea
de `Involucrados::normalizar()` y `ConcentracionCalculo`: quien reúne los DET
de los sitios de una zona en un array es el controlador, no esta clase. **No
se parte en un fichero de cálculo aparte** —a diferencia de Concentración—
porque Frecuentación no se genera desde un script: no hay ningún riesgo de
que una regeneración borre un método a mano.

```php
<?php

namespace App\Matrices;

use InvalidArgumentException;

/**
 * Índice Espacial de Frecuentación Turística.
 *
 * Reparte la frecuentación de una zona entre sus sitios: por sitio,
 * ÍETP = DET ÷ ST; para el territorio, ÍEFT = Σ ÍETP. A diferencia de
 * Involucrados::normalizar(), el ÍETP de un sitio NO depende de los demás
 * sitios -su único divisor es ST, un dato de la zona, no una función de los
 * propios DET-. Ver el diseño para la comparación completa con Involucrados.
 */
final class Frecuentacion
{
    /**
     * ÍETP de un sitio: DET ÷ ST.
     *
     * ST NULA O CERO -> null, NUNCA una excepción ni un número inventado:
     * en PHP 8 la división por cero con el operador `/` lanza
     * DivisionByZeroError, así que hay que interceptarla antes. Un sitio sin
     * Superficie Territorial no tiene "un ÍETP bajo": no tiene ÍETP. Misma
     * jurisprudencia que ConcentracionCalculo::pi() con un sector vacío,
     * aplicada aquí a un divisor que es de la ZONA, no de cada fila -ver el
     * diseño para por qué eso cambia cómo se presenta, no cómo se calcula-.
     *
     * DET es obligatorio en esta función: un sitio sin DET no debe llegar
     * aquí -la completitud se comprueba antes, en el controlador o la
     * vista-, igual que Concentración e Involucrados exigen sus arrays de
     * entrada completos antes de calcular nada.
     */
    public static function ietp(float $det, ?float $st): ?float
    {
        if ($det < 0) {
            throw new InvalidArgumentException('DET no puede ser negativo.');
        }

        if ($st !== null && $st < 0) {
            throw new InvalidArgumentException('ST no puede ser negativa.');
        }

        return ($st === null || $st === 0.0) ? null : $det / $st;
    }

    /**
     * ÍEFT del territorio: la suma de los ÍETP de todos sus sitios.
     *
     * Exige el array completo, sin huecos y con al menos un elemento: un
     * sitio sin ÍETP (DET sin responder, o ST ausente/cero) no tiene una
     * suma parcial razonable -descontar el término que falta daría un
     * número medido sobre otra escala, la misma lección que dejó GP5-.
     * Mismo principio que ConcentracionCalculo::validarConteosCompletos() e
     * Involucrados::validarGradosCompletos(): la completitud se comprueba
     * ANTES de llamar aquí -en la vista de resultados, con
     * <x-matriz-sin-resultados> para el caso incompleto-, así que un hueco
     * que llegue hasta esta función es un fallo de quien llama, y debe
     * fallar ruidoso, no devolver un número que parece un resultado.
     *
     * @param array<int, float|null> $ietps
     */
    public static function ieft(array $ietps): float
    {
        if ($ietps === []) {
            throw new InvalidArgumentException('ÍEFT no está definido para un territorio sin sitios.');
        }

        foreach ($ietps as $valor) {
            if (! is_float($valor) && ! is_int($valor)) {
                throw new InvalidArgumentException(
                    'ÍEFT exige que todos los sitios tengan su ÍETP calculado: ninguno puede faltar.'
                );
            }
        }

        return (float) array_sum($ietps);
    }
}
```

- [ ] **Step 3: Ejecutar y verificar que pasa**

```bash
php artisan test --filter=FrecuentacionCalculoTest
```

- [ ] **Step 4: Escribir la migración**

Crear `database/migrations/2026_08_12_000001_create_frecuentacion_tables.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice Espacial de Frecuentación Turística: dos tablas, mismo reparto que
 * Involucrados y por el mismo motivo -lo que se valida es el conjunto
 * entero, no una fila suelta- con un escalar de más.
 *
 * `frecuentacion_config` lleva el estado del conjunto Y la Superficie
 * Territorial (ST): un dato de la ZONA, compartido por todos sus sitios, no
 * uno por sitio. Guardarlo junto a cada sitio lo repetiría sin necesidad, y
 * guardarlo en una fila aparte obligaría a inventar un sitio especial que no
 * es un sitio.
 *
 * `frecuentacion_sitios` es un sitio por fila: nombre y su DET (Densidad/
 * Densidad Espacial Turística; la unidad no está confirmada y no bloquea,
 * ver el diseño).
 *
 * `st` y `det` son `decimal`, nullable y sin defecto -un sitio recién creado
 * no tiene "cero frecuentación", tiene "sin responder todavía", igual que
 * los criterios de Involucrados-. Dígitos generosos: decimal(14,4), para no
 * repetir el aprieto que dejó anotado la migración de Irritación
 * (decimal(5,3) "va justo de dígitos").
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('frecuentacion_config', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');
            $table->decimal('st', 14, 4)->nullable();

            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('frecuentacion_sitios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->constrained('zonas')->cascadeOnDelete();
            $table->string('nombre', 200);
            $table->unsignedInteger('orden')->default(0);
            $table->decimal('det', 14, 4)->nullable();

            $table->timestamps();

            $table->index('zona_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frecuentacion_sitios');
        Schema::dropIfExists('frecuentacion_config');
    }
};
```

- [ ] **Step 5: Escribir los modelos y la relación**

Crear `app/Models/FrecuentacionConfig.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Estado del conjunto de sitios de la Matriz de Frecuentación de una zona,
 * y la Superficie Territorial (ST) que comparten todos ellos.
 *
 * A diferencia de InvolucradosConfig, esta configuración SÍ lleva un dato
 * del cálculo -ST-, no solo el estado: ST no es un sitio, no tiene DET, y
 * repetirla en cada fila de sitio la duplicaría sin necesidad.
 */
class FrecuentacionConfig extends Model
{
    protected $table = 'frecuentacion_config';

    protected $fillable = ['zona_id', 'user_id', 'estado', 'st'];

    // PostgreSQL devuelve las columnas numeric como string; sin este cast,
    // Frecuentacion::ietp() recibiría una cadena donde espera un float. Mismo
    // motivo que EvaluacionFit.
    protected function casts(): array
    {
        return ['st' => 'float'];
    }

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

Crear `app/Models/SitioFrecuentacion.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Un sitio de la Matriz de Frecuentación: su nombre y su DET. */
class SitioFrecuentacion extends Model
{
    protected $table = 'frecuentacion_sitios';

    protected $fillable = ['zona_id', 'nombre', 'orden', 'det'];

    protected function casts(): array
    {
        // Mismo motivo que FrecuentacionConfig::$st: PostgreSQL devuelve
        // numeric como string.
        return ['det' => 'float'];
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function estaCompleto(): bool
    {
        return $this->det !== null;
    }

    /**
     * Los sitios sin DET, en SQL y no en PHP: la página de zona y las
     * pestañas lo cuentan en cada carga.
     */
    public function scopeIncompletos(Builder $consulta): Builder
    {
        return $consulta->whereNull('det');
    }
}
```

En `app/Models/Zona.php`, junto a `involucrados()`:

```php
    public function frecuentacionSitios()
    {
        return $this->hasMany(SitioFrecuentacion::class)->orderBy('orden')->orderBy('id');
    }
```

- [ ] **Step 6: Migrar, ejecutar y commitear**

```bash
php artisan migrate
php artisan test
```

```bash
git add app/Matrices/Frecuentacion.php app/Models/FrecuentacionConfig.php \
        app/Models/SitioFrecuentacion.php app/Models/Zona.php \
        database/migrations/2026_08_12_000001_create_frecuentacion_tables.php \
        tests/Unit/FrecuentacionCalculoTest.php
git commit -m "feat(frecuentacion): calculo puro, tablas y modelos"
```

---

### Task 3: El CRUD de sitios, la Superficie Territorial y el registro

**Files:**
- Create: `app/Http/Controllers/Operativo/FrecuentacionController.php`
- Modify: `routes/web.php`, `app/Matrices/Registro.php`, `resources/views/components/icono.blade.php`
- Test: `tests/Feature/FrecuentacionTest.php`, `tests/Feature/PermisosAdminTest.php`, `tests/Unit/EstadoZonaTest.php`, `tests/Feature/RegistroMatricesTest.php`

No extiende `MatrizPonderadaController`: esa clase asume un formulario fijo
de criterios de la zona (`updateOrCreate` por `zona_id`), y aquí hay una
lista variable más un escalar de configuración. Sigue el patrón de
`InvolucradosController`.

**Rutas**, con los mismos cuidados de orden que ya dejó anotado
`routes/web.php` para Involucrados —los segmentos fijos (`nuevo`, `validar`,
`resultados`, `superficie`) van declarados **antes** que `{sitio}/editar`, o
una petición a `/frecuentacion/validar` encajaría con `sitio = 'validar'`—:

```php
        // Índice Espacial de Frecuentación Turística
        Route::get('/frecuentacion',                [FrecuentacionController::class, 'index'])->name('frecuentacion.index');
        Route::get('/frecuentacion/nuevo',           [FrecuentacionController::class, 'create'])->name('frecuentacion.create');
        Route::post('/frecuentacion',                [FrecuentacionController::class, 'store'])->name('frecuentacion.store');
        Route::post('/frecuentacion/superficie',     [FrecuentacionController::class, 'actualizarSuperficie'])->name('frecuentacion.superficie');
        Route::post('/frecuentacion/validar',        [FrecuentacionController::class, 'validar'])->name('frecuentacion.validar');
        Route::get('/frecuentacion/resultados',      [FrecuentacionController::class, 'resultados'])->name('frecuentacion.resultados');
        Route::get('/frecuentacion/{sitio}/editar',  [FrecuentacionController::class, 'edit'])->name('frecuentacion.edit');
        Route::put('/frecuentacion/{sitio}',         [FrecuentacionController::class, 'update'])->name('frecuentacion.update');
        Route::delete('/frecuentacion/{sitio}',      [FrecuentacionController::class, 'destroy'])->name('frecuentacion.destroy');
```

**La entrada del registro**, al final de `ENTRADAS`:

```php
        'frecuentacion' => [
            'nombre'     => 'Frecuentación turística',
            'icono'      => 'ubicacion',
            'grupo'      => 'presion',
            'tipo'       => 'sitios',
            'modelo'     => FrecuentacionConfig::class,
            'criterios'  => null,
            'rutas'      => [
                'editar' => 'operativo.frecuentacion.index',
                'ver'    => 'operativo.frecuentacion.resultados',
            ],
            'depende_de' => [],
        ],
```

Añadir el `use App\Models\FrecuentacionConfig;` correspondiente en
`Registro.php`.

**Icono `ubicacion` nuevo** en `resources/views/components/icono.blade.php`
—un marcador de posición, distinto de los once ya usados—; el test de iconos
fallará si se repite uno.

- [ ] **Step 1: Escribir los tests del CRUD y de la ST, antes del controlador**

Crear `tests/Feature/FrecuentacionTest.php`, con la forma de
`InvolucradosTest.php`. Cubrir, como mínimo:

- Crear, editar y borrar un sitio; un sitio de otra zona no se puede tocar.
- El equipo puede editar mientras la configuración esté en borrador, y no
  cuando está confirmada.
- **Guardar la ST**: acepta un número positivo, rechaza cero y negativos
  (`gt:0`), y guarda `null` si se deja vacío.
- **`validar()`** exige, en este orden observable: al menos un sitio: sin
  ninguno, error; con sitios pero alguno sin DET, error; con todos los DET
  respondidos pero sin ST o con ST en 0, error; con todo completo,
  `confirmado`.
- Editar un sitio, borrar un sitio o cambiar la ST de una configuración ya
  confirmada la devuelve a `borrador` (mismo mecanismo que
  `InvolucradosController::reabrirSiConfirmada()`).

- [ ] **Step 2: Verificar que fallan, escribir el controlador, y volver a ejecutar**

`FrecuentacionController` sigue el reparto de `InvolucradosController`:

- `index($zonaId)` — carga `$zona`, `$sitios` (`$zona->frecuentacionSitios()->get()`),
  `$config` (`FrecuentacionConfig::where('zona_id', $zonaId)->first()`), y
  las mismas banderas que Involucrados (`puedeEditar`, `puedeValidar`,
  `avisoValidacion`), con la condición de completitud a dos partes (sin
  sitios incompletos, **y** ST definida y positiva).
- `actualizarSuperficie(Request, $zonaId)` — valida `'st' =>
  'nullable|numeric|gt:0'`, hace `FrecuentacionConfig::updateOrCreate(['zona_id'
  => $zonaId], ['user_id' => ..., 'st' => ...])` y llama a
  `reabrirSiConfirmada()` en la misma transacción, con el mismo bloqueo por
  cierre que el resto (`bloqueoSiCerrada()`: solo el Jefe de Zona toca una
  configuración confirmada).
- `create`/`store`/`edit`/`update`/`destroy` — CRUD de sitio con un único
  campo, `det` (`nullable|numeric|min:0`), más `nombre`
  (`required|string|max:200`). Cada escritura llama a
  `reabrirSiConfirmada()` dentro de su propia transacción, igual que
  Involucrados.
- `validar($zonaId)` — `abort_unless($user->esJefe(), 403)`; en orden: sin
  sitios, error; con algún sitio sin DET, error; con `st` nula o `<= 0`,
  error (mensaje explícito: «No puedes validar sin una Superficie
  Territorial mayor que cero.»); si no, `FrecuentacionConfig::updateOrCreate(...,
  ['estado' => 'confirmado'])`.
- `resultados($zonaId)` — `$completa` exige sitios no vacío, ningún sitio
  incompleto, y `$config->st` no nula y mayor que 0. Solo entonces arma las
  filas: `Frecuentacion::ietp($sitio->det, $config->st)` por sitio, y
  `Frecuentacion::ieft(...)` sobre el array resultante. Si no, `$filas =
  collect()` y la vista pinta `<x-matriz-sin-resultados>`.

`bloqueoSiCerrada()`, `reabrirSiConfirmada()` y `mensajeConReapertura()` se
copian del patrón de `InvolucradosController` casi sin cambios —el
comentario de por qué solo el Jefe de Zona pasa una vez confirmado
(`! esJefe()`, no `esEquipo()`) aplica igual, con la misma referencia al
cambio de permisos del admin—.

```bash
php artisan test --filter=FrecuentacionTest
```

- [ ] **Step 3: Ajustar los guardianes y recuentos**

`RegistroMatricesTest::test_solo_las_matrices_validables_cuentan_para_el_progreso`
pasa de 9 a 10 (`assertCount(10, Registro::matrices())`): el resto de esa
suite ya es genérico y no necesita más cambios.

`EstadoZonaTest`: `assertSame(10, $estado->totalMatrices())`. El test de la
Task 1 que se saltaba (`test_una_entrada_de_sitios_sin_empezar_lo_dice`) se
ejecuta de verdad ahora: compruébalo.

`PermisosAdminTest::rutasDeEscrituraClasificadas()` —el guardián de
`test_toda_ruta_de_escritura_del_grupo_zona_esta_clasificada` falla si una
ruta nueva no está en ninguna de las dos listas—:

```php
            'permitidas' => [
                // ... las ya existentes ...
                'operativo.frecuentacion.store',
                'operativo.frecuentacion.update',
                'operativo.frecuentacion.destroy',
                'operativo.frecuentacion.superficie',
            ],
            'prohibidas' => [
                // ... las ya existentes ...
                'operativo.frecuentacion.validar',
            ],
```

Y ampliar `test_el_admin_puede_usar_todas_las_rutas_de_escritura_permitidas()`
con el mismo bloque que ya cubre el CRUD de Involucrados: el admin crea un
sitio, actualiza su DET, guarda una ST, y borra el sitio, todo sin errores de
sesión.

**Esos números y esas listas no son cosmética: son lo único que obliga a que
la entrada nueva esté registrada y clasificada.** Si esta tarea construyera
el CRUD entero y se olvidara del registro o de la lista blanca de permisos,
la suite quedaría en rojo de inmediato —a diferencia del bug de Paisaje, que
tardó meses en notarse—: es justo lo que estos dos guardianes existen para
impedir.

- [ ] **Step 4: Suite completa y commit**

```bash
php artisan test
git add app/Http/Controllers/Operativo/FrecuentacionController.php \
        app/Matrices/Registro.php routes/web.php \
        resources/views/components/icono.blade.php \
        tests/Feature/FrecuentacionTest.php tests/Feature/PermisosAdminTest.php \
        tests/Unit/EstadoZonaTest.php tests/Feature/RegistroMatricesTest.php
git commit -m "feat(frecuentacion): CRUD de sitios, superficie territorial y registro"
```

---

### Task 4: Formulario

**Files:**
- Create: `resources/views/operativo/frecuentacion/index.blade.php`, `form.blade.php`
- Test: ampliar `tests/Feature/FrecuentacionTest.php`

**`index.blade.php`** — mismo patrón que `involucrados/index.blade.php`:

- `<x-pestanas-matriz clave="frecuentacion" :zona="$zona" activa="formulario" />`.
- Un pequeño formulario de un solo campo, la Superficie Territorial, con su
  valor actual precargado; deshabilitado si `! $puedeEditar`. Submit a
  `operativo.frecuentacion.superficie`.
- La lista de sitios, con su nombre y su DET (o «— sin responder —» si es
  `null`), y un enlace de edición por fila. Botón «+ Nuevo sitio» cuando se
  puede editar.
- El botón de validar, visible solo cuando `puedeValidar`; el aviso «Lista
  para validar» cuando `avisoValidacion`.

**`form.blade.php`** — alta y edición de un sitio: su nombre y un único
campo numérico, DET, con el mismo tratamiento de vacío-vs-cero que ya usan
los campos numéricos de Concentración (un campo que puede quedar realmente
sin responder, no precargado a 0).

Tests: que el índice pinta la ST actual y los sitios con su estado; que
guardar una ST de 0 o negativa devuelve el error de validación sin guardar
nada; que el admin ve el formulario editable y no ve el botón de validar;
que el equipo no puede tocar nada con la configuración confirmada.

- [ ] Suite completa y commit.

---

### Task 5: Resultados

**Files:**
- Create: `resources/views/operativo/frecuentacion/resultados.blade.php`
- Modify: `app/Http/Controllers/Operativo/FrecuentacionController.php`
- Test: ampliar `tests/Feature/FrecuentacionTest.php`

Tabla de sitios con su DET y su ÍETP, y el **ÍEFT del territorio al pie**,
destacado como un total, no como una fila más. `<x-pestanas-matriz
clave="frecuentacion" :zona="$zona" activa="resultados" />`.

**Con la lista vacía, algún sitio sin DET, o la ST ausente o en cero,
`<x-matriz-sin-resultados>`**, con un texto en el slot que distinga el
motivo —«faltan sitios por responder» frente a «falta la Superficie
Territorial, o es cero»—, igual que hace Concentración con sus 113 conteos.
**No hay un «no aplica» fila por fila**: la condición de ST es del
territorio entero, no de un sitio (ver el diseño), así que se resuelve una
vez, antes de la tabla, no en cada fila.

Tests: que la tabla se pinta con sus números en el caso completo —no solo
`assertDontSee` en el incompleto, la aserción de una sola cara que ya se
coló dos veces en esta serie de matrices—; que ÍEFT es la suma correcta de
los ÍETP con números concretos; que con la ST en 0 (o ausente) la página
completa cae en `<x-matriz-sin-resultados>` y no revienta con un 500.

- [ ] Suite completa y commit.

---

### Task 6: Revisión final

- [ ] Suite y `npm run build` en verde.
- [ ] **La migración contra PostgreSQL real**, con un contenedor desechable
      (`docker run --rm`, parado por nombre explícito, sin `prune` de nada).
      Comprobar que `st` y `det` quedan `numeric` nullable sin defecto, y que
      `FrecuentacionConfig::$st` / `SitioFrecuentacion::$det` los devuelven
      como `float` de verdad, no como cadena.
- [ ] **La suite entera contra PostgreSQL**:
      `php -d extension=pdo_pgsql -d extension=pgsql vendor/phpunit/phpunit/phpunit`.
      Debe dar los mismos números que SQLite.
- [ ] Recorrido manual con `jefe@local.test` / `password`: añadir tres
      sitios, dejar uno sin DET, comprobar que la página de zona dice «3
      sitios, 1 sin DET, falta la Superficie Territorial»; completar el DET
      que falta y seguir viendo el segundo motivo; guardar una ST; validar;
      comprobar en resultados el ÍETP de cada sitio y el ÍEFT total; volver a
      editar un sitio ya validado y comprobar que la lista vuelve a
      `borrador` con el aviso correspondiente en el mensaje de éxito.
- [ ] Probar a mano el caso de ST en 0 vía el formulario: confirmar que la
      validación de `gt:0` lo rechaza antes de guardar nada, y que no hay
      forma de llegar a una página de resultados con un `DivisionByZeroError`.
- [ ] Actualizar `docs/ESTADO-PROYECTO.md`: décima y última matriz hecha, el
      sistema queda con las diez implementadas, actualizar el recuento de
      tests del §8 («Comprobar que la suite da N tests»).

---

## Fuera de este plan

- **Reordenar sitios por arrastre.** La columna `orden` existe para
  añadirlo después sin migración, igual que en Involucrados.
- **Las unidades de DET y ST.** Siguen sin confirmar y no bloquean, tal como
  fija `docs/ESTADO-PROYECTO.md`, §6 punto 3.
- **El formato de porcentaje** que trae la hoja de Excel en la columna de
  ÍETP. Se muestra como número decimal simple; ver el diseño.
- **Cualquier gráfico** del instrumento original.
- **Un umbral o clasificación de ÍEFT.** El instrumento no trae ninguno.
- **Generalizar `EstadoZona::filaActores()`/`filaSitios()` en un único
  método parametrizado por relación.** Se decidió a propósito mantenerlos
  hermanos y no fundirlos: ver el diseño, punto 4.
