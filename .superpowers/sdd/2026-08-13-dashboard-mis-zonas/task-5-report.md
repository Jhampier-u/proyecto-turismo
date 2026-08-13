# Tarea 5 — la vista lista pasa a ser una tabla ordenable

## Qué implementé

1. **`resources/views/components/cabecera-ordenable.blade.php`** (nuevo): componente
   que pinta un `<th scope="col">` con un enlace `<a>` a `?orden=…&dir=…`. Calcula
   si la columna es la activa (`$orden === $columna`), el siguiente `dir` a
   ofrecer (invierte si es la activa, `asc` si no) y el `aria-sort` (solo en la
   activa: `ascending`/`descending`, `null` en las demás, que el `@if` omite del
   todo). El texto del enlace es el slot, seguido de una flecha decorativa
   (`aria-hidden="true"`) que apunta hacia abajo solo si la activa está en `desc`.
   Implementado literal según el brief.

2. **`resources/views/operativo/dashboard.blade.php`**: sustituido el bloque
   entero de la vista lista (identificado por el comentario `═══ VISTA LISTA ═══`
   hasta su `</x-tarjeta>` de cierre, que ya no coincidía con los números de
   línea del brief) por una tabla real: `<table>` con `<thead>`/`<tbody>`, cinco
   columnas (`Zona`, `Lugar`, `Estado`, `Progreso`, `Acciones`). Zona, Lugar y
   Progreso usan `<x-cabecera-ordenable>`; Estado y Acciones son `<th scope="col">`
   simples, no ordenables. Cada fila usa `<x-desglose-estados>` en la celda
   Estado (reusado tal cual de T4) y ya no lleva el `📍` delante del lugar —ahora
   es una celda con su propia cabecera—. El contenedor `<x-tarjeta id="zonas-lista">`
   se mantiene, con `class="overflow-x-auto"` en vez de `divide-y` (ya no aplica
   a una tabla) para que en móvil la tabla se desplace en lugar de romperse.

3. **`tests/Feature/OrdenMisZonasTest.php`**: añadidos los cinco tests literales
   del brief (`test_la_vista_lista_es_una_tabla_con_cabeceras`,
   `test_solo_tres_cabeceras_ordenan`, `test_solo_la_columna_activa_anuncia_su_orden`,
   `test_la_columna_activa_ofrece_invertir_y_las_demas_ascendente`,
   `test_la_tabla_lleva_el_desglose_por_estado`).

4. **`tests/Feature/ConmutadorVistaTest.php`**: sustituido el `assertSame` que
   contaba `'📍 ' . $this->zona->lugar->nombre` (línea real 109, el brief la
   nombraba como 101 pero el número había cambiado) por el `assertSame(2,
   substr_count($html, $this->zona->lugar->nombre), ...)` literal del brief,
   comparando sobre el nombre del lugar y no sobre el emoji.

## Qué probé y con qué resultado

### Evidencia TDD — ROJO

```
php artisan test --filter=OrdenMisZonasTest
```

Resultado: 4 failed, 9 passed (58 assertions). Los cuatro que fallaron son
exactamente los cuatro nuevos que dependen de marcado que aún no existía:

- `la vista lista es una tabla con cabeceras` — sin `<thead>` ni `<th scope="col">`.
- `solo tres cabeceras ordenan` — sin ningún `orden=` en el HTML.
- `solo la columna activa anuncia su orden` — `aria-sort=` aparecía 0 veces, se
  esperaba 1.
- `la columna activa ofrece invertir y las demas ascendente` — no había ningún
  `orden=nombre&amp;dir=desc` en el HTML.

El quinto nuevo, `la tabla lleva el desglose por estado`, **pasó ya** en rojo:
la Tarea 4 puso `<x-desglose-estados>` en la fila de la vista lista (el "10 sin
empezar" ya estaba en `$lista`), así que este test no tenía nada que fallar.
Sigue haciendo falta en la suite para que, al reescribir el bloque entero en
esta tarea, la tabla no pierda el desglose por el camino.

### Evidencia TDD — VERDE

```
php artisan test --filter="OrdenMisZonasTest|ConmutadorVistaTest"
```

Resultado: 19 passed (83 assertions).

### Otros tests que tocan `/mis-zonas` o el marcado del dashboard (barrido del brief)

```
php artisan test --filter="AlmacenamientoImagenesTest|ContenedorTest|RegistroMatricesTest|DashboardTest|PaisajeTest|ValoracionTerritorialTest"
```

Resultado: 95 passed (669 assertions). Ninguno se puso rojo — no hicieron falta
cambios en esos ficheros.

### Suite entera

```
npm run build
php artisan test
```

Resultado: **631 passed (3967 assertions)**, ~63.6 s. Coincide con la base real
de 626 (no los 627 que arrastraba el brief) más los 5 tests nuevos.
`package-lock.json` no se modificó (no aparece en `git status`).

## Ficheros cambiados

- `resources/views/components/cabecera-ordenable.blade.php` (nuevo)
- `resources/views/operativo/dashboard.blade.php` (bloque de la vista lista)
- `tests/Feature/OrdenMisZonasTest.php` (cinco tests nuevos)
- `tests/Feature/ConmutadorVistaTest.php` (un assert actualizado)

Ningún fichero fuera de la lista del brief tuvo que tocarse.

## Auto-revisión

- El `<thead>`/`<tbody>` lleva exactamente 5 `<th scope="col">` — Zona, Lugar,
  Estado, Progreso, Acciones — y solo 3 enlaces `orden=` (Zona, Lugar, Progreso).
  Verificado por los propios tests y por lectura del diff.
- `aria-sort` vive solo en `cabecera-ordenable.blade.php`, nunca repartido en la
  vista, tal como pide el brief.
- La regla "activa invierte, las demás arrancan en asc" no tiene excepción por
  columna — ni siquiera para "progreso" — confirmado por
  `test_la_columna_activa_ofrece_invertir_y_las_demas_ascendente`.
- El `id="zonas-lista"` se conserva en el `<x-tarjeta>`, tal como exigían los
  tests de `OrdenMisZonasTest` que recortan por él.
- `<x-desglose-estados :progreso="$p" />` se reutiliza tal cual, sin `class="mt-2"`
  (ese modificador era para el contexto de la lista antigua, apilada verticalmente
  bajo la barra de progreso; en la celda de tabla no hace falta).
- No quedó ningún `<button>` en la tabla — las cabeceras ordenables son enlaces,
  como pide el brief y confirma `test_solo_tres_cabeceras_ordenan`.
- No se tocó nada de `admin/` ni sus tests.
- `git add` del commit no incluye `public/build` (está en `.gitignore`).
- YAGNI: no añadí ordenación a Estado ni a Acciones (el brief es explícito en que
  solo tres columnas ordenan), ni inventé parámetros adicionales al componente
  más allá de los que el brief especifica (`columna`, `orden`, `dir`, `alineacion`).

No encontré nada que corregir tras la auto-revisión.

## Dudas o preocupaciones

Ninguna. La implementación siguió el brief de forma literal, la evidencia roja
salió exactamente como el brief predijo (4 fallan, 1 pasa ya), y la suite
entera cerró en 631 verdes, que es la cifra corregida que se me indicó (626 +
5), no los 632 que arrastraba el brief original.
