# Navbar y migas (Fase 1 del rediseño) — Plan de implementación

> **Para trabajadores agénticos:** SUB-SKILL REQUERIDA: usa
> `superpowers:subagent-driven-development` (recomendada) o
> `superpowers:executing-plans` para implementar este plan tarea a tarea. Los
> pasos usan casillas (`- [ ]`) para el seguimiento.

**Objetivo:** dar a la aplicación una sola forma de saber dónde se está y de
subir de nivel —migas— y una barra de navegación que sirva a los dos perfiles
sin duplicar sus enlaces.

**Arquitectura:** un componente `<x-migas>` que cada vista declara, siguiendo
el patrón que `<x-pestanas-matriz>` ya estableció en 18 vistas. Hereda de
`<x-boton-volver>` la lógica de raíz por rol y lo sustituye: 22 llamadas y el
componente se borran. La barra pasa a decidir sus destinos en un solo sitio,
que escritorio y móvil consumen, y gana un selector de zona para el operativo.

**Pila:** Blade + Alpine, Tailwind, PHPUnit. Sin dependencias nuevas.

**Spec:** `docs/superpowers/specs/2026-08-13-navbar-y-migas-design.md`

## Restricciones globales

Se aplican a todas las tareas.

- **Ningún test anterior a la rama se modifica.** Uno que se ponga rojo es la
  señal de que la sustitución cambió comportamiento y no aspecto: parar y
  consultar. (En `fundacion-visual` esta restricción chocó dos veces y las dos
  veces tenía razón.)
- **Clases de Tailwind literales, nunca construidas por concatenación.** Lo que
  no aparezca tal cual en el fuente se purga.
- **Se escribe `gray-*`, nunca `slate-*`.** `tailwind.config.js` redefine `gray`
  como alias de `slate` y es el único sitio que decide qué es gris.
- **El nombre de una matriz sale de `App\Matrices\Registro::ENTRADAS`,** nunca
  escrito en una vista.
- **`package-lock.json` no entra en ningún commit.** Si aparece modificado:
  `git checkout -- package-lock.json`.
- **Orden innegociable: las migas se ponen ANTES de quitar ningún botón.** El
  riesgo de la fase es dejar una pantalla sin salida. Las tareas 2 y 3 añaden;
  la 4 quita.
- La suite se corre con `php artisan test`. Si muere con `Out of memory`, es el
  archivo de paginación de Windows y no el código: partir en
  `php artisan test tests/Unit` y `php artisan test tests/Feature`.

## Ficheros

| Fichero | Responsabilidad |
|---|---|
| `resources/views/components/migas.blade.php` | **Crear.** El rastro y sus enlaces. Único sitio que sabe cuál es la raíz según rol. |
| `tests/Feature/MigasTest.php` | **Crear.** El contrato del componente en aislado. |
| `tests/Feature/NavegacionCompletaTest.php` | **Crear.** Dos guardianes: ninguna página sin migas, y escritorio/móvil con los mismos destinos. |
| `resources/views/layouts/navigation.blade.php` | **Modificar.** Un solo sitio decide los destinos; añade el selector de zona; adopta la estética del sistema. |
| `resources/views/components/boton-volver.blade.php` | **Borrar** en la tarea 4. |
| `resources/views/components/nav-link.blade.php` | **Borrar** en la tarea 7. |
| `resources/views/components/responsive-nav-link.blade.php` | **Borrar** en la tarea 7. |
| 20 vistas de `resources/views/operativo/` y `admin/` | **Modificar.** Añaden migas (T2, T3) y pierden su «Volver» (T4). |

---

### Tarea 1: El componente `<x-migas>`

**Ficheros:**
- Crear: `resources/views/components/migas.blade.php`
- Crear: `tests/Feature/MigasTest.php`

**Interfaces:**
- Consume: `App\Matrices\Registro::ENTRADAS` (`['nombre']`, `['rutas']['editar']`),
  `auth()->user()->esAdmin()`, rutas `admin.zonas.index`, `operativo.dashboard`,
  `operativo.zona.panel`.
- Produce: `<x-migas :zona="$zona" clave="fit" actual="Resultados" />`. Los tres
  props son opcionales. Las tareas 2 y 3 lo consumen.

- [ ] **Paso 1: escribir el test que falla**

Crear `tests/Feature/MigasTest.php`:

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
 * El contrato de <x-migas>, fijado sobre el componente y no sobre las vistas
 * que lo usan.
 *
 * Hereda la lógica de raíz que era de <x-boton-volver>: la jerarquía es
 * lista de zonas → zona → matriz, y quién es la lista de arriba depende del
 * rol. Ese ternario vive en UN sitio a propósito: replicado es exactamente la
 * forma que tomó el fallo que dejó al admin viendo enlaces de edición durante
 * toda una rama.
 */
class MigasTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private User $admin;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe  = User::factory()->create(['role_id' => Role::where('nombre', 'jefe_zona')->value('id')]);
        $this->admin = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    private function migas(User $usuario, string $etiqueta): string
    {
        return (string) $this->actingAs($usuario)->blade($etiqueta, ['zona' => $this->zona]);
    }

    public function test_la_raiz_del_operativo_es_mis_zonas(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" />');

        $this->assertStringContainsString('Mis Zonas', $html);
        $this->assertStringContainsString(route('operativo.dashboard'), $html);
    }

    public function test_la_raiz_del_admin_es_su_listado_de_zonas(): void
    {
        $html = $this->migas($this->admin, '<x-migas :zona="$zona" />');

        $this->assertStringContainsString('Zonas', $html);
        $this->assertStringContainsString(route('admin.zonas.index'), $html);
        $this->assertStringNotContainsString(route('operativo.dashboard'), $html);
    }

    /**
     * El último tramo no es enlace, lo pida quien lo pida. Sin esto, la miga
     * de la página actual llevaría a la página actual: un enlace que no hace
     * nada y que además invita a pulsarlo.
     */
    public function test_el_ultimo_tramo_no_es_enlace(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" />');

        // La zona es el último tramo aquí, así que su destino NO puede estar.
        $this->assertStringContainsString('Zona de prueba', $html);
        $this->assertStringNotContainsString(route('operativo.zona.panel', $this->zona->id), $html);
    }

    /**
     * Con una hoja, la zona SÍ pasa a ser enlace: deja de ser el último tramo.
     * Es la contraparte del test anterior; sin ella, un componente que no
     * pintara nunca el enlace de la zona pasaría los dos.
     */
    public function test_con_hoja_la_zona_pasa_a_ser_enlace(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" actual="Inventario" />');

        $this->assertStringContainsString(route('operativo.zona.panel', $this->zona->id), $html);
        $this->assertStringContainsString('Inventario', $html);
    }

    /**
     * El nombre de la matriz sale del Registro y no de la vista. Si se
     * escribiera a mano, la miga y la pestaña podrían acabar diciendo cosas
     * distintas del mismo criterio -que es lo que costó dos ramas cerrar en
     * las etiquetas de FIT y FET-.
     */
    public function test_el_nombre_de_la_matriz_sale_del_registro(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" clave="fit" actual="Resultados" />');

        $this->assertStringContainsString(
            \App\Matrices\Registro::ENTRADAS['fit']['nombre'],
            $html
        );
    }

    public function test_una_clave_desconocida_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('clave «no-existe» desconocida');

        $this->migas($this->jefe, '<x-migas :zona="$zona" clave="no-existe" />');
    }

    /**
     * Una matriz sin zona no tiene destino posible -su ruta necesita el id-,
     * así que revienta aquí en vez de construir una URL rota. Mismo patrón de
     * guardia que <x-barra-lateral-formulario> y <x-resumen-lista>.
     */
    public function test_una_matriz_sin_zona_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage(':zona es obligatoria cuando se da una clave');

        $this->actingAs($this->jefe)->blade('<x-migas clave="fit" />');
    }
}
```

- [ ] **Paso 2: correr los tests y verlos fallar**

```bash
php artisan test tests/Feature/MigasTest.php
```

Esperado: los siete fallan con `InvalidArgumentException: Unable to locate a
class or view for component [migas]`.

**Si fallan por otra cosa, leerla antes de tocar nada.** El componente llama a
`auth()->user()->esAdmin()`, y estos tests lo renderizan con `$this->blade()`
después de `actingAs()`. Debería bastar —el guard queda puesto—, pero
`BotonVolverTest` eligió montar páginas reales con `->get()` en vez de
`blade()`, y puede que fuera por esto. Si `auth()->user()` llega nulo, la salida
es ejercitar el componente desde una página servida, como hacía aquel: es un
cambio de montaje del test, no del contrato, y el resto del fichero sigue
valiendo igual.

- [ ] **Paso 3: escribir el componente**

Crear `resources/views/components/migas.blade.php`:

```blade
@props(['zona' => null, 'clave' => null, 'actual' => null])

{{--
    Dónde estás, y cómo subir.

    Sustituye a <x-boton-volver> y hereda su decisión central sin
    reescribirla: la navegación es una jerarquía -lista de zonas → zona →
    matriz- y quién es la lista de arriba depende del rol. Ese ternario vive
    en UN sitio a propósito: replicado es exactamente la forma que tomó el
    fallo que dejó al admin viendo enlaces de edición durante toda una rama.

    El nombre de la matriz sale del Registro y nunca de la vista: escrito a
    mano, la miga y la pestaña podrían decir cosas distintas del mismo
    criterio.

    Los GRUPOS del Registro -'base', 'vocacion'...- no entran en el rastro
    aunque tengan título. Ninguno tiene ruta, así que serían un tramo
    intermedio no navegable, y una miga que no lleva a ningún sitio enseña una
    jerarquía que la aplicación no tiene.
--}}

@php
    if ($clave !== null && $zona === null) {
        throw new \InvalidArgumentException(
            '<x-migas>: :zona es obligatoria cuando se da una clave; la ruta de una matriz necesita el id de la zona.'
        );
    }

    if ($clave !== null && ! isset(\App\Matrices\Registro::ENTRADAS[$clave])) {
        throw new \InvalidArgumentException(
            "<x-migas>: clave «{$clave}» desconocida; las válidas son "
            . implode(', ', array_keys(\App\Matrices\Registro::ENTRADAS)) . '.'
        );
    }

    $esAdmin = auth()->user()->esAdmin();

    $tramos = [[
        'texto'   => $esAdmin ? 'Zonas' : 'Mis Zonas',
        'destino' => $esAdmin ? route('admin.zonas.index') : route('operativo.dashboard'),
    ]];

    if ($zona) {
        $tramos[] = [
            'texto'   => $zona->nombre,
            'destino' => route('operativo.zona.panel', $zona->id),
        ];
    }

    if ($clave !== null) {
        $entrada  = \App\Matrices\Registro::ENTRADAS[$clave];
        $tramos[] = [
            'texto'   => $entrada['nombre'],
            'destino' => route($entrada['rutas']['editar'], $zona->id),
        ];
    }

    if ($actual !== null) {
        $tramos[] = ['texto' => $actual, 'destino' => null];
    }

    // El último nunca es enlace, lo haya puesto quien lo haya puesto: un
    // enlace a la página en la que ya estás no hace nada y se pulsa igual.
    $tramos[array_key_last($tramos)]['destino'] = null;
@endphp

<nav aria-label="Migas de pan" {{ $attributes->merge(['class' => 'mb-4']) }}>
    <ol class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
        @foreach($tramos as $i => $tramo)
            @if($i > 0)
                <li aria-hidden="true" class="text-gray-300">/</li>
            @endif

            <li>
                @if($tramo['destino'])
                    <a href="{{ $tramo['destino'] }}" class="hover:text-gray-900 hover:underline">
                        {{ $tramo['texto'] }}
                    </a>
                @else
                    <span class="font-medium text-gray-900" aria-current="page">{{ $tramo['texto'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
```

- [ ] **Paso 4: correr los tests y verlos pasar**

```bash
php artisan test tests/Feature/MigasTest.php
```

Esperado: 7 en verde.

- [ ] **Paso 5: correr la suite entera**

```bash
php artisan test
```

Esperado: 589 + 7 = **596**, ningún test anterior tocado.

- [ ] **Paso 6: commit**

```bash
git add resources/views/components/migas.blade.php tests/Feature/MigasTest.php
git commit -m "feat(migas): el componente que dice donde estas y como subir"
```

---

### Tarea 2: Migas en el panel de zona y en las nueve matrices

**Ficheros:**
- Modificar: `resources/views/operativo/zona/panel.blade.php`
- Modificar los `form.blade.php` de `evaluacion_fit`, `evaluacion_fet`,
  `evaluacion_paisaje`, `evaluacion_percepcion`, `evaluacion_irritacion`,
  `evaluacion_concentracion`, `evaluacion_valoracion_territorial`,
  `evaluacion_potencialidad`
- Modificar los `ponderacion.blade.php` de esas mismas ocho, más
  `resources/views/operativo/vtt/resultado.blade.php`

**Interfaces:**
- Consume: `<x-migas>` de la tarea 1.
- Produce: nada nuevo. La tarea 4 depende de que esto esté hecho.

**No se quita ningún `<x-boton-volver>` en esta tarea.** Conviven a propósito
durante dos tareas: es lo que permite comprobar que la miga lleva al mismo
sitio que el botón antes de borrarlo.

- [ ] **Paso 1: escribir el test que falla**

Añadir a `tests/Feature/MigasTest.php`:

```php
    /**
     * La miga de un formulario de matriz nombra la zona y la matriz, y las dos
     * llevan a donde deben. Se comprueba sobre la página servida y no sobre el
     * componente: lo que hay que garantizar no es que <x-migas> sepa recibir
     * una clave, sino que esta vista se la pase.
     */
    public function test_el_formulario_de_una_matriz_trae_sus_migas(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_fit.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Mis Zonas', $html);
        $this->assertStringContainsString('Zona de prueba', $html);
        $this->assertStringContainsString(\App\Matrices\Registro::ENTRADAS['fit']['nombre'], $html);
        $this->assertStringContainsString(route('operativo.dashboard'), $html);
    }
```

- [ ] **Paso 2: correr el test y verlo fallar**

```bash
php artisan test tests/Feature/MigasTest.php --filter=test_el_formulario_de_una_matriz_trae_sus_migas
```

Esperado: FAIL, no encuentra el nombre de la matriz.

- [ ] **Paso 3: añadir las migas**

En `resources/views/operativo/zona/panel.blade.php`, dentro del cuerpo y antes
del contenido, **añadir** (sin tocar todavía el `<x-boton-volver texto="← Volver" />`
de la línea 68):

```blade
<x-migas :zona="$zona" />
```

En cada uno de los ocho `form.blade.php`, **justo encima** de
`<x-pestanas-matriz ... />`, con la clave que esa vista ya le pasa a las
pestañas:

```blade
{{-- La clave es la misma que recibe <x-pestanas-matriz> tres líneas más
     abajo: las dos responden a «qué matriz es esta», y responderla dos veces
     con dos valores distintos es el defecto que este componente evita. --}}
<x-migas :zona="$zona" clave="fit" actual="Formulario" />
```

En cada `ponderacion.blade.php` y en `vtt/resultado.blade.php`, lo mismo con
`actual="Resultados"`.

**Las claves, una por fichero** —tomarlas del `<x-pestanas-matriz>` que ya hay
en cada vista, no adivinarlas—: `fit`, `fet`, `paisaje`, `percepcion`,
`irritacion`, `concentracion`, `vtt`, `potencialidad`. `vtt/resultado.blade.php`
usa `vtt` y no tiene formulario propio.

- [ ] **Paso 4: correr el test y verlo pasar**

```bash
php artisan test tests/Feature/MigasTest.php
```

Esperado: 8 en verde.

- [ ] **Paso 5: correr la suite entera**

```bash
php artisan test
```

Esperado: **597**. Ningún test anterior tocado.

- [ ] **Paso 6: commit**

```bash
git add resources/views/operativo tests/Feature/MigasTest.php
git commit -m "feat(migas): el panel de zona y las nueve matrices las declaran"
```

---

### Tarea 3: Migas en inventarios, involucrados, frecuentación y el estado vacío

**Ficheros:**
- Modificar: `resources/views/operativo/inventarios/index.blade.php`,
  `show.blade.php`, `form.blade.php`
- Modificar: `resources/views/operativo/involucrados/index.blade.php` y su formulario
- Modificar: `resources/views/operativo/frecuentacion/index.blade.php` y `form.blade.php`

**No hay vista de detalle de zona para el admin, y no hace falta crearla.**
`resources/views/admin/zonas/` solo tiene `index` y `form`: el admin ve una zona
a través del mismo `operativo/zona/panel.blade.php` que el resto, que es
exactamente por lo que `<x-boton-volver>` tenía una rama de admin. La fila «Zona
vista por el admin → Zonas › Chanduy» de la spec ya queda cubierta por la miga
que la tarea 2 puso en ese panel, porque el componente elige la raíz por rol.

**Las páginas de `admin/` —dashboard, usuarios, lugares, zonas— no llevan
migas, y es deliberado:** son secciones de primer nivel a las que se llega
desde la barra, así que un rastro de un solo tramo no diría nada que la barra
no diga ya. Por eso el guardián de la tarea 4 se ciñe a `operativo/`.

**Interfaces:**
- Consume: `<x-migas>` de la tarea 1.
- Produce: la cobertura que el guardián de la tarea 4 va a exigir.

- [ ] **Paso 1: escribir el test que falla**

Añadir a `tests/Feature/MigasTest.php`:

```php
    /**
     * El detalle de un inventario es el único sitio con cuatro niveles, así
     * que es donde una miga mal montada se nota primero.
     */
    public function test_el_listado_de_inventarios_trae_sus_migas(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.inventarios.index', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Mis Zonas', $html);
        $this->assertStringContainsString('Zona de prueba', $html);
        $this->assertStringContainsString(route('operativo.zona.panel', $this->zona->id), $html);
    }
```

- [ ] **Paso 2: correr el test y verlo fallar**

```bash
php artisan test tests/Feature/MigasTest.php --filter=test_el_listado_de_inventarios_trae_sus_migas
```

Esperado: FAIL, no encuentra el enlace al panel de la zona.

- [ ] **Paso 3: añadir las migas**

En cada vista, encima del contenido:

- `inventarios/index`: `<x-migas :zona="$zona" clave="inventario" />`
- `inventarios/show`: `<x-migas :zona="$zona" clave="inventario" :actual="$inventario->nombre" />`
- `inventarios/form`: `<x-migas :zona="$zona" clave="inventario" :actual="$inventario->exists ? 'Editar' : 'Nuevo'" />`
- `involucrados/index`: `<x-migas :zona="$zona" clave="involucrados" />`
- `frecuentacion/index`: `<x-migas :zona="$zona" clave="frecuentacion" />`
- Los formularios de involucrados y frecuentación: igual con su `actual`.

**Comprobar la clave real de cada uno en `App\Matrices\Registro::ENTRADAS`
antes de escribirla.** El plan da los nombres esperados, pero el registro
manda: si no coincide, el componente revienta con la lista de claves válidas,
que es justo para lo que está esa guardia.

- [ ] **Paso 4: correr el test y verlo pasar**

```bash
php artisan test tests/Feature/MigasTest.php
```

Esperado: 9 en verde.

- [ ] **Paso 5: correr la suite entera**

```bash
php artisan test
```

Esperado: **598**.

- [ ] **Paso 6: commit**

```bash
git add resources/views tests/Feature/MigasTest.php
git commit -m "feat(migas): inventarios, involucrados y frecuentacion las declaran"
```

---

### Tarea 4: Fuera `<x-boton-volver>`, con el guardián que lo hace seguro

**Ficheros:**
- Crear: `tests/Feature/NavegacionCompletaTest.php`
- Modificar: las 20 vistas con `<x-boton-volver>`
- Borrar: `resources/views/components/boton-volver.blade.php`
- Borrar: `tests/Feature/BotonVolverTest.php` — su contrato lo hereda `MigasTest`

**Interfaces:**
- Consume: las migas puestas en las tareas 2 y 3.
- Produce: un sistema con un solo control para subir de nivel.

**Esta es la tarea con más superficie de la fase.** El guardián se escribe
PRIMERO y se ve en rojo antes de borrar nada.

- [ ] **Paso 1: escribir el guardián y verlo fallar por el motivo correcto**

Crear `tests/Feature/NavegacionCompletaTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Ninguna página se queda sin salida.
 *
 * La Fase 1 quita 22 botones «Volver» y los sustituye por migas. El riesgo
 * real no es que una miga esté mal: es que una página se quede sin ninguna de
 * las dos cosas y no haya forma de subir. Abrir las vistas de una en una no lo
 * caza; recorrerlas, sí.
 *
 * Se afirma sobre el fuente y no sobre páginas servidas a propósito: así cubre
 * también las que ninguna prueba renderiza hoy. Mismo patrón y misma razón que
 * TipografiaUnicaTest.
 */
class NavegacionCompletaTest extends TestCase
{
    public function test_toda_pagina_de_zona_trae_migas(): void
    {
        foreach ($this->paginas() as $ruta => $contenido) {
            if (! str_starts_with($ruta, 'operativo/')) {
                continue;
            }

            $this->assertStringContainsString(
                '<x-migas',
                $contenido,
                "{$ruta} es una página y no trae migas. Desde la Fase 1 las migas son la "
                . 'única forma de subir de nivel: sin ellas esta pantalla no tiene salida.'
            );
        }
    }

    /**
     * Una página es una vista que monta el layout. Los componentes y parciales
     * no lo hacen, y no deben traer migas: las pinta la página que los
     * incluye, y repetirlas daría dos rastros en la misma pantalla.
     *
     * @return array<string, string> ruta relativa => contenido sin comentarios
     */
    private function paginas(): array
    {
        $directorio = resource_path('views');

        $ficheros = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directorio, \FilesystemIterator::SKIP_DOTS)
            ),
            '/\.blade\.php$/'
        );

        $paginas = [];

        foreach ($ficheros as $fichero) {
            $contenido = preg_replace(
                '/\{\{--.*?--\}\}/s',
                '',
                (string) file_get_contents($fichero->getPathname())
            );

            if (! str_contains($contenido, '<x-app-layout')) {
                continue;
            }

            $relativa = str_replace('\\', '/', substr($fichero->getPathname(), strlen($directorio) + 1));
            $paginas[$relativa] = $contenido;
        }

        $this->assertNotEmpty($paginas, 'No se encontró ninguna página que revisar.');

        return $paginas;
    }
}
```

```bash
php artisan test tests/Feature/NavegacionCompletaTest.php
```

Esperado: **FAIL, y hay que leer qué páginas nombra.** Son las que las tareas 2
y 3 no cubrieron. Cada una se arregla añadiéndole sus migas, no relajando el
test. Repetir hasta verde.

- [ ] **Paso 2: quitar las 22 llamadas**

```bash
grep -rn "x-boton-volver" resources/views
```

Devuelve **23** ocurrencias en 20 ficheros. **Una es un comentario** —en
`operativo/vtt/resultado.blade.php`, el que explica el arreglo a medias de
FV11—: ese comentario también sobra, porque describe un componente que deja de
existir. Quitar las 22 llamadas y el comentario.

Dos merecen mirarse y no borrarse a ciegas:

- `resources/views/components/matriz-sin-resultados.blade.php` — es un estado
  vacío compartido por cinco matrices. Al quitar el botón queda solo «Ir al
  formulario». **Abrir esa pantalla de verdad**, no solo sus tests.
- `resources/views/operativo/zona/panel.blade.php` — es el único caso sin zona,
  el de primer nivel.

- [ ] **Paso 3: borrar el componente y su test**

```bash
git rm resources/views/components/boton-volver.blade.php tests/Feature/BotonVolverTest.php
```

`BotonVolverTest` se va porque su contrato —la raíz según rol— lo fija ahora
`MigasTest`, sobre el componente que sí existe. **No es relajar la cobertura:
es moverla.** Comprobar que `MigasTest` tiene un test por cada afirmación que
hacía `BotonVolverTest` antes de borrarlo; si falta alguna, escribirla primero.

- [ ] **Paso 4: correr la suite entera**

```bash
php artisan test
```

Esperado: verde. El número baja por los tests de `BotonVolverTest` que se van y
sube por los del guardián: **anotar la cifra real, no predecirla.** Si algún
test anterior se pone rojo, **parar**: significa que la sustitución cambió
comportamiento y no aspecto.

- [ ] **Paso 5: commit**

```bash
git add -A
git commit -m "refactor(navegacion): las migas sustituyen a x-boton-volver"
```

---

### Tarea 5: El navbar decide sus destinos en un solo sitio

**Ficheros:**
- Modificar: `resources/views/layouts/navigation.blade.php`
- Modificar: `tests/Feature/NavegacionCompletaTest.php`

**Interfaces:**
- Produce: un array `$secciones` con `['texto', 'destino', 'activa']`, que los
  bloques de escritorio y móvil recorren. La tarea 6 le añade el selector.

- [ ] **Paso 1: escribir el test que falla**

Añadir a `tests/Feature/NavegacionCompletaTest.php` —necesita
`use Illuminate\Foundation\Testing\RefreshDatabase;` y el montaje de usuarios,
copiado de `MigasTest`—:

```php
    /**
     * Escritorio y móvil ofrecen los mismos destinos.
     *
     * No es hipotético: el propio fichero llevaba el comentario de que el
     * bloque móvil llegó a tener solo 'dashboard' y «la app era inservible en
     * móvil». Estaban escritos dos veces, así que desincronizarlos era
     * cuestión de que alguien tocara uno.
     */
    public function test_escritorio_y_movil_ofrecen_los_mismos_destinos(): void
    {
        $fuente = (string) file_get_contents(
            resource_path('views/layouts/navigation.blade.php')
        );

        $sinComentarios = preg_replace('/\{\{--.*?--\}\}/s', '', $fuente);

        // Un solo @if de rol: el que arma la lista. Dos significa que los
        // destinos vuelven a estar decididos en dos sitios.
        $this->assertSame(
            1,
            preg_match_all('/esAdmin\(\)/', $sinComentarios),
            'El navbar decide el perfil más de una vez: los destinos han vuelto a '
            . 'estar escritos dos veces, que es como el menú móvil se quedó atrás.'
        );
    }
```

- [ ] **Paso 2: correr el test y verlo fallar**

```bash
php artisan test tests/Feature/NavegacionCompletaTest.php --filter=test_escritorio_y_movil
```

Esperado: FAIL, encuentra 2 apariciones de `esAdmin()`.

- [ ] **Paso 3: unificar la fuente de los destinos**

Al principio de `resources/views/layouts/navigation.blade.php`:

```blade
@php
    // Los destinos, en un sitio. Estaban escritos dos veces -escritorio y
    // móvil- y el bloque móvil llegó a quedarse con solo 'dashboard', dejando
    // la aplicación inservible en el teléfono. Un array que los dos recorren
    // hace que eso no pueda volver a pasar por descuido.
    $secciones = auth()->user()->esAdmin()
        ? [
            ['texto' => 'Panel Admin', 'destino' => route('admin.dashboard'),     'activa' => request()->routeIs('admin.dashboard')],
            ['texto' => 'Usuarios',    'destino' => route('admin.users.index'),   'activa' => request()->routeIs('admin.users.*')],
            ['texto' => 'Lugares',     'destino' => route('admin.lugares.index'), 'activa' => request()->routeIs('admin.lugares.*')],
            ['texto' => 'Zonas',       'destino' => route('admin.zonas.index'),   'activa' => request()->routeIs('admin.zonas.*')],
        ]
        : [
            ['texto' => 'Mis Zonas',   'destino' => route('operativo.dashboard'), 'activa' => request()->routeIs('operativo.dashboard')],
        ];
@endphp
```

Sustituir los dos bloques `@if(Auth::user()->esAdmin()) ... @else ... @endif`
por sendos `@foreach($secciones as $seccion)` que pinten `<x-nav-link>` y
`<x-responsive-nav-link>` con `:href="$seccion['destino']"` y
`:active="$seccion['activa']"`.

- [ ] **Paso 4: correr el test y verlo pasar**

```bash
php artisan test tests/Feature/NavegacionCompletaTest.php
```

- [ ] **Paso 5: correr la suite entera**

```bash
php artisan test
```

Esperado: verde. Los tests de navegación existentes no deben moverse: los
destinos son los mismos, solo dejan de estar escritos dos veces.

- [ ] **Paso 6: commit**

```bash
git add resources/views/layouts/navigation.blade.php tests/Feature/NavegacionCompletaTest.php
git commit -m "refactor(navbar): los destinos se deciden en un solo sitio"
```

---

### Tarea 6: El selector de zona

**Ficheros:**
- Crear: `resources/views/components/selector-zona.blade.php`
- Modificar: `resources/views/layouts/navigation.blade.php`
- Crear: `tests/Feature/SelectorZonaTest.php`

**Interfaces:**
- Consume: `zonasComoJefe` y `zonasComoEquipo` de `App\Models\User`.
- Produce: `<x-selector-zona />`, sin props: se saca el usuario de `auth()`.

- [ ] **Paso 1: escribir el test que falla**

Crear `tests/Feature/SelectorZonaTest.php` con tres tests:

```php
    public function test_el_jefe_ve_sus_zonas_en_el_selector(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Zona de prueba', $html);
        $this->assertStringContainsString(route('operativo.zona.panel', $this->zona->id), $html);
    }

    /**
     * Las zonas del equipo también salen: «mis zonas» es la unión de las dos
     * relaciones, no solo las que uno dirige.
     */
    public function test_el_equipo_ve_las_zonas_a_las_que_esta_asignado(): void
    {
        $this->zona->equipo()->attach($this->equipo->id);

        $html = $this->actingAs($this->equipo)
            ->get(route('operativo.dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Zona de prueba', $html);
    }

    /**
     * El admin no lo lleva: ya tiene la sección «Zonas» con su buscador, y un
     * desplegable con todas las zonas del sistema crece sin techo. Decidido a
     * propósito; este test es lo que impide que se cuele por descuido.
     */
    public function test_el_admin_no_lleva_selector_de_zona(): void
    {
        $html = $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))->assertOk()->getContent();

        $this->assertStringNotContainsString('id="selector-zona"', $html);
    }
```

- [ ] **Paso 2: correr los tests y verlos fallar**

```bash
php artisan test tests/Feature/SelectorZonaTest.php
```

- [ ] **Paso 3: escribir el componente y montarlo**

Crear `resources/views/components/selector-zona.blade.php`:

```blade
{{--
    Saltar de zona sin subir a «Mis Zonas».

    El árbol tiene tres niveles y el salto que de verdad se repite es el
    primero: estás dentro de una matriz de una zona y quieres la misma matriz
    de otra. Antes había que subir dos niveles y volver a bajar dos.

    «Mis zonas» es la UNIÓN de las dos relaciones -las que uno dirige y las
    que tiene asignadas como equipo-, no solo las primeras: si mirara sólo
    zonasComoJefe, el equipo vería un selector vacío teniendo zonas.

    Reusa <x-dropdown>, el mismo que el menú de usuario. Un segundo
    desplegable escrito a mano sería un segundo sistema para lo mismo.
--}}

@php
    $usuario = auth()->user();

    $zonas = $usuario->zonasComoJefe
        ->merge($usuario->zonasComoEquipo)
        ->unique('id')
        ->sortBy('nombre');
@endphp

{{-- Sin zonas no se pinta nada: un selector vacío es peor que ausente,
     porque promete una navegación que no existe. --}}
@if($zonas->isNotEmpty())
    <div id="selector-zona" class="hidden sm:flex sm:items-center sm:ms-6">
        <x-dropdown align="left" width="48">
            <x-slot name="trigger">
                <button class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                    <x-icono nombre="lista" class="w-4 h-4" />
                    Cambiar de zona
                </button>
            </x-slot>

            <x-slot name="content">
                @foreach($zonas as $zonaDelSelector)
                    <x-dropdown-link :href="route('operativo.zona.panel', $zonaDelSelector->id)">
                        {{ $zonaDelSelector->nombre }}
                    </x-dropdown-link>
                @endforeach
            </x-slot>
        </x-dropdown>
    </div>
@endif
```

**Verificado al escribir el plan, para que no haya que adivinarlo:**
`<x-icono nombre="lista">` existe (`icono.blade.php:11`) y `<x-dropdown>` acepta
`align="left"`.

**Cuidado con `width`:** su `match` solo traduce `'48'` a `w-48`; cualquier otro
valor cae en `default => $width` y **se pinta tal cual como clase**, así que un
`width="60"` acabaría poniendo `class="... 60 ..."`, que no es ninguna anchura.
Por eso va `width="48"`, el mismo que el menú de usuario. Si hiciera falta más
ancho, se pasa la clase entera (`width="w-60"`), nunca el número suelto.

En `navigation.blade.php`, montarlo solo para el perfil operativo:

```blade
@unless(auth()->user()->esAdmin())
    <x-selector-zona />
@endunless
```

- [ ] **Paso 4: correr los tests y verlos pasar**

```bash
php artisan test tests/Feature/SelectorZonaTest.php
```

- [ ] **Paso 5: correr la suite entera**

```bash
php artisan test
```

- [ ] **Paso 6: commit**

```bash
git add resources/views tests/Feature/SelectorZonaTest.php
git commit -m "feat(navbar): selector de zona para el perfil operativo"
```

---

### Tarea 7: La estética de la barra, y fuera los dos componentes de Breeze

**Ficheros:**
- Modificar: `resources/views/layouts/navigation.blade.php`
- Borrar: `resources/views/components/nav-link.blade.php`,
  `resources/views/components/responsive-nav-link.blade.php`

- [ ] **Paso 1: sustituir los dos componentes de Breeze**

`<x-nav-link>` y `<x-responsive-nav-link>` traen la estética de Breeze que la
Fase 0 no tocó porque son navegación, no botones. Sustituir su uso por enlaces
con las clases del sistema, **con las clases del estado activo literales en
variables** —igual que hace `<x-pestanas-matriz>`— porque Tailwind purga lo que
no aparezca tal cual en el fuente.

Junto al `$secciones` de la tarea 5:

```blade
@php
    $estiloActivo   = 'border-indigo-500 text-gray-900';
    $estiloInactivo = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';

    $estiloActivoMovil   = 'border-indigo-500 text-indigo-700 bg-indigo-50';
    $estiloInactivoMovil = 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50';
@endphp
```

El bloque de escritorio:

```blade
<div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
    @foreach($secciones as $seccion)
        <a href="{{ $seccion['destino'] }}"
           class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium transition {{ $seccion['activa'] ? $estiloActivo : $estiloInactivo }}">
            {{ $seccion['texto'] }}
        </a>
    @endforeach
</div>
```

Y el de móvil, recorriendo **el mismo `$secciones`**:

```blade
<div class="pt-2 pb-3 space-y-1">
    @foreach($secciones as $seccion)
        <a href="{{ $seccion['destino'] }}"
           class="block w-full ps-3 pe-4 py-2 border-s-4 text-base font-medium transition {{ $seccion['activa'] ? $estiloActivoMovil : $estiloInactivoMovil }}">
            {{ $seccion['texto'] }}
        </a>
    @endforeach
</div>
```

El menú de usuario del bloque móvil —perfil y cerrar sesión— también usa
`<x-responsive-nav-link>`: convertirlo con el mismo marcado de arriba, o el
componente no se podrá borrar.

- [ ] **Paso 2: borrar los dos componentes**

```bash
grep -rn "nav-link" resources/views    # debe quedar vacío
git rm resources/views/components/nav-link.blade.php resources/views/components/responsive-nav-link.blade.php
```

- [ ] **Paso 3: correr la suite entera**

```bash
php artisan test
```

Esperado: verde. Si un test de navegación se pone rojo, **parar y leerlo**:
probablemente afirma sobre una clase de Breeze, y esa es la conversación de la
restricción global, no un arreglo silencioso.

- [ ] **Paso 4: mirar la barra de verdad**

`npm run build` y abrir la aplicación con los dos perfiles. Los tests miran
marcado; que la barra no se rompa en móvil no lo ve ninguno.

- [ ] **Paso 5: commit**

```bash
git add -A
git commit -m "feat(navbar): la barra adopta la estetica del sistema"
```

---

### Tarea 8: Revisión de rama y traspaso

- [ ] **Paso 1: revisar la rama entera**

```bash
git diff main...HEAD
```

Buscar lo que ningún test ve: una miga que nombre una matriz distinta de la de
su pestaña, una página que perdiera su salida, la barra rota en móvil.

- [ ] **Paso 2: actualizar el traspaso**

`docs/ESTADO-PROYECTO.md`: sección de rama en §3, el punto 14 de §6 con la
Fase 1 tachada y las fases 2 a 4 en pie, y el recuento de la suite. Anotar
también que el punto 7 pierde su entrada de `<x-nav-link>`.

- [ ] **Paso 3: correr la suite sobre el resultado fusionado**

No solo sobre la rama.

- [ ] **Paso 4: commit y subir**

Fusionar se pregunta; subir no.
