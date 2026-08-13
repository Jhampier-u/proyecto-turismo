# Task 3: Quitar los contenedores de página — admin, perfil y componentes — Informe

## Estado encontrado al empezar

El `task-3-report.md` que existía en este mismo directorio **no correspondía
a esta tarea**: era el informe de una tarea anterior y ya cerrada ("Task 3:
Frecuentación", sobre la franja de resumen en `frecuentacion/index.blade.php`),
que reutilizó el mismo nombre de fichero antes de que existiera este plan de
fundación visual — el mismo fenómeno que ya se documentó en `task-2-report.md`
para su propio informe. Lo sustituyo por este, que sí es el de `task-3-brief.md`.

Las 8 vistas que tocaba esta tarea estaban intactas en el árbol de trabajo
(sin editar) al arrancar la sesión; no había nada a auditar de una sesión
previa, a diferencia de la Tarea 2.

## Verificación fichero a fichero

`grep -rn 'max-w-[a-z0-9]* mx-auto' resources/views/admin/ resources/views/profile/ resources/views/components/`
señaló 9 coincidencias en 8 ficheros de `admin/` y `profile/`, más una en
`components/`. También revisé los tres directorios completos en busca de
`max-width` en línea y bloques `<style>` propios (el caso que se dio en
`evaluacion_potencialidad/form.blade.php` durante la Tarea 2): no apareció
ninguno.

- **`admin/dashboard.blade.php`** — `max-w-7xl mx-auto sm:px-6 lg:px-8`,
  contenedor de página único, primero dentro de `<x-app-layout>`. Quitado; el
  `py-10` (nótese que este panel usa 10, no 12) queda intacto.
- **`admin/lugares/index.blade.php`** — mismo patrón con `max-w-7xl`. Quitado.
- **`admin/users/index.blade.php`** — mismo patrón con `max-w-7xl` (llevaba
  además un `<script>` al final de la vista, fuera del contenedor; no se tocó).
  Quitado.
- **`admin/zonas/index.blade.php`** — mismo patrón con `max-w-7xl`, pero el
  `py-12` que sobrevive lleva `x-data`/`x-init` de Alpine (el conmutador
  lista/tarjetas): esos atributos ya vivían en el `div` exterior, no en el que
  se borra, así que no hubo nada que mover. Quitado.
- **`admin/zonas/form.blade.php`** y **`admin/users/form.blade.php`** —
  ambos `max-w-7xl`. El brief nombra estos dos explícitamente como los casos
  donde **no** aplica la excepción de ancho estrecho, aunque sean formularios
  de alta: pasan a ancho normal como el resto, sin estrechar. Quitados sin
  sustituir por `<x-contenedor>`.
- **`admin/lugares/form.blade.php`** — el único caso real de la excepción:
  contenedor de página en `max-w-2xl` (≤ `max-w-2xl`). Sustituido por
  `<x-contenedor ancho="estrecho">…</x-contenedor>`, tal cual pide el brief.
- **`profile/edit.blade.php`** — caso distinto a los demás: el `div` de ancho
  llevaba clases extra, `max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6`.
  `space-y-6` no fija el ancho de página, separa verticalmente las tres
  tarjetas (datos de perfil / contraseña / borrar cuenta) que son hijas
  directas de ese `div`. Borrar el `div` entero sin más habría hecho
  desaparecer esa separación — un cambio de estructura que el brief no pide
  ("todo lo demás se queda"). Trasladé `space-y-6` al `div class="py-12"` que
  sobrevive (mismo efecto: sigue aplicando margen superior a las tres
  tarjetas salvo la primera, ahora como hijas directas de ese `div`), y dejé
  un comentario en castellano explicando el porqué.

Comprobé balance de `<div>`/`</div>` en los 8 ficheros tocados (coinciden
exactamente) y busqué `slate-` en `admin/`, `profile/` y `components/`: no
aparece ninguna. No añadí ni quité texto, tamaños de fuente ni `uppercase`.

## Cajas interiores que se quedaron (1, en 1 fichero)

`resources/views/components/matriz-sin-resultados.blade.php:15` —
`max-w-3xl mx-auto sm:px-6 lg:px-8`. Es el aviso amarillo "… sin resultados"
compartido por las vistas de resultados cuando la matriz no está completa: se
renderiza **dentro** de la página de otra vista (vía `<x-matriz-sin-resultados>`),
no es una página en sí misma — un componente no tiene `<x-app-layout>` propio
del que ser "el primer div". Es exactamente el caso que el propio brief nombra
como excepción explícita, verificado sin ambigüedad.

No encontré ningún otro `max-w-* mx-auto` en `admin/`, `profile/` ni
`components/` tras el cambio — la única coincidencia que queda en esos tres
directorios es esta.

(Fuera de mi alcance: quedan varias cajas interiores en `resources/views/operativo/`,
ya inventariadas en `task-2-report.md`; no las repito aquí.)

## Suite completa

```
php artisan test
Tests:    558 passed (3268 assertions)
Duration: 32.62s
```

558 = los mismos 558 de después de la Tarea 2. Ningún test existente se puso
rojo; no modifiqué ningún fichero de `tests/`.

## Commit

```
04e8a1c refactor(admin): las vistas dejan de fijar su propio ancho
```

8 ficheros, 6 inserciones, 18 borrados.

## Ficheros

**Modificados y commiteados** (commit `04e8a1c`):
- `resources/views/admin/dashboard.blade.php`
- `resources/views/admin/lugares/form.blade.php`
- `resources/views/admin/lugares/index.blade.php`
- `resources/views/admin/users/form.blade.php`
- `resources/views/admin/users/index.blade.php`
- `resources/views/admin/zonas/form.blade.php`
- `resources/views/admin/zonas/index.blade.php`
- `resources/views/profile/edit.blade.php`

**No tocado:** `package-lock.json` — ya estaba modificado antes de empezar
esta sesión, no es mío y no se incluyó en el commit. Ningún fichero de
`resources/views/profile/partials/` ni de `resources/views/components/` (salvo
la revisión de `matriz-sin-resultados.blade.php`, que se dejó igual) se tocó.

## Dudas

Ninguna. El único caso con matiz propio —`profile/edit.blade.php` y su
`space-y-6`— no está cubierto literalmente por el brief ni por el patrón de la
Tarea 2, pero la resolución (mover la clase al `div` que sobrevive) sigue la
misma regla general: solo desaparece el contenedor de ancho, todo lo demás se
queda. Queda documentado en el propio fichero por si el criterio no fuera el
esperado.
