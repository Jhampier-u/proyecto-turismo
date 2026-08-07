# Vistas de administración — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que el panel, la lista de usuarios y la de lugares muestren el dato por el que se consultan, con un tamaño de letra legible y un buscador en las listas que crecen.

**Architecture:** No cambia la estructura: siguen siendo un panel de tarjetas y dos tablas paginadas, porque para esto una tabla está bien. Cambia lo que muestran, cómo se filtran y a qué tamaño se lee.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, PHPUnit 11.

**Depende de:** nada. Se puede ejecutar antes o después de los otros dos planes.

## Global Constraints

- Nada por debajo de 14 px salvo insignias. Sin `uppercase` ni `tracking-widest`.
- Las clases de Tailwind se escriben completas, nunca por concatenación.
- Los buscadores filtran en servidor con `LIKE` sobre columnas indexadas y conservan el término al paginar (`->withQueryString()`).
- Sin consultas dentro de bucles Blade: los contadores se resuelven con `withCount()`.
- Comentarios en castellano explicando el *por qué*.
- Suite completa en verde antes de cada commit.

## Estructura de ficheros

**Modificar:**
- `app/Http/Controllers/Admin/UserController.php` — `index()` con búsqueda y filtro.
- `app/Http/Controllers/Admin/LugarController.php` — `index()` con búsqueda y contador.
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/lugares/index.blade.php`
- `routes/web.php` — el panel pasa de closure a controlador.

**Crear:**
- `app/Http/Controllers/Admin/PanelController.php`
- `tests/Feature/AdminPanelTest.php`
- `tests/Feature/AdminBusquedasTest.php`

---

### Task 1: Panel con números reales

Hoy son tres tarjetas con texto fijo. El único dato del panel sobre el que un
admin actúa —zonas sin jefe asignado— no aparece por ninguna parte.

**Files:**
- Create: `app/Http/Controllers/Admin/PanelController.php`
- Modify: `routes/web.php`
- Modify: `resources/views/admin/dashboard.blade.php`
- Test: `tests/Feature/AdminPanelTest.php`

**Interfaces:**
- Consumes: `User`, `Lugar`, `Zona`, `Role`.
- Produces: la vista recibe `$resumen` con las claves `usuarios`, `jefes`,
  `equipo`, `lugares`, `zonas`, `zonasSinJefe` — todas `int`.

- [ ] **Step 1: Escribir el test**

Crear `tests/Feature/AdminPanelTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Models\Lugar;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);
    }

    public function test_el_panel_cuenta_usuarios_por_rol(): void
    {
        User::factory()->count(2)->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
        User::factory()->count(3)->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('2 jefes de zona')
            ->assertSee('3 en equipos');
    }

    /**
     * Una zona sin jefe queda bloqueada en borrador sin explicación: solo el
     * rol jefe_zona puede validar. Es lo único del panel que exige actuar, y
     * hoy no se ve en ninguna pantalla.
     */
    public function test_el_panel_avisa_de_las_zonas_sin_jefe(): void
    {
        $jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
        $lugarId = DB::table('lugares')->value('id');

        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => $jefe->id, 'nombre' => 'Con jefe']);
        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => null,      'nombre' => 'Sin jefe']);

        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee('1 sin jefe asignado');
    }

    public function test_sin_zonas_huerfanas_no_sale_el_aviso(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertDontSee('sin jefe asignado');
    }

    public function test_el_panel_cuenta_lugares_y_zonas(): void
    {
        $this->actingAs($this->admin)->get('/admin/dashboard')
            ->assertOk()
            ->assertSee(Lugar::count() . ' lugares');
    }

    public function test_un_usuario_no_admin_no_entra(): void
    {
        $jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($jefe)->get('/admin/dashboard')->assertForbidden();
    }
}
```

Si `jefe_user_id` no admite nulos en el esquema, el segundo test fallará al
crear la zona. Comprobarlo antes:

```bash
grep -n "jefe_user_id" database/migrations/*zonas*.php
```

Si es `NOT NULL`, cambiar el test para que use una zona cuyo jefe fue eliminado
—`fix_user_foreign_keys_on_delete` pone `nullOnDelete`— o adaptar el aviso a
«zonas cuyo jefe ya no existe». **No añadas una migración para esto**: sale del
alcance de este plan.

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=AdminPanelTest
```

Esperado: FAIL en los cuatro primeros; PASS en el de permisos.

- [ ] **Step 3: Escribir el controlador**

Crear `app/Http/Controllers/Admin/PanelController.php`:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;

class PanelController extends Controller
{
    public function index()
    {
        $idRol = fn(string $nombre) => Role::where('nombre', $nombre)->value('id');

        $resumen = [
            'usuarios'     => User::count(),
            'jefes'        => User::where('role_id', $idRol('jefe_zona'))->count(),
            'equipo'       => User::where('role_id', $idRol('equipo'))->count(),
            'lugares'      => Lugar::count(),
            'zonas'        => Zona::count(),
            // Una zona sin jefe no puede validar ninguna matriz: se queda
            // atascada en borrador y nadie sabe por qué. Es lo único de este
            // panel que pide una acción concreta del admin.
            'zonasSinJefe' => Zona::whereNull('jefe_user_id')->count(),
        ];

        return view('admin.dashboard', compact('resumen'));
    }
}
```

- [ ] **Step 4: Cambiar la ruta**

En `routes/web.php`, sustituir la closure del panel:

```php
    Route::get('/dashboard', [PanelController::class, 'index'])->name('dashboard');
```

Y añadir el `use` junto a los demás del admin:

```php
use App\Http\Controllers\Admin\PanelController;
```

- [ ] **Step 5: Reescribir la vista**

Reemplazar `resources/views/admin/dashboard.blade.php`:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Panel de administración') }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if($resumen['zonasSinJefe'] > 0)
                <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 text-amber-900 p-4 rounded">
                    <p class="text-base">
                        Hay <strong>{{ $resumen['zonasSinJefe'] }} sin jefe asignado</strong>.
                        Sin jefe de zona nadie puede validar sus matrices, así que se quedan
                        en borrador indefinidamente.
                    </p>
                    <a href="{{ route('admin.zonas.index') }}"
                       class="inline-block mt-2 text-base font-medium text-amber-900 underline">
                        Ver las zonas
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['usuarios'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Usuarios</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        {{ $resumen['jefes'] }} jefes de zona · {{ $resumen['equipo'] }} en equipos
                    </p>
                    <a href="{{ route('admin.users.index') }}"
                       class="inline-block mt-4 text-base font-medium text-indigo-700 hover:underline">
                        Gestionar →
                    </a>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['lugares'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Lugares</h3>
                    <p class="text-sm text-gray-600 mt-2">{{ $resumen['lugares'] }} lugares en el catálogo</p>
                    <a href="{{ route('admin.lugares.index') }}"
                       class="inline-block mt-4 text-base font-medium text-indigo-700 hover:underline">
                        Gestionar →
                    </a>
                </div>

                <div class="bg-white border border-gray-200 rounded-xl p-6 shadow-sm">
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['zonas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Zonas</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        @if($resumen['zonasSinJefe'] > 0)
                            {{ $resumen['zonasSinJefe'] }} sin jefe asignado
                        @else
                            Todas con jefe asignado
                        @endif
                    </p>
                    <a href="{{ route('admin.zonas.index') }}"
                       class="inline-block mt-4 text-base font-medium text-indigo-700 hover:underline">
                        Gestionar →
                    </a>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
```

- [ ] **Step 6: Ejecutar los tests**

```bash
php artisan test --filter=AdminPanelTest
```

Esperado: PASS, 5 tests.

Cuidado con `test_sin_zonas_huerfanas_no_sale_el_aviso`: la tarjeta de zonas
dice «Todas con jefe asignado» cuando no hay huérfanas, así que la cadena «sin
jefe asignado» no aparece. Si el test falla porque sí aparece, revisa la rama
`@else`.

- [ ] **Step 7: Suite completa y commit**

```bash
php artisan test
git add app/Http/Controllers/Admin/PanelController.php routes/web.php \
        resources/views/admin/dashboard.blade.php tests/Feature/AdminPanelTest.php
git commit -m "feat(admin): panel con cifras reales y aviso de zonas sin jefe"
```

---

### Task 2: Lista de usuarios con buscador y zonas

Para saber dónde está asignada una persona hay que abrir las zonas una por una.
Es la pregunta que más se le hace a esta pantalla y es la que no responde.

**Files:**
- Modify: `app/Http/Controllers/Admin/UserController.php`
- Modify: `resources/views/admin/users/index.blade.php`
- Test: `tests/Feature/AdminBusquedasTest.php`

**Interfaces:**
- Consumes: `User::zonasComoJefe()`, `User::zonasComoEquipo()`, `Role`.
- Produces: la vista recibe `$users` (paginado), `$roles` y los filtros activos
  `$buscar` y `$rol`.

- [ ] **Step 1: Escribir los tests**

Crear `tests/Feature/AdminBusquedasTest.php`:

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

class AdminBusquedasTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);
    }

    public function test_el_buscador_de_usuarios_filtra_por_nombre(): void
    {
        User::factory()->create(['name' => 'Ana Pérez', 'role_id' => 3]);
        User::factory()->create(['name' => 'Luis Gómez', 'role_id' => 3]);

        $this->actingAs($this->admin)->get('/admin/users?buscar=Ana')
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertDontSee('Luis Gómez');
    }

    public function test_el_buscador_de_usuarios_filtra_por_correo(): void
    {
        User::factory()->create(['name' => 'Ana Pérez', 'email' => 'ana@ejemplo.test', 'role_id' => 3]);
        User::factory()->create(['name' => 'Luis Gómez', 'email' => 'luis@otro.test', 'role_id' => 3]);

        $this->actingAs($this->admin)->get('/admin/users?buscar=ejemplo')
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertDontSee('Luis Gómez');
    }

    public function test_se_filtra_por_rol(): void
    {
        $idJefe = Role::where('nombre', 'jefe_zona')->value('id');

        User::factory()->create(['name' => 'Jefa Marta', 'role_id' => $idJefe]);
        User::factory()->create(['name' => 'Equipo Pedro', 'role_id' => 3]);

        $this->actingAs($this->admin)->get("/admin/users?rol={$idJefe}")
            ->assertOk()
            ->assertSee('Jefa Marta')
            ->assertDontSee('Equipo Pedro');
    }

    public function test_la_lista_muestra_las_zonas_de_cada_persona(): void
    {
        $jefe = User::factory()->create([
            'name'    => 'Jefa Marta',
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $jefe->id,
            'nombre'       => 'Zona El Cajas',
        ]);

        $this->actingAs($this->admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('Zona El Cajas');
    }

    public function test_quien_no_tiene_zonas_lo_dice(): void
    {
        User::factory()->create(['name' => 'Suelto Juan', 'role_id' => 3]);

        $this->actingAs($this->admin)->get('/admin/users?buscar=Suelto')
            ->assertOk()
            ->assertSee('Sin zonas');
    }

    public function test_el_buscador_de_lugares_filtra_por_nombre(): void
    {
        $this->actingAs($this->admin)->get('/admin/lugares?buscar=zzzzinexistente')
            ->assertOk()
            ->assertSee('No hay lugares que coincidan');
    }

    public function test_la_lista_de_lugares_cuenta_sus_zonas(): void
    {
        $lugarId = DB::table('lugares')->value('id');
        $nombre  = DB::table('lugares')->where('id', $lugarId)->value('nombre');

        $jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => $jefe->id, 'nombre' => 'Zona A']);
        Zona::create(['lugar_id' => $lugarId, 'jefe_user_id' => $jefe->id, 'nombre' => 'Zona B']);

        $this->actingAs($this->admin)->get('/admin/lugares?buscar=' . urlencode($nombre))
            ->assertOk()
            ->assertSee('2 zonas');
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=AdminBusquedasTest
```

Esperado: FAIL en todos menos, quizá, los que buscan texto que ya existe.

- [ ] **Step 3: Cambiar el controlador de usuarios**

En `app/Http/Controllers/Admin/UserController.php`, reemplazar `index()`:

```php
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $rol    = $request->query('rol');

        $users = User::with('role')
            ->withCount(['zonasComoJefe', 'zonasComoEquipo'])
            ->with(['zonasComoJefe:id,nombre', 'zonasComoEquipo:id,nombre'])
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($q) use ($buscar) {
                    $q->where('name', 'like', "%{$buscar}%")
                      ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->when($rol, fn($q) => $q->where('role_id', $rol))
            ->orderBy('name')
            ->paginate(10)
            // Sin esto, pasar de página pierde el filtro y el usuario cree que
            // la búsqueda no funciona.
            ->withQueryString();

        $roles = Role::orderBy('id')->get();

        return view('admin.users.index', compact('users', 'roles', 'buscar', 'rol'));
    }
```

- [ ] **Step 4: Añadir el buscador y la columna de zonas a la vista**

En `resources/views/admin/users/index.blade.php`, justo antes del `<div>` que
envuelve la tabla, insertar el formulario de filtros:

```blade
                    <form method="GET" action="{{ route('admin.users.index') }}"
                          class="flex flex-wrap gap-3 mb-6">
                        <input type="search" name="buscar" value="{{ $buscar }}"
                               placeholder="Buscar por nombre o correo"
                               class="flex-1 min-w-64 text-base border-gray-300 rounded-lg shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500">

                        <select name="rol"
                                class="text-base border-gray-300 rounded-lg shadow-sm
                                       focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Todos los roles</option>
                            @foreach($roles as $unRol)
                                <option value="{{ $unRol->id }}" @selected((string) $rol === (string) $unRol->id)>
                                    {{ ucfirst(str_replace('_', ' ', $unRol->nombre)) }}
                                </option>
                            @endforeach
                        </select>

                        <button type="submit"
                                class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-base font-medium hover:bg-indigo-700">
                            Buscar
                        </button>

                        @if($buscar !== '' || $rol)
                            <a href="{{ route('admin.users.index') }}"
                               class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-base text-gray-700 hover:bg-gray-50">
                                Limpiar
                            </a>
                        @endif
                    </form>
```

Cambiar todas las cabeceras `<th>` de `text-xs … uppercase tracking-wider` a
`text-sm font-medium text-gray-600`, y añadir una columna «Zonas» antes de
«Acciones»:

```blade
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Zonas</th>
```

Y su celda, antes del `<td>` de acciones:

```blade
                                    <td class="px-6 py-4">
                                        @php
                                            // Un usuario es jefe de unas zonas o miembro del equipo de
                                            // otras, nunca las dos cosas por rol, pero se juntan por si
                                            // alguna vez cambia de rol conservando asignaciones.
                                            $suyas = $user->zonasComoJefe->merge($user->zonasComoEquipo);
                                        @endphp

                                        @if($suyas->isEmpty())
                                            <span class="text-sm text-gray-400">Sin zonas</span>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($suyas as $zona)
                                                    <span class="text-xs bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                                        {{ $zona->nombre }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
```

Subir también el nombre y el correo de `text-sm` a `text-base`, y los botones de
`text-xs` a `text-sm` sin `uppercase`.

- [ ] **Step 5: Ejecutar los tests de usuarios**

```bash
php artisan test --filter="usuarios|zonas_de_cada_persona|filtra_por_rol"
```

Esperado: PASS.

Si `test_se_filtra_por_rol` falla porque `role_id => 3` no es «equipo» en el
seeder, sustituye el 3 literal por
`Role::where('nombre', 'equipo')->value('id')` en los tests.

- [ ] **Step 6: Suite completa y commit**

```bash
php artisan test
git add app/Http/Controllers/Admin/UserController.php \
        resources/views/admin/users/index.blade.php \
        tests/Feature/AdminBusquedasTest.php
git commit -m "feat(admin): buscador de usuarios y columna con sus zonas"
```

---

### Task 3: Lista de lugares con buscador y uso

**Files:**
- Modify: `app/Http/Controllers/Admin/LugarController.php`
- Modify: `resources/views/admin/lugares/index.blade.php`

**Interfaces:**
- Consumes: `Lugar::zonas()`, `Lugar::provincia()`.
- Produces: la vista recibe `$lugares` (paginado, con `zonas_count`) y `$buscar`.

- [ ] **Step 1: Cambiar el controlador**

En `app/Http/Controllers/Admin/LugarController.php`, reemplazar `index()`:

```php
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));

        $lugares = Lugar::with('provincia')
            // El contador responde la pregunta útil —¿se puede borrar?— sin una
            // consulta por fila dentro de la vista.
            ->withCount('zonas')
            ->when($buscar !== '', fn($q) => $q->where('nombre', 'like', "%{$buscar}%"))
            ->orderBy('nombre')
            ->paginate(10)
            ->withQueryString();

        return view('admin.lugares.index', compact('lugares', 'buscar'));
    }
```

- [ ] **Step 2: Añadir buscador, columna de uso y estado vacío**

En `resources/views/admin/lugares/index.blade.php`, antes de la tabla:

```blade
                <form method="GET" action="{{ route('admin.lugares.index') }}"
                      class="flex flex-wrap gap-3 mb-6">
                    <input type="search" name="buscar" value="{{ $buscar }}"
                           placeholder="Buscar por nombre"
                           class="flex-1 min-w-64 text-base border-gray-300 rounded-lg shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-base font-medium hover:bg-indigo-700">
                        Buscar
                    </button>
                    @if($buscar !== '')
                        <a href="{{ route('admin.lugares.index') }}"
                           class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-base text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    @endif
                </form>

                @if($lugares->isEmpty())
                    <p class="text-base text-gray-600 py-8 text-center">
                        No hay lugares que coincidan con la búsqueda.
                    </p>
                @endif
```

Envolver la `<table>` en `@if($lugares->isNotEmpty()) … @endif` para que no salga
una tabla vacía debajo del aviso.

Cambiar las cabeceras a `text-sm font-medium text-gray-600` y añadir la columna
de uso antes de «Acciones»:

```blade
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-600">Zonas</th>
```

Y su celda:

```blade
                            <td class="px-6 py-4 text-center">
                                @if($lugar->zonas_count === 0)
                                    <span class="text-sm text-gray-400">Sin uso</span>
                                @else
                                    <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                        {{ $lugar->zonas_count }} {{ $lugar->zonas_count === 1 ? 'zona' : 'zonas' }}
                                    </span>
                                @endif
                            </td>
```

- [ ] **Step 3: Avisar antes de borrar un lugar en uso**

Sustituir el `onsubmit` del formulario de eliminación por uno que dependa del
contador. Borrar un lugar con zonas falla por clave ajena y el usuario recibe un
error críptico; mejor decírselo antes:

```blade
                                    <form action="{{ route('admin.lugares.destroy', $lugar) }}" method="POST"
                                          onsubmit="return confirm({{ $lugar->zonas_count > 0
                                              ? Js::from('Este lugar lo usan ' . $lugar->zonas_count . ' zonas. No se puede borrar mientras las tengan asignadas.')
                                              : Js::from('¿Borrar este lugar?') }});">
```

`Js::from()` escapa el texto correctamente dentro del atributo. **No concatenes
el nombre del lugar aquí sin `Js::from()`**: dentro de un atributo HTML el
navegador decodifica las entidades antes de que el parser de JS las vea, y un
nombre con comilla simple rompe la página. Ya pasó una vez en
`admin/users/index.blade.php`.

Cuando hay zonas, el `confirm` avisa pero sigue permitiendo aceptar; la clave
ajena lo impedirá de todos modos y `LugarController::destroy` ya captura el
error. Si no lo captura, comprobarlo:

```bash
grep -n "destroy" -A 15 app/Http/Controllers/Admin/LugarController.php
```

Si no hay `try/catch` sobre `QueryException`, añadirlo devolviendo
`back()->with('error', 'No se puede borrar un lugar que tiene zonas asignadas.')`.
La vista ya pinta `session('error')`.

- [ ] **Step 4: Ejecutar los tests de lugares**

```bash
php artisan test --filter="lugares"
```

Esperado: PASS.

- [ ] **Step 5: Suite completa y commit**

```bash
php artisan test
git add app/Http/Controllers/Admin/LugarController.php \
        resources/views/admin/lugares/index.blade.php
git commit -m "feat(admin): buscador de lugares y aviso al borrar uno en uso"
```

---

### Task 4: Revisión final

- [ ] **Step 1: Suite completa y build**

```bash
php artisan test && npm run build
```

- [ ] **Step 2: Comprobar que no queda text-xs en sitios legibles**

```bash
grep -rn "text-xs" resources/views/admin/
```

Los únicos resultados aceptables son insignias: las etiquetas de rol y los chips
de zona. Cualquier `text-xs` en una cabecera de tabla, un botón o un texto de
celda hay que subirlo.

- [ ] **Step 3: Comprobar que no quedan mayúsculas forzadas**

```bash
grep -rn "uppercase\|tracking-wider\|tracking-widest" resources/views/admin/
```

Esperado: sin resultados.

- [ ] **Step 4: Recorrido manual**

Entrar como admin y comprobar:

1. El panel enseña cifras, no texto fijo. Con una zona sin jefe, sale el aviso
   ámbar arriba.
2. Buscar «ana» en usuarios filtra; pasar de página **conserva** el filtro.
3. Filtrar por rol y buscar a la vez funcionan combinados.
4. Un usuario con dos zonas las enseña ambas; uno sin zonas dice «Sin zonas».
5. En lugares, intentar borrar uno con zonas avisa del número antes de enviar.

El punto 2 es el que se rompe si falta `->withQueryString()`.

- [ ] **Step 5: Commit final si algo cambió**

```bash
git status --short
```

---

## Fuera de este plan

- **La lista de zonas del admin** se rediseña en
  `docs/superpowers/plans/2026-08-07-pagina-de-zona.md`, Task 7.
- **Los formularios de alta y edición** (`users/form`, `lugares/form`,
  `zonas/form`) no se tocan. Comparten los mismos defectos de tipografía, pero
  se usan de forma puntual y no bloquean nada; van en un lote posterior.
