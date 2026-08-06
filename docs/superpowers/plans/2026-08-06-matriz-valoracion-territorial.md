# Matriz de Valoración Territorial — plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usa
> `superpowers:subagent-driven-development` (recomendada) o
> `superpowers:executing-plans` para ejecutar este plan tarea por tarea. Los pasos
> usan casillas (`- [ ]`) para el seguimiento.

**Objetivo:** implementar la Matriz de Valoración Territorial (dos dimensiones CT y UC
en escala 0–2 que sitúan a la zona en un cuadrante), extrayendo primero una jerarquía
de controladores que elimine la duplicación entre las matrices existentes.

**Arquitectura:** dos niveles de controlador abstracto. El nivel 1
(`EvaluacionZonaController`) concentra la máquina de estados borrador/confirmado y la
persistencia. El nivel 2 (`MatrizPonderadaController`) añade validación y cálculo
declarativos para las matrices de criterios ponderados. Potencialidad extiende el
nivel 1 directamente porque su flujo es atípico; FIT, FET, Percepción y la matriz nueva
extienden el nivel 2.

**Stack:** Laravel 12, PHP 8.2, PostgreSQL en producción y SQLite en pruebas, Blade +
Tailwind 3 + Alpine, Chart.js desde CDN, PHPUnit 11.

**Diseño de referencia:** `docs/superpowers/specs/2026-08-06-matriz-valoracion-territorial-design.md`

## Restricciones globales

- PHP 8.2 exacto. `composer.json` fija `config.platform.php = "8.2"`; no usar sintaxis posterior.
- Toda ruta operativa va dentro del grupo `operativo/zona/{zona}` con middleware `zona`.
- El escalado de roles se comprueba con `$user->esAdmin()`, `esJefe()`, `esEquipo()` — nunca comparando `role_id` a un número.
- `@json()` se usa **solo sobre variables**, nunca sobre arrays literales: la directiva separa su argumento por comas y trunca el array silenciosamente.
- Toda tabla de evaluación lleva `unique('zona_id')` desde su migración inicial.
- Las claves foráneas a `users` usan `nullOnDelete()`.
- Los comentarios y textos de interfaz van en español.
- La suite completa debe quedar verde al final de cada tarea: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`

---

## Estructura de archivos

**Fase 1 — base común**

| Archivo | Responsabilidad |
|---|---|
| `app/Http/Controllers/Operativo/EvaluacionZonaController.php` (nuevo) | Máquina de estados y persistencia. Abstracta. |
| `app/Http/Controllers/Operativo/MatrizPonderadaController.php` (nuevo) | Validación y cálculo declarativos sobre criterios ponderados. Abstracta. |
| `app/Http/Controllers/Operativo/EvaluacionFetController.php` | Pasa a extender el nivel 2. |
| `app/Http/Controllers/Operativo/EvaluacionFitController.php` | Pasa a extender el nivel 2. |
| `app/Http/Controllers/Operativo/EvaluacionPercepcionController.php` | Pasa a extender el nivel 2. |
| `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php` | Pasa a extender el nivel 1. |

**Fase 2 — la matriz**

| Archivo | Responsabilidad |
|---|---|
| `Documentación/Matriz de Valoración Territorial.xlsx` (nuevo) | Instrumento original, como fuente de verdad. |
| `database/matrices/generar_valoracion_territorial.py` (nuevo) | Genera la definición PHP desde el xlsx. Reproducible. |
| `app/Matrices/ValoracionTerritorial.php` (generado) | Criterios, pesos, escala, umbral y descripciones por nivel. |
| `database/migrations/..._create_evaluaciones_valoracion_territorial_table.php` (nuevo) | Esquema. |
| `app/Models/EvaluacionValoracionTerritorial.php` (nuevo) | Modelo, casts y accesor `cuadrante`. |
| `app/Http/Controllers/Operativo/EvaluacionValoracionTerritorialController.php` (nuevo) | Declaración de criterios y cálculo de CT/UC. |
| `resources/views/components/criterio-escala.blade.php` (nuevo) | Tres tarjetas seleccionables con la descripción de cada nivel. |
| `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php` (nuevo) | Formulario. |
| `resources/views/operativo/evaluacion_valoracion_territorial/ponderacion.blade.php` (nuevo) | Resultados. |
| `tests/Feature/ValoracionTerritorialTest.php` (nuevo) | Cálculo, cuadrantes, flujo y autorización. |

---

# FASE 1 — Base común

### Tarea 1: clases base y migración de FET

Se crean **los dos niveles a la vez** porque son pequeños y porque cada uno necesita al
menos un cliente para demostrar que funciona: FET valida el nivel 2 en esta misma
tarea, y Potencialidad valida el nivel 1 en la tarea 4. Crearlos por separado dejaría a
FET colgando temporalmente de una jerarquía que el diseño no le asigna.

**Archivos:**
- Crear: `app/Http/Controllers/Operativo/EvaluacionZonaController.php`
- Crear: `app/Http/Controllers/Operativo/MatrizPonderadaController.php`
- Modificar: `app/Http/Controllers/Operativo/EvaluacionFetController.php`
- Test existente que debe seguir verde: `tests/Feature/EvaluacionesTest.php`

**Interfaces:**
- Produce: `EvaluacionZonaController` con los métodos abstractos `modelo(): string`,
  `rutaResultados(): string`, `prepararDatos(Request, $zonaId, ?Model): array`; los
  hooks `despuesDeGuardar($zonaId, User): void` y
  `mensajeExito(string $estado, array $datos): string`; y el `update()` concreto.
- Produce: `MatrizPonderadaController extends EvaluacionZonaController` con los métodos
  abstractos `criterios(): array` (dimensión → lista de campos), `escala(): array`
  ([min, max]) y `calcular(array $valores): array`; implementa `prepararDatos()` y
  ofrece `campos(): array`.

- [ ] **Paso 1: verificar el punto de partida**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: `OK (67 tests, 169 assertions)`

Si no está verde, **detente**: el refactor necesita la red de tests intacta.

- [ ] **Paso 2: crear la clase base**

Crear `app/Http/Controllers/Operativo/EvaluacionZonaController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Flujo común a todas las evaluaciones por zona.
 *
 * Concentra la máquina de estados borrador → confirmado y la persistencia, que
 * eran idénticas en los cuatro controladores de evaluación salvo el bloque de
 * cálculo. La duplicación ya había costado un fallo replicado cuatro veces
 * (accion_estado sin validar).
 *
 * Cada matriz aporta su modelo, su ruta de resultados y sus datos calculados.
 */
abstract class EvaluacionZonaController extends Controller
{
    /** FQCN del modelo de la evaluación. */
    abstract protected function modelo(): string;

    /** Nombre de la ruta a la que se redirige tras guardar. */
    abstract protected function rutaResultados(): string;

    /**
     * Valida la petición y devuelve las columnas a persistir, sin user_id ni
     * estado, de los que se encarga esta clase.
     *
     * @return array<string, mixed>
     */
    abstract protected function prepararDatos(Request $request, $zonaId, ?Model $actual): array;

    /** Se ejecuta tras guardar. FIT y FET lo usan para la instantánea del VTT. */
    protected function despuesDeGuardar($zonaId, User $user): void
    {
        //
    }

    protected function mensajeCerrada(): string
    {
        return 'Esta evaluación ya fue validada por el Jefe de Zona. No puedes editarla.';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación VALIDADA y CERRADA correctamente.'
            : 'Borrador guardado. El Jefe de Zona debe validarlo.';
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

        $datos = $this->prepararDatos($request, $zonaId, $actual);

        // Solo el Jefe de Zona puede confirmar; el equipo siempre guarda borrador.
        $estado = $user->esJefe()
            ? $request->input('accion_estado', 'borrador')
            : 'borrador';

        $modelo::updateOrCreate(
            ['zona_id' => $zonaId],
            $datos + ['user_id' => $user->id, 'estado' => $estado]
        );

        $this->despuesDeGuardar($zonaId, $user);

        return redirect()
            ->route($this->rutaResultados(), $zonaId)
            ->with('success', $this->mensajeExito($estado, $datos));
    }
}
```

- [ ] **Paso 3: crear el nivel 2**

Crear `app/Http/Controllers/Operativo/MatrizPonderadaController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Matrices de criterios fijos con peso y escala fija.
 *
 * La validación se deriva de la declaración de criterios, de modo que cada
 * matriz solo aporta sus criterios, su escala y su cálculo.
 */
abstract class MatrizPonderadaController extends EvaluacionZonaController
{
    /**
     * Campos agrupados por dimensión o bloque de cálculo.
     *
     * Solo los nombres: los pesos viven donde se usan, en calcular(), porque
     * no todas las matrices ponderan criterio a criterio. FIT y FET promedian
     * por bloque y ponderan el bloque, así que un peso por criterio sería un
     * dato decorativo que nadie lee.
     *
     * @return array<string, list<string>> dimensión => [campo, ...]
     */
    abstract protected function criterios(): array;

    /** @return array{0: int, 1: int} [mínimo, máximo] de la escala */
    abstract protected function escala(): array;

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

    protected function prepararDatos(Request $request, $zonaId, ?Model $actual): array
    {
        [$min, $max] = $this->escala();

        $reglas = [];
        foreach ($this->campos() as $campo) {
            $reglas[$campo] = "required|integer|min:{$min}|max:{$max}";
        }

        $valores = $request->validate($reglas);

        return $valores + $this->calcular($valores);
    }
}
```

- [ ] **Paso 4: migrar FET al nivel 2**

Reemplazar el contenido de `app/Http/Controllers/Operativo/EvaluacionFetController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Models\EvaluacionFet;
use App\Models\User;
use App\Models\VocacionTuristicaTerritorio;
use App\Models\Zona;

class EvaluacionFetController extends MatrizPonderadaController
{
    protected function criterios(): array
    {
        return [
            'demanda' => ['demanda_flujos', 'demanda_estadia'],
            'super'   => ['super_institucionalidad', 'super_organizacion', 'super_planificacion'],
            'imagen'  => ['imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing'],
        ];
    }

    protected function escala(): array
    {
        return [0, 3];
    }

    /** Peso de cada bloque sobre el total. Suman 1.0. */
    private const PESOS = ['demanda' => 0.20, 'super' => 0.40, 'imagen' => 0.40];

    protected function calcular(array $valores): array
    {
        $resultado = [];
        $total = 0.0;

        foreach ($this->criterios() as $bloque => $campos) {
            $media = array_sum(array_map(fn($c) => $valores[$c], $campos)) / count($campos);
            $ponderado = $media * self::PESOS[$bloque];

            $resultado["media_{$bloque}"] = $media;
            $resultado["fet_{$bloque}"]   = $ponderado;

            $total += $ponderado;
        }

        $resultado['fet'] = $total;

        return $resultado;
    }

    protected function modelo(): string
    {
        return EvaluacionFet::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_fet.ponderacion';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación FET VALIDADA y CERRADA correctamente.'
            : 'Borrador FET guardado. El Jefe de Zona debe validarlo.';
    }

    protected function despuesDeGuardar($zonaId, User $user): void
    {
        VocacionTuristicaTerritorio::registrar($zonaId, $user->id);
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFet::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fet.form', compact('zona', 'evaluacion'));
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fet  = EvaluacionFet::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_fet.ponderacion', compact('zona', 'fet'));
    }
}
```

- [ ] **Paso 5: correr la suite**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: `OK (67 tests, 169 assertions)`

Los tests `test_fet_pondera_demanda_superestructura_e_imagen`,
`test_el_jefe_confirma_y_el_equipo_solo_guarda_borrador` y
`test_el_vtt_se_guarda_al_confirmar_y_no_al_consultarlo` cubren exactamente lo que
acabas de mover. Si alguno falla, el refactor cambió comportamiento.

- [ ] **Paso 6: commit**

```bash
git add app/Http/Controllers/Operativo/EvaluacionZonaController.php app/Http/Controllers/Operativo/MatrizPonderadaController.php app/Http/Controllers/Operativo/EvaluacionFetController.php
git commit -m "refactor(evaluaciones): extrae las clases base de evaluación y migra FET"
```

---

### Tarea 2: migración de FIT

**Archivos:**
- Modificar: `app/Http/Controllers/Operativo/EvaluacionFitController.php`

**Interfaces:**
- Consume: `MatrizPonderadaController` de la tarea 1.

- [ ] **Paso 1: migrar FIT**

Reemplazar `app/Http/Controllers/Operativo/EvaluacionFitController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Models\EvaluacionFit;
use App\Models\User;
use App\Models\VocacionTuristicaTerritorio;
use App\Models\Zona;

class EvaluacionFitController extends MatrizPonderadaController
{
    protected function criterios(): array
    {
        return [
            'rtt' => ['recursos_culturales', 'recursos_naturales'],
            'at'  => ['atractivos_manifestaciones', 'atractivos_sitios'],
            'pst' => ['prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza'],
            'ptt' => ['productos_territoriales'],
            'i'   => ['infraestructura_basica', 'infraestructura_apoyo'],
            'ft'  => [
                'facilidades_senaletica', 'facilidades_recepcion',
                'facilidades_interpretacion', 'facilidades_senderos',
                'facilidades_estacionamientos', 'facilidades_campamentos',
                'facilidades_miradores', 'facilidades_sanitarios',
            ],
        ];
    }

    protected function escala(): array
    {
        return [0, 3];
    }

    /** Peso de cada bloque sobre el total. Suman 1.0. */
    private const PESOS = [
        'rtt' => 0.30, 'at' => 0.05, 'pst' => 0.20,
        'ptt' => 0.05, 'i'  => 0.20, 'ft'  => 0.20,
    ];

    protected function calcular(array $valores): array
    {
        $resultado = [];
        $total = 0.0;

        foreach ($this->criterios() as $bloque => $campos) {
            $media = array_sum(array_map(fn($c) => $valores[$c], $campos)) / count($campos);
            $ponderado = $media * self::PESOS[$bloque];

            $resultado["media_{$bloque}"] = $media;
            $resultado["fit_{$bloque}"]   = $ponderado;

            $total += $ponderado;
        }

        $resultado['fit'] = $total;

        return $resultado;
    }

    protected function modelo(): string
    {
        return EvaluacionFit::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_fit.ponderacion';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación FIT VALIDADA y CERRADA correctamente.'
            : 'Borrador FIT guardado. Total: ' . number_format($datos['fit'], 2);
    }

    protected function despuesDeGuardar($zonaId, User $user): void
    {
        VocacionTuristicaTerritorio::registrar($zonaId, $user->id);
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionFit::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_fit.form', compact('zona', 'evaluacion'));
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);
        $fit  = EvaluacionFit::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_fit.ponderacion', compact('zona', 'fit'));
    }
}
```

**Atención:** los pesos por criterio de `criterios()` son solo para derivar la
validación; el cálculo real usa `self::PESOS` por bloque, igual que antes. No cambies
la aritmética: los tests `test_fit_con_la_puntuacion_maxima_da_el_tope_de_la_escala` y
`test_fit_promedia_por_bloque_y_no_por_campo` fijan los valores exactos.

- [ ] **Paso 2: correr la suite**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: `OK (67 tests, 169 assertions)`

- [ ] **Paso 3: commit**

```bash
git add app/Http/Controllers/Operativo/EvaluacionFitController.php
git commit -m "refactor(evaluaciones): migra FIT a MatrizPonderadaController"
```

---

### Tarea 3: migración de Percepción

**Archivos:**
- Modificar: `app/Http/Controllers/Operativo/EvaluacionPercepcionController.php`

**Interfaces:**
- Consume: `MatrizPonderadaController` de la tarea 2.

Percepción tiene dos particularidades: escala 1–3 (no 0–3) y un campo de texto libre
`acciones_mejora` que no es criterio. `prepararDatos()` se sobrescribe para añadirlo.

- [ ] **Paso 1: migrar el controlador**

En `EvaluacionPercepcionController`, cambiar la declaración de clase a
`extends MatrizPonderadaController`, eliminar `update()` completo, y añadir:

```php
    protected function modelo(): string
    {
        return EvaluacionPercepcion::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_percepcion.ponderacion';
    }

    protected function escala(): array
    {
        return [1, 3];
    }

    protected function criterios(): array
    {
        $criterios = [];
        foreach (self::$categorias as $codigo => $cat) {
            $criterios[strtolower($codigo)] = array_keys($cat['items']);
        }

        return $criterios;
    }

    /** Añade el campo de texto libre, que no es un criterio puntuable. */
    protected function prepararDatos(Request $request, $zonaId, ?Model $actual): array
    {
        $datos = parent::prepararDatos($request, $zonaId, $actual);

        $extra = $request->validate(['acciones_mejora' => 'nullable|string|max:5000']);

        return $datos + $extra;
    }

    protected function calcular(array $valores): array
    {
        return $this->calcularPercepcion($valores);
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        $pct = number_format($datos['percepcion_total'] * 100, 2);

        return $estado === 'confirmado'
            ? "Matriz de Percepción VALIDADA correctamente. Percepción total: {$pct}%"
            : "Borrador guardado. Percepción total: {$pct}%";
    }
```

Renombrar el método privado existente `calcular(array $v)` a
`calcularPercepcion(array $v)` sin tocar su cuerpo. Añadir los `use` de
`Illuminate\Database\Eloquent\Model` y `Illuminate\Http\Request` si faltan.

- [ ] **Paso 2: correr la suite**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: `OK (67 tests, 169 assertions)`

`test_percepcion_normaliza_el_total_entre_cero_y_uno` y
`test_percepcion_en_el_minimo_da_cero` fijan los valores exactos.

- [ ] **Paso 3: commit**

```bash
git add app/Http/Controllers/Operativo/EvaluacionPercepcionController.php
git commit -m "refactor(evaluaciones): migra Percepción a MatrizPonderadaController"
```

---

### Tarea 4: migración de Potencialidad

**Archivos:**
- Modificar: `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`

**Interfaces:**
- Consume: `EvaluacionZonaController` de la tarea 1 (nivel 1, no nivel 2).

Potencialidad valida en dos pasadas, persiste `potencialidad_campos_activos` y preserva
los valores de los campos inactivos. Todo eso entra en su `prepararDatos()`.

- [ ] **Paso 1: migrar el controlador**

Cambiar la declaración a `extends EvaluacionZonaController`, eliminar `update()`, y
añadir:

```php
    protected function modelo(): string
    {
        return EvaluacionPotencialidad::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_potencialidad.ponderacion';
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        return $estado === 'confirmado'
            ? 'Evaluación CONFIRMADA. FN: ' . number_format($datos['fn_total'], 2)
              . ' | FX: ' . number_format($datos['fx_total'], 2)
            : 'Borrador guardado correctamente.';
    }

    protected function prepararDatos(Request $request, $zonaId, ?Model $actual): array
    {
        $user = Auth::user();

        // La selección de campos se valida contra la lista blanca antes de nada:
        // sus valores se usan como claves de reglas y se serializan a JSON.
        $request->validate([
            'campos'   => 'nullable|array',
            'campos.*' => ['string', Rule::in($this->getAllCampos())],
        ]);

        $esJefe = $user->esJefe();

        if ($esJefe) {
            $camposActivos = $request->input('campos', []);
        } else {
            $config = PotencialidadCamposActivos::where('zona_id', $zonaId)->first();
            $camposActivos = $config ? $config->campos_activos : $this->getAllCampos();
        }

        $reglas = [];
        foreach ($camposActivos as $campo) {
            $reglas[$campo] = 'integer|min:0|max:2';
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

        $valores = [];
        foreach ($this->getAllCampos() as $campo) {
            $valores[$campo] = in_array($campo, $camposActivos)
                ? (int) $request->input($campo, 0)
                : ($actual->$campo ?? 0);
        }

        return $valores + $this->calcular($valores, $camposActivos);
    }
```

Añadir los `use` de `Illuminate\Database\Eloquent\Model` si falta.

- [ ] **Paso 2: correr la suite**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: `OK (67 tests, 169 assertions)`

- [ ] **Paso 3: commit**

```bash
git add app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php
git commit -m "refactor(evaluaciones): migra Potencialidad a EvaluacionZonaController"
```

---

# FASE 2 — La matriz

### Tarea 5: definición de criterios generada desde el instrumento

**Archivos:**
- Crear: `Documentación/Matriz de Valoración Territorial.xlsx` (copia del original)
- Crear: `database/matrices/generar_valoracion_territorial.py`
- Generar: `app/Matrices/ValoracionTerritorial.php`
- Crear: `tests/Unit/ValoracionTerritorialCriteriosTest.php`

**Interfaces:**
- Produce: `App\Matrices\ValoracionTerritorial` con las constantes `CT` y `UC`
  (mapas `campo => ['sigla', 'peso', 'nombre', 'niveles' => [0,1,2]]`), `ESCALA_MIN`,
  `ESCALA_MAX`, `UMBRAL` y el método estático `todos(): array`.

Transcribir a mano 21 criterios con 63 descripciones y 21 pesos es donde se cuela el
error que ningún test funcional detecta: un `0.05` donde va `0.10` no rompe nada, solo
sesga todas las evaluaciones. Por eso la definición se **genera** desde el xlsx y el
generador queda versionado.

- [ ] **Paso 1: copiar el instrumento al repositorio**

```bash
cp "C:/Users/sebastiantapia_advan/Downloads/fwdmatrices/Matriz de Valoración Territorial.xlsx" "Documentación/"
```

- [ ] **Paso 2: crear el generador**

Requiere `openpyxl` (`pip install openpyxl`). Crear
`database/matrices/generar_valoracion_territorial.py`:

```python
"""Genera la definición PHP de los criterios de la Matriz de Valoración Territorial
directamente desde el instrumento original, para evitar errores de transcripción.

Uso, desde la raíz del proyecto:  python database/matrices/generar_valoracion_territorial.py
"""
import openpyxl
from pathlib import Path

RAIZ = Path(__file__).resolve().parents[2]
ORIGEN = RAIZ / "Documentación" / "Matriz de Valoración Territorial.xlsx"
DESTINO = RAIZ / "app" / "Matrices" / "ValoracionTerritorial.php"

# sigla en el instrumento -> nombre de columna en la base de datos
CAMPOS = {
    'EE': 'ct_energia_electrica',       'AP': 'ct_agua_potable',
    'SC': 'ct_comunicacion',            'RB': 'ct_recoleccion_basura',
    'PS': 'ct_problemas_sociales',      'SS': 'ct_salud',
    'SG': 'ct_seguridad',               'CR': 'ct_conservacion_recursos',
    'AE': 'ct_actividad_economica',     'OS': 'ct_organizacion_social',
    'DC': 'ct_elementos_culturales',    'DN': 'ct_espacios_naturales',
    'V':  'uc_vialidad',                'IC': 'uc_infraestructura_conectividad',
    'FC': 'uc_frecuencia_conectividad', 'DT': 'uc_distancia_atractivo',
    'DS': 'uc_distancia_sitio_visita',  'DD': 'uc_distancia_destino',
    'DM': 'uc_distancia_mercado_emisor','CO': 'uc_conglomeracion_oferta',
    'S':  'uc_senalizacion',
}

wb = openpyxl.load_workbook(ORIGEN, data_only=True)


def limpiar(t):
    return " ".join(str(t).split()) if t else ""


def leer(hoja_ref, fila_ini, fila_fin, hoja_val, val_ini, val_fin):
    """Empareja la hoja de descripciones con la de pesos. Ambas listan los
    criterios en el mismo orden; la aserción lo verifica."""
    ref, val = wb[hoja_ref], wb[hoja_val]
    filas_ref = list(range(fila_ini, fila_fin + 1))
    filas_val = list(range(val_ini, val_fin + 1))
    assert len(filas_ref) == len(filas_val), "las hojas no tienen el mismo número de criterios"

    salida = []
    for fr, fv in zip(filas_ref, filas_val):
        sigla = limpiar(val.cell(fv, 6).value)          # columna F
        salida.append({
            'sigla': sigla,
            'campo': CAMPOS[sigla],
            'peso': val.cell(fv, 2).value,              # columna B
            'nombre': limpiar(ref.cell(fr, 1).value),   # columna A
            'desc': [limpiar(ref.cell(fr, c).value) for c in (2, 3, 4)],  # B, C, D
        })
    return salida


ct = leer('Contenido Territorial', 6, 17, 'Valoración CT', 5, 16)
uc = leer('Ubicación y Conectividad', 6, 14, 'Valoración UC', 5, 13)

for nombre, grupo in (('CT', ct), ('UC', uc)):
    total = round(sum(c['peso'] for c in grupo), 10)
    assert total == 1.0, f"{nombre}: los pesos suman {total}, no 1.0"
    print(f"// {nombre}: {len(grupo)} criterios, pesos suman {total}")


def php_str(s):
    return "'" + s.replace("\\", "\\\\").replace("'", "\\'") + "'"


lineas = ["<?php", "", "namespace App\\Matrices;", "",
          "/**", " * Criterios de la Matriz de Valoración Territorial.",
          " *", " * GENERADO por database/matrices/generar_valoracion_territorial.py",
          " * desde Documentación/Matriz de Valoración Territorial.xlsx.",
          " * No editar a mano: vuelve a ejecutar el generador.",
          " *", " * Instrumento de Calle Lituma y Chaca Espinoza. Los pesos de cada",
          " * dimensión suman 1 y la escala es 0-2, así que cada total va de 0 a 2.",
          " */", "class ValoracionTerritorial", "{",
          "    public const ESCALA_MIN = 0;",
          "    public const ESCALA_MAX = 2;", "",
          "    /** Umbral que separa los cuadrantes en ambos ejes. */",
          "    public const UMBRAL = 1.0;", ""]

for clave, grupo, titulo, fuente in (
    ('CT', ct, 'Contenido Territorial',
     'PDOT, PDT, fuentes secundarias y fuentes oficiales públicas. Para elementos '
     'culturales y espacios naturales: visitas in situ y documentos públicos.'),
    ('UC', uc, 'Ubicación y Conectividad',
     'PDOT, fuentes de información primaria y secundaria, visitas in situ y '
     'documentos oficiales.'),
):
    lineas.append(f"    /** {titulo}. Fuente sugerida: {fuente} */")
    lineas.append(f"    public const {clave} = [")
    for c in grupo:
        lineas.append(f"        {php_str(c['campo'])} => [")
        lineas.append(f"            'sigla'  => {php_str(c['sigla'])},")
        lineas.append(f"            'peso'   => {c['peso']},")
        lineas.append(f"            'nombre' => {php_str(c['nombre'])},")
        lineas.append("            'niveles' => [")
        for i, d in enumerate(c['desc']):
            lineas.append(f"                {i} => {php_str(d)},")
        lineas.append("            ],")
        lineas.append("        ],")
    lineas.append("    ];")
    lineas.append("")

lineas += ["    /** @return array<string, array> todos los criterios, CT seguidos de UC */",
           "    public static function todos(): array",
           "    {",
           "        return self::CT + self::UC;",
           "    }",
           "}"]

DESTINO.parent.mkdir(parents=True, exist_ok=True)
DESTINO.write_text("\n".join(lineas) + "\n", encoding="utf-8")
print(f"escrito: {DESTINO}")
```

Las dos aserciones son la garantía real: si los pesos no suman 1 o las hojas se
desalinean, el generador falla en vez de emitir una definición sesgada.

- [ ] **Paso 3: ejecutar el generador**

Run: `python database/matrices/generar_valoracion_territorial.py`
Esperado:
```
// CT: 12 criterios, pesos suman 1.0
// UC: 9 criterios, pesos suman 1.0
```

- [ ] **Paso 4: escribir el test de la definición**

Crear `tests/Unit/ValoracionTerritorialCriteriosTest.php`:

```php
<?php

namespace Tests\Unit;

use App\Matrices\ValoracionTerritorial;
use PHPUnit\Framework\TestCase;

class ValoracionTerritorialCriteriosTest extends TestCase
{
    public function test_los_pesos_de_cada_dimension_suman_uno(): void
    {
        $this->assertEqualsWithDelta(1.0, array_sum(array_column(ValoracionTerritorial::CT, 'peso')), 0.0001);
        $this->assertEqualsWithDelta(1.0, array_sum(array_column(ValoracionTerritorial::UC, 'peso')), 0.0001);
    }

    public function test_el_numero_de_criterios_coincide_con_el_instrumento(): void
    {
        $this->assertCount(12, ValoracionTerritorial::CT);
        $this->assertCount(9, ValoracionTerritorial::UC);
        $this->assertCount(21, ValoracionTerritorial::todos());
    }

    public function test_cada_criterio_describe_sus_tres_niveles(): void
    {
        foreach (ValoracionTerritorial::todos() as $campo => $criterio) {
            $this->assertArrayHasKey('nombre', $criterio, $campo);
            $this->assertCount(3, $criterio['niveles'], $campo);

            foreach ([0, 1, 2] as $nivel) {
                $this->assertNotEmpty($criterio['niveles'][$nivel], "{$campo} nivel {$nivel}");
            }
        }
    }
}
```

- [ ] **Paso 5: correr los tests nuevos**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit --filter ValoracionTerritorialCriteriosTest`
Esperado: `OK (3 tests, ...)`

- [ ] **Paso 6: commit**

```bash
git add "Documentación/Matriz de Valoración Territorial.xlsx" database/matrices/ app/Matrices/ tests/Unit/ValoracionTerritorialCriteriosTest.php
git commit -m "feat(valoracion-territorial): define los criterios desde el instrumento original"
```

---

### Tarea 6: migración y modelo

**Archivos:**
- Crear: `database/migrations/2026_08_06_000005_create_evaluaciones_valoracion_territorial_table.php`
- Crear: `app/Models/EvaluacionValoracionTerritorial.php`
- Crear: `tests/Feature/ValoracionTerritorialTest.php` (solo el test del accesor por ahora)

**Interfaces:**
- Consume: `App\Matrices\ValoracionTerritorial` de la tarea 5.
- Produce: modelo `EvaluacionValoracionTerritorial` con `$fillable`, casts
  `ct_total`/`uc_total` a `float`, y el accesor `cuadrante` (string).

- [ ] **Paso 1: escribir la migración**

Crear la migración con este contenido:

```php
<?php

use App\Matrices\ValoracionTerritorial;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones_valoracion_territorial', function (Blueprint $table) {
            $table->id();

            $table->foreignId('zona_id')->unique()->constrained('zonas')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('estado', ['borrador', 'confirmado'])->default('borrador');

            foreach (array_keys(ValoracionTerritorial::todos()) as $campo) {
                $table->tinyInteger($campo)->default(0);
            }

            $table->decimal('ct_total', 5, 3)->default(0);
            $table->decimal('uc_total', 5, 3)->default(0);

            $table->timestamps();

            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluaciones_valoracion_territorial');
    }
};
```

- [ ] **Paso 2: escribir el modelo**

Crear `app/Models/EvaluacionValoracionTerritorial.php`:

```php
<?php

namespace App\Models;

use App\Matrices\ValoracionTerritorial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionValoracionTerritorial extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_valoracion_territorial';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'ct_total' => 'float',
            'uc_total' => 'float',
        ];
    }

    public function __construct(array $attributes = [])
    {
        // Los 21 criterios se declaran una sola vez, en la definición del
        // instrumento; repetirlos aquí sería una segunda fuente de verdad.
        $this->fillable = array_merge(
            ['zona_id', 'user_id', 'estado', 'ct_total', 'uc_total'],
            array_keys(ValoracionTerritorial::todos())
        );

        parent::__construct($attributes);
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cuadrante según los cuatro definidos en el instrumento original.
     *
     * Se deriva en vez de almacenarse: el VTT sí guardaba su resultado y por eso
     * podía quedar desfasado respecto a los datos que lo originaban.
     */
    public function getCuadranteAttribute(): string
    {
        $ct = $this->ct_total >= ValoracionTerritorial::UMBRAL;
        $uc = $this->uc_total >= ValoracionTerritorial::UMBRAL;

        return match (true) {
            $ct && $uc   => 'Territorio a Priorizar para el Turismo IV',
            !$ct && $uc  => 'Territorio con Limitación II',
            $ct && !$uc  => 'Territorio con Limitación III',
            default      => 'Territorio No Apto para el Turismo',
        };
    }
}
```

- [ ] **Paso 3: escribir el test del accesor**

Crear `tests/Feature/ValoracionTerritorialTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\EvaluacionValoracionTerritorial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ValoracionTerritorialTest extends TestCase
{
    use RefreshDatabase;

    public static function cuadrantes(): array
    {
        return [
            'ambos bajos'      => [0.5, 0.5, 'Territorio No Apto para el Turismo'],
            'solo conectado'   => [0.5, 1.5, 'Territorio con Limitación II'],
            'solo contenido'   => [1.5, 0.5, 'Territorio con Limitación III'],
            'ambos altos'      => [1.5, 1.5, 'Territorio a Priorizar para el Turismo IV'],
            'justo en el umbral' => [1.0, 1.0, 'Territorio a Priorizar para el Turismo IV'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('cuadrantes')]
    public function test_el_cuadrante_se_deriva_de_los_totales(float $ct, float $uc, string $esperado): void
    {
        $evaluacion = new EvaluacionValoracionTerritorial([
            'ct_total' => $ct,
            'uc_total' => $uc,
        ]);

        $this->assertSame($esperado, $evaluacion->cuadrante);
    }
}
```

El caso `justo en el umbral` fija por escrito la decisión de tratar 1.00 como alto.

- [ ] **Paso 4: correr los tests**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit --filter ValoracionTerritorialTest`
Esperado: `OK (5 tests, 5 assertions)`

- [ ] **Paso 5: verificar la migración contra PostgreSQL**

La suite corre en SQLite y producción es PostgreSQL. Levantar Postgres y migrar:

```bash
docker network create turismo-test
docker run -d --name turismo-pg-plan --network turismo-test -e POSTGRES_PASSWORD=secret -e POSTGRES_DB=turismo -e POSTGRES_USER=turismo postgres:16-alpine
```

Esperar 10 segundos y ejecutar las migraciones con `DB_CONNECTION=pgsql`,
`DB_HOST=turismo-pg-plan`. Esperado: la migración nueva aparece como `DONE`.

Limpiar después con `docker stop turismo-pg-plan && docker container prune --force`.

- [ ] **Paso 6: commit**

```bash
git add database/migrations/ app/Models/EvaluacionValoracionTerritorial.php tests/Feature/ValoracionTerritorialTest.php
git commit -m "feat(valoracion-territorial): tabla, modelo y cuadrante derivado"
```

---

### Tarea 7: controlador, cálculo y rutas

**Archivos:**
- Crear: `app/Http/Controllers/Operativo/EvaluacionValoracionTerritorialController.php`
- Modificar: `routes/web.php`
- Modificar: `tests/Feature/ValoracionTerritorialTest.php`

**Interfaces:**
- Consume: `MatrizPonderadaController` (tarea 2), `ValoracionTerritorial` (tarea 5),
  `EvaluacionValoracionTerritorial` (tarea 6).
- Produce: rutas `operativo.evaluacion_valoracion_territorial.edit`, `.update` y
  `.ponderacion`.

- [ ] **Paso 1: escribir los tests de cálculo primero**

Añadir a `tests/Feature/ValoracionTerritorialTest.php` (más los `use` de
`App\Matrices\ValoracionTerritorial`, `App\Models\Role`, `App\Models\User`,
`App\Models\Zona`, `Database\Seeders\SystemSeeder`, `Illuminate\Support\Facades\DB`):

```php
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

    /** Rellena los 21 criterios con el mismo valor. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(array_keys(ValoracionTerritorial::todos()), $valor);
    }

    private function url(string $sufijo = ''): string
    {
        return "/operativo/zona/{$this->zona->id}/valoracion-territorial{$sufijo}";
    }

    public function test_todos_los_criterios_al_maximo_dan_el_tope_de_la_escala(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionValoracionTerritorial::firstOrFail();

        $this->assertEqualsWithDelta(2.0, $eval->ct_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->uc_total, 0.0001);
        $this->assertSame('Territorio a Priorizar para el Turismo IV', $eval->cuadrante);
    }

    public function test_todos_los_criterios_en_uno_dan_exactamente_uno(): void
    {
        // Los pesos suman 1, así que este es el caso del umbral.
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(1))
            ->assertSessionHasNoErrors();

        $eval = EvaluacionValoracionTerritorial::firstOrFail();

        $this->assertEqualsWithDelta(1.0, $eval->ct_total, 0.0001);
        $this->assertEqualsWithDelta(1.0, $eval->uc_total, 0.0001);
        $this->assertSame('Territorio a Priorizar para el Turismo IV', $eval->cuadrante);
    }

    public function test_cada_criterio_aporta_segun_su_peso(): void
    {
        // Vialidad pesa 0.15 en UC; con calificación 2 aporta 0.30 y nada más.
        $datos = $this->todosEn(0);
        $datos['uc_vialidad'] = 2;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionValoracionTerritorial::firstOrFail();

        $this->assertEqualsWithDelta(0.30, $eval->uc_total, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->ct_total, 0.0001);
    }

    public function test_una_calificacion_fuera_de_escala_se_rechaza(): void
    {
        $datos = $this->todosEn(1);
        $datos['ct_salud'] = 3;

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasErrors('ct_salud');

        $this->assertDatabaseCount('evaluaciones_valoracion_territorial', 0);
    }

    public function test_el_equipo_solo_guarda_borrador(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $this->actingAs($equipo)->post(
            $this->url(),
            $this->todosEn(2) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionValoracionTerritorial::value('estado'));
    }

    public function test_no_se_accede_desde_una_zona_ajena(): void
    {
        $ajeno = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($ajeno)->get($this->url())->assertForbidden();
    }
```

- [ ] **Paso 2: correr los tests y verificar que fallan**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit --filter ValoracionTerritorialTest`
Esperado: FAIL con `Route [operativo.evaluacion_valoracion_territorial...] not defined`
o error 404. Los cinco tests del cuadrante siguen pasando.

- [ ] **Paso 3: escribir el controlador**

Crear `app/Http/Controllers/Operativo/EvaluacionValoracionTerritorialController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\ValoracionTerritorial;
use App\Models\EvaluacionValoracionTerritorial;
use App\Models\Zona;

class EvaluacionValoracionTerritorialController extends MatrizPonderadaController
{
    protected function modelo(): string
    {
        return EvaluacionValoracionTerritorial::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_valoracion_territorial.ponderacion';
    }

    protected function escala(): array
    {
        return [ValoracionTerritorial::ESCALA_MIN, ValoracionTerritorial::ESCALA_MAX];
    }

    protected function criterios(): array
    {
        return [
            'ct' => array_keys(ValoracionTerritorial::CT),
            'uc' => array_keys(ValoracionTerritorial::UC),
        ];
    }

    /**
     * Suma ponderada por dimensión, con los pesos del instrumento original.
     * Como cada dimensión suma 1 y la escala es 0-2, cada total va de 0 a 2.
     */
    protected function calcular(array $valores): array
    {
        $dimensiones = ['ct' => ValoracionTerritorial::CT, 'uc' => ValoracionTerritorial::UC];

        $totales = [];

        foreach ($dimensiones as $dimension => $criterios) {
            $totales["{$dimension}_total"] = array_sum(array_map(
                fn($campo, $criterio) => $valores[$campo] * $criterio['peso'],
                array_keys($criterios),
                $criterios
            ));
        }

        return $totales;
    }

    protected function mensajeExito(string $estado, array $datos): string
    {
        $ct = number_format($datos['ct_total'], 2);
        $uc = number_format($datos['uc_total'], 2);

        return $estado === 'confirmado'
            ? "Valoración Territorial VALIDADA. CT: {$ct} | UC: {$uc}"
            : "Borrador guardado. CT: {$ct} | UC: {$uc}";
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionValoracionTerritorial::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_valoracion_territorial.form', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'ct'         => ValoracionTerritorial::CT,
            'uc'         => ValoracionTerritorial::UC,
        ]);
    }

    public function ponderacion($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionValoracionTerritorial::where('zona_id', $zonaId)->firstOrFail();

        return view('operativo.evaluacion_valoracion_territorial.ponderacion', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'ct'         => ValoracionTerritorial::CT,
            'uc'         => ValoracionTerritorial::UC,
        ]);
    }
}
```

- [ ] **Paso 4: registrar las rutas**

En `routes/web.php`, dentro del grupo `Route::prefix('operativo/zona/{zona}')->middleware('zona')->name('operativo.')`, añadir tras el bloque de percepción:

```php
        // Matriz de Valoración Territorial
        Route::get('/valoracion-territorial',            [EvaluacionValoracionTerritorialController::class, 'edit'])->name('evaluacion_valoracion_territorial.edit');
        Route::post('/valoracion-territorial',           [EvaluacionValoracionTerritorialController::class, 'update'])->name('evaluacion_valoracion_territorial.update');
        Route::get('/valoracion-territorial/resultados', [EvaluacionValoracionTerritorialController::class, 'ponderacion'])->name('evaluacion_valoracion_territorial.ponderacion');
```

Y añadir el `use App\Http\Controllers\Operativo\EvaluacionValoracionTerritorialController;` en la cabecera.

- [ ] **Paso 5: correr la suite completa**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: los 67 anteriores más los nuevos, todos verdes. Los tests que invocan
`edit()` fallarán con «View not found» hasta la tarea 8: coméntalos temporalmente si
bloquean, o ejecútalos con `--filter` sobre los de cálculo.

- [ ] **Paso 6: commit**

```bash
git add app/Http/Controllers/Operativo/EvaluacionValoracionTerritorialController.php routes/web.php tests/Feature/ValoracionTerritorialTest.php
git commit -m "feat(valoracion-territorial): controlador, cálculo ponderado y rutas"
```

---

### Tarea 8: formulario

**Archivos:**
- Crear: `resources/views/components/criterio-escala.blade.php`
- Crear: `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`

**Interfaces:**
- Consume: las rutas de la tarea 7 y las constantes de la tarea 5.
- Produce: componente `<x-criterio-escala :campo :criterio :valor :bloqueado />`.

- [ ] **Paso 1: crear el componente de criterio**

Crear `resources/views/components/criterio-escala.blade.php`:

```blade
@props(['campo', 'criterio', 'bloqueado' => false])

{{--
    Tres tarjetas seleccionables, cada una con la descripción textual de su
    nivel. La descripción es la opción: es lo que permite que dos evaluadores
    distintos puntúen igual, y evita tener que consultar el instrumento aparte.

    El estado vive en el `x-data` de la sección que envuelve al componente, no
    aquí: así la sección puede sumar el subtotal de todos sus criterios.
--}}
<fieldset class="border-b border-gray-200 py-4">
    <legend class="font-semibold text-gray-800 text-sm mb-3">
        {{ $criterio['nombre'] }}
        <span class="text-xs text-gray-400 font-normal">({{ $criterio['sigla'] }} · peso {{ $criterio['peso'] * 100 }}%)</span>
    </legend>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($criterio['niveles'] as $nivel => $descripcion)
            <label class="block border rounded-lg p-3 transition cursor-pointer"
                   :class="valores['{{ $campo }}'] === {{ $nivel }}
                       ? 'ring-2 ring-indigo-500 bg-indigo-50'
                       : 'bg-white hover:bg-gray-50'">
                <input type="radio"
                       name="{{ $campo }}"
                       value="{{ $nivel }}"
                       x-model.number="valores['{{ $campo }}']"
                       @disabled($bloqueado)
                       class="sr-only">
                <span class="block font-bold text-sm mb-1"
                      :class="valores['{{ $campo }}'] === {{ $nivel }} ? 'text-indigo-700' : 'text-gray-400'">
                    {{ $nivel }}
                </span>
                <span class="block text-xs text-gray-600 leading-snug">{{ $descripcion }}</span>
            </label>
        @endforeach
    </div>

    @error($campo)
        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
    @enderror
</fieldset>
```

- [ ] **Paso 2: crear el formulario**

Crear `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`.
Estructura, siguiendo el patrón de `evaluacion_fet/form.blade.php` para la cabecera,
los avisos de estado y los botones:

1. `<x-app-layout>` con `<x-slot name="header">` que muestre el nombre de la zona.
2. Banner de estado: si `$evaluacion->estado === 'confirmado'`, aviso verde de
   validada; si no, aviso amarillo de borrador. Calcular
   `$bloqueado = $evaluacion->estado === 'confirmado' && auth()->user()->esEquipo()`.
3. `<form method="POST" action="{{ route('operativo.evaluacion_valoracion_territorial.update', $zona->id) }}">` con `@csrf`.
4. Dos secciones, una por dimensión:

```blade
        @php
            // Estado inicial de Alpine: campo => calificación guardada.
            $inicialCt = collect($ct)->mapWithKeys(fn($c, $campo) => [$campo => (int) ($evaluacion->$campo ?? 0)]);
            $pesosCt   = collect($ct)->map(fn($c) => $c['peso']);
        @endphp

        <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                 x-data="{ valores: @js($inicialCt), pesos: @js($pesosCt) }">
            <div class="flex justify-between items-baseline mb-1">
                <h3 class="text-lg font-bold text-gray-800">Contenido Territorial (CT)</h3>
                <span class="text-sm font-bold text-indigo-700">
                    Subtotal:
                    <span x-text="Object.entries(valores)
                        .reduce((t, [k, v]) => t + v * pesos[k], 0).toFixed(3)"></span>
                    / 2.000
                </span>
            </div>
            <p class="text-xs text-gray-500 mb-4">
                Fuente sugerida: PDOT, PDT, fuentes secundarias y fuentes oficiales
                públicas. Para elementos culturales y espacios naturales, visitas in
                situ y documentos públicos.
            </p>

            @foreach($ct as $campo => $criterio)
                <x-criterio-escala :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />
            @endforeach
        </section>
```

   Repetir para `$uc` con `$inicialUc` y `$pesosUc`, el título «Ubicación y
   Conectividad (UC)» y su fuente («PDOT, fuentes de información primaria y secundaria,
   visitas in situ y documentos oficiales»).

   `@js()` es la directiva correcta para volcar un array a un atributo HTML: codifica
   comillas y no sufre el problema de `@json()` con arrays literales, porque aquí se le
   pasa una variable.

5. Botones al pie:

```blade
        @unless($bloqueado)
            <div class="flex justify-end gap-3">
                <button type="submit"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-5 rounded shadow">
                    Guardar Borrador
                </button>

                @if(auth()->user()->esJefe())
                    <button type="submit" name="accion_estado" value="confirmado"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow"
                            onclick="return confirm('Al validar, la evaluación queda cerrada para el equipo. ¿Continuar?');">
                        Validar y Finalizar
                    </button>
                @endif
            </div>
        @endunless
```

- [ ] **Paso 3: verificar que el formulario se renderiza**

Añadir a `tests/Feature/ValoracionTerritorialTest.php`:

```php
    public function test_el_formulario_se_renderiza_con_los_21_criterios(): void
    {
        $respuesta = $this->actingAs($this->jefe)->get($this->url());

        $respuesta->assertOk();

        foreach (array_keys(ValoracionTerritorial::todos()) as $campo) {
            $respuesta->assertSee('name="' . $campo . '"', false);
        }
    }
```

- [ ] **Paso 4: correr la suite**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: todo verde, incluidos los tests que antes fallaban por vista ausente.

- [ ] **Paso 5: commit**

```bash
git add resources/views/components/criterio-escala.blade.php resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php tests/Feature/ValoracionTerritorialTest.php
git commit -m "feat(valoracion-territorial): formulario con descripciones por criterio"
```

---

### Tarea 9: vista de resultados

**Archivos:**
- Crear: `resources/views/operativo/evaluacion_valoracion_territorial/ponderacion.blade.php`
- Modificar: `tests/Feature/ValoracionTerritorialTest.php`

- [ ] **Paso 1: crear la vista**

Tomar como referencia `resources/views/operativo/evaluacion_potencialidad/ponderacion.blade.php`, que ya
resuelve el gráfico de dispersión con cuadrantes. La vista contiene:

1. **Tarjeta del cuadrante**, con el mapa de clases completas (Tailwind purga las
   clases construidas por concatenación):

```blade
@php
    $estilos = [
        'Territorio a Priorizar para el Turismo IV' => ['caja' => 'bg-green-50 border-green-500',  'texto' => 'text-green-700',  'emoji' => '🟢', 'lectura' => 'Apto en ambas dimensiones: contenido territorial y conectividad.'],
        'Territorio con Limitación II'              => ['caja' => 'bg-yellow-50 border-yellow-500','texto' => 'text-yellow-700', 'emoji' => '🟡', 'lectura' => 'Bien conectado, pero sin base territorial suficiente.'],
        'Territorio con Limitación III'             => ['caja' => 'bg-blue-50 border-blue-500',    'texto' => 'text-blue-700',   'emoji' => '🔵', 'lectura' => 'Con base territorial, pero aislado.'],
        'Territorio No Apto para el Turismo'        => ['caja' => 'bg-red-50 border-red-500',      'texto' => 'text-red-700',    'emoji' => '🔴', 'lectura' => 'Ni contenido territorial ni conectividad.'],
    ][$evaluacion->cuadrante];
@endphp
```

2. **Los dos totales**, `ct_total` y `uc_total`, con `number_format(..., 2)` y la nota
   «Escala 0 – 2».

3. **Tabla por dimensión** con criterio, calificación, peso y aporte:

```blade
        @foreach([['Contenido Territorial (CT)', $ct, $evaluacion->ct_total], ['Ubicación y Conectividad (UC)', $uc, $evaluacion->uc_total]] as [$titulo, $grupo, $total])
            <h4 class="font-bold text-gray-700 mt-6 mb-2">{{ $titulo }}</h4>
            <table class="min-w-full text-sm border-collapse border border-gray-300 mb-4">
                <thead class="bg-gray-100 text-xs uppercase">
                    <tr>
                        <th class="border border-gray-300 p-2 text-left">Criterio</th>
                        <th class="border border-gray-300 p-2">Calificación</th>
                        <th class="border border-gray-300 p-2">Peso</th>
                        <th class="border border-gray-300 p-2">Aporte</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($grupo as $campo => $criterio)
                        <tr>
                            <td class="border border-gray-300 p-2">{{ $criterio['nombre'] }}</td>
                            <td class="border border-gray-300 p-2 text-center font-bold">{{ $evaluacion->$campo }}</td>
                            <td class="border border-gray-300 p-2 text-center">{{ $criterio['peso'] * 100 }}%</td>
                            <td class="border border-gray-300 p-2 text-center">{{ number_format($evaluacion->$campo * $criterio['peso'], 3) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-gray-50 font-bold">
                    <tr>
                        <td colspan="3" class="border border-gray-300 p-2 text-right">TOTAL</td>
                        <td class="border border-gray-300 p-2 text-center">{{ number_format($total, 3) }}</td>
                    </tr>
                </tfoot>
            </table>
        @endforeach
```

4. **Gráfico de dispersión** con Chart.js. Los datos se pasan por variable, nunca como
   array literal dentro de `@json()`:

```blade
    @php
        $punto = [['x' => (float) $evaluacion->ct_total, 'y' => (float) $evaluacion->uc_total]];
    @endphp
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('cuadranteVT').getContext('2d'), {
            type: 'scatter',
            data: {
                datasets: [{
                    label: @json($zona->nombre),
                    data: @json($punto),
                    backgroundColor: 'rgba(79, 70, 229, 0.9)',
                    pointRadius: 12,
                }],
            },
            options: {
                scales: {
                    x: { min: 0, max: 2, title: { display: true, text: 'Contenido Territorial' } },
                    y: { min: 0, max: 2, title: { display: true, text: 'Ubicación y Conectividad' } },
                },
            },
        });
    </script>
```

5. Rótulos de los cuatro cuadrantes alrededor del lienzo, con la misma disposición que
   el instrumento:

```blade
    <div class="max-w-2xl mx-auto">
        <div class="grid grid-cols-2 gap-2 text-xs text-center mb-2">
            <div class="bg-yellow-50 border border-yellow-300 rounded p-2">
                <strong>Territorio con Limitación II</strong><br>
                <span class="text-gray-500">UC &gt; 1 · CT &lt; 1</span>
            </div>
            <div class="bg-green-50 border border-green-300 rounded p-2">
                <strong>Territorio a Priorizar para el Turismo IV</strong><br>
                <span class="text-gray-500">CT &gt; 1 · UC &gt; 1</span>
            </div>
        </div>

        <canvas id="cuadranteVT" height="420"></canvas>

        <div class="grid grid-cols-2 gap-2 text-xs text-center mt-2">
            <div class="bg-red-50 border border-red-300 rounded p-2">
                <strong>Territorio No Apto para el Turismo</strong><br>
                <span class="text-gray-500">UC &lt; 1 · CT &lt; 1</span>
            </div>
            <div class="bg-blue-50 border border-blue-300 rounded p-2">
                <strong>Territorio con Limitación III</strong><br>
                <span class="text-gray-500">UC &lt; 1 · CT &gt; 1</span>
            </div>
        </div>
    </div>
```

- [ ] **Paso 2: añadir el test de renderizado**

```php
    public function test_la_pagina_de_resultados_se_renderiza(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2));

        $this->actingAs($this->jefe)
            ->get($this->url('/resultados'))
            ->assertOk()
            ->assertSee('Territorio a Priorizar para el Turismo IV');
    }
```

- [ ] **Paso 3: correr la suite**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: todo verde.

- [ ] **Paso 4: commit**

```bash
git add resources/views/operativo/evaluacion_valoracion_territorial/ponderacion.blade.php tests/Feature/ValoracionTerritorialTest.php
git commit -m "feat(valoracion-territorial): vista de resultados con cuadrante y desglose"
```

---

### Tarea 10: integración en dashboard y panel de administración

**Archivos:**
- Modificar: `resources/views/operativo/dashboard.blade.php`
- Modificar: `app/Http/Controllers/Operativo/DashboardController.php`
- Modificar: `app/Http/Controllers/Admin/ZonaController.php`
- Modificar: `routes/web.php`
- Modificar: `resources/views/admin/zonas/index.blade.php`

- [ ] **Paso 1: cargar el estado en el dashboard**

En `DashboardController::index()`, junto a `$evaluaciones` y `$percepciones`, añadir:

```php
        $valoraciones = EvaluacionValoracionTerritorial::whereIn('zona_id', $zonas->pluck('id'))
            ->get()->keyBy('zona_id');
```

Añadir `EvaluacionValoracionTerritorial` a los `use` y a `compact(...)`.

- [ ] **Paso 2: añadir la tarjeta en el dashboard**

En `resources/views/operativo/dashboard.blade.php`, replicar el bloque que ya existe
para Potencialidad y Percepción, enlazando a
`route('operativo.evaluacion_valoracion_territorial.edit', $zona->id)` y mostrando el
estado desde `$valoraciones[$zona->id] ?? null`.

- [ ] **Paso 3: añadir la vista de administración**

En `ZonaController`, junto a `potencialidad()` y `percepcion()`:

```php
    // Vista admin de resultados de valoración territorial
    public function valoracionTerritorial($id) {
        $zona = Zona::findOrFail($id);
        $evaluacion = EvaluacionValoracionTerritorial::where('zona_id', $id)->firstOrFail();

        return view('operativo.evaluacion_valoracion_territorial.ponderacion', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'ct'         => ValoracionTerritorial::CT,
            'uc'         => ValoracionTerritorial::UC,
        ]);
    }
```

Y la ruta, dentro del grupo `admin`:

```php
    Route::get('/zona/{zona}/valoracion-territorial', [ZonaController::class, 'valoracionTerritorial'])
        ->name('zonas.valoracion_territorial');
```

Añadir el enlace en `admin/zonas/index.blade.php`, junto a los de potencialidad y
percepción.

- [ ] **Paso 4: test de acceso del administrador**

```php
    public function test_el_admin_consulta_los_resultados(): void
    {
        $this->actingAs($this->jefe)->post($this->url(), $this->todosEn(2));

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get("/admin/zona/{$this->zona->id}/valoracion-territorial")
            ->assertOk();
    }
```

- [ ] **Paso 5: correr la suite completa**

Run: `docker run --rm -v C:/proyecto-turismo:/app -w /app -e APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= -e APP_ENV=testing php:8.2-cli ./vendor/bin/phpunit`
Esperado: todo verde.

- [ ] **Paso 6: compilar assets y verificar la imagen**

```bash
docker run --rm -v C:/proyecto-turismo:/app -w /app node:20-alpine sh -c "npm run build"
docker build -t turismo-vt C:/proyecto-turismo
```

Esperado: build de Vite correcto y la imagen construye sin error. Limpiar con
`docker rmi turismo-vt`.

- [ ] **Paso 7: commit**

```bash
git add resources/views/operativo/dashboard.blade.php app/Http/Controllers/Operativo/DashboardController.php app/Http/Controllers/Admin/ZonaController.php routes/web.php resources/views/admin/zonas/index.blade.php tests/Feature/ValoracionTerritorialTest.php
git commit -m "feat(valoracion-territorial): integra en dashboard y panel de administración"
```

---

## Fuera de alcance

- Comparativa entre zonas.
- Las otras cinco matrices del lote.
- Migración de datos: la matriz es nueva y no hay histórico.
