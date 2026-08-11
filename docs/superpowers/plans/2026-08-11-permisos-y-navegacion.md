# Permisos del admin y navegación — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que el admin y el equipo rellenen formularios y guarden borradores —validando solo el jefe—, que cada matriz tenga pestañas entre formulario y resultados con los resultados bloqueados hasta completarla, y que las dos vistas de zonas se puedan ver como lista o como tarjetas.

**Architecture:** El bloqueo del admin desaparece del middleware y de las vistas; lo único que sobrevive de `puedeEditarEvaluaciones()` es el destino del botón «Volver», encapsulado en un componente. La navegación entre las dos caras de una matriz se unifica en `<x-pestanas-matriz>`, alimentada por el registro. El control de vista de Inventario se extrae y se reutiliza en zonas.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, Alpine.js, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-08-11-permisos-y-navegacion-design.md`

## Global Constraints

- Clases de Tailwind **completas** en mapas asociativos, nunca por concatenación. El purgado elimina las construidas dinámicamente.
- Nada de texto por debajo de 14 px salvo insignias. Sin `uppercase` ni `tracking-widest` en botones.
- **Sin botones desactivados**: donde una acción no corresponde, va el texto que dice quién o qué la habilita.
- El color codifica **solo el estado**, nunca la identidad de la matriz.
- **Solo el jefe de zona valida.** Ni el admin ni el equipo, en ninguna matriz.
- **El admin no puede editar una matriz ya validada**, igual que el equipo.
- Comentarios en castellano, explicando el *por qué*, siguiendo el estilo del repositorio.
- Los tests van con `php artisan test`. **PHP 8.2.33 nativo.** No uses Docker para nada y **no toques ningún contenedor de Docker**.
- Suite completa en verde antes de cada commit. Línea base: **394 tests**.

## Estructura de ficheros

**Crear:**
- `resources/views/components/boton-volver.blade.php` — destino de vuelta según el rol.
- `resources/views/components/pestanas-matriz.blade.php` — navegación formulario/resultados.
- `resources/views/components/conmutador-vista.blade.php` — los dos botones de lista/tarjetas.
- `tests/Feature/PermisosAdminTest.php`
- `tests/Feature/PestanasMatrizTest.php`
- `tests/Feature/ConmutadorVistaTest.php`

**Modificar:**
- `app/Http/Middleware/PerteneceAZona.php`
- `app/Http/Controllers/Operativo/EvaluacionZonaController.php`
- `app/Models/User.php`
- `app/Servicios/EstadoZona.php`
- `resources/views/components/aviso-bloqueo-matriz.blade.php`
- Los 9 `resources/views/operativo/*/form.blade.php`
- Las 9 vistas de resultados (`ponderacion.blade.php` y `involucrados/resultados.blade.php`)
- `resources/views/operativo/inventarios/index.blade.php`
- `resources/views/operativo/dashboard.blade.php`
- `resources/views/admin/zonas/index.blade.php`
- `resources/views/admin/{users,lugares,zonas}/form.blade.php`
- Tests: `AutorizacionZonaTest`, `ConcentracionTest`, `IrritacionTest`, `PaisajeTest`, `ValoracionTerritorialTest`, `EvaluacionesTest`, `InvolucradosTest`, `ReabrirMatrizTest`

---

### Task 1: El admin escribe — backend

Solo el guardián y la máquina de estados. Las vistas siguen escondiéndole los
controles, así que la aplicación queda en un estado intermedio coherente: la
API ya le deja, la interfaz todavía no. La Task 2 lo hace visible.

**Files:**
- Modify: `app/Http/Middleware/PerteneceAZona.php`
- Modify: `app/Http/Controllers/Operativo/EvaluacionZonaController.php`
- Test: `tests/Feature/PermisosAdminTest.php`

**Interfaces:**
- Consumes: `App\Matrices\Registro::matrices()`, `User::esAdmin()/esJefe()/esEquipo()`.
- Produces: nada nuevo en código. El admin pasa a poder hacer POST a todas las rutas del grupo `operativo/zona/{zona}`.

- [ ] **Step 1: Escribir los tests**

Crear `tests/Feature/PermisosAdminTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Matrices\Registro;
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
        $categoria = DB::table('categorias')->value('id');

        $this->actingAs($this->admin)->post(
            route('operativo.inventarios.store', $this->zona->id),
            ['nombre' => 'Recurso del admin', 'categoria_id' => $categoria]
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
     * El guardián del riesgo que abre esta tarea: al quitar la restricción del
     * middleware, cualquier ruta de escritura nueva del grupo queda permitida
     * al admin por omisión. Las que validen tienen que guardarse en su
     * controlador, y este test lo comprueba recorriendo las rutas en vez de
     * fiarse de que alguien se acuerde.
     */
    public function test_toda_ruta_de_validacion_sigue_exigiendo_jefe(): void
    {
        $rutasDeValidacion = collect(Route::getRoutes())
            ->filter(fn($r) => str_starts_with($r->getName() ?? '', 'operativo.'))
            ->filter(fn($r) => str_contains($r->getName(), 'validar'))
            ->pluck('uri');

        $this->assertNotEmpty(
            $rutasDeValidacion,
            'No se encontró ninguna ruta de validación; el filtro de este test se ha quedado obsoleto.'
        );

        foreach ($rutasDeValidacion as $uri) {
            $url = str_replace('{zona}', (string) $this->zona->id, $uri);

            $this->actingAs($this->admin)->post("/{$url}")->assertForbidden("POST /{$url}");
        }
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
}
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=PermisosAdminTest
```

Esperado: FAIL en los tests de escritura del admin, con 403. Los de equipo,
ajeno e Involucrados deben pasar ya.

Si `test_el_admin_crea_y_borra_recursos_del_inventario` falla por la forma del
formulario de inventario —los campos obligatorios de `InventarioController::store`
pueden no ser solo `nombre` y `categoria_id`—, **mira ese controlador y ajusta el
payload del test**, no el controlador.

- [ ] **Step 3: Abrir el middleware**

En `app/Http/Middleware/PerteneceAZona.php`, sustituir la rama del admin:

```php
        if ($user->esAdmin()) {
            // El admin trabaja en cualquier zona, con cualquier método.
            //
            // Antes esto le limitaba a métodos seguros. Se abrió a propósito:
            // rellena formularios y gestiona el inventario como uno más. Lo
            // único que NO puede es validar, y esa guarda no vive aquí sino en
            // los controladores —InvolucradosController::validar() aborta si no
            // eres jefe, y EvaluacionZonaController degrada a borrador a quien
            // no lo sea—.
            //
            // Consecuencia: una ruta de escritura nueva en este grupo queda
            // permitida al admin por omisión. Si alguna acción debe ser solo
            // del jefe, guárdala en su controlador;
            // PermisosAdminTest::test_toda_ruta_de_validacion_sigue_exigiendo_jefe
            // recorre las rutas y lo comprueba.
            return $next($request);
        }
```

- [ ] **Step 4: Cerrar la matriz validada también para el admin**

En `app/Http/Controllers/Operativo/EvaluacionZonaController.php`, la guarda de
apertura de `update()` comprueba hoy `$user->esEquipo()`. Cambiarla:

```php
        // ! esJefe() y no esEquipo(): desde que el admin escribe, él también
        // tiene que encontrarse la matriz cerrada. Con esEquipo() podría
        // reabrir lo que el jefe validó y luego no poder volver a validarlo.
        if ($actual && $actual->estado === 'confirmado' && ! $user->esJefe()) {
            return back()->with('error', $this->mensajeCerrada());
        }
```

- [ ] **Step 5: Tests nuevos en verde**

```bash
php artisan test --filter=PermisosAdminTest
```

Esperado: PASS, 8 tests.

- [ ] **Step 6: Suite completa y adaptación de los tests que cambian de sentido**

```bash
php artisan test
```

Van a fallar los que afirman que el admin no escribe. Son estos, localizados de
antemano:

- `ConcentracionTest::test_el_admin_no_puede_modificar_la_matriz`
- `IrritacionTest::test_el_admin_no_puede_modificar_la_matriz`
- `PaisajeTest::test_el_admin_no_puede_modificar_la_matriz`
- `ValoracionTerritorialTest::test_el_admin_no_puede_modificar_la_matriz`

**Si falla alguno que no esté en esta lista, para y averígualo** antes de tocar
ningún test: habrás roto algo que no tocaba.

Los cuatro pasan a comprobar la regla nueva. Patrón, adaptando el nombre de la
matriz y sus criterios en cada fichero:

```php
    /**
     * El admin escribe borradores desde que se le dio permiso; lo que no puede
     * es validar. La petición no se rechaza, se degrada.
     */
    public function test_el_admin_guarda_borrador_pero_no_valida(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)->post(
            $this->url(),
            $this->todosEn(5) + ['accion_estado' => 'confirmado']
        )->assertSessionHasNoErrors();

        $this->assertSame('borrador', EvaluacionPaisaje::value('estado'));
    }
```

`ReabrirMatrizTest::test_el_admin_no_puede_reabrir_una_matriz_confirmada`
**no se toca**: sigue siendo cierto, y ahora lo prueba por el motivo correcto —
la guarda del Step 4 y no un 403 del middleware. Comprueba que sigue en verde.

- [ ] **Step 7: Suite completa**

```bash
php artisan test
```

Esperado: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Http/Middleware/PerteneceAZona.php \
        app/Http/Controllers/Operativo/EvaluacionZonaController.php \
        tests/Feature/PermisosAdminTest.php tests/Feature
git commit -m "feat(permisos): el admin escribe evaluaciones e inventario, pero no valida"
```

---

### Task 2: El admin escribe — vistas

Ahora la interfaz deja de tratarlo como observador.

**Files:**
- Modify: `app/Models/User.php`
- Create: `resources/views/components/boton-volver.blade.php`
- Modify: `resources/views/components/aviso-bloqueo-matriz.blade.php`
- Modify: los 9 `resources/views/operativo/*/form.blade.php`
- Modify: las 9 vistas de resultados
- Modify: `resources/views/components/matriz-sin-resultados.blade.php`
- Modify: `resources/views/operativo/inventarios/index.blade.php`
- Test: ampliar `tests/Feature/PermisosAdminTest.php`, adaptar `tests/Feature/AutorizacionZonaTest.php`

**Interfaces:**
- Consumes: `User::esAdmin()`.
- Produces: `<x-boton-volver />` — enlace de vuelta cuyo destino depende del rol. Acepta `class` para el estilo.
- Elimina: `User::puedeEditarEvaluaciones()` y toda variable `$readonly` / `$bloqueadoPorRol` / `$soloLectura` derivada de ella.

- [ ] **Step 1: Escribir los tests**

Añadir a `tests/Feature/PermisosAdminTest.php`:

```php
    public function test_el_admin_ve_el_formulario_editable(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Guardar Borrador', $html);
        $this->assertStringNotContainsString('puede consultar esta', $html);
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
```

`test_el_admin_no_ve_el_boton_de_validar` busca el `name="accion_estado"` del
botón de confirmar. **Comprueba antes cómo se llama de verdad** en
`resources/views/operativo/evaluacion_paisaje/form.blade.php` y ajusta la cadena
si difiere; lo que importa es que sea el control que confirma, no su texto.

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=PermisosAdminTest
```

Esperado: FAIL en los cinco nuevos.

- [ ] **Step 3: Eliminar el predicado**

Borrar de `app/Models/User.php`:

```php
    public function puedeEditarEvaluaciones(): bool
    {
        return ! $this->esAdmin();
    }
```

- [ ] **Step 4: Crear el botón de vuelta**

Crear `resources/views/components/boton-volver.blade.php`:

```blade
@props(['texto' => 'Volver'])

{{--
    A dónde vuelve cada rol. Es lo único que sobrevive del antiguo $readonly,
    que decidía dos cosas bajo un nombre: si podías editar —ya no aplica, el
    admin también edita— y a dónde volvías.

    Vive en un componente y no repetido en las diecinueve vistas a propósito:
    ese ternario replicado es exactamente la forma que tomó el fallo que dejó
    al admin viendo enlaces de edición durante toda una rama.
--}}

<a href="{{ auth()->user()->esAdmin() ? route('admin.zonas.index') : route('operativo.dashboard') }}"
   {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-base text-gray-700 hover:bg-gray-50 shadow-sm']) }}>
    {{ $texto }}
</a>
```

- [ ] **Step 5: Colapsar el aviso de bloqueo**

`resources/views/components/aviso-bloqueo-matriz.blade.php` tiene hoy dos ramas
según `$porRol`. Con el admin editando, esa rama nunca se alcanza: el único
motivo de bloqueo que queda es la matriz validada. Reemplazar el fichero entero:

```blade
@props(['sustantivo' => 'matriz'])

{{--
    El texto cuando un formulario de matriz llega bloqueado.

    Antes había dos motivos: el admin no editaba nunca (por ROL) y el equipo
    dejaba de poder al confirmarse (por ESTADO). Desde que el admin edita, solo
    queda el segundo, así que el prop $porRol y su rama desaparecen en vez de
    quedarse como una condición que siempre es falsa.

    En una sola línea a propósito: Blade respeta los saltos del fichero, y una
    frase partida en dos no la encontraría un assertSee() que busque la frase
    entera.
--}}
Solo el Jefe de Zona puede reabrir o editar una {{ $sustantivo }} validada.
```

- [ ] **Step 6: Simplificar los nueve formularios**

Cada formulario calcula hoy el bloqueo de una de estas tres formas. Localízalas:

```bash
grep -rn "bloqueadoPorRol\|soloLectura\s*=\|bloqueado\s*=" resources/views/operativo/*/form.blade.php
```

**Patrón A** — cinco ficheros (`evaluacion_fit`, `evaluacion_fet`,
`evaluacion_percepcion`, `evaluacion_irritacion`, `evaluacion_concentracion`):

```php
                $bloqueadoPorRol = ! auth()->user()->puedeEditarEvaluaciones();
                $bloqueado = $bloqueadoPorRol || ($estaConfirmado && !$esJefe);
```

pasa a:

```php
                // Un solo motivo de bloqueo desde que el admin edita: la
                // matriz está validada y tú no eres quien la valida.
                $bloqueado = $estaConfirmado && ! $esJefe;
```

Y el `<x-aviso-bloqueo-matriz :por-rol="$bloqueadoPorRol" …>` de esos ficheros
pierde el atributo:

```blade
<x-aviso-bloqueo-matriz sustantivo="evaluación" />
```

**Patrón B** — dos ficheros (`evaluacion_paisaje:22`,
`evaluacion_valoracion_territorial:22`):

```php
                $bloqueado      = ! auth()->user()->puedeEditarEvaluaciones() || ($estaConfirmado && !$esJefe);
```

pasa a la misma línea que el patrón A.

**Patrón C** — `evaluacion_potencialidad/form.blade.php:31`:

```php
        $soloLectura     = (! $user->puedeEditarEvaluaciones()) || ($isConfirmado && $user->esEquipo());
```

pasa a:

```php
        // Un solo motivo desde que el admin edita. esEquipo() se cambia por
        // ! esJefe() para que el admin también encuentre cerrada una matriz
        // validada, como decidió el diseño.
        $soloLectura     = $isConfirmado && ! $user->esJefe();
```

**Patrón D** — `involucrados/form.blade.php:44`:

```php
                    $bloqueado = ! auth()->user()->puedeEditarEvaluaciones();
```

Involucrados no tiene estado confirmado en el propio formulario de actor. Su
bloqueo por rol desaparece sin sustituto:

```php
                    // Ya no hay bloqueo por rol: el admin gestiona actores
                    // como uno más. El cierre de la lista validada se guarda
                    // en InvolucradosController, no aquí.
                    $bloqueado = false;
```

Si al quitarlo `$bloqueado` deja de usarse en ese fichero, **elimina también la
variable y sus usos** en vez de dejar una constante falsa.

- [ ] **Step 7: Sustituir `$readonly` en las vistas de resultados**

```bash
grep -rn "readonly" resources/views/
```

En cada una, el `$readonly` gobierna dos cosas: si se muestra el enlace al
formulario y a dónde va el botón de volver. Como ahora **todos** pueden ir al
formulario, el `@if(!$readonly)` que envuelve ese enlace se elimina —el enlace
se muestra siempre— y el botón de volver pasa a `<x-boton-volver />`.

Aplícalo también en `resources/views/components/matriz-sin-resultados.blade.php`.

- [ ] **Step 8: Destapar los botones del inventario**

En `resources/views/operativo/inventarios/index.blade.php`, quitar los tres
`@unless(auth()->user()->esAdmin())` que envuelven «Agregar Recurso», y
«Editar»/«Eliminar» en las variantes de lista y de tarjetas. Los `@endunless`
correspondientes también.

- [ ] **Step 8b: Mostrar quién editó, dentro de la matriz**

Es la mitad de la decisión que autoriza al admin a escribir: si alguien con
acceso a todo puede editar cualquier evaluación, lo que evita discusiones no es
restringirlo, es que se vea quién tocó qué.

El dato ya existe —`user_id` y `updated_at` en cada evaluación— y la ficha de la
zona ya lo muestra. Falta dentro de la matriz.

Añadir a los nueve `form.blade.php` y a las nueve vistas de resultados, bajo la
cabecera:

```blade
                @if($evaluacion?->exists && $evaluacion->user)
                    <p class="text-sm text-gray-500 mb-4">
                        Última edición: {{ $evaluacion->user->name }},
                        {{ $evaluacion->updated_at->diffForHumans() }}
                    </p>
                @endif
```

Dos cosas que comprobar antes de pegarlo en cada fichero:

- **El nombre de la variable cambia entre vistas.** FIT y FET usan `$fit` y
  `$fet`, no `$evaluacion`. Localízalos:
  ```bash
  grep -n "compact(" app/Http/Controllers/Operativo/Evaluacion*.php
  ```
- **La relación `user()` debe existir en el modelo.** Se añadió a los seis
  modelos de la rama del rediseño, pero Irritación, Concentración e Involucrados
  llegaron después. Comprueba y añádela donde falte:
  ```bash
  grep -L "function user()" app/Models/Evaluacion*.php
  ```

Añadir el test a `tests/Feature/PermisosAdminTest.php`:

```php
    public function test_la_matriz_dice_quien_la_edito_por_ultima_vez(): void
    {
        $this->actingAs($this->admin)->post($this->urlPaisaje(), $this->criteriosDePaisaje(3));

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee($this->admin->name);
    }
```

- [ ] **Step 9: Tests nuevos en verde y suite completa**

```bash
php artisan test --filter=PermisosAdminTest
php artisan test
```

Va a fallar `AutorizacionZonaTest`, que comprueba que el admin **no** ve los
botones del inventario. Es lo previsto. Invertir esas aserciones: el admin
**sí** los ve, y el jefe también.

- [ ] **Step 10: Comprobar que no queda rastro del predicado**

```bash
grep -rn "puedeEditarEvaluaciones\|bloqueadoPorRol" app/ resources/ tests/
```

Esperado: sin resultados.

- [ ] **Step 11: Build y commit**

```bash
npm run build
git add -A
git commit -m "feat(permisos): la interfaz deja de tratar al admin como observador"
```

---

### Task 3: Recuento de criterios reutilizable

Las pestañas necesitan saber cuántos criterios van respondidos en **una**
matriz. `EstadoZona` ya lo calcula para toda la zona, en un método privado.

**Files:**
- Modify: `app/Servicios/EstadoZona.php`
- Test: ampliar `tests/Unit/EstadoZonaTest.php`

**Interfaces:**
- Produces: `EstadoZona::criteriosRespondidos(Model $evaluacion): int` — pública y estática.

- [ ] **Step 1: Escribir el test**

Añadir a `tests/Unit/EstadoZonaTest.php`:

```php
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
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=test_el_contador_de_criterios_es_reutilizable_desde_fuera
```

Esperado: FAIL — método no definido.

- [ ] **Step 3: Hacer público el contador**

En `app/Servicios/EstadoZona.php`, cambiar la firma del método privado
`respondidos(Model $evaluacion): int` por una pública y estática, y actualizar
sus llamadas internas:

```php
    /**
     * Criterios respondidos de una evaluación.
     *
     * Pública y estática porque la usan dos consumidores que no comparten
     * instancia: la ficha de la zona y las pestañas de cada matriz. Si cada
     * uno contara por su cuenta, habría dos respuestas a la misma pregunta y
     * el «21 de 34» de un sitio no coincidiría con el del otro.
     */
    public static function criteriosRespondidos(Model $evaluacion): int
    {
        return count(array_filter(
            $evaluacion->getAttributes(),
            fn($valor, string $columna) => $valor !== null && self::esColumnaDeCriterio($columna),
            ARRAY_FILTER_USE_BOTH
        ));
    }
```

- [ ] **Step 4: Verde, suite y commit**

```bash
php artisan test --filter=EstadoZonaTest
php artisan test
git add app/Servicios/EstadoZona.php tests/Unit/EstadoZonaTest.php
git commit -m "refactor(zona): el contador de criterios pasa a ser reutilizable"
```

---

### Task 4: Pestañas formulario/resultados

**Files:**
- Create: `resources/views/components/pestanas-matriz.blade.php`
- Modify: los 9 `form.blade.php` y las 9 vistas de resultados
- Test: `tests/Feature/PestanasMatrizTest.php`

**Interfaces:**
- Consumes: `Registro::ENTRADAS`, `EstadoZona::criteriosRespondidos()`.
- Produces: `<x-pestanas-matriz clave="paisaje" :zona="$zona" activa="formulario" />` — `activa` es `'formulario'` o `'resultados'`.

- [ ] **Step 1: Escribir los tests**

Crear `tests/Feature/PestanasMatrizTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Matrices\Registro;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PestanasMatrizTest extends TestCase
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
     * La pareja del test de integridad del registro: si una matriz nueva no
     * engancha sus pestañas, salta aquí en vez de quedarse sin navegación.
     */
    public function test_todas_las_matrices_muestran_las_pestanas_en_su_formulario(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            if (! isset($entrada['rutas']['editar'], $entrada['rutas']['ver'])) {
                continue;
            }

            $this->actingAs($this->jefe)
                ->get(route($entrada['rutas']['editar'], $this->zona->id))
                ->assertOk()
                ->assertSee('Resultados', false);
        }
    }

    public function test_una_matriz_vacia_no_ofrece_enlace_a_resultados(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString(
            route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id),
            $html
        );
        $this->assertStringContainsString('0 de 34', $html);
    }

    public function test_una_matriz_a_medias_dice_cuantos_faltan(): void
    {
        $evaluacion = \App\Models\EvaluacionPaisaje::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        foreach (array_slice(array_keys(\App\Matrices\Paisaje::todos()), 0, 30) as $campo) {
            $evaluacion->$campo = 3;
        }
        $evaluacion->save();

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee('30 de 34');
    }

    public function test_una_matriz_completa_desbloquea_el_enlace(): void
    {
        $this->actingAs($this->jefe)->post(
            route('operativo.evaluacion_paisaje.update', $this->zona->id),
            array_fill_keys(array_keys(\App\Matrices\Paisaje::todos()), 3)
        );

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id), false);
    }

    public function test_los_resultados_tambien_llevan_pestanas(): void
    {
        $this->actingAs($this->jefe)->post(
            route('operativo.evaluacion_paisaje.update', $this->zona->id),
            array_fill_keys(array_keys(\App\Matrices\Paisaje::todos()), 3)
        );

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.ponderacion', $this->zona->id))
            ->assertOk()
            ->assertSee(route('operativo.evaluacion_paisaje.edit', $this->zona->id), false);
    }

    /**
     * El recuento de las pestañas y el de la ficha de la zona salen del mismo
     * contador. Este test es lo que impide que se separen.
     */
    public function test_el_recuento_coincide_con_el_de_la_ficha_de_la_zona(): void
    {
        $evaluacion = \App\Models\EvaluacionPaisaje::create([
            'zona_id' => $this->zona->id,
            'estado'  => 'borrador',
        ]);

        foreach (array_slice(array_keys(\App\Matrices\Paisaje::todos()), 0, 21) as $campo) {
            $evaluacion->$campo = 3;
        }
        $evaluacion->save();

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee('21 de 34');

        $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->assertSee('21 de 34');
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=PestanasMatrizTest
```

- [ ] **Step 3: Escribir el componente**

Crear `resources/views/components/pestanas-matriz.blade.php`:

```blade
@props(['clave', 'zona', 'activa' => 'formulario'])

{{--
    Navegación entre las dos caras de una matriz.

    Antes eran nueve enlaces escritos a mano, cada uno con su texto y su sitio,
    y ninguno decía si al otro lado había algo que ver. Ese conocimiento
    repetido es el que dejó a Paisaje sin enlace en el admin durante meses.

    Cuando la matriz no está completa, «Resultados» NO es un botón gris: es
    texto con candado y el motivo. Un botón desactivado no explica nada y se
    pulsa igual.
--}}

@php
    $entrada = \App\Matrices\Registro::ENTRADAS[$clave];
    $modelo  = $entrada['modelo'];

    $evaluacion  = $modelo ? $modelo::where('zona_id', $zona->id)->first() : null;
    $total       = $entrada['criterios'];
    $respondidos = $evaluacion
        ? \App\Servicios\EstadoZona::criteriosRespondidos($evaluacion)
        : 0;

    $completa = $total !== null && $respondidos >= $total;

    // Clases completas: Tailwind purga las construidas por concatenación.
    $estiloActiva   = 'border-indigo-600 text-indigo-700 font-medium';
    $estiloInactiva = 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300';
@endphp

<div class="border-b border-gray-200 mb-6 flex gap-6">
    <a href="{{ route($entrada['rutas']['editar'], $zona->id) }}"
       class="pb-3 border-b-2 text-base {{ $activa === 'formulario' ? $estiloActiva : $estiloInactiva }}">
        Formulario
    </a>

    @if($completa)
        <a href="{{ route($entrada['rutas']['ver'], $zona->id) }}"
           class="pb-3 border-b-2 text-base {{ $activa === 'resultados' ? $estiloActiva : $estiloInactiva }}">
            Resultados
        </a>
    @else
        <span class="pb-3 border-b-2 border-transparent text-base text-gray-400 flex items-center gap-2">
            <x-icono nombre="candado" class="w-4 h-4" />
            Resultados
            <span class="text-sm">— {{ $respondidos }} de {{ $total }} criterios</span>
        </span>
    @endif
</div>
```

- [ ] **Step 4: Engancharlo en las dieciocho vistas**

En cada `form.blade.php`, justo dentro del contenedor principal y **antes** del
`<form>`:

```blade
<x-pestanas-matriz clave="paisaje" :zona="$zona" activa="formulario" />
```

En cada vista de resultados, en el mismo sitio:

```blade
<x-pestanas-matriz clave="paisaje" :zona="$zona" activa="resultados" />
```

Cambiando `clave` por la del registro en cada caso: `fit`, `fet`,
`potencialidad`, `paisaje`, `valoracion_territorial`, `percepcion`,
`irritacion`, `concentracion`, `involucrados`.

**Al añadirlas, quita el enlace suelto a resultados que cada formulario tenía**
—los localizaste con el `grep` de la Task 2— para no dejar dos navegaciones.

- [ ] **Step 5: Involucrados**

Su `criterios` en el registro es `null` y su completitud no es un recuento. En
el componente, la rama de Involucrados usa su propia condición:

```php
    if ($entrada['tipo'] === 'actores') {
        $actores  = $zona->involucrados();
        $completa = $actores->count() > 0 && ! $actores->incompletos()->exists();
    }
```

Colócala justo después de calcular `$completa`, y en la rama bloqueada muestra
«— sin actores completos» en vez del recuento. Comprueba antes el nombre real
de la relación y del scope:

```bash
grep -n "function involucrados\|scopeIncompletos" app/Models/Zona.php app/Models/Involucrado.php
```

- [ ] **Step 6: Verde, suite, build y commit**

```bash
php artisan test --filter=PestanasMatrizTest
php artisan test
npm run build
git add -A
git commit -m "feat(matrices): pestañas entre formulario y resultados, con bloqueo explicito"
```

---

### Task 5: Conmutador lista/tarjetas en zonas

**Files:**
- Create: `resources/views/components/conmutador-vista.blade.php`
- Modify: `resources/views/operativo/inventarios/index.blade.php`
- Modify: `resources/views/operativo/dashboard.blade.php`
- Modify: `resources/views/admin/zonas/index.blade.php`
- Test: `tests/Feature/ConmutadorVistaTest.php`

**Interfaces:**
- Produces: `<x-conmutador-vista modelo="vista" />` — pinta los dos botones enlazados a la variable Alpine que se le nombre. No sabe dónde se guarda la preferencia.

- [ ] **Step 1: Escribir los tests**

Crear `tests/Feature/ConmutadorVistaTest.php`:

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

/**
 * El conmutador es Alpine puro, así que un test de servidor no puede pulsarlo.
 * Lo que sí se puede comprobar, y es lo que importa: que las dos maquetaciones
 * viajan en el HTML con los mismos datos y enlaces. Si alguien añade un botón
 * a solo una de las dos, salta.
 */
class ConmutadorVistaTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private User $admin;
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
            'nombre'       => 'Zona conmutable',
        ]);
    }

    public function test_mis_zonas_trae_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString("vista === 'lista'", $html);
        $this->assertStringContainsString("vista === 'tarjetas'", $html);
        $this->assertStringContainsString('zonas_vista', $html);
    }

    public function test_el_enlace_a_la_zona_esta_en_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $url = route('operativo.zona.panel', $this->zona->id);

        $this->assertSame(
            2,
            substr_count($html, $url),
            'El enlace a la zona debe aparecer una vez en cada maquetación.'
        );
    }

    public function test_la_lista_del_admin_trae_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/zonas')->assertOk()->getContent();

        $this->assertStringContainsString("vista === 'lista'", $html);
        $this->assertStringContainsString("vista === 'tarjetas'", $html);
    }

    public function test_el_admin_ve_jefe_y_miembros_en_las_dos_maquetaciones(): void
    {
        $html = $this->actingAs($this->admin)->get('/admin/zonas')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, $this->jefe->name));
        $this->assertSame(2, substr_count($html, 'miembros'));
    }

    public function test_inventario_conserva_su_propia_preferencia(): void
    {
        $this->actingAs($this->jefe)
            ->get(route('operativo.inventarios.index', $this->zona->id))
            ->assertOk()
            ->assertSee('inventario_vista', false)
            ->assertDontSee('zonas_vista', false);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=ConmutadorVistaTest
```

Esperado: pasa solo `test_inventario_conserva_su_propia_preferencia`.

- [ ] **Step 3: Extraer el control**

Crear `resources/views/components/conmutador-vista.blade.php` copiando el
marcado que hoy vive en `resources/views/operativo/inventarios/index.blade.php`
(líneas 27-45), parametrizando el nombre de la variable Alpine:

```blade
@props(['modelo'])

{{--
    Los dos botones de lista/tarjetas, y nada más.

    No sabe dónde se guarda la preferencia ni cómo se llama la variable: eso lo
    pone quien lo usa. Así el mismo control sirve en Inventario y en las dos
    vistas de zonas sin que ninguna herede las decisiones de otra.
--}}

<div class="inline-flex rounded-lg bg-gray-100 p-1">
    <button type="button" @click="{{ $modelo }} = 'lista'"
            :class="{{ $modelo }} === 'lista' ? 'bg-white shadow text-blue-700' : 'text-gray-500 hover:text-gray-700'"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition">
        <x-icono nombre="lista" class="w-4 h-4" />
        Lista
    </button>

    <button type="button" @click="{{ $modelo }} = 'tarjetas'"
            :class="{{ $modelo }} === 'tarjetas' ? 'bg-white shadow text-blue-700' : 'text-gray-500 hover:text-gray-700'"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition">
        <x-icono nombre="cuadricula" class="w-4 h-4" />
        Tarjetas
    </button>
</div>
```

`cuadricula` no existe en `<x-icono>`. Añádele el trazo, junto a los demás del
mapa `$trazos` de `resources/views/components/icono.blade.php`:

```php
        'cuadricula'    => 'M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z',
```

Después, en `inventarios/index.blade.php`, sustituir el marcado extraído por:

```blade
<x-conmutador-vista modelo="vista" />
```

Ejecuta la suite antes de seguir: Inventario debe comportarse exactamente igual.

- [ ] **Step 4: Añadir la lista a «Mis zonas»**

En `resources/views/operativo/dashboard.blade.php`, envolver el contenido en el
`x-data` con su propia clave y añadir el conmutador y la maquetación de lista:

```blade
    <div class="py-12" x-data="{ vista: localStorage.getItem('zonas_vista') || 'tarjetas' }"
         x-init="$watch('vista', v => localStorage.setItem('zonas_vista', v))">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <div class="flex justify-end mb-4">
                <x-conmutador-vista modelo="vista" />
            </div>
```

Las tarjetas actuales pasan a `<div x-show="vista === 'tarjetas'" x-transition>`.
La lista nueva va en `<div x-show="vista === 'lista'" x-transition>`:

```blade
            <div x-show="vista === 'lista'" x-transition
                 class="bg-white shadow-sm rounded-xl border border-gray-200 divide-y divide-gray-200">
                @foreach($zonas as $zona)
                    @php $p = $progreso[$zona->id]; @endphp
                    <div class="flex items-center gap-4 p-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-base text-gray-900">{{ $zona->nombre }}</p>
                            <p class="text-sm text-gray-600">📍 {{ $zona->lugar->nombre }}</p>
                        </div>

                        <div class="w-40 shrink-0">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $p['hechas'] }} / {{ $p['total'] }}</p>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
                               class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                                Abrir zona
                            </a>
                            <a href="{{ route('operativo.inventarios.index', $zona->id) }}"
                               class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Inventario
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
```

Los dos enlaces son **los mismos** que en la tarjeta, y ése es el punto que
comprueba `test_el_enlace_a_la_zona_esta_en_las_dos_maquetaciones`.

- [ ] **Step 5: Añadir las tarjetas a la lista del admin**

Lo mismo en `resources/views/admin/zonas/index.blade.php`, con la clave
`zonas_vista` y arrancando en `'lista'`. La tabla actual pasa a
`x-show="vista === 'lista'"`; las tarjetas nuevas llevan foto, nombre, lugar,
**jefe asignado**, **número de miembros**, progreso y los tres botones —«Abrir
zona», «Editar», «Eliminar»—.

El paginador se queda **fuera** de los dos `x-show`, visible en ambos formatos:
el conmutador cambia la maquetación, no la consulta.

- [ ] **Step 6: Verde, suite, build y commit**

```bash
php artisan test --filter=ConmutadorVistaTest
php artisan test
npm run build
git add -A
git commit -m "feat(zonas): conmutador lista/tarjetas con preferencia propia"
```

---

### Task 6: Detalles menores

**Files:**
- Modify: `resources/views/admin/users/form.blade.php`
- Modify: `resources/views/admin/lugares/form.blade.php`
- Modify: `resources/views/admin/zonas/form.blade.php`
- Modify: los 9 `resources/views/operativo/*/form.blade.php`
- Test: ampliar `tests/Feature/PermisosAdminTest.php`

- [ ] **Step 1: Escribir el test del aviso**

Añadir a `tests/Feature/PermisosAdminTest.php`:

```php
    public function test_el_jefe_ve_que_guardar_una_matriz_validada_la_reabre(): void
    {
        $this->actingAs($this->jefe)->post(
            $this->urlPaisaje(),
            $this->criteriosDePaisaje(5) + ['accion_estado' => 'confirmado']
        );

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertSee('la devolverá a borrador');
    }

    public function test_una_matriz_en_borrador_no_muestra_ese_aviso(): void
    {
        $this->actingAs($this->jefe)->post($this->urlPaisaje(), $this->criteriosDePaisaje(3));

        $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->assertDontSee('la devolverá a borrador');
    }
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter="reabre|ese_aviso"
```

- [ ] **Step 3: Añadir el aviso a los nueve formularios**

Junto al botón de guardar, cuando la matriz esté validada y quien mira sea el
jefe:

```blade
                @if($estaConfirmado && $esJefe)
                    <p class="text-sm text-amber-700 mb-3">
                        <strong>Esta matriz está validada.</strong>
                        Guardarla la devolverá a borrador y habrá que validarla de nuevo.
                    </p>
                @endif
```

Solo al jefe: el equipo y el admin tienen el formulario bloqueado en ese estado,
así que para ellos el aviso sería ruido.

En Involucrados el nombre de las variables difiere; localízalas antes:

```bash
grep -n "estaConfirmado\|esJefe\|validad" resources/views/operativo/involucrados/form.blade.php | head
```

- [ ] **Step 4: Tipografía de los tres formularios de admin**

En `admin/users/form.blade.php`, `admin/lugares/form.blade.php` y
`admin/zonas/form.blade.php`, aplicar la escala del proyecto:

- Etiquetas: `text-sm` (14 px).
- Campos y botones: `text-base` (16 px).
- Cabecera de página: `text-2xl`.
- Eliminar todo `text-xs`, `uppercase` y `tracking-widest`.

Comprobar después:

```bash
grep -rn "text-xs\|uppercase\|tracking-wide" resources/views/admin/users/form.blade.php \
    resources/views/admin/lugares/form.blade.php resources/views/admin/zonas/form.blade.php
```

Esperado: sin resultados, salvo insignias si las hubiera —y en ese caso, dilo.

- [ ] **Step 5: Verde, suite, build y commit**

```bash
php artisan test
npm run build
git add -A
git commit -m "feat(ui): aviso de reapertura y tipografia de los formularios de admin"
```

---

### Task 7: Revisión final

- [ ] **Step 1: Suite y build**

```bash
php artisan test && npm run build
```

- [ ] **Step 2: Comprobar que no queda rastro del modelo viejo de permisos**

```bash
grep -rn "puedeEditarEvaluaciones\|bloqueadoPorRol\|porRol" app/ resources/ tests/
```

Esperado: sin resultados.

- [ ] **Step 3: Comprobar que el purgado de Tailwind conserva las clases nuevas**

```bash
grep -c "border-indigo-600\|bg-white shadow text-blue-700" public/build/assets/*.css
```

Esperado: al menos 1. Si sale 0, alguna clase se está construyendo por
concatenación.

- [ ] **Step 4: Recorrido manual**

Con `php artisan serve`, y entrando con cada rol:

1. **Admin**: entra en una zona, abre una matriz en borrador, la rellena y
   guarda. No ve botón de validar. Abre el inventario y crea un recurso.
2. **Admin sobre una matriz validada**: el formulario llega bloqueado con el
   texto «Solo el Jefe de Zona puede reabrir o editar una evaluación validada».
3. **Jefe**: en una matriz validada, ve el aviso de que guardar la reabre.
4. **Pestañas**: con la matriz a medias, «Resultados» sale con candado y el
   recuento; al completarla, se vuelve enlace.
5. **Conmutador**: en `/mis-zonas` y en `/admin/zonas`, cambia de formato,
   recarga y comprueba que la preferencia se mantiene. Comprueba que Inventario
   conserva la suya por separado.

El punto 5 es el único que los tests no cubren: es JavaScript de navegador.

- [ ] **Step 5: Commit final si algo cambió**

```bash
git status --short
```

---

## Fuera de este plan

- **La décima matriz**, el Índice Espacial de Frecuentación, bloqueada por una
  contradicción del instrumento que hay que aclarar con su autor.
- **Un botón «Reabrir» explícito**, separado de guardar.
- **Los tres puntos de Render**: contraseña del admin, SMTP y bucket S3.
