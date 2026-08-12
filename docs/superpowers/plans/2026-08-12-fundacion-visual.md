# Fundación visual (Fase 0) — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dar a la aplicación un sistema visual —un ancho, una paleta, una tipografía y tres primitivos— y adoptarlo en las vistas que hoy repiten sus propias variantes.

**Architecture:** Tres componentes Blade sin estado (`<x-contenedor>`, `<x-badge>`, `<x-tarjeta>`, `<x-boton>`) que pintan lo que reciben y no consultan nada, más dos cambios de configuración (`tailwind.config.js`) que actúan sobre toda la aplicación sin tocar las vistas. Ninguna vista cambia de estructura: solo de ancho, color y de quién pinta sus cajas.

**Tech Stack:** Laravel 12, Blade, Alpine.js 3, Tailwind CSS 3, PHPUnit 11, Vite.

Diseño: `docs/superpowers/specs/2026-08-12-fundacion-visual-design.md`.

## Global Constraints

- **Ninguna vista cambia de estructura.** Ni columnas, ni navbar más allá de su contenedor, ni breadcrumbs, ni KPIs, ni tablas ordenables. Solo ancho, color, tipografía y sustituir lo repetido por lo compartido.
- **Todo el código escribe `gray-*`, también el nuevo.** `tailwind.config.js` es el único sitio que decide qué significa gris. Nunca `slate-*` en una vista o componente.
- **Clases de Tailwind completas**, nunca construidas por concatenación (`"max-w-" . $x` está prohibido; un array con las clases literales, no).
- **Los 553 tests existentes tienen que seguir en verde SIN modificar ninguno.** Un test que haya que tocar es la señal de que la sustitución cambió comportamiento y no solo aspecto: hay que entender por qué antes de seguir, y reportarlo.
- **Nada por debajo de 14 px** salvo insignias. Sin `uppercase` ni `tracking-widest`.
- **Comentarios en castellano explicando el porqué**, no el qué.
- `npm run build` limpio, y **verificado en el CSS generado**, no solo ejecutado.
- Entorno: PHP 8.2.33 nativo. `php artisan test` corre directo. **No usar Docker para nada.**
- `package-lock.json` aparece modificado en el árbol y **no forma parte de esta rama**: no incluirlo en ningún commit.

## Estructura de ficheros

| Fichero | Responsabilidad |
|---|---|
| `resources/views/components/contenedor.blade.php` | El ancho del documento, en un solo sitio |
| `resources/views/components/badge.blade.php` | Un estado del sistema, pintado |
| `resources/views/components/tarjeta.blade.php` | La caja blanca sobre el fondo |
| `resources/views/components/boton.blade.php` | La acción, en tres variantes y dos tamaños |
| `app/Servicios/EstadoZona.php` | Gana `ESTILOS_ESTADO`: el mapa estado → color, una sola vez |
| `tailwind.config.js` | Qué significa «gris» y cuál es la tipografía |
| `resources/views/layouts/app.blade.php` | El contenedor del cuerpo y de la cabecera; el fondo |
| `resources/views/layouts/navigation.blade.php` | El contenedor de la barra, para que alinee con el cuerpo |

---

### Task 1: `<x-contenedor>` y el layout

**Files:**
- Create: `resources/views/components/contenedor.blade.php`
- Create: `tests/Feature/ContenedorTest.php`
- Modify: `resources/views/layouts/app.blade.php:18,23-27,31-33`
- Modify: `resources/views/layouts/navigation.blade.php:3`

**Interfaces:**
- Produces: `<x-contenedor>` con prop `ancho` (`'normal'` por defecto, o `'estrecho'`). Acepta atributos sueltos y los fusiona en la clase. Las tareas 2 y 3 lo consumen.

**Contexto:** hoy el `<main>` del layout **no tiene contenedor**: cada vista pone el suyo. Esta tarea mete el contenedor en el layout. Durante las tareas 2 y 3 convivirá con los de las vistas —quedan anidados, y gana el más estrecho—; es un estado intermedio correcto, no un fallo.

- [ ] **Paso 1: Escribir los tests que fallan**

`tests/Feature/ContenedorTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-contenedor> no deriva nada: recibe un ancho por nombre y pinta.
 * Mismo patrón que ResumenListaTest, y por el mismo motivo: un componente
 * que solo pinta se prueba pintándolo.
 */
class ContenedorTest extends TestCase
{
    public function test_el_ancho_normal_llega_a_1440(): void
    {
        $html = (string) $this->blade('<x-contenedor>contenido</x-contenedor>');

        $this->assertStringContainsString('max-w-[1440px]', $html);
        $this->assertStringContainsString('contenido', $html);
    }

    /** Fluido: en pantallas menores que el tope usa todo el ancho disponible. */
    public function test_es_fluido_hasta_el_tope(): void
    {
        $html = (string) $this->blade('<x-contenedor>contenido</x-contenedor>');

        $this->assertStringContainsString('w-full', $html);
        $this->assertStringContainsString('mx-auto', $html);
    }

    public function test_el_estrecho_se_pide_a_proposito(): void
    {
        $html = (string) $this->blade('<x-contenedor ancho="estrecho">contenido</x-contenedor>');

        $this->assertStringContainsString('max-w-2xl', $html);
        $this->assertStringNotContainsString('max-w-[1440px]', $html);
    }

    /**
     * Un ancho mal escrito tiene que reventar, no caer en el ancho por
     * defecto: un contenedor silenciosamente normal donde se pidió estrecho
     * es un fallo que solo se ve mirando la página, y nadie la mira.
     */
    public function test_un_ancho_desconocido_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('ancho «gigante» desconocido');

        $this->blade('<x-contenedor ancho="gigante">contenido</x-contenedor>');
    }

    /** Las clases propias se suman a las del contenedor, no lo sustituyen. */
    public function test_admite_clases_extra_sin_perder_las_suyas(): void
    {
        $html = (string) $this->blade('<x-contenedor class="py-12">contenido</x-contenedor>');

        $this->assertStringContainsString('py-12', $html);
        $this->assertStringContainsString('max-w-[1440px]', $html);
    }
}
```

- [ ] **Paso 2: Verlos fallar**

Run: `php artisan test --filter=ContenedorTest`
Expected: FAIL — `Unable to locate a class or view for component [contenedor]`.

- [ ] **Paso 3: Escribir el componente**

`resources/views/components/contenedor.blade.php`:

```blade
@props(['ancho' => 'normal'])

{{--
    El ancho del documento, en un solo sitio.

    Antes vivía en 39 ficheros con 9 valores distintos, y el <main> del layout
    no tenía ninguno: cada vista decidía por su cuenta lo ancha que era la
    aplicación. Cambiar el ancho de la aplicación era editar 39 ficheros; ahora
    es editar esta tabla.

    Fluido con tope: en monitores grandes se comporta como un ancho fijo de
    1440, y en portátiles de 1280-1366 aprovecha todo el ancho en vez de dejar
    muerto el margen del contenedor.

    «estrecho» existe porque no todas las vistas mienten al ser estrechas: un
    formulario de cuatro campos a 1440px es peor, no mejor.

    Las clases van literales en el array y no construidas con el nombre del
    ancho: Tailwind purga las que no aparezcan tal cual en el fuente.
--}}

@php
    $anchos = [
        'normal'   => 'max-w-[1440px]',
        'estrecho' => 'max-w-2xl',
    ];

    if (! isset($anchos[$ancho])) {
        throw new \InvalidArgumentException(
            "<x-contenedor>: ancho «{$ancho}» desconocido; los válidos son normal y estrecho."
        );
    }
@endphp

<div {{ $attributes->merge(['class' => 'w-full ' . $anchos[$ancho] . ' mx-auto px-4 sm:px-6 lg:px-8']) }}>
    {{ $slot }}
</div>
```

- [ ] **Paso 4: Verlos pasar**

Run: `php artisan test --filter=ContenedorTest`
Expected: PASS, 5 tests.

- [ ] **Paso 5: Meterlo en el layout**

En `resources/views/layouts/app.blade.php`, sustituir el bloque de la cabecera y el del contenido. Antes (líneas 22-33):

```blade
            @isset($header)
                <header class="bg-white shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
```

Después:

```blade
            @isset($header)
                <header class="bg-white shadow">
                    <x-contenedor class="py-6">
                        {{ $header }}
                    </x-contenedor>
                </header>
            @endisset

            {{--
                El contenedor vive aquí y no en cada vista: si la cabecera y el
                cuerpo llevaran anchos distintos, el título de la página no
                alinearía con su contenido, y se nota en todas las páginas a la
                vez.
            --}}
            <main>
                <x-contenedor>
                    {{ $slot }}
                </x-contenedor>
            </main>
```

- [ ] **Paso 6: Alinear la barra de navegación**

En `resources/views/layouts/navigation.blade.php`, línea 3, sustituir:

```blade
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
```

por:

```blade
    <x-contenedor>
```

y su `</div>` de cierre correspondiente por `</x-contenedor>`. **Localizar el cierre correcto contando la anidación**, no por el primer `</div>` que aparezca.

- [ ] **Paso 7: Correr la suite entera**

Run: `php artisan test`
Expected: **558 passed** (553 + los 5 de `ContenedorTest`). **Ningún test existente modificado.** Si alguno se pone rojo, el contenedor anidado cambió algo que se afirmaba: reportarlo antes de tocar el test.

- [ ] **Paso 8: Commit**

```bash
git add resources/views/components/contenedor.blade.php tests/Feature/ContenedorTest.php resources/views/layouts/app.blade.php resources/views/layouts/navigation.blade.php
git commit -m "feat(layout): el ancho de la aplicacion deja de vivir en cada vista"
```

---

### Task 2: Quitar los contenedores de página — vistas operativas

**Files:**
- Modify: los ficheros de `resources/views/operativo/` que tengan contenedor de página (ver Paso 1)

**Interfaces:**
- Consumes: `<x-contenedor>` de la Tarea 1, ya montado en el layout.

**Contexto, y es lo que puede romper esta tarea:** los ficheros medidos **no son todos contenedores de página**. Hay dos cosas escritas con las mismas clases:

1. **Contenedor de página** — el `max-w-* mx-auto` **más externo**, el primero dentro del `<x-app-layout>`. Ese se borra: su trabajo lo hace ahora el layout.
2. **Caja estrecha dentro de la página** — un bloque deliberadamente más angosto que su página: un cuadro de resumen, una tabla pequeña, un aviso centrado. **Esas se quedan.**

`operativo/evaluacion_valoracion_territorial/ponderacion.blade.php` tiene `max-w-5xl`, `max-w-3xl`, `max-w-2xl`, `max-w-xl` y `max-w-sm`. **No son cinco contenedores de página: es uno y cuatro cajas interiores.** Arrancarlas rompe la vista.

- [ ] **Paso 1: Listar los ficheros y mirar cada uno**

```bash
grep -rn 'max-w-[a-z0-9]* mx-auto' resources/views/operativo/ --include=*.blade.php
```

Para cada fichero, abrirlo y decidir cuál de sus coincidencias es la más externa. Solo esa se toca.

- [ ] **Paso 2: Quitar el contenedor de página de cada fichero**

El patrón habitual es este; el `py-12` se conserva porque es separación vertical, no ancho:

```blade
    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            ... contenido ...
        </div>
    </div>
```

queda:

```blade
    <div class="py-12">
        ... contenido ...
    </div>
```

**Regla de excepción, sin interpretación:** si el contenedor de página del fichero es hoy `max-w-2xl` o **menor**, no se borra sin más — la vista pasa a declararse estrecha en el layout. En `operativo/` el único caso es `operativo/frecuentacion/form.blade.php`, donde el contenedor de página se sustituye por:

```blade
        <x-contenedor ancho="estrecho">
            ... contenido ...
        </x-contenedor>
```

- [ ] **Paso 3: Correr la suite entera**

Run: `php artisan test`
Expected: **558 passed**, ningún test existente modificado.

- [ ] **Paso 4: Comprobar que no queda ningún contenedor de página**

```bash
grep -rn 'max-w-[a-z0-9]* mx-auto' resources/views/operativo/ --include=*.blade.php
```

Cada coincidencia que quede tiene que ser una caja interior. **Enumerarlas en el informe con una línea cada una diciendo por qué se quedó.** Una lista vacía también es sospechosa: significaría que se borraron cajas interiores.

- [ ] **Paso 5: Commit**

```bash
git add resources/views/operativo/
git commit -m "refactor(operativo): las vistas dejan de fijar su propio ancho"
```

---

### Task 3: Quitar los contenedores de página — admin, perfil y componentes

**Files:**
- Modify: los ficheros de `resources/views/admin/`, `resources/views/profile/` y `resources/views/components/` que tengan contenedor de página

**Interfaces:**
- Consumes: `<x-contenedor>` de la Tarea 1.

Misma distinción que la Tarea 2, repetida aquí porque las tareas se leen sueltas: se borra **solo el `max-w-* mx-auto` más externo** de cada vista, el que hace de contenedor de página. Las cajas interiores —bloques deliberadamente más angostos que su página— se quedan.

- [ ] **Paso 1: Listar los ficheros y mirar cada uno**

```bash
grep -rn 'max-w-[a-z0-9]* mx-auto' resources/views/admin/ resources/views/profile/ resources/views/components/ --include=*.blade.php
```

- [ ] **Paso 2: Quitar el contenedor de página de cada fichero**

Mismo patrón que la Tarea 2:

```blade
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            ... contenido ...
        </div>
    </div>
```

queda:

```blade
    <div class="py-12">
        ... contenido ...
    </div>
```

**Regla de excepción, sin interpretación:** `ancho="estrecho"` va donde el contenedor de página es **hoy** `max-w-2xl` o menor. Aquí el único caso es `admin/lugares/form.blade.php`:

```blade
        <x-contenedor ancho="estrecho">
            ... contenido ...
        </x-contenedor>
```

**`admin/users/form.blade.php` y `admin/zonas/form.blade.php` NO son estrechos**, aunque sean formularios de alta: hoy son `max-w-7xl` y pasan a `normal` como todo lo demás. Estrecharlos sería cambiar la estructura de una vista, y esta fase no cambia estructuras. Si se ven mal anchos, se anota para la fase que les toque.

`resources/views/components/matriz-sin-resultados.blade.php` tiene `max-w-3xl mx-auto` y **es una caja interior**: es un aviso centrado dentro de la página de otro. No se toca.

- [ ] **Paso 3: Correr la suite entera**

Run: `php artisan test`
Expected: **558 passed**, ningún test existente modificado.

- [ ] **Paso 4: Comprobar lo que queda**

```bash
grep -rn 'max-w-[a-z0-9]* mx-auto' resources/views/ --include=*.blade.php
```

Enumerar en el informe cada coincidencia que quede, con una línea diciendo por qué es una caja interior y no un contenedor de página.

- [ ] **Paso 5: Commit**

```bash
git add resources/views/admin/ resources/views/profile/ resources/views/components/
git commit -m "refactor(admin): las vistas dejan de fijar su propio ancho"
```

---

### Task 4: Paleta y tipografía

**Files:**
- Modify: `tailwind.config.js:12-18`
- Modify: `resources/views/layouts/app.blade.php:12,18`

**Interfaces:**
- Produces: `gray-*` pasa a valer la escala `slate` en toda la aplicación. Ninguna vista cambia de fuente.

Esta tarea es la de mayor efecto visual y menor diff. **No se toca ninguno de los 1056 usos de `gray-*`.**

- [ ] **Paso 1: Redefinir el gris y la tipografía**

`tailwind.config.js` completo tras el cambio:

```js
import defaultTheme from 'tailwindcss/defaultTheme';
import colors from 'tailwindcss/colors';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
            },

            // Los 1056 usos de gray-* de las vistas siguen escritos igual y
            // pasan todos a la vez al tinte azulado de slate. Se hace aquí y
            // no reescribiéndolos porque el único riesgo real era que
            // convivieran los dos grises -gray es neutro, slate tira a azul, y
            // mezclados se nota-; migrados juntos, no hay mezcla posible.
            //
            // La regla que se sigue de esto: TODO el código escribe gray-*,
            // también el nuevo. Este fichero es el único sitio que decide qué
            // significa gris.
            colors: {
                gray: colors.slate,
            },
        },
    },

    plugins: [forms],
};
```

- [ ] **Paso 2: Cambiar la fuente y el fondo en el layout**

En `resources/views/layouts/app.blade.php`, línea 12:

```blade
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
```

pasa a:

```blade
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
```

Y la línea 18:

```blade
        <div class="min-h-screen bg-gray-100">
```

pasa a:

```blade
        {{-- bg-gray-50 vale #F8FAFC con el alias de tailwind.config.js: el
             fondo pedido, escrito con el único nombre de gris que usa el
             proyecto. --}}
        <div class="min-h-screen bg-gray-50">
```

- [ ] **Paso 3: Construir y verificar el CSS generado**

```bash
npm run build
```

Después, comprobar que el gris es de verdad slate. `slate-50` es `#f8fafc`; `gray-50` de Tailwind sería `#f9fafb`:

```bash
grep -o '\.bg-gray-50{[^}]*}' public/build/assets/*.css
```

Expected: contiene `#f8fafc`, no `#f9fafb`.

- [ ] **Paso 4: Correr la suite entera**

Run: `php artisan test`
Expected: **558 passed**, ningún test existente modificado. Los tests miran HTML, no CSS, así que un cambio de paleta no debería moverlos; si mueve alguno, hay un test afirmando sobre un color y hay que reportarlo.

- [ ] **Paso 5: Commit**

```bash
git add tailwind.config.js resources/views/layouts/app.blade.php public/build
git commit -m "feat(estilo): gris azulado y tipografia Inter en toda la aplicacion"
```

---

### Task 5: El mapa de estados en un solo sitio, y `<x-badge>`

**Files:**
- Modify: `app/Servicios/EstadoZona.php` (añadir la constante `ESTILOS_ESTADO` junto a la declaración de la clase)
- Modify: `resources/views/components/fila-matriz.blade.php:5-12`
- Create: `resources/views/components/badge.blade.php`
- Create: `tests/Feature/BadgeTest.php`

**Interfaces:**
- Produces: `App\Servicios\EstadoZona::ESTILOS_ESTADO`, un `array<string, array{icono: string, detalle: string, insignia: string}>` con las cinco claves `sin_empezar`, `sin_estado`, `borrador`, `validada`, `bloqueada`. Y `<x-badge :estado="...">` con ranura opcional que sustituye el texto conservando el color.

**Contexto:** `<x-fila-matriz>` ya tiene su propio mapa de estado → color. Si `<x-badge>` escribe el suyo, son dos tablas del mismo conocimiento y el día que se añada un estado una se queda atrás sin que nada falle.

- [ ] **Paso 1: Escribir los tests que fallan**

`tests/Feature/BadgeTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Servicios\EstadoZona;
use Tests\TestCase;

/**
 * <x-badge> pinta un estado del sistema. Los cinco valores no son inventados:
 * son los que produce EstadoZona y consume <x-fila-matriz>.
 */
class BadgeTest extends TestCase
{
    public function test_pinta_los_cinco_estados_con_su_texto(): void
    {
        $esperado = [
            'sin_empezar' => 'Sin empezar',
            'sin_estado'  => 'Sin estado',
            'borrador'    => 'Borrador',
            'validada'    => 'Validada',
            'bloqueada'   => 'Bloqueada',
        ];

        foreach ($esperado as $estado => $texto) {
            $html = (string) $this->blade(
                '<x-badge :estado="$estado" />',
                ['estado' => $estado]
            );

            $this->assertStringContainsString($texto, $html, "El estado {$estado} no dijo «{$texto}».");
        }
    }

    /**
     * «Listo para validar» no es un estado sino un borrador que además está
     * completo. Se pasa como ranura para que el sistema no acabe con seis
     * estados en la interfaz y cinco en el servicio.
     */
    public function test_la_ranura_sustituye_el_texto_y_conserva_el_color(): void
    {
        $html = (string) $this->blade(
            '<x-badge estado="borrador">Listo para validar</x-badge>'
        );

        $this->assertStringContainsString('Listo para validar', $html);
        $this->assertStringNotContainsString('>Borrador<', $html);
        $this->assertStringContainsString(EstadoZona::ESTILOS_ESTADO['borrador']['insignia'], $html);
    }

    public function test_un_estado_desconocido_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('estado «inventado» desconocido');

        $this->blade('<x-badge estado="inventado" />');
    }

    /**
     * El test que impide que el mapa vuelva a duplicarse: si alguien copia los
     * colores dentro del badge y luego cambia los de fila-matriz, esto falla.
     */
    public function test_el_badge_y_la_fila_leen_el_mismo_mapa(): void
    {
        $fuenteBadge = file_get_contents(resource_path('views/components/badge.blade.php'));
        $fuenteFila  = file_get_contents(resource_path('views/components/fila-matriz.blade.php'));

        foreach ([$fuenteBadge, $fuenteFila] as $fuente) {
            $this->assertStringContainsString('ESTILOS_ESTADO', $fuente);
        }

        // Ningún color escrito a mano en ninguno de los dos: los colores
        // vienen del mapa, no del componente.
        $this->assertDoesNotMatchRegularExpression('/text-(amber|green)-[0-9]{3}/', $fuenteBadge);
        $this->assertDoesNotMatchRegularExpression('/text-(amber|green)-[0-9]{3}/', $fuenteFila);
    }
}
```

- [ ] **Paso 2: Verlos fallar**

Run: `php artisan test --filter=BadgeTest`
Expected: FAIL — `Undefined constant App\Servicios\EstadoZona::ESTILOS_ESTADO`.

- [ ] **Paso 3: Añadir la constante a `EstadoZona`**

En `app/Servicios/EstadoZona.php`, justo después de `final class EstadoZona {`, antes de la propiedad `$evaluaciones`:

```php
    /**
     * Estado → cómo se pinta, una sola vez para todo el sistema.
     *
     * Vivía dentro de <x-fila-matriz>. Al añadir <x-badge>, que pinta los
     * mismos cinco estados, habrían quedado dos tablas del mismo
     * conocimiento: el día que se añada un estado o se cambie un color, una
     * de las dos se queda atrás y nada falla en rojo.
     *
     * Tres claves por estado porque los dos consumidores necesitan cosas
     * distintas y ninguno debería cambiar lo que pinta solo para compartir de
     * dónde lo lee: `icono` y `detalle` son las que ya usaba fila-matriz,
     * `insignia` es la pareja fondo+texto+borde que necesita el badge.
     *
     * Clases completas: Tailwind purga las construidas por concatenación.
     *
     * @var array<string, array{icono: string, detalle: string, insignia: string}>
     */
    public const ESTILOS_ESTADO = [
        'sin_empezar' => [
            'icono'    => 'text-gray-400',
            'detalle'  => 'text-gray-500',
            'insignia' => 'bg-gray-100 text-gray-600 border-gray-200',
        ],
        'sin_estado' => [
            'icono'    => 'text-gray-500',
            'detalle'  => 'text-gray-600',
            'insignia' => 'bg-gray-100 text-gray-700 border-gray-200',
        ],
        'borrador' => [
            'icono'    => 'text-amber-600',
            'detalle'  => 'text-amber-700',
            'insignia' => 'bg-amber-100 text-amber-800 border-amber-200',
        ],
        'validada' => [
            'icono'    => 'text-green-600',
            'detalle'  => 'text-gray-600',
            'insignia' => 'bg-green-100 text-green-800 border-green-200',
        ],
        'bloqueada' => [
            'icono'    => 'text-gray-300',
            'detalle'  => 'text-gray-400',
            'insignia' => 'bg-gray-100 text-gray-400 border-gray-200',
        ],
    ];

    /** Estado → cómo se llama en pantalla. */
    public const NOMBRES_ESTADO = [
        'sin_empezar' => 'Sin empezar',
        'sin_estado'  => 'Sin estado',
        'borrador'    => 'Borrador',
        'validada'    => 'Validada',
        'bloqueada'   => 'Bloqueada',
    ];
```

- [ ] **Paso 4: Escribir el badge**

`resources/views/components/badge.blade.php`:

```blade
@props(['estado'])

{{--
    Un estado del sistema, pintado.

    No inventa sus valores: los cinco son los que produce EstadoZona, y los
    colores salen de EstadoZona::ESTILOS_ESTADO, el mismo mapa que usa
    <x-fila-matriz>. Aquí no hay ni un color escrito a mano, a propósito.

    La ranura sustituye el texto y conserva el color. Es lo que resuelve
    «Listo para validar», que no es un estado sino un borrador que además
    está completo: si fuera un valor más del mapa, el sistema tendría seis
    estados en la interfaz y cinco en el servicio.
--}}

@php
    $estilos = \App\Servicios\EstadoZona::ESTILOS_ESTADO;

    if (! isset($estilos[$estado])) {
        throw new \InvalidArgumentException(
            "<x-badge>: estado «{$estado}» desconocido; los válidos son "
            . implode(', ', array_keys($estilos)) . '.'
        );
    }
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center border rounded-full px-2.5 py-0.5 text-sm font-medium '
        . $estilos[$estado]['insignia'],
]) }}>
    {{ $slot->isEmpty() ? \App\Servicios\EstadoZona::NOMBRES_ESTADO[$estado] : $slot }}
</span>
```

- [ ] **Paso 5: Hacer que `<x-fila-matriz>` lea del mapa**

En `resources/views/components/fila-matriz.blade.php`, sustituir el array literal de las líneas 6-12 por la lectura del mapa, dejando el comentario que ya explica la decisión de color:

```blade
    // El color codifica SOLO el estado. La identidad la dan icono y nombre.
    // El mapa vive en EstadoZona porque <x-badge> pinta los mismos cinco
    // estados y dos copias se separan sin que nada falle.
    $estilos = \App\Servicios\EstadoZona::ESTILOS_ESTADO[$fila->estado];
```

**La apariencia no cambia ni un píxel**: `$estilos['icono']` y `$estilos['detalle']` siguen valiendo lo mismo que antes.

- [ ] **Paso 6: Verlos pasar, y la suite entera**

Run: `php artisan test --filter=BadgeTest`
Expected: PASS, 4 tests.

Run: `php artisan test`
Expected: **562 passed** (558 + los 4 de `BadgeTest`). **Los tests que cubren `<x-fila-matriz>` tienen que seguir verdes sin tocarlos**: esa es la prueba de que mover el mapa fue neutro.

- [ ] **Paso 7: Commit**

```bash
git add app/Servicios/EstadoZona.php resources/views/components/badge.blade.php resources/views/components/fila-matriz.blade.php tests/Feature/BadgeTest.php
git commit -m "feat(estado): un solo mapa de estado a color, y el badge que lo usa"
```

---

### Task 6: `<x-tarjeta>`

**Files:**
- Create: `resources/views/components/tarjeta.blade.php`
- Create: `tests/Feature/TarjetaTest.php`

**Interfaces:**
- Produces: `<x-tarjeta>` con prop `padding` (booleano, `true` por defecto). Las tareas 7 y 8 lo consumen.

- [ ] **Paso 1: Escribir los tests que fallan**

`tests/Feature/TarjetaTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-tarjeta> es la caja blanca sobre el fondo. Sustituye 52 repeticiones a
 * mano de «bg-white shadow-sm sm:rounded-lg», cada una con su variación.
 */
class TarjetaTest extends TestCase
{
    public function test_es_una_caja_blanca_con_borde_y_sombra_suave(): void
    {
        $html = (string) $this->blade('<x-tarjeta>contenido</x-tarjeta>');

        $this->assertStringContainsString('bg-white', $html);
        $this->assertStringContainsString('border-gray-200/80', $html);
        $this->assertStringContainsString('rounded-xl', $html);
        $this->assertStringContainsString('shadow-sm', $html);
        $this->assertStringContainsString('contenido', $html);
    }

    public function test_trae_su_propio_espaciado(): void
    {
        $html = (string) $this->blade('<x-tarjeta>contenido</x-tarjeta>');

        $this->assertStringContainsString('p-6', $html);
    }

    /**
     * Sin padding para las tarjetas que envuelven una tabla a sangre: con él,
     * la cabecera gris de la tabla queda flotando dentro de un marco blanco.
     */
    public function test_el_espaciado_se_puede_quitar(): void
    {
        $html = (string) $this->blade('<x-tarjeta :padding="false">contenido</x-tarjeta>');

        $this->assertStringNotContainsString('p-6', $html);
        $this->assertStringContainsString('bg-white', $html);
    }

    public function test_admite_clases_extra_sin_perder_las_suyas(): void
    {
        $html = (string) $this->blade('<x-tarjeta class="mb-6">contenido</x-tarjeta>');

        $this->assertStringContainsString('mb-6', $html);
        $this->assertStringContainsString('rounded-xl', $html);
    }
}
```

- [ ] **Paso 2: Verlos fallar**

Run: `php artisan test --filter=TarjetaTest`
Expected: FAIL — `Unable to locate a class or view for component [tarjeta]`.

- [ ] **Paso 3: Escribir el componente**

`resources/views/components/tarjeta.blade.php`:

```blade
@props(['padding' => true])

{{--
    La caja blanca sobre el fondo.

    Estaba escrita a mano 52 veces, cada una con su variación mínima de
    sombra, radio y espaciado. No es que faltara diseño: es que no había
    sistema, y cada vista reinventó el suyo.

    El borde a 80% de opacidad en vez de opaco es lo que separa una tarjeta
    del fondo sin dibujarle una raya: con shadow-sm sola las cajas se
    desdibujan sobre un fondo tan claro como #F8FAFC.

    Sin padding para las que envuelven una tabla a sangre: con él, la cabecera
    gris de la tabla queda flotando dentro de un marco blanco.
--}}

<div {{ $attributes->merge([
    'class' => 'bg-white border border-gray-200/80 rounded-xl shadow-sm' . ($padding ? ' p-6' : ''),
]) }}>
    {{ $slot }}
</div>
```

- [ ] **Paso 4: Verlos pasar**

Run: `php artisan test --filter=TarjetaTest`
Expected: PASS, 4 tests.

- [ ] **Paso 5: Commit**

```bash
git add resources/views/components/tarjeta.blade.php tests/Feature/TarjetaTest.php
git commit -m "feat(componentes): la tarjeta deja de escribirse a mano"
```

---

### Task 7: Adoptar `<x-tarjeta>` — vistas operativas

**Files:**
- Modify: los ficheros de `resources/views/operativo/` y `resources/views/components/` con tarjetas escritas a mano

**Interfaces:**
- Consumes: `<x-tarjeta>` de la Tarea 6.

- [ ] **Paso 1: Listar los sitios**

```bash
grep -rn 'bg-white[^"]*shadow[^"]*rounded\|bg-white[^"]*rounded[^"]*shadow' resources/views/operativo/ resources/views/components/ --include=*.blade.php
```

- [ ] **Paso 2: Sustituir cada uno**

Antes:

```blade
<div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
    ... contenido ...
</div>
```

Después:

```blade
<x-tarjeta class="mb-6">
    ... contenido ...
</x-tarjeta>
```

Las clases que **no** son de caja —`mb-6`, `overflow-hidden`, anclas, `x-data`— se conservan como atributos sueltos, que el componente fusiona.

Cuando la caja envuelve una tabla a sangre (`<table class="min-w-full ...">` como primer hijo), va sin espaciado:

```blade
<x-tarjeta :padding="false" class="overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
```

**No tocar** `resources/views/components/resumen-lista.blade.php`: su caja lleva `flex flex-wrap items-center justify-between gap-4`, que son de maquetación interna. Convertirla exige mover ese `flex` a un hijo, y eso es cambiar la estructura de un componente que se acaba de revisar. Se anota para la fase que le toque.

- [ ] **Paso 3: Correr la suite entera**

Run: `php artisan test`
Expected: **566 passed** (562 + los 4 de `TarjetaTest`, añadidos en la Tarea 6), ningún test existente modificado. Varios tests afirman sobre contenido dentro de estas cajas; si alguno se pone rojo, la sustitución se tragó contenido.

- [ ] **Paso 4: Commit**

```bash
git add resources/views/operativo/ resources/views/components/
git commit -m "refactor(operativo): las cajas blancas pasan a x-tarjeta"
```

---

### Task 8: Adoptar `<x-tarjeta>` — admin y perfil

**Files:**
- Modify: los ficheros de `resources/views/admin/` y `resources/views/profile/` con tarjetas escritas a mano

**Interfaces:**
- Consumes: `<x-tarjeta>` de la Tarea 6.

- [ ] **Paso 1: Listar los sitios**

```bash
grep -rn 'bg-white[^"]*shadow[^"]*rounded\|bg-white[^"]*rounded[^"]*shadow' resources/views/admin/ resources/views/profile/ --include=*.blade.php
```

- [ ] **Paso 2: Sustituir cada uno**

Antes:

```blade
<div class="bg-white shadow-sm sm:rounded-lg p-6">
    ... contenido ...
</div>
```

Después:

```blade
<x-tarjeta>
    ... contenido ...
</x-tarjeta>
```

Con tabla a sangre como primer hijo:

```blade
<x-tarjeta :padding="false" class="overflow-hidden">
    <table class="min-w-full divide-y divide-gray-200">
```

Las vistas de `profile/` son de Breeze y nadie las ha mirado en meses. **Se convierten igual**, y se abren una vez en el navegador antes de dar la tarea por terminada.

- [ ] **Paso 3: Correr la suite entera**

Run: `php artisan test`
Expected: **566 passed**, ningún test existente modificado.

- [ ] **Paso 4: Commit**

```bash
git add resources/views/admin/ resources/views/profile/
git commit -m "refactor(admin): las cajas blancas pasan a x-tarjeta"
```

---

### Task 9: `<x-boton>`

**Files:**
- Create: `resources/views/components/boton.blade.php`
- Create: `tests/Feature/BotonTest.php`

**Interfaces:**
- Produces: `<x-boton>` con props `variante` (`primario` | `secundario` | `peligro`), `tamano` (`normal` | `grande`) y `href` (`null` por defecto). La Tarea 10 lo consume.

**Contexto:** hoy hay **12 variantes del mismo botón primario** —`py-2 px-5`, `py-2 px-4`, `py-3 px-6`; `rounded`, `rounded-lg`, `rounded-md`; en cuatro colores— para lo que conceptualmente es «la acción principal de esta pantalla».

- [ ] **Paso 1: Escribir los tests que fallan**

`tests/Feature/BotonTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-boton> sustituye 12 variantes escritas a mano del mismo botón primario.
 *
 * Con href da un <a> y sin él un <button>, porque hoy la mitad de los
 * «botones» del sistema son enlaces: esa diferencia es de comportamiento, no
 * de estilo, y el estilo no tiene por qué enterarse.
 */
class BotonTest extends TestCase
{
    public function test_sin_href_es_un_boton(): void
    {
        $html = (string) $this->blade('<x-boton>Guardar</x-boton>');

        $this->assertStringContainsString('<button', $html);
        $this->assertStringNotContainsString('<a ', $html);
        $this->assertStringContainsString('Guardar', $html);
    }

    public function test_con_href_es_un_enlace_con_el_mismo_aspecto(): void
    {
        $html = (string) $this->blade('<x-boton href="/zonas">Ver zonas</x-boton>');

        $this->assertStringContainsString('<a ', $html);
        $this->assertStringContainsString('href="/zonas"', $html);
        $this->assertStringNotContainsString('<button', $html);
    }

    public function test_las_tres_variantes_no_se_parecen(): void
    {
        $clases = [];

        foreach (['primario', 'secundario', 'peligro'] as $variante) {
            $clases[$variante] = (string) $this->blade(
                '<x-boton :variante="$v">Acción</x-boton>',
                ['v' => $variante]
            );
        }

        $this->assertNotSame($clases['primario'], $clases['secundario']);
        $this->assertNotSame($clases['primario'], $clases['peligro']);
        $this->assertNotSame($clases['secundario'], $clases['peligro']);
    }

    public function test_el_tamano_grande_es_mas_grande(): void
    {
        $normal = (string) $this->blade('<x-boton>Acción</x-boton>');
        $grande = (string) $this->blade('<x-boton tamano="grande">Acción</x-boton>');

        $this->assertNotSame($normal, $grande);
        $this->assertStringContainsString('px-6', $grande);
    }

    public function test_una_variante_desconocida_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('variante «morado» desconocida');

        $this->blade('<x-boton variante="morado">Acción</x-boton>');
    }

    public function test_un_tamano_desconocido_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('tamaño «enorme» desconocido');

        $this->blade('<x-boton tamano="enorme">Acción</x-boton>');
    }
}
```

- [ ] **Paso 2: Verlos fallar**

Run: `php artisan test --filter=BotonTest`
Expected: FAIL — `Unable to locate a class or view for component [boton]`.

- [ ] **Paso 3: Escribir el componente**

`resources/views/components/boton.blade.php`:

```blade
@props(['variante' => 'primario', 'tamano' => 'normal', 'href' => null])

{{--
    La acción, en tres variantes y dos tamaños.

    Había 12 escrituras distintas del mismo botón primario -py-2 px-5, py-2
    px-4, py-3 px-6; rounded, rounded-lg, rounded-md; en cuatro colores- para
    lo que conceptualmente es «la acción principal de esta pantalla».

    Con href da un <a> y sin él un <button>: hoy la mitad de los botones del
    sistema son enlaces, y esa diferencia es de comportamiento, no de estilo.

    Clases completas en los arrays, nunca compuestas con el nombre de la
    variante: Tailwind purga las que no aparezcan literales en el fuente.
--}}

@php
    $variantes = [
        'primario'   => 'bg-indigo-600 hover:bg-indigo-700 text-white border-transparent shadow-sm',
        'secundario' => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300 shadow-sm',
        'peligro'    => 'bg-red-600 hover:bg-red-700 text-white border-transparent shadow-sm',
    ];

    $tamanos = [
        'normal' => 'px-4 py-2 text-sm',
        'grande' => 'px-6 py-3 text-base',
    ];

    if (! isset($variantes[$variante])) {
        throw new \InvalidArgumentException(
            "<x-boton>: variante «{$variante}» desconocida; las válidas son "
            . implode(', ', array_keys($variantes)) . '.'
        );
    }

    if (! isset($tamanos[$tamano])) {
        throw new \InvalidArgumentException(
            "<x-boton>: tamaño «{$tamano}» desconocido; los válidos son "
            . implode(', ', array_keys($tamanos)) . '.'
        );
    }

    $clases = 'inline-flex items-center justify-center gap-2 border rounded-lg font-semibold '
        . 'transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 '
        . 'disabled:opacity-50 disabled:cursor-not-allowed '
        . $variantes[$variante] . ' ' . $tamanos[$tamano];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $clases]) }}>{{ $slot }}</button>
@endif
```

- [ ] **Paso 4: Verlos pasar**

Run: `php artisan test --filter=BotonTest`
Expected: PASS, 6 tests.

- [ ] **Paso 5: Commit**

```bash
git add resources/views/components/boton.blade.php tests/Feature/BotonTest.php
git commit -m "feat(componentes): un solo boton con tres variantes"
```

---

### Task 10: Adoptar `<x-boton>`

**Files:**
- Modify: las vistas de `resources/views/` con botones y enlaces-botón escritos a mano

**Interfaces:**
- Consumes: `<x-boton>` de la Tarea 9.

**Contexto y límite.** Esta tarea **sí cambia colores**, y hay que leerlo así de claro porque es lo que se va a notar al abrir la aplicación: hoy la acción principal se escribe en verde en una vista, en azul en otra y en índigo en una tercera, sin que la diferencia signifique nada. Todas pasan a `primario` —índigo—; las de borrar, a `peligro`; las de «Volver» y demás acciones de apoyo, a `secundario`.

Lo que **no** cambia es qué acción es principal en cada pantalla. Si al convertir un botón hace falta decidir si es principal o de apoyo y la vista no lo deja claro, se conserva el peso que tenía —el que era grande y sólido queda `primario`, el que era pequeño o de contorno queda `secundario`— y se anota en el informe. Reordenar la jerarquía de acciones de una pantalla es rediseñarla, y eso es de otra fase.

**Excepción, y va antes que la regla:** el botón de validar de `<x-resumen-lista>` es **verde a propósito** —lo eligió el diseño de esa franja, es la única acción que cierra una lista, y se revisó hace dos commits—. Se deja tal cual y se anota. Lo mismo el «Guardar Borrador» de `<x-barra-lateral-formulario>`.

- [ ] **Paso 1: Listar los sitios**

```bash
grep -rnE 'bg-(blue|green|indigo|red|gray)-600[^"]*rounded' resources/views/ --include=*.blade.php
```

- [ ] **Paso 2: Sustituir cada uno**

Enlace que hace de botón. Antes:

```blade
<a href="{{ route('operativo.zona.panel', $zona->id) }}"
   class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
    ← Volver a la zona
</a>
```

Después:

```blade
<x-boton :href="route('operativo.zona.panel', $zona->id)" variante="secundario" class="mb-4">
    ← Volver a la zona
</x-boton>
```

Botón de envío. Antes:

```blade
<button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg">
    Guardar
</button>
```

Después:

```blade
<x-boton tamano="grande">Guardar</x-boton>
```

Borrado. Antes:

```blade
<button class="text-red-600 font-bold text-sm bg-red-50 px-2 py-1 rounded">Eliminar</button>
```

Después: **se deja como está.** Es un botón de tabla, pequeño y de bajo peso visual a propósito; convertirlo a `peligro` lo pinta rojo sólido y lo hace competir con el contenido de la fila. Se anota para la fase de las tablas.

Los atributos que no son de estilo —`type`, `name`, `value`, `@click`, `x-show`, `form`, `onsubmit`— se conservan tal cual; el componente los fusiona.

- [ ] **Paso 3: Correr la suite entera**

Run: `php artisan test`
Expected: **572 passed** (566 + los 6 de `BotonTest`, añadidos en la Tarea 9), ningún test existente modificado. Hay tests que cuentan botones deshabilitados y que afirman sobre `type="submit"` y sobre `name="accion_estado"`; si alguno se pone rojo, la sustitución perdió un atributo.

- [ ] **Paso 4: Construir y verificar el purgado**

```bash
npm run build
```

Comprobar que las clases de las tres variantes y de los dos tamaños sobrevivieron:

```bash
for c in bg-indigo-600 bg-red-600 border-gray-300 px-4 px-6 rounded-xl bg-amber-100 bg-green-100; do
  n=$(grep -o "\.$c[{:]" public/build/assets/*.css | wc -l)
  echo "$c: $n"
done
```

Expected: todas mayores que 0. Una en 0 significa que la clase solo existe dentro de un array de PHP que Tailwind no está escaneando, y la aplicación se ve sin ese estilo **sin que ningún test falle**.

- [ ] **Paso 5: Commit**

```bash
git add resources/views/ public/build
git commit -m "refactor(vistas): los botones pasan a x-boton"
```

---

### Task 11: Revisión final de la rama

- [ ] **Paso 1: Suite entera y build**

```bash
php artisan test
npm run build
```

Expected: **572 passed**. **Ningún test existente modificado en toda la rama** — comprobarlo:

```bash
git diff --stat $(git merge-base main HEAD)..HEAD -- tests/
```

Los únicos ficheros de `tests/` que deben aparecer son los cuatro creados: `ContenedorTest.php`, `BadgeTest.php`, `TarjetaTest.php`, `BotonTest.php`.

- [ ] **Paso 2: Recorrido en el navegador**

El servidor de desarrollo ya está levantado. Con sesión iniciada, abrir y mirar a 1920 px:

- `/mis-zonas`, `/operativo/zona/1`, `/operativo/zona/1/involucrados`, `/operativo/zona/1/frecuentacion`, `/operativo/zona/1/fit`
- `/admin/dashboard`, `/admin/zonas`, `/admin/users`
- `/profile` — **de Breeze, nadie la ha mirado en meses**, y entra en el cambio de gris igual que las demás.

Comprobar que no hay scroll horizontal, que la cabecera alinea con el cuerpo, y que ninguna caja quedó sin estilo.

- [ ] **Paso 3: Revisión de la rama**

Generar el paquete de revisión desde la base de la rama y despacharla en el modelo más capaz disponible.

- [ ] **Paso 4: Terminar la rama**

Usar `superpowers:finishing-a-development-branch`.

---

## Autorrevisión del plan

**Cobertura del diseño.** Contenedor → Tareas 1-3. Paleta y tipografía → Tarea 4. Mapa de estados en un solo sitio y badge → Tarea 5. Tarjeta → Tareas 6-8. Botón → Tareas 9-10. Comprobación de purgado → Tareas 4 y 10. Recorrido de las vistas de Breeze → Tareas 8 y 11.

**Un hueco reconocido y dejado a propósito.** El diseño dice «52 tarjetas» y «12 botones»; este plan no los enumera uno a uno, sino que da el comando que los encuentra, la transformación exacta y las excepciones nombradas. Enumerarlos habría fijado en el plan un recuento que cambia con cada commit.

**Tres excepciones nombradas para que nadie las descubra tarde:** la caja de `<x-resumen-lista>` (lleva maquetación flex propia), su botón verde de validar y el de `<x-barra-lateral-formulario>` (color deliberado), y los botones pequeños de borrar en tablas (peso visual bajo a propósito).

**Números.** La suite va de **553 a 572**, y cada tarea dice el suyo para que un implementador que solo lea la suya sepa qué esperar:

| Tras la tarea | Tests | Qué añadió |
|---|---|---|
| 1 | 558 | +5 `ContenedorTest` |
| 2, 3, 4 | 558 | nada: solo sustituyen |
| 5 | 562 | +4 `BadgeTest` |
| 6 | 566 | +4 `TarjetaTest` |
| 7, 8 | 566 | nada |
| 9 | 572 | +6 `BotonTest` |
| 10, 11 | 572 | nada |

En el primer borrador de este plan las tareas 5 a 11 decían todas 557, que salía de sumar mal. Se corrigió en el fichero y no con una nota al pie: un plan que dice 557 en siete sitios y 572 en la autorrevisión es peor que uno que se equivoque de forma coherente, porque el implementador lee su tarea y no la autorrevisión.

**El número no es decorativo.** Es el que delata que alguien «arregló» un test existente en vez de reportarlo: si la cifra cuadra pero `git diff` toca un fichero de test viejo, la restricción global se saltó.
