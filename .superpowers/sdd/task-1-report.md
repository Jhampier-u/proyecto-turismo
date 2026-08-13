# Task 1: `<x-contenedor>` y el layout — Informe de Implementación

## Qué implementé

Creé el componente `<x-contenedor>` que centraliza el ancho de la aplicación en un
solo sitio (antes vivía repartido en ~39 vistas con 9 valores distintos, y el
`<main>` del layout no tenía ninguno). Lo monté en `layouts/app.blade.php`
(cabecera y `<main>`) y en `layouts/navigation.blade.php` (barra de Breeze).

- Prop `ancho`: `'normal'` (por defecto, `max-w-[1440px]`) o `'estrecho'`
  (`max-w-2xl`)
- Fluido con tope: `w-full ... mx-auto` — en pantallas menores que el tope usa
  todo el ancho disponible
- Un ancho desconocido revienta con `InvalidArgumentException` (Blade la
  envuelve en `ViewException`), en vez de caer silenciosamente al ancho normal
- Acepta atributos sueltos (`$attributes->merge`) y los fusiona en la clase,
  sin perder las propias
- Clases de Tailwind literales en un array (`$anchos`), no concatenadas, para
  que Tailwind las purgue correctamente

Estado intermedio esperado (no es un fallo, lo señala el propio brief): el
contenedor del layout ahora convive anidado con los que cada vista sigue
poniendo por su cuenta. Gana el más estrecho, así que el ancho visible en
pantalla no cambia todavía — las tareas 2 y 3 quitan los de las vistas.

---

## Evidencia TDD

### Fase ROJA (antes de la implementación)

```
$ php artisan test --filter=ContenedorTest

FAILED  Tests\Feature\ContenedorTest > el ancho normal llega a 1440
FAILED  Tests\Feature\ContenedorTest > es fluido hasta el tope
FAILED  Tests\Feature\ContenedorTest > el estrecho se pide a proposito
FAILED  Tests\Feature\ContenedorTest > un ancho desconocido revienta
FAILED  Tests\Feature\ContenedorTest > admite clases extra sin perder las suyas

InvalidArgumentException: Unable to locate a class or view for component [contenedor].

Tests: 5 failed (1 assertions)
```

### Fase VERDE (después de crear el componente)

```
$ php artisan test --filter=ContenedorTest

PASS  Tests\Feature\ContenedorTest
✓ el ancho normal llega a 1440              0.51s
✓ es fluido hasta el tope                   0.15s
✓ el estrecho se pide a proposito           0.17s
✓ un ancho desconocido revienta             0.17s
✓ admite clases extra sin perder las suyas  0.19s

Tests: 5 passed (10 assertions)
Duration: 1.57s
```

---

## Localización del `</div>` en `navigation.blade.php`

Conté la anidación de `<div>` desde la línea 3 (excluyendo tags de componentes
Blade como `<x-dropdown>`/`<x-slot>`, que no son `<div>` literales en el
fuente): el `<div class="max-w-7xl mx-auto ...">` de la línea 3 abre en
profundidad 1 y no vuelve a esa profundidad hasta la línea 85 (`</div>` justo
antes del comentario `<!-- Responsive Navigation Menu -->`). Ese es el cierre
que sustituí por `</x-contenedor>`; el resto de `<div>` internos (menú,
dropdown, hamburguesa) quedaron intactos.

---

## Salida de la Suite Completa

```
$ php artisan test

Tests:    558 passed (3274 assertions)
Duration: 70.73s
```

Verificación: 553 previos + 5 nuevos = 558. Ningún test existente se modificó
ni se puso en rojo.

---

## Ficheros Modificados

**Creados:**
- `resources/views/components/contenedor.blade.php`
- `tests/Feature/ContenedorTest.php`

**Modificados:**
- `resources/views/layouts/app.blade.php` (cabecera y `<main>` ahora usan `<x-contenedor>`)
- `resources/views/layouts/navigation.blade.php` (línea 3 y su cierre en línea 85 ahora usan `<x-contenedor>`)

**No tocado:** `package-lock.json` — aparecía modificado en el árbol desde antes de empezar, no es mío, no se incluyó en el commit.

---

## Commit

```
9d7b934 feat(layout): el ancho de la aplicacion deja de vivir en cada vista
```

4 files changed, 110 insertions(+), 6 deletions(-)

---

## Dudas / cosas que no encajaron

Ninguna. El brief traía el código literal y encajó sin fricción. La única
parte que requería juicio propio —contar la anidación de `<div>` en
`navigation.blade.php` para encontrar el cierre correcto— se resolvió
extrayendo todas las líneas con `<div`/`</div>` y contando profundidad a
mano; confirmé el resultado antes de editar.

---

## Autorrevisión

- Los 5 tests de `ContenedorTest` pasan, incluido el caso de ancho desconocido (`ViewException`)
- Ninguna clase Tailwind construida por concatenación (`$anchos` es un array literal)
- Sin `slate-*` en ningún sitio nuevo
- Nada por debajo de 14px, sin `uppercase`
- Comentarios en castellano explicando el porqué (ver el bloque `{{-- --}}` del componente y el del `<main>`)
- Suite completa en 558 (553 + 5) ✓, sin tocar ningún test existente
- `package-lock.json` excluido del commit
