# Índice de Irritación Turística — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Añadir la séptima matriz de evaluación del sistema —el Índice de Irritación Turística— con sus dos bloques de seis atributos, su escala inversa de 0 a 10 y sus dos resultados clasificados.

**Architecture:** Una entrada más en `App\Matrices\Registro` y un controlador que extiende `MatrizPonderadaController`. De ahí salen gratis el guardado parcial, la obligatoriedad al confirmar, el aviso de matriz sin resultados y el recuento de la página de zona. Lo propio de esta matriz son doce nombres de campo, una escala de 0 a 10, dos medias simples y una clasificación derivada por umbrales.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, SQLite en desarrollo y tests, PostgreSQL 16 en producción, PHPUnit 11.

**Diseño:** `docs/superpowers/specs/2026-08-07-indice-irritacion-turistica-design.md`

## Global Constraints

- **La escala es inversa.** 0 es lo mejor y 10 lo peor. Es la primera matriz del sistema que funciona así, y es el fallo más fácil de cometer.
- Nada por debajo de 14 px salvo insignias. Sin `uppercase`.
- Clases de Tailwind completas, nunca por concatenación.
- Comentarios en castellano explicando el *por qué*, siguiendo el estilo del repositorio.
- Suite completa (`php artisan test`) en verde antes de cada commit.
- Las columnas de criterio nacen `nullable` y sin defecto: el guardado parcial ya está en el sistema.
- No se toca ningún contenedor Docker.

## Estructura de ficheros

**Crear:**
- `app/Matrices/Irritacion.php` — la definición del instrumento: los dos bloques
  de campos, las etiquetas, los umbrales y `clasificar()`. Es de donde beben el
  modelo, el controlador y las tres vistas, para que ninguna de ellas tenga que
  importar a las otras.
- `resources/views/components/select-0-10.blade.php` — el desplegable de la escala inversa.
- `database/migrations/2026_08_09_000001_create_evaluaciones_irritacion_table.php`
- `app/Models/EvaluacionIrritacion.php` — modelo y clasificación derivada.
- `app/Http/Controllers/Operativo/EvaluacionIrritacionController.php`
- `resources/views/operativo/evaluacion_irritacion/form.blade.php`
- `resources/views/operativo/evaluacion_irritacion/ponderacion.blade.php`
- `tests/Feature/IrritacionTest.php`

**Modificar:**
- `app/Matrices/Registro.php` — la entrada nueva, en el grupo `presion`.
- `routes/web.php` — tres rutas.

---

### Task 1: El desplegable de la escala inversa

Los componentes de tarjeta y píldora colorean por posición dando por hecho que
más alto es mejor. Aquí es al revés, así que esta matriz necesita su propio
control.

**Files:**
- Create: `resources/views/components/select-0-10.blade.php`
- Test: `tests/Feature/IrritacionTest.php`

**Interfaces:**
- Consume: `$label`, `$name`, `$val` (puede ser `null`), `$disabled`.
- Produce: un `<select>` con once opciones más la de «sin responder», cada una
  con su clasificación en el texto.

- [ ] **Step 1: Escribir el test**

Crear `tests/Feature/IrritacionTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IrritacionTest extends TestCase
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
     * La escala es inversa y el desplegable tiene que decirlo: un 7 suelto no
     * significa nada, «7 — Crítico» sí. Es lo que evita tener que consultar la
     * tabla de rangos del instrumento aparte.
     */
    public function test_el_desplegable_etiqueta_cada_valor_con_su_clasificacion(): void
    {
        $this->withViewErrors([]);

        $html = (string) $this->blade('<x-select-0-10 label="Congestión" name="c" :val="null" />');

        $this->assertStringContainsString('<option value="" selected>', $html);
        $this->assertStringContainsString('0 — Bajo', $html);
        $this->assertStringContainsString('2 — Bajo', $html);
        $this->assertStringContainsString('3 — Moderado', $html);
        $this->assertStringContainsString('6 — Moderado', $html);
        $this->assertStringContainsString('7 — Crítico', $html);
        $this->assertStringContainsString('10 — Crítico', $html);
    }

    /** Mismo contrato que los demás desplegables: el hueco no es un cero. */
    public function test_el_desplegable_distingue_el_hueco_del_cero(): void
    {
        $this->withViewErrors([]);

        $conCero = (string) $this->blade('<x-select-0-10 label="C" name="c" :val="0" />');

        $this->assertStringContainsString('<option value="0" selected>', $conCero);
        $this->assertStringNotContainsString('<option value="" selected>', $conCero);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: FAIL, «Unable to locate a class or view for component [select-0-10]».

- [ ] **Step 3: Escribir el componente**

Crear `resources/views/components/select-0-10.blade.php`:

```blade
@props(['label', 'name', 'val', 'disabled' => false])

@php
    // old() gana sobre lo guardado: si la validación rechaza el envío, el
    // formulario tiene que devolver lo que el usuario acababa de responder.
    $val = old($name, $val);
    $val = ($val === null || $val === '') ? null : (int) $val;

    // La escala es INVERSA: 0 es el mejor caso y 10 el peor. Los umbrales son
    // los del instrumento y los mismos que aplica el modelo al promedio.
    $clasificacion = fn(int $n) => match (true) {
        $n >= 7 => 'Crítico',
        $n >= 3 => 'Moderado',
        default => 'Bajo',
    };
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full text-base border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- Sin responder tiene que ser distinguible de un 0 elegido a
             conciencia: aquí el 0 es el mejor resultado posible, no un hueco. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        @for($n = 0; $n <= 10; $n++)
            <option value="{{ $n }}" @selected($val === $n)>{{ $n }} — {{ $clasificacion($n) }}</option>
        @endfor
    </select>
    @error($name)
        <span class="text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>
```

**Nota sobre el color.** El diseño pedía la escala coloreada, y aquí no se
colorean las opciones: los navegadores no estilan `<option>` de forma fiable.
El color va donde sí funciona y sí significa algo: la leyenda del formulario
(Task 4) y los resultados (Task 5). La clasificación en el texto de cada opción
cubre la necesidad real, que es no tener que recordar hacia dónde va la escala.

- [ ] **Step 4: Ejecutar y verificar que pasa**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: PASS, 2 tests.

- [ ] **Step 5: Commit**

```bash
git add resources/views/components/select-0-10.blade.php tests/Feature/IrritacionTest.php
git commit -m "feat(ui): desplegable de escala inversa 0-10 con su clasificacion"
```

---

### Task 2: Tabla, modelo y clasificación

**Files:**
- Create: `database/migrations/2026_08_09_000001_create_evaluaciones_irritacion_table.php`
- Create: `app/Models/EvaluacionIrritacion.php`
- Test: ampliar `tests/Feature/IrritacionTest.php`

**Interfaces:**
- Produce: `App\Matrices\Irritacion::clasificar(?float $valor): ?string` — la
  única puerta a la clasificación. El modelo la consume desde sus accesorios;
  no la reexpone.
- Produce: los accesorios `clasificacion_visitantes` y `clasificacion_residentes`.

- [ ] **Step 1: Escribir los tests de los umbrales**

Añadir a `tests/Feature/IrritacionTest.php`:

```php
    /**
     * Los tres tramos en sus bordes exactos. El instrumento se contradice a sí
     * mismo en una tabla —dice «De 3 a 5» en un lado y «De 3 a 6» en el otro—
     * pero todas sus fórmulas usan >=3, y eso es lo que se implementa.
     */
    public function test_la_clasificacion_respeta_los_umbrales_del_instrumento(): void
    {
        // Pares y no un array asociativo: PHP trunca las claves float a
        // entero, así que 2.9 pisaría a 2.0 y los dos casos con decimales
        // —los que de verdad distinguen >= de >— nunca se llegarían a probar.
        $casos = [
            [0.0, 'Bajo'], [2.0, 'Bajo'], [2.9, 'Bajo'],
            [3.0, 'Moderado'], [6.0, 'Moderado'], [6.9, 'Moderado'],
            [7.0, 'Crítico'], [10.0, 'Crítico'],
        ];

        foreach ($casos as [$valor, $esperada]) {
            $this->assertSame(
                $esperada,
                \App\Models\EvaluacionIrritacion::clasificar($valor),
                "El promedio {$valor} no se clasificó como {$esperada}."
            );
        }
    }

    /** Sin promedio no hay clasificación: la matriz está a medias. */
    public function test_sin_promedio_no_hay_clasificacion(): void
    {
        $this->assertNull(\App\Models\EvaluacionIrritacion::clasificar(null));
    }
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=test_la_clasificacion_respeta_los_umbrales_del_instrumento
```

Esperado: FAIL, «Class "App\Models\EvaluacionIrritacion" not found».

- [ ] **Step 3: Escribir la migración**

Crear `database/migrations/2026_08_09_000001_create_evaluaciones_irritacion_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice de Irritación Turística: dos bloques de seis atributos, escala 0-10.
 *
 * Los criterios nacen nullable y sin defecto, a diferencia de las cinco
 * matrices anteriores, que necesitaron una migración posterior para llegar
 * aquí: un criterio sin responder no es un 0, y aquí el 0 significa además el
 * mejor resultado posible.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_irritacion', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            // Bloque de visitantes — 6 atributos.
            $table->tinyInteger('vis_congestion')->nullable();
            $table->tinyInteger('vis_calidad_servicios')->nullable();
            $table->tinyInteger('vis_calidad_actividades')->nullable();
            $table->tinyInteger('vis_calidad_vida')->nullable();
            $table->tinyInteger('vis_apertura')->nullable();
            $table->tinyInteger('vis_seguridad')->nullable();

            // Bloque de la localidad receptora — 6 atributos.
            $table->tinyInteger('res_congestion')->nullable();
            $table->tinyInteger('res_impacto_social')->nullable();
            $table->tinyInteger('res_impacto_economico')->nullable();
            $table->tinyInteger('res_impacto_ambiental')->nullable();
            $table->tinyInteger('res_calidad_vida')->nullable();
            $table->tinyInteger('res_seguridad')->nullable();

            // El sufijo _promedio no es cosmético: EstadoZona::esColumnaDeCriterio()
            // separa criterios de columnas calculadas por él, y un nombre distinto
            // haría que estas dos contaran como criterios en el «8 de 12».
            $table->decimal('visitantes_promedio', 5, 3)->nullable();
            $table->decimal('residentes_promedio', 5, 3)->nullable();

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_irritacion');
    }
};
```

- [ ] **Step 4: Escribir el modelo**

Crear `app/Models/EvaluacionIrritacion.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionIrritacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_irritacion';

    protected $fillable = [
        'zona_id', 'user_id', 'estado',

        'vis_congestion', 'vis_calidad_servicios', 'vis_calidad_actividades',
        'vis_calidad_vida', 'vis_apertura', 'vis_seguridad',

        'res_congestion', 'res_impacto_social', 'res_impacto_economico',
        'res_impacto_ambiental', 'res_calidad_vida', 'res_seguridad',

        'visitantes_promedio', 'residentes_promedio',
    ];

    protected function casts(): array
    {
        return [
            'visitantes_promedio' => 'float',
            'residentes_promedio' => 'float',
        ];
    }

    /** Umbrales del instrumento. La escala es inversa: más alto es peor. */
    public const UMBRAL_CRITICO  = 7;
    public const UMBRAL_MODERADO = 3;

    /**
     * Clasifica un valor de la escala, sea un atributo suelto o el promedio de
     * un bloque: el instrumento aplica los mismos umbrales a los dos.
     *
     * Se deriva en vez de almacenarse, como el cuadrante de Valoración
     * Territorial: guardarla sería una segunda fuente de verdad que se
     * desincroniza en cuanto alguien corrija un umbral.
     */
    public static function clasificar(?float $valor): ?string
    {
        return match (true) {
            $valor === null                 => null,
            $valor >= self::UMBRAL_CRITICO  => 'Crítico',
            $valor >= self::UMBRAL_MODERADO => 'Moderado',
            default                         => 'Bajo',
        };
    }

    public function getClasificacionVisitantesAttribute(): ?string
    {
        return self::clasificar($this->visitantes_promedio);
    }

    public function getClasificacionResidentesAttribute(): ?string
    {
        return self::clasificar($this->residentes_promedio);
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

- [ ] **Step 5: Ejecutar y verificar que pasa**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: PASS, 4 tests.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/2026_08_09_000001_create_evaluaciones_irritacion_table.php \
        app/Models/EvaluacionIrritacion.php tests/Feature/IrritacionTest.php
git commit -m "feat(irritacion): tabla, modelo y clasificacion por umbrales"
```

---

### Task 3: Controlador, rutas y registro

**Files:**
- Create: `app/Http/Controllers/Operativo/EvaluacionIrritacionController.php`
- Modify: `routes/web.php`
- Modify: `app/Matrices/Registro.php`
- Test: ampliar `tests/Feature/IrritacionTest.php`

**Interfaces:**
- Consume: `MatrizPonderadaController` (`criterios()`, `escala()`, `calcular()`).
- Produce: las constantes públicas `VISITANTES` y `RESIDENTES` con los nombres
  de campo de cada bloque, que consumen las vistas.

- [ ] **Step 1: Escribir los tests del cálculo y del estado**

Añadir a `tests/Feature/IrritacionTest.php`:

```php
    private function url(string $sufijo = ''): string
    {
        return "/operativo/zona/{$this->zona->id}/irritacion{$sufijo}";
    }

    /** Los doce atributos al mismo valor. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(
            array_merge(
                \App\Matrices\Irritacion::VISITANTES,
                \App\Matrices\Irritacion::RESIDENTES,
            ),
            $valor
        );
    }

    public function test_cada_bloque_promedia_solo_sus_seis_atributos(): void
    {
        $datos = $this->todosEn(0);

        // Visitantes a 6 de media: 10, 8, 6, 4, 2, 6.
        $valores = [10, 8, 6, 4, 2, 6];
        foreach (\App\Matrices\Irritacion::VISITANTES as $i => $campo) {
            $datos[$campo] = $valores[$i];
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = \App\Models\EvaluacionIrritacion::firstOrFail();

        $this->assertEqualsWithDelta(6.0, $eval->visitantes_promedio, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->residentes_promedio, 0.0001);
        $this->assertSame('Moderado', $eval->clasificacion_visitantes);
        $this->assertSame('Bajo', $eval->clasificacion_residentes);
    }

    /** La escala más ancha del sistema hasta ahora era 0-5. */
    public function test_el_diez_se_acepta_y_el_once_se_rechaza(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(10))
            ->assertSessionHasNoErrors();

        $datos = $this->todosEn(5);
        $datos['vis_congestion'] = 11;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('vis_congestion');
    }

    /** Heredado de la clase base, pero es la primera matriz que nace con ello. */
    public function test_un_atributo_sin_responder_no_baja_la_media(): void
    {
        $datos = $this->todosEn(6);
        unset($datos['vis_congestion']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = \App\Models\EvaluacionIrritacion::firstOrFail();

        $this->assertNull($eval->vis_congestion);
        $this->assertNull($eval->visitantes_promedio);
        $this->assertNull($eval->residentes_promedio);
    }

    public function test_el_jefe_confirma_y_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', \App\Models\EvaluacionIrritacion::value('estado'));

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('confirmado', \App\Models\EvaluacionIrritacion::value('estado'));
    }

    /**
     * La clase base no responde 403 aquí: devuelve al formulario con el
     * mensaje de cerrada. Lo que hay que comprobar es que los valores del jefe
     * siguen intactos, igual que en EvaluacionesTest y PaisajeTest.
     */
    public function test_una_evaluacion_confirmada_queda_cerrada_para_el_equipo(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($this->jefe)->post(
            $this->url(),
            $this->todosEn(4) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($equipo)->from($this->url())
            ->post($this->url(), $this->todosEn(9))
            ->assertSessionHas('error', fn(string $m) => str_contains($m, 'ya fue validado'));

        $eval = \App\Models\EvaluacionIrritacion::firstOrFail();

        $this->assertSame('confirmado', $eval->estado);
        $this->assertSame(4, (int) $eval->vis_congestion);
    }
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: FAIL, «Class ... EvaluacionIrritacionController not found».

- [ ] **Step 3: Escribir el controlador**

Crear `app/Http/Controllers/Operativo/EvaluacionIrritacionController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\Irritacion;
use App\Models\EvaluacionIrritacion;
use App\Models\Zona;

/**
 * Índice de Irritación Turística.
 *
 * Dos encuestas paralelas de seis atributos, una a los visitantes y otra a la
 * localidad receptora, con **escala inversa**: 0 es el mejor caso y 10 la
 * irritación crítica. No hay pesos —el instrumento promedia por igual— ni un
 * índice combinado: cruzar los dos bloques mezclaría dos poblaciones distintas
 * en una cifra que no significa nada.
 */
class EvaluacionIrritacionController extends MatrizPonderadaController
{
    protected function modelo(): string
    {
        return EvaluacionIrritacion::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_irritacion.ponderacion';
    }

    protected function escala(): array
    {
        return [0, 10];
    }

    protected function criterios(): array
    {
        return [
            'visitantes' => Irritacion::VISITANTES,
            'residentes' => Irritacion::RESIDENTES,
        ];
    }

    /**
     * Media simple de cada bloque. Sin pesos: el instrumento no los tiene.
     *
     * La clase base solo llama aquí con la matriz completa; con algún atributo
     * sin responder deja los dos promedios en null y no se llega a esta línea.
     */
    protected function calcular(array $valores): array
    {
        $media = fn(array $campos) => array_sum(
            array_map(fn(string $campo) => (float) $valores[$campo], $campos)
        ) / count($campos);

        return [
            'visitantes_promedio' => $media(Irritacion::VISITANTES),
            'residentes_promedio' => $media(Irritacion::RESIDENTES),
        ];
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        $vis = number_format($datos['visitantes_promedio'], 2);
        $res = number_format($datos['residentes_promedio'], 2);

        return $estado === 'confirmado'
            ? "Índice de Irritación VALIDADO. Visitantes: {$vis} | Residentes: {$res}"
            : "Borrador guardado. Visitantes: {$vis} | Residentes: {$res}";
    }

    protected function mensajeCerrada(): string
    {
        return 'Este Índice de Irritación ya fue validado por el Jefe de Zona. No puedes editarlo.';
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionIrritacion::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_irritacion.form', compact('zona', 'evaluacion'));
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        // ->first() y no ->firstOrFail(): se puede llegar a esta URL antes de
        // completar la matriz, y la vista ya contempla ese caso con un aviso.
        $evaluacion = EvaluacionIrritacion::where('zona_id', $zonaId)->first();

        return view('operativo.evaluacion_irritacion.ponderacion', compact('zona', 'evaluacion'));
    }
}
```

- [ ] **Step 4: Añadir las rutas**

En `routes/web.php`, junto al resto de matrices del grupo operativo, después del
bloque de Valoración Territorial (línea ~89):

```php
        // Índice de Irritación Turística
        Route::get('/irritacion',            [EvaluacionIrritacionController::class, 'edit'])->name('evaluacion_irritacion.edit');
        Route::post('/irritacion',           [EvaluacionIrritacionController::class, 'update'])->name('evaluacion_irritacion.update');
        Route::get('/irritacion/resultados', [EvaluacionIrritacionController::class, 'ponderacion'])->name('evaluacion_irritacion.ponderacion');
```

Y el `use` junto a los demás operativos, en orden alfabético:

```php
use App\Http\Controllers\Operativo\EvaluacionIrritacionController;
```

- [ ] **Step 5: Añadir la entrada al registro**

En `app/Matrices\Registro.php`, añadir el `use` del modelo y, **al final** del
array `ENTRADAS` —el orden de declaración es el orden de recorrido—:

```php
        'irritacion' => [
            'nombre'     => 'Irritación turística',
            'icono'      => 'brujula',
            'grupo'      => 'presion',
            'tipo'       => 'matriz',
            'modelo'     => EvaluacionIrritacion::class,
            'criterios'  => 12,
            'rutas'      => [
                'editar' => 'operativo.evaluacion_irritacion.edit',
                'ver'    => 'operativo.evaluacion_irritacion.ponderacion',
            ],
            'depende_de' => [],
        ],
```

Actualizar también el comentario de `GRUPOS`, que dice que `presion` está vacío
a la espera de cuatro matrices: ahora son tres.

- [ ] **Step 6: Ajustar el recuento del test del registro**

`RegistroMatricesTest::test_solo_las_matrices_validables_cuentan_para_el_progreso`
afirma `assertCount(6, Registro::matrices())`. Pasan a ser 7:

```php
        $this->assertCount(7, Registro::matrices());
```

- [ ] **Step 7: Ejecutar la suite completa**

```bash
php artisan test
```

Esperado: PASS. Si falla
`EstadoZonaTest::test_el_filtro_de_criterios_cuadra_con_el_esquema_de_las_seis_tablas`
diciendo que la tabla nueva deja pasar 14 columnas y el registro declara 12, es
que los promedios no acabaron con el sufijo `_promedio`. Ese test es la red de
esto, no un estorbo: arregla los nombres, no el test.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/Operativo/EvaluacionIrritacionController.php \
        routes/web.php app/Matrices/Registro.php \
        tests/Feature/IrritacionTest.php tests/Feature/RegistroMatricesTest.php
git commit -m "feat(irritacion): controlador, rutas y entrada en el registro"
```

---

### Task 4: Formulario

**Files:**
- Create: `resources/views/operativo/evaluacion_irritacion/form.blade.php`
- Test: ampliar `tests/Feature/IrritacionTest.php`

- [ ] **Step 1: Escribir los tests**

Añadir a `tests/Feature/IrritacionTest.php`:

```php
    public function test_el_formulario_muestra_los_doce_atributos(): void
    {
        $pagina = $this->actingAs($this->jefe)->get($this->url())->assertOk();

        foreach (\App\Matrices\Irritacion::ETIQUETAS as $campo => $etiqueta) {
            $pagina->assertSee("name=\"{$campo}\"", false);
        }
    }

    /**
     * Quien viene de rellenar Paisaje trae la escala al revés en la cabeza.
     * El aviso no es decoración: es lo que evita doce respuestas invertidas.
     */
    public function test_el_formulario_avisa_de_que_la_escala_es_inversa(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('cuanto más alto, peor');
    }

    public function test_el_admin_recibe_el_formulario_bloqueado(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $pagina = $this->actingAs($admin)->get($this->url())->assertOk();

        $pagina->assertSee('disabled', false);
        $pagina->assertDontSee('Guardar Borrador');
    }
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: FAIL, «View [operativo.evaluacion_irritacion.form] not found».

- [ ] **Step 3: Escribir la vista**

Crear `resources/views/operativo/evaluacion_irritacion/form.blade.php`:

```blade
@php
    use App\Matrices\Irritacion;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Índice de Irritación Turística: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            @php
                $esJefe         = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // El admin nunca edita evaluaciones, aunque estén en borrador.
                $bloqueado      = ! auth()->user()->puedeEditarEvaluaciones() || ($estaConfirmado && !$esJefe);
            @endphp

            @if($estaConfirmado)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong class="font-bold text-lg">✓ Índice de Irritación validado</strong>
                            <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                        </div>
                        <a href="{{ route('operativo.evaluacion_irritacion.ponderacion', $zona->id) }}"
                           class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700">
                            Ver Resultados
                        </a>
                    </div>
                </div>
            @else
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    <strong class="font-bold">Modo Borrador</strong>
                    <p>Los datos ingresados son preliminares.</p>
                </div>
            @endif

            {{-- La escala de esta matriz va al revés que la de todas las demás.
                 Quien viene de rellenar Paisaje puntuaría los doce atributos
                 invertidos sin este aviso. --}}
            <div class="mb-6 bg-white border border-gray-200 rounded-lg p-5 shadow-sm">
                <p class="text-base font-semibold text-gray-900 mb-2">
                    Ojo con la escala: aquí mide molestia, así que cuanto más alto, peor.
                </p>
                <div class="flex flex-wrap gap-2 text-sm">
                    <span class="px-3 py-1 rounded bg-green-100 text-green-800">0 a 2 · Bajo</span>
                    <span class="px-3 py-1 rounded bg-amber-100 text-amber-800">3 a 6 · Moderado</span>
                    <span class="px-3 py-1 rounded bg-red-100 text-red-800">7 a 10 · Crítico</span>
                </div>
            </div>

            <x-flash-exito />

            <form method="POST" action="{{ route('operativo.evaluacion_irritacion.update', $zona->id) }}">
                @csrf

                @foreach([
                    'visitantes' => ['titulo' => 'Percepción de los visitantes', 'campos' => Irritacion::VISITANTES],
                    'residentes' => ['titulo' => 'Percepción de la localidad receptora', 'campos' => Irritacion::RESIDENTES],
                ] as $clave => $bloque)
                    <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $bloque['titulo'] }}</h3>
                        <p class="text-sm text-gray-500 mb-5">
                            Seis atributos, de 0 a 10. Se registra el resultado agregado del
                            trabajo de campo, no una respuesta individual.
                        </p>

                        @foreach($bloque['campos'] as $campo)
                            <x-select-0-10
                                :label="Irritacion::ETIQUETAS[$campo]"
                                :name="$campo"
                                :val="$evaluacion->$campo"
                                :disabled="$bloqueado" />
                        @endforeach
                    </section>
                @endforeach

                @unless($bloqueado)
                    <div class="flex justify-end gap-3">
                        <button type="submit"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-5 rounded shadow">
                            Guardar Borrador
                        </button>

                        @if($esJefe)
                            <button type="submit" name="accion_estado" value="confirmado"
                                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow"
                                    onclick="return confirm('Al validar, la evaluación queda cerrada para el equipo. ¿Continuar?');">
                                Validar y Finalizar
                            </button>
                        @endif
                    </div>
                @endunless
            </form>

        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 4: Ejecutar y verificar que pasan**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: PASS.

- [ ] **Step 5: Commit**

```bash
git add resources/views/operativo/evaluacion_irritacion/form.blade.php tests/Feature/IrritacionTest.php
git commit -m "feat(irritacion): formulario con el aviso de escala inversa"
```

---

### Task 5: Resultados

**Files:**
- Create: `resources/views/operativo/evaluacion_irritacion/ponderacion.blade.php`
- Test: ampliar `tests/Feature/IrritacionTest.php`

- [ ] **Step 1: Escribir los tests**

Añadir a `tests/Feature/IrritacionTest.php`:

```php
    public function test_los_resultados_muestran_los_dos_bloques_con_su_interpretacion(): void
    {
        $datos = $this->todosEn(1);
        foreach (\App\Matrices\Irritacion::RESIDENTES as $campo) {
            $datos[$campo] = 8;
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Bajo')
            ->assertSee('Crítico')
            ->assertSee('nivel de aceptación amplio')
            ->assertSee('estado de insatisfacción');
    }

    /** Mismo trato que las otras cinco: sin resultado no se pinta un cero. */
    public function test_con_la_matriz_a_medias_no_hay_resultados(): void
    {
        $datos = $this->todosEn(5);
        unset($datos['res_seguridad']);

        $this->actingAs($this->jefe)->post($this->url(), $datos);

        $this->actingAs($this->jefe)->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('todavía no está completa')
            ->assertDontSee('0.00');
    }
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: FAIL, «View [operativo.evaluacion_irritacion.ponderacion] not found».

- [ ] **Step 3: Escribir la vista**

Crear `resources/views/operativo/evaluacion_irritacion/ponderacion.blade.php`:

```blade
@php
    use App\Matrices\Irritacion;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Índice de Irritación: {{ $zona->nombre }}
        </h2>
    </x-slot>

    {{-- Dos casos, un mismo aviso: la matriz no existe todavía, o existe a
         medias y sus promedios están en null. --}}
    @if(! $evaluacion || $evaluacion->visitantes_promedio === null)
        <x-matriz-sin-resultados
            nombre="Índice de Irritación"
            :zona="$zona"
            ruta-formulario="operativo.evaluacion_irritacion.edit" />
    @else

    @php
        // Clases completas: Tailwind purga las construidas por concatenación.
        $estilos = [
            'Bajo'     => ['caja' => 'bg-green-50 border-green-500', 'texto' => 'text-green-800'],
            'Moderado' => ['caja' => 'bg-amber-50 border-amber-500', 'texto' => 'text-amber-800'],
            'Crítico'  => ['caja' => 'bg-red-50 border-red-500',     'texto' => 'text-red-800'],
        ];

        $interpretacion = [
            'visitantes' => [
                'Bajo'     => 'Los visitantes presentan un nivel de aceptación amplio hacia el destino y su dinámica turística.',
                'Moderado' => 'Los visitantes empiezan a expresar descontento por la dinámica turística que se desarrolla en el lugar.',
                'Crítico'  => 'Los visitantes se encuentran en un estado de insatisfacción con la dinámica turística del sitio.',
            ],
            'residentes' => [
                'Bajo'     => 'Los residentes presentan un nivel de aceptación amplio hacia el destino y su dinámica turística.',
                'Moderado' => 'Los residentes empiezan a expresar descontento por la dinámica turística que se desarrolla en el lugar.',
                'Crítico'  => 'Los residentes se encuentran en un estado de insatisfacción con la dinámica turística del sitio.',
            ],
        ];

        $bloques = [
            'visitantes' => [
                'titulo'        => 'Percepción de los visitantes',
                'campos'        => Irritacion::VISITANTES,
                'promedio'      => $evaluacion->visitantes_promedio,
                'clasificacion' => $evaluacion->clasificacion_visitantes,
            ],
            'residentes' => [
                'titulo'        => 'Percepción de la localidad receptora',
                'campos'        => Irritacion::RESIDENTES,
                'promedio'      => $evaluacion->residentes_promedio,
                'clasificacion' => $evaluacion->clasificacion_residentes,
            ],
        ];
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <p class="text-sm text-gray-500 mb-6">
                La escala es inversa: cuanto más alto el valor, mayor la irritación.
                Los dos bloques no se combinan, porque miden a poblaciones distintas.
            </p>

            @foreach($bloques as $clave => $bloque)
                @php $e = $estilos[$bloque['clasificacion']]; @endphp

                <div class="mb-8">
                    <div class="w-full p-6 rounded-xl border-4 mb-4 {{ $e['caja'] }}">
                        <h3 class="text-lg font-semibold text-gray-800">{{ $bloque['titulo'] }}</h3>
                        <p class="text-4xl font-black mt-2 {{ $e['texto'] }}">
                            {{ number_format($bloque['promedio'], 2) }}
                            <span class="text-xl font-normal text-gray-500">/ 10.00</span>
                        </p>
                        <p class="text-2xl font-bold uppercase mt-1 {{ $e['texto'] }}">
                            {{ $bloque['clasificacion'] }}
                        </p>
                        <p class="text-base text-gray-700 mt-3">
                            {{ $interpretacion[$clave][$bloque['clasificacion']] }}
                        </p>
                    </div>

                    <div class="overflow-x-auto border border-gray-300 rounded-lg">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-100 text-gray-700">
                                <tr>
                                    <th class="p-3 text-left font-medium">Atributo</th>
                                    <th class="p-3 text-center font-medium">Valor</th>
                                    <th class="p-3 text-center font-medium">Clasificación</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                @foreach($bloque['campos'] as $campo)
                                    @php
                                        $valor = $evaluacion->$campo;
                                        $clase = Irritacion::clasificar($valor);
                                    @endphp
                                    <tr>
                                        <td class="p-3 text-gray-800">{{ Irritacion::ETIQUETAS[$campo] }}</td>
                                        <td class="p-3 text-center font-semibold text-gray-900">{{ $valor }}</td>
                                        <td class="p-3 text-center {{ $estilos[$clase]['texto'] }}">{{ $clase }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach

            <div class="text-center">
                <a href="{{ auth()->user()->puedeEditarEvaluaciones()
                            ? route('operativo.evaluacion_irritacion.edit', $zona->id)
                            : route('operativo.zona.panel', $zona->id) }}"
                   class="inline-block px-5 py-2 bg-gray-200 text-black font-bold rounded-lg hover:bg-gray-400 shadow">
                    {{ auth()->user()->puedeEditarEvaluaciones() ? 'Volver al formulario' : 'Volver a la zona' }}
                </a>
            </div>

        </div>
    </div>

    @endif
</x-app-layout>
```

- [ ] **Step 4: Ejecutar y verificar que pasan**

```bash
php artisan test --filter=IrritacionTest
```

Esperado: PASS.

- [ ] **Step 5: Suite completa**

```bash
php artisan test
```

Esperado: PASS. El test del registro que recorre todas las entradas comprueba
que la página de zona pinta la matriz nueva; si falla, revisa la entrada del
registro antes que la vista.

- [ ] **Step 6: Commit**

```bash
git add resources/views/operativo/evaluacion_irritacion/ponderacion.blade.php tests/Feature/IrritacionTest.php
git commit -m "feat(irritacion): resultados por bloque con su interpretacion"
```

---

### Task 6: Revisión final

- [ ] **Step 1: Suite completa y build**

```bash
php artisan test && npm run build
```

- [ ] **Step 2: Comprobar la migración contra PostgreSQL**

La tabla nueva solo se habrá probado en SQLite. Levantar un Postgres desechable
—sin tocar contenedores ajenos— y aplicar todas las migraciones:

```bash
docker run --rm -d --name turismo-verificacion-pg -e POSTGRES_PASSWORD=verif \
  -e POSTGRES_USER=turismo -e POSTGRES_DB=turismo -p 15432:5432 postgres:16-alpine
```

XAMPP trae las extensiones de PostgreSQL comentadas; se cargan por invocación
sin tocar `php.ini`:

```bash
DB_CONNECTION=pgsql DB_HOST=127.0.0.1 DB_PORT=15432 DB_DATABASE=turismo \
DB_USERNAME=turismo DB_PASSWORD=verif \
php -d extension=pdo_pgsql -d extension=pgsql artisan migrate --force
```

Esperado: todas DONE. Después, parar el contenedor **por nombre explícito** y
comprobar que no queda ningún volumen anónimo suyo:

```bash
docker stop turismo-verificacion-pg
docker volume ls -qf dangling=true
```

- [ ] **Step 3: Recorrido manual**

Con `jefe@local.test` / `password`:

1. La zona muestra «Irritación turística» en el grupo «Presión y uso», con
   «12 criterios · sin empezar».
2. En el formulario, la leyenda avisa de la escala inversa y cada opción del
   desplegable lleva su clasificación.
3. Responder ocho atributos y guardar: el mensaje dice «Llevas 8 de 12» y la
   página de zona lo refleja.
4. Entrar a los resultados por URL: sale el aviso de matriz incompleta, no un
   0,00.
5. Completar los doce y validar: dos paneles con su promedio, su clasificación
   y su texto de interpretación.

- [ ] **Step 4: Actualizar el estado del proyecto**

En `docs/ESTADO-PROYECTO.md`, mover Irritación de las matrices pendientes a las
implementadas y dejar anotado que quedan tres. Corregir también que los ficheros
de las matrices **ya están** en `~/Downloads/fwdmatrices` de esta máquina: el
documento dice que hay que copiarlos.

- [ ] **Step 5: Commit final**

```bash
git status --short
```

Si hay cambios sin commitear, revisarlos y commitearlos.

---

## Fuera de este plan

- **Las otras tres matrices.** Involucrados es un CRUD de actores, Concentración
  se solapa con el inventario y Frecuentación tiene una fórmula dudosa en el
  original. Cada una necesita su propio diseño.
- **El gráfico de radar** del instrumento original.
- **Migrar FIT, FET, Percepción y Potencialidad** a los componentes de criterio
  nuevos.
