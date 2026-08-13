# Task 2: Quitar los contenedores de página — vistas operativas — Informe

## Estado encontrado al empezar

El árbol de trabajo ya traía las 28 vistas de `resources/views/operativo/` editadas
**sin commit** cuando arranqué esta sesión (`git status` las mostraba como `M`
antes de tocar nada). El fichero `task-2-report.md` que existía en este mismo
directorio **no correspondía a esta tarea**: era el informe de un plan anterior y
ya cerrado (commit `60f9772` y alrededores, sobre una "franja de resumen en
Involucrados"), que reutilizó el mismo nombre de fichero antes de que existiera
este plan de fundación visual. Lo he sustituido por este informe, que sí es el de
`task-2-brief.md`.

No hay forma de saber, desde este sesión, quién dejó esas 28 vistas editadas ni
si llegó a correr la suite. Traté el diff como un borrador a auditar, no como un
hecho consumado: abrí cada fichero, comparé contra el brief, y solo después
corrí los tests.

## Verificación fichero a fichero

Repasé las 28 vistas modificadas (todas las que `grep -rn 'max-w-[a-z0-9]* mx-auto'
resources/views/operativo/` señalaba) y confirmé que en cada una se quitó
exactamente **un** contenedor de página — el `max-w-* mx-auto` más externo,
primero dentro de `<x-app-layout>` — y nada más:

- `dashboard.blade.php`, `evaluacion_concentracion/{form,ponderacion}`,
  `evaluacion_fet/{form,ponderacion}`, `evaluacion_fit/{form,ponderacion}`,
  `evaluacion_irritacion/{form,ponderacion}`, `evaluacion_paisaje/{form,ponderacion}`,
  `evaluacion_percepcion/{form,ponderacion}`, `evaluacion_potencialidad/ponderacion`,
  `evaluacion_valoracion_territorial/{form,ponderacion}`, `frecuentacion/{index,resultados}`,
  `inventarios/{create,edit,index,show}`, `involucrados/{form,index,resultados}`,
  `vtt/resultado`, `zona/panel`: el patrón habitual (`<div class="py-12"><div
  class="max-w-* mx-auto sm:px-6 lg:px-8">…</div></div>` → `<div class="py-12">…</div>`),
  con el `py-12` intacto y ningún otro cambio de estructura.

- `frecuentacion/form.blade.php`: su contenedor de página era `max-w-2xl` (≤
  `max-w-2xl`, cae en la regla de excepción). Se sustituyó por
  `<x-contenedor ancho="estrecho">…</x-contenedor>`, tal cual pide el brief.

- `evaluacion_potencialidad/form.blade.php` **no está tocado, y es correcto que
  no lo esté**: no usa la clase Tailwind `max-w-* mx-auto` en absoluto — su
  contenedor es `<div style="max-width:1160px;margin:0 auto;...">` dentro de un
  `.pt-root` con CSS a medida (fuente DM Sans, colores propios). Queda fuera del
  patrón que el Paso 1 del brief usa para acotar la tarea, y tocarlo sería
  rediseñar una vista con un lenguaje visual deliberadamente distinto, algo que
  esta fase no pide.

Comprobé balance de `<div>`/`</div>` en los 28 ficheros tocados (coinciden
exactamente) y busqué `slate-` en todo `resources/views/operativo/`: no aparece
ninguna. No añadí ni quité texto, tamaños de fuente ni `uppercase` — el único
cambio en cada fichero es la desaparición de un `<div>` de apertura y su cierre.

## Cajas interiores que se quedaron (11, en 5 ficheros)

Esta es la lista que pide el brief, con el porqué de cada una:

1. **`evaluacion_valoracion_territorial/ponderacion.blade.php:17`** —
   `max-w-3xl mx-auto sm:px-6 lg:px-8`, dentro de `@if($sinDatos)`. Envuelve el
   aviso amarillo "Evaluación aún no realizada" — un **aviso centrado**, la
   tercera categoría que el propio brief nombra en la definición de caja
   interior. Es la excepción que el brief cita explícitamente para este
   fichero (junto con las otras tres de abajo): de los 5 `max-w-*` que tenía el
   fichero, éste es una de las "cuatro cajas interiores", no el contenedor de
   página (ese es el `max-w-5xl` de la rama `@else`, ya retirado).
2. **`evaluacion_valoracion_territorial/ponderacion.blade.php:75`** —
   `max-w-xl mx-auto` sobre un `<p>` de texto descriptivo
   (`{{ $estilos['lectura'] }}`). Angosta solo el párrafo para que no se
   estire a todo el ancho de la página; es texto, no estructura.
3. **`evaluacion_valoracion_territorial/ponderacion.blade.php:76`** —
   `max-w-sm mx-auto` sobre un `grid grid-cols-2` de dos tarjetas de cifras
   (FN/FT). Es exactamente el "cuadro de resumen" del brief.
4. **`evaluacion_valoracion_territorial/ponderacion.blade.php:95`** —
   `max-w-2xl mx-auto mb-10` alrededor de la leyenda de colores del gráfico de
   cuadrantes (grid 2×2 con las cuatro etiquetas de zona). Otro cuadro de
   resumen, deliberadamente más angosto que la página.
5. **`evaluacion_percepcion/ponderacion.blade.php:22`** —
   `max-w-3xl mx-auto sm:px-6 lg:px-8`, dentro de `@if($sinDatos)`. Mismo
   patrón exacto que el punto 1: un aviso centrado ("Matriz de Percepción no
   disponible") en la rama sin datos, mutuamente excluyente con la rama
   `@else` (cuyo contenedor de página, `max-w-7xl`, sí se retiró). El brief no
   nombra este fichero, pero la estructura es idéntica a la que sí nombra para
   Valoración Territorial, y coincide con la definición general de "aviso
   centrado". Todas las demás matrices resuelven este mismo caso con el
   componente compartido `<x-matriz-sin-resultados>` (que el propio plan
   señala como caja interior en la Tarea 3, línea 366); Percepción y
   Valoración Territorial son las dos únicas que lo reimplementan a mano en
   vez de usar el componente, pero el bloque resultante cumple la misma
   función y la misma forma.
6. **`evaluacion_paisaje/ponderacion.blade.php:55`** —
   `max-w-3xl mx-auto text-sm leading-relaxed` sobre un `<p>` descriptivo del
   escenario. Texto, no contenedor de página.
7. **`evaluacion_paisaje/ponderacion.blade.php:60`** —
   `max-w-lg mx-auto` envolviendo un único `<canvas>` de radar. Angosta el
   gráfico para que no se deforme a todo el ancho.
8. **`evaluacion_potencialidad/ponderacion.blade.php:108`** —
   `max-w-xl mx-auto` sobre el `<p>` de descripción del cuadrante. Mismo caso
   que el punto 2.
9. **`evaluacion_potencialidad/ponderacion.blade.php:109`** —
   `max-w-sm mx-auto` sobre el `grid grid-cols-2` de FN/FT. Mismo caso que el
   punto 3 (es la misma pareja de tarjetas que en Valoración Territorial).
10. **`vtt/resultado.blade.php:127`** — `max-w-2xl mx-auto` envolviendo la
    tabla "Resultado Obtenido / Zona / Explicación". Es literalmente "una
    tabla pequeña", el tercer ejemplo del brief.
11. **`vtt/resultado.blade.php:157`** — `max-w-2xl mx-auto` envolviendo la
    segunda tabla ("Lugar / Factores Intrínsecos / Extrínsecos / Vocación").
    Mismo caso.

Ningún fichero quedó con dos contenedores de página compitiendo: comprobé
aparte que ningún otro `max-w-*` en `operativo/` usa el patrón
`sm:px-6 lg:px-8` propio de un contenedor de página salvo los puntos 1 y 5, ya
justificados arriba como aviso centrado.

## Suite completa — resultado y hallazgo que detiene la tarea

```
php artisan test
Tests:    6 failed, 552 passed (3244 assertions)
Duration: 87.34s
```

Los 6 fallos son todos de la misma familia y la misma causa:

```
FAILED  Tests\Feature\ConcentracionTest > el formulario ensancha y muestra la barra lateral con atractivos y planta
FAILED  Tests\Feature\FetTest > el formulario ensancha y muestra la barra lateral con sus bloques
FAILED  Tests\Feature\FitTest > el formulario ensancha y muestra la barra lateral con sus seis bloques
FAILED  Tests\Feature\IrritacionTest > el formulario ensancha y muestra la barra lateral con sus bloques
FAILED  Tests\Feature\PaisajeTest > el formulario ensancha y muestra la barra lateral con sus categorias
FAILED  Tests\Feature\ValoracionTerritorialTest > el formulario ensancha y muestra la barra lateral con rtt y uc
```

Cada uno falla en la misma línea, con la misma aserción:

```php
$this->assertStringContainsString('max-w-7xl', $html);
```

**Causa raíz:** estos 6 tests (uno por cada `evaluacion_*/form.blade.php` que
tiene `<x-barra-lateral-formulario>`) son anteriores a esta fase de rediseño.
Cuando se les añadió la barra lateral, alguien ensanchó esas 6 vistas a
`max-w-7xl` para que la barra no las apretara, y fijó esa cifra como test de
regresión — el propio docblock de `FitTest` lo dice: *"La barra lateral
aparece con el ancho nuevo"*. El test comprueba la clase Tailwind literal como
sustituto de "la página es lo bastante ancha", no el ancho en sí.

Esta tarea quitó, correctamente y tal como pide el Paso 2 del brief, el
`<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">` de esos 6 ficheros — es el
contenedor de página canónico, sin ninguna ambigüedad de caja interior (una
sola rama, sin `@if/@else`, idéntico al patrón habitual del Paso 2). El ancho
ahora lo decide `<x-contenedor>` del layout (Tarea 1, ya en `main`), cuyo
ancho `normal` es `max-w-[1440px]` — **más ancho que `max-w-7xl` (1280px)**,
así que la barra lateral sigue teniendo sitio de sobra; funcionalmente la
página sigue "ensanchada" e incluso más. Lo que falla es la cadena literal
`max-w-7xl`, que ya no aparece en ningún sitio del HTML porque el ancho ahora
se declara como `max-w-[1440px]`.

**Descarté que sea un error de clasificación mío** antes de reportarlo:
- Repasé los 6 ficheros (`evaluacion_fit/form.blade.php` como muestra en el
  informe, y el resto por diff): en los seis el `max-w-7xl` retirado es el
  primer y único `<div>` con `max-w-* mx-auto` del fichero, envolviendo todo
  el cuerpo de la vista — el "patrón habitual" del Paso 2 letra por letra, sin
  ramas alternativas.
- El plan maestro (`docs/superpowers/plans/2026-08-12-fundacion-visual.md`,
  líneas 298-301) espera **558 passed** tras esta tarea, igual que el brief; no
  menciona estos 6 tests como excepción conocida, y su propia autorrevisión
  (líneas 1266-1280) trata precisamente un recuento de tests que no cuadra
  como la señal de que "alguien arregló un test viejo en vez de reportarlo" —
  es decir, el plan da por hecho que si algo así ocurre, se reporta y no se
  toca.

## Qué NO hice en el intento inicial, y por qué (antes de la resolución)

En el primer paso de esta tarea, antes de consultar al responsable del
proyecto:

- **No modifiqué los 6 tests.** El brief lo prohíbe explícitamente ("Si uno se
  pone rojo, para y repórtalo... No lo arregles"), y el plan maestro trata
  precisamente esa modificación como la señal de que se saltó la restricción
  global.
- **No hice commit.** El Paso 5 asume la suite en verde; commitear 28 vistas
  con 6 tests rojos dejaría la rama en un estado que el propio plan usa como
  detector de "algo se rompió".
- **No revertí nada.** El trabajo en las 28 vistas es correcto contra el
  brief; retroceder tiraría un diff válido por un problema que está en los
  tests, no en las vistas.

Reporté el hallazgo tal cual, con la lectura de causa raíz de arriba, y quedé
a la espera de una decisión. Lo que sigue es esa decisión y su ejecución.

---

## Resolución: autorización para tocar 6 tests

El responsable del proyecto confirmó el diagnóstico ("el choque era real") y
decidió que la restricción de "no tocar tests" no encajaba en este caso
concreto: el ancho ya tiene su propio test (`ContenedorTest` comprueba
`max-w-[1440px]`), así que los 6 tests de vista repetían exactamente la
duplicación que esta fase existe para eliminar, y además afirmaban sobre un
detalle de implementación (un nombre de clase Tailwind) en vez de sobre el
comportamiento real (que la barra lateral tenga sitio).

Autorización explícita, acotada a 6 ficheros y a una sola línea por test:
quitar `$this->assertStringContainsString('max-w-7xl', $html);`, renombrar el
test de `..._ensancha_y_muestra_la_barra_lateral_con_...` a
`..._muestra_la_barra_lateral_con_...`, y anotar en el docblock por qué se fue
la aserción. El resto de cada test —el recuento real de enlaces de ancla por
bloque/categoría, que es lo que de verdad prueba que la barra lateral se
pinta— se dejó intacto.

### Los 6 tests tocados, línea por línea

| Fichero | Método anterior | Método nuevo | Línea retirada |
|---|---|---|---|
| `tests/Feature/ConcentracionTest.php` | `test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_atractivos_y_planta` | `test_el_formulario_muestra_la_barra_lateral_con_atractivos_y_planta` | `$this->assertStringContainsString('max-w-7xl', $html);` |
| `tests/Feature/FetTest.php` | `test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_bloques` | `test_el_formulario_muestra_la_barra_lateral_con_sus_bloques` | `$this->assertStringContainsString('max-w-7xl', $html);` |
| `tests/Feature/FitTest.php` | `test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_seis_bloques` | `test_el_formulario_muestra_la_barra_lateral_con_sus_seis_bloques` | `$this->assertStringContainsString('max-w-7xl', $html);` |
| `tests/Feature/IrritacionTest.php` | `test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_bloques` | `test_el_formulario_muestra_la_barra_lateral_con_sus_bloques` | `$this->assertStringContainsString('max-w-7xl', $html);` |
| `tests/Feature/PaisajeTest.php` | `test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_categorias` | `test_el_formulario_muestra_la_barra_lateral_con_sus_categorias` | `$this->assertStringContainsString('max-w-7xl', $html);` |
| `tests/Feature/ValoracionTerritorialTest.php` | `test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_rtt_y_uc` | `test_el_formulario_muestra_la_barra_lateral_con_rtt_y_uc` | `$this->assertStringContainsString('max-w-7xl', $html);` |

En los cuatro que mencionaban "el ancho nuevo" en su docblock (Fet, Fit,
Paisaje) o no mencionaban el ancho en absoluto (Concentracion, Irritacion,
ValoracionTerritorial) se añadió el mismo párrafo de cierre, adaptado al
fichero: que el ancho ya no se comprueba ahí, que ahora lo decide
`<x-contenedor>` en el layout y lo cubre `ContenedorTest`, y que repetir un
literal de clase Tailwind en cada vista era la duplicación que esta fase
existe para quitar.

Confirmé con `git status`/`git diff --stat -- tests/` que **no se tocó ningún
otro fichero de test**: solo estos 6, y en cada uno solo la línea de la
aserción, el nombre del método y su docblock.

### Suite completa tras la resolución (VERDE)

```
php artisan test
Tests:    558 passed (3268 assertions)
Duration: 99.24s
```

558 = los mismos 558 de después de la Tarea 1. No se quitó ningún test, solo
una aserción de cada uno de los 6, así que el recuento de tests no baja aunque
sí baje el de aserciones (3268 aquí, contra 3274 después de la Tarea 1: la
diferencia son las 6 aserciones de `max-w-7xl` retiradas).

### Commits

```
2b8159a refactor(operativo): las vistas dejan de fijar su propio ancho
fe4baf4 test(operativo): la barra lateral deja de exigir max-w-7xl literal
```

Dos commits, como sugirió el responsable: uno para las 28 vistas (el trabajo
de la Tarea 2 propiamente dicha) y otro aparte para los 6 tests, con el
motivo completo en el cuerpo de cada mensaje.

## Ficheros

**Modificados y commiteados:**
- Las 28 vistas de `resources/views/operativo/` listadas en "Verificación
  fichero a fichero" — commit `2b8159a`.
- `tests/Feature/ConcentracionTest.php`, `FetTest.php`, `FitTest.php`,
  `IrritacionTest.php`, `PaisajeTest.php`, `ValoracionTerritorialTest.php` —
  commit `fe4baf4`.

**No tocado:** `package-lock.json` — seguía modificado desde antes de esta
sesión, no es mío y no se incluyó en ningún commit. Ningún otro fichero de
`tests/` se modificó.

## Dudas para quien retome esto

1. ~~¿Se actualizan los 6 tests de "el formulario ensancha..." en una tarea
   aparte, o el plan prefiere que Task 2 quede pendiente hasta resolverlo?~~
   Resuelto: el responsable autorizó tocarlos aquí mismo, ver "Resolución"
   arriba.
2. Sigue abierta: confirmar que tratar el aviso centrado de
   `evaluacion_percepcion/ponderacion.blade.php` (punto 5 de la lista de
   cajas interiores) como caja interior es la lectura correcta. El brief no
   nombra ese fichero explícitamente, solo
   `evaluacion_valoracion_territorial/ponderacion.blade.php`, pero comparten
   estructura idéntica (rama `@if($sinDatos)` con su propio
   `max-w-3xl mx-auto sm:px-6 lg:px-8` envolviendo un aviso amarillo). La
   suite en verde valida que no rompe ningún test existente, pero no confirma
   que sea la intención de diseño correcta.
