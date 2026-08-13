# Task 6 — Detalles menores

Nota: este fichero reemplaza un `task-6-report.md` de un ciclo SDD anterior
(«Tarjeta reducida en Mis Zonas», ajeno a la rama `permisos-y-navegacion` y a
este brief). El contenido de abajo corresponde al brief actual:
`.superpowers/sdd/task-6-brief.md` — aviso de reapertura + tipografía de los
tres formularios de admin.

## Qué nombres de variable usaba cada uno de los nueve formularios

| Formulario | Confirmado | Es jefe | Bloqueo |
|---|---|---|---|
| evaluacion_concentracion | `$estaConfirmado` | `$esJefe` | `$bloqueado` |
| evaluacion_fet | `$estaConfirmado` | `$esJefe` | `$bloqueado` |
| evaluacion_fit | `$estaConfirmado` | `$esJefe` | `$bloqueado` |
| evaluacion_irritacion | `$estaConfirmado` | `$esJefe` | `$bloqueado` |
| evaluacion_paisaje | `$estaConfirmado` | `$esJefe` | `$bloqueado` |
| evaluacion_percepcion | `$estaConfirmado` | `$esJefe` | `$bloqueado` |
| evaluacion_valoracion_territorial | `$estaConfirmado` | `$esJefe` | `$bloqueado` |
| evaluacion_potencialidad | `$isConfirmado` | `$user->esJefe()` | `$soloLectura` |
| involucrados | *(ninguna — ver más abajo)* | — | — |

Siete formularios comparten el patrón exacto del brief
(`$estaConfirmado && $esJefe`). Potencialidad usa nombres propios
(`$isConfirmado`, `$user->esJefe()`, `$soloLectura = $isConfirmado &&
!$user->esJefe()`), pero la misma lógica: el jefe nunca está bloqueado, así
que el bloque `@if(!$soloLectura)` es justo donde el equipo/admin desaparecen
y el jefe siempre entra.

**Involucrados es el caso distinto.** Su `form.blade.php` (crear/editar UN
actor) no declara ni `$estaConfirmado` ni `$esJefe` en ningún sitio — el grep
del Step 3 solo encuentra esas palabras dentro de un comentario que *habla*
de las otras siete matrices, no una variable real:

```
$ grep -n "estaConfirmado\|esJefe\|validad" resources/views/operativo/involucrados/form.blade.php
31:                    `$estaConfirmado && ! $esJefe`-. El cierre de la lista
32:                    validada se guarda en
```

Investigando por qué, encontré que Involucrados **ya avisa de esto desde
antes** — es justo lo que dice el contexto de la tarea ("Solo Involucrados
avisa de ello"). El aviso vive en `involucrados/index.blade.php` (el listado,
de donde salen "+ Nuevo actor", "Editar" y "Eliminar"), no en `form.blade.php`:

```blade
@if($confirmada)
  ...
  <p class="text-sm mt-1">
      Si la modificas —añades, editas o borras un actor—, vuelve a borrador: hay que validarla de nuevo.
  </p>
```

Y el propio `InvolucradosController` documenta la decisión de diseño (ver
docblock de `reabrirSiConfirmada()`): el aviso tiene que estar **antes** de
que el jefe pulse cualquiera de los tres disparadores, no solo en el flash
que sale después de guardar — por eso vive en el listado y no en el
formulario del actor. Esto ya está cubierto por
`InvolucradosTest::test_el_banner_de_lista_validada_avisa_que_modificarla_la_reabre`
y tres tests más de reapertura, los cuatro en verde desde antes de esta
tarea (no los toqué).

**Decisión:** no añadí ningún aviso a `involucrados/form.blade.php`. Añadir
uno ahí habría exigido inventar una vía nueva (pasar `confirmada` desde
`create()`/`edit()`, que hoy no la pasan) para duplicar una advertencia que
el usuario ya recibe, en el sitio correcto, antes de llegar al formulario.
Los ocho instrumentos restantes sí llevan el aviso nuevo.

## Evidencia TDD

**Rojo** (Step 2, antes de tocar ninguna vista):

```
$ php artisan test --filter="reabre|ese_aviso"
...
FAIL  Tests\Feature\PermisosAdminTest
⨯ el jefe ve que guardar una matriz validada la reabre
  Expected: <!DOCTYPE html>...  [no contiene 'la devolverá a borrador']

✓ una matriz en borrador no muestra ese aviso   0.07s

Tests: 1 failed, ... 
```

El primer test falla (el HTML no contiene la frase todavía); el segundo pasa
trivialmente porque la frase no existe en ningún sitio — no prueba nada por
sí solo, pero confirma que no hay falsos positivos previos.

**Verde** (tras añadir el aviso a `evaluacion_paisaje/form.blade.php`):

```
$ php artisan test --filter="reabre|ese_aviso"

PASS  Tests\Feature\InvolucradosTest (4 tests, ya existentes)
PASS  Tests\Feature\PermisosAdminTest
✓ el jefe ve que guardar una matriz validada la reabre        1.02s
✓ una matriz en borrador no muestra ese aviso                 0.07s
PASS  Tests\Feature\ReabrirMatrizTest (2 tests, ya existentes)

Tests:    8 passed (26 assertions)
Duration: 2.44s
```

Después extendí el mismo bloque a los otros siete formularios y verifiqué
con una corrida agregada (`--filter` sobre los ocho instrumentos +
Involucrados + PermisosAdmin): **281 passed**, sin roturas.

## Grep de tipografía tras el cambio (Step 4)

```
$ grep -rn "text-xs\|uppercase\|tracking-wide" resources/views/admin/users/form.blade.php \
    resources/views/admin/lugares/form.blade.php resources/views/admin/zonas/form.blade.php
$ echo $?
1
```

Sin resultados — `$?` = 1 (grep no encontró nada). El único `text-xs` que
había (`admin/zonas/form.blade.php:68`, el pie "JPG, PNG, WEBP · máx. 3 MB"
bajo el selector de imagen) subió a `text-sm`. No hay insignias en estos tres
formularios, así que no hace falta ninguna excepción.

Cambios aplicados a los tres ficheros:
- Cabecera (`<h2>` del `x-slot name="header"`): `text-xl` → `text-2xl`.
- Las seis/tres/cinco etiquetas `<label class="block text-gray-700 font-bold
  mb-2">` de cada formulario ganan `text-sm` (incluida la etiqueta "Jefe de
  Zona", que tenía su propio color `text-blue-900`).
- Todos los `<input>`/`<select>`/`<textarea>` ganan `text-base` (incluido el
  input de tipo `file` de zonas, con sus clases `file:*`).
- El enlace "Cancelar" y el botón de guardar de los tres formularios ganan
  `text-base` explícito.
- Los subtítulos de sección en `zonas/form.blade.php` ("Datos Generales",
  "Equipo de Trabajo", ya en `text-lg`) y las etiquetas de checkbox que ya
  estaban en `text-sm` ("Quitar imagen actual", cada estudiante de la lista)
  se dejaron igual: ya cumplían el mínimo de 14px y no son ninguna de las
  tres categorías que pedía el Step 4.

## Suite completa (Step 5)

```
$ php artisan test
...
Tests:    430 passed (2638 assertions)
Duration: 24.99s
```

428 (base) + 2 (los tests nuevos de `PermisosAdminTest`) = 430. Cero fallos.
PHP usado: nativo, `php -v` → `PHP 8.2.33 (cli)`, sin Docker.

`npm run build`:

```
✓ 56 modules transformed.
✓ built in 1.50s
```

Sin errores; los únicos avisos son de `baseline-browser-mapping` y
`caniuse-lite` desactualizados, ajenos a este cambio.

`package-lock.json` seguía modificado desde antes de empezar (así constaba
en el estado de git al inicio de la tarea) y no se incluyó en el commit —
`npm run build` no lo tocó más de lo que ya estaba.

## Ficheros modificados

- `resources/views/operativo/evaluacion_concentracion/form.blade.php`
- `resources/views/operativo/evaluacion_fet/form.blade.php`
- `resources/views/operativo/evaluacion_fit/form.blade.php`
- `resources/views/operativo/evaluacion_irritacion/form.blade.php`
- `resources/views/operativo/evaluacion_paisaje/form.blade.php`
- `resources/views/operativo/evaluacion_percepcion/form.blade.php`
- `resources/views/operativo/evaluacion_potencialidad/form.blade.php`
- `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`
- `resources/views/admin/users/form.blade.php`
- `resources/views/admin/lugares/form.blade.php`
- `resources/views/admin/zonas/form.blade.php`
- `tests/Feature/PermisosAdminTest.php`

`resources/views/operativo/involucrados/form.blade.php` **no** se tocó (ver
razón arriba).

## Commit

`f6f873e` — `feat(ui): aviso de reapertura y tipografia de los formularios de admin`
(12 files changed, 144 insertions(+), 39 deletions(-))

## Dudas

1. **La decisión más grande de esta tarea fue no tocar Involucrados.** El
   brief lista los nueve `form.blade.php` como ficheros a modificar y da un
   grep asumiendo que ahí también viven `$estaConfirmado`/`$esJefe` con otro
   nombre. La realidad es que esas variables no existen en ese fichero
   porque el aviso de Involucrados ya está resuelto — y mejor ubicado, según
   su propia documentación — en `index.blade.php`, con tests que ya lo
   cubren. Interpreté "los nueve formularios" como los nueve instrumentos
   del sistema (vocabulario que ya usan los docblocks del propio código:
   "las siete matrices de formulario" + Potencialidad + Involucrados = nueve),
   no como "modifica literalmente los nueve `form.blade.php`". Si la
   intención real era duplicar el aviso también en el formulario de un actor
   individual (redundante con el del listado), dímelo y lo añado — implica
   tocar `InvolucradosController::create()`/`edit()` para pasar `confirmada`
   a la vista, algo que el brief no listaba como fichero a tocar.
2. No encontré ninguna insignia (`text-xs` legítimo) en los tres formularios
   de admin, así que no apliqué ninguna excepción del Step 4.

---

# Cierre de revisión de código — dos hallazgos sobre f6f873e

## Estado de partida

Al empezar esta tarea el repo ya tenía trabajo a medio hacer de un intento
anterior: `tests/Feature/PermisosAdminTest.php` ya contenía los tres tests
nuevos del hallazgo 1 (recorrido por `Registro::ENTRADAS`), el componente
`resources/views/components/aviso-reapertura.blade.php` ya existía (creado
pero sin usar en ningún formulario), y `evaluacion_irritacion/form.blade.php`
tenía la condición rota a propósito (`!$esJefe` en vez de `$esJefe`) sin
revertir — el punto exacto donde quedó cortado el ciclo rojo→verde del
hallazgo 1. Aproveché esa evidencia de rojo en vez de descartarla y rehacerla,
y completé lo que faltaba: verificar el rojo, revertir, extraer el componente
en los ocho ficheros (hallazgo 2, que no se había tocado) y dejar la suite en
verde.

## Hallazgo 1 — cobertura por los ocho instrumentos de matriz

Los tests recorren `Registro::ENTRADAS` filtrando `tipo === 'matriz'`, que
son exactamente ocho: `paisaje`, `fit`, `fet`, `valoracion_territorial`,
`percepcion`, `irritacion`, `concentracion` y `potencialidad`. No hay un
noveno «matriz» oculto (confirmado con
`grep -n "'tipo'" app/Matrices/Registro.php`: ocho `'matriz'`, uno
`'inventario'`, uno `'resultado'`, uno `'actores'`). O sea, la cobertura no
es "al menos tres": son los ocho, con un método (`criteriosCompletosDe()`)
que sabe rellenar los criterios de cada uno para dejarlo validable con un
POST del jefe, igual que el patrón ya usado para Paisaje.

Los tres tests nuevos, por instrumento:
- `test_el_jefe_ve_el_aviso_de_reapertura_en_todas_las_matrices_validadas`:
  valida cada instrumento y comprueba que el jefe ve el aviso.
- `test_el_equipo_y_el_admin_no_ven_el_aviso_en_una_matriz_validada`: mismo
  recorrido, comprueba que equipo y admin no lo ven.
- `test_nadie_ve_el_aviso_de_reapertura_con_una_matriz_en_borrador`: deja
  cada instrumento en borrador y comprueba que nadie (jefe/equipo/admin) ve
  el aviso.

## Evidencia del rojo antes del verde

**Rojo heredado del intento anterior (Irritacion, con el marcado duplicado
todavía sin extraer):** el repo ya tenía `@if($estaConfirmado && !$esJefe)`
en `evaluacion_irritacion/form.blade.php` cuando empecé. Lo primero que hice
fue correr la suite para capturar esa evidencia antes de revertir nada:

```
$ php artisan test --filter=PermisosAdminTest
⨯ el jefe ve el aviso de reapertura en todas las matrices validadas   0.40s
✓ el equipo y el admin no ven el aviso en una matriz validada         0.65s
✓ nadie ve el aviso de reapertura con una matriz en borrador          0.85s
Tests:    1 failed, 17 passed (151 assertions)
```

El HTML devuelto en el fallo correspondía al formulario de Irritación
("Índice de Irritación Validado"), y la traza señala
`PermisosAdminTest.php:351`, la aserción `assertStringContainsString('la
devolverá a borrador', ...)` dentro del recorrido por los ocho instrumentos.
Los otros dos tests seguían en verde porque, con `!$esJefe`, el bloque
del aviso (dentro de `@unless($bloqueado)`) solo cambia de comportamiento
para el jefe: equipo/admin ni siquiera llegan a esa rama al estar
bloqueados, así que ese test no se ve afectado por este error concreto —
consistente con que cada test aísla una premisa distinta.

Revertí ese cambio (`@if($estaConfirmado && $esJefe)`) y confirmé el verde:

```
$ php artisan test --filter=PermisosAdminTest
✓ el jefe ve el aviso de reapertura en todas las matrices validadas   1.62s
✓ el equipo y el admin no ven el aviso en una matriz validada         0.75s
✓ nadie ve el aviso de reapertura con una matriz en borrador          0.86s
Tests:    18 passed (154 assertions)
```

**Segunda verificación, ya con el componente extraído (hallazgo 2 aplicado),
sobre un instrumento distinto y con el otro layout (`w-full mb-1` en vez de
`mb-3`):** para responder a la autorrevisión ("¿fallan de verdad en
cualquier formulario, o solo en el que probaste?") rompí también
`evaluacion_fet/form.blade.php` después de sustituir el marcado por
`<x-aviso-reapertura>`:

```
$ # cambio temporal: @if($estaConfirmado && !$esJefe) en evaluacion_fet/form.blade.php
$ php artisan test --filter=PermisosAdminTest
⨯ el jefe ve el aviso de reapertura en todas las matrices validadas   1.13s
Tests:    1 failed, 17 passed (136 assertions)
```

El HTML del fallo esta vez correspondía a "Evaluación FET Validada" — un
instrumento y un layout distintos a la primera prueba, confirmando que el
recorrido detecta la regresión en cualquiera de los ocho, no solo en el que
se rompió primero. Reverti (`@if($estaConfirmado && $esJefe)`) y la suite
volvió a 18/18 en verde (154 assertions).

## Hallazgo 2 — extracción del componente

Se creó `resources/views/components/aviso-reapertura.blade.php` (ya existía
del intento anterior, sin cambios de contenido) siguiendo el patrón de
`<x-aviso-bloqueo-matriz>`: prop `sustantivo` con default `'matriz'`, el
componente pone el texto, la vista decide si mostrarlo con su propio
`@if($estaConfirmado && $esJefe)` (los nombres de esas variables difieren
entre ficheros, así que esa condición se queda en cada vista, tal como pedía
el hallazgo). El margen/ancho (`mb-3` suelto vs `w-full mb-1` dentro de un
contenedor flex) se pasa vía `$attributes` en cada invocación, para no
imponer un layout que el componente hermano tampoco impone.

Sustantivo que quedó en cada uno de los ocho ficheros (el mismo que tenían
antes de la extracción — ninguno cambió):

| Fichero | Invocación | Sustantivo |
|---|---|---|
| evaluacion_concentracion/form.blade.php | `<x-aviso-reapertura class="mb-3" />` | matriz (default) |
| evaluacion_fet/form.blade.php | `<x-aviso-reapertura class="w-full mb-1" />` | matriz (default) |
| evaluacion_fit/form.blade.php | `<x-aviso-reapertura class="w-full mb-1" />` | matriz (default) |
| evaluacion_irritacion/form.blade.php | `<x-aviso-reapertura class="mb-3" />` | matriz (default) |
| evaluacion_paisaje/form.blade.php | `<x-aviso-reapertura class="mb-3" />` | matriz (default) |
| evaluacion_percepcion/form.blade.php | `<x-aviso-reapertura class="w-full mb-1" />` | matriz (default) |
| evaluacion_potencialidad/form.blade.php | `<x-aviso-reapertura sustantivo="evaluación" class="mb-3" />` | evaluación (explícito) |
| evaluacion_valoracion_territorial/form.blade.php | `<x-aviso-reapertura class="mb-3" />` | matriz (default) |

Comprobación de que no queda texto duplicado fuera del componente:

```
$ grep -rn "Esta matriz está validada\|Esta evaluación está validada" resources/views --include="*.blade.php"
(sin resultados)
```

(Sin resultados porque el único sitio donde existe ese texto ahora es la
interpolación `{{ $sustantivo }}` dentro del propio componente, que un grep
literal no encuentra — correcto.)

`involucrados/form.blade.php` no se tocó, como ya documentaba este mismo
informe para el ciclo anterior: su aviso vive en `index.blade.php` y no
forma parte de estos dos hallazgos.

## Suite completa

```
$ php artisan test
Tests:    431 passed (2754 assertions)
Duration: 29.27s
```

430 (base, según la tarea) − 2 (los dos tests viejos que este cambio
reemplaza) + 3 (los tres tests nuevos) = 431. Cero fallos.

```
$ npm run build
✓ 56 modules transformed.
✓ built in 1.84s
```

Sin errores. `package-lock.json` seguía modificado desde antes de empezar
(constaba así en `git status` al inicio) y no se incluyó en el commit.

## Commit

`f22b78c` — `test(permisos): recorre los ocho instrumentos de matriz para el aviso de reapertura`
(10 files changed, 175 insertions(+), 47 deletions(-): el componente nuevo,
los ocho formularios y `PermisosAdminTest.php`)

## Dudas

Ninguna. Los dos hallazgos quedan cerrados sin cambios de comportamiento
visible: mismo texto, mismo sustantivo por fichero, misma condición
`@if(validada && esJefe)` en cada vista — solo se ganó cobertura de test y
se quitó duplicación de marcado.
