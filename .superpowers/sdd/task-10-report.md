# Task 10: Adoptar `<x-boton>` — Informe

## Estado

DONE_WITH_CONCERNS — la conversión está completa y la suite sigue en 575/575,
pero cuatro pares de botones (FIT, FET, Paisaje, Valoración Territorial)
tuvieron que revertirse a mano tras romper tests existentes; quedan escritos
tal como estaban, documentados con un comentario que explica por qué.

## Commit

```
20983c1 refactor(vistas): los botones pasan a x-boton
```

(rama `fundacion-visual`; `package-lock.json` se dejó fuera del commit,
como se pidió — no es mío).

## Salida de `php artisan test`

```
Tests:    575 passed (3320 assertions)
Duration: 43.61s
```

575 es el número de partida indicado en la tarea (3320 aserciones, sin
ninguna añadida por mí). El brief de `task-10-brief.md` menciona 572 como
esperado, pero ese número es de un estado anterior del árbol; el de la
tarea que me diste — 575 — es el que ya tenía este checkout antes de tocar
nada, y es el que queda después del commit.

## Recuento de botones

- **Convertidos a `<x-boton>`: 40** instancias, en 24 archivos de vista.
- **Encontrados pero dejados sin convertir: 4 pares/tríos** (9 botones) en
  `evaluacion_fet/form.blade.php`, `evaluacion_fit/form.blade.php`,
  `evaluacion_paisaje/form.blade.php` y
  `evaluacion_valoracion_territorial/form.blade.php` — revertidos tras
  romper tests (ver más abajo).
- **`type="button"` encontrados: 0.** Ninguno de los botones que coincidían
  con el patrón `bg-(blue|green|indigo|red|gray)-600 ... rounded` (ni sus
  variantes de orden de clases) era `type="button"`; todos eran
  `type="submit"` (implícito o explícito) o enlaces `<a href>`. Los botones
  `type="button"` de Alpine que sí existen en el código (el "Borrar
  respuesta" de `<x-criterio-pildoras>`, el hamburger de
  `layouts/navigation.blade.php`, los `pt-btn-all`/`pt-btn-none` de
  Potencialidad) usan una paleta distinta (subrayado gris, clases CSS
  propias) que nunca entró en el patrón buscado, así que no hubo ningún
  caso de "type=submit por defecto pisa un type=button" que vigilar en la
  práctica — lo comprobé de todas formas botón por botón antes de escribir
  cada `<x-boton>`.

## El bloqueo real: `disabled:` de Tailwind vs. tests que cuentan "disabled"

`<x-boton>` (Tarea 9) lleva en su lista de clases compartida
`disabled:opacity-50 disabled:cursor-not-allowed`. Son clases *variante* de
Tailwind — el navegador nunca las aplica salvo que el elemento tenga el
atributo `disabled` — pero el **texto literal** "disabled" queda en el HTML
igualmente, aparezca o no el atributo.

Cuatro tests, escritos antes de que `<x-boton>` existiera, cuentan ese texto
en la página entera para confirmar que ningún control está deshabilitado:

- `EvaluacionesTest::test_el_admin_recibe_el_formulario_fit_editable_estando_en_borrador`
  — `assertSame(0, substr_count($respuesta->getContent(), 'disabled'))`
- `EvaluacionesTest::test_el_admin_recibe_el_formulario_fet_editable_estando_en_borrador`
  — misma aserción
- `PaisajeTest::test_el_admin_recibe_el_formulario_editable_estando_en_borrador`
  — `assertDontSee('disabled', false)`
- `ValoracionTerritorialTest::test_el_admin_recibe_el_formulario_editable_estando_en_borrador`
  — misma aserción

El comentario de la primera incluso lo dice explícito: *"contar 'disabled' a
secas es seguro aquí (...) porque esta vista no tiene ninguna clase Tailwind
'disabled:' que infle el conteo"*. Cambié esa premisa al meter `<x-boton>`
ahí, y los cuatro tests se pusieron en rojo — sin que se perdiera ningún
atributo, sin ningún `type`, `name` o `value` de menos: es la propia clase
`disabled:opacity-50` del componente la que mete la palabra.

Siguiendo la instrucción de la tarea ("si uno se pone rojo (...) para y
repórtalo, no lo arregles"): **no toqué el test ni el componente**. Reverti
los 8-9 botones de esos cuatro `form.blade.php` a su marcado original
(mismo `class=`, mismos `name`/`value`/`onclick` que tenían), dejando un
comentario en castellano en cada sitio que explica el motivo y apunta a
este informe. El resto de la conversión — los otros 40 botones, en 24
archivos — no tocó ninguna vista con este patrón de test y quedó en verde.

Nota para quien retome esto: `evaluacion_percepcion/form.blade.php` y
`evaluacion_potencialidad/form.blade.php` tienen la misma forma de botones
pero SÍ se convirtió percepción sin problema, porque su test
(`PercepcionTest`) no cuenta "disabled" en toda la página — filtra por los
`<input>` de tipo radio con una regex, así que las clases del botón no lo
afectan. Si en algún momento se quiere recuperar FIT/FET/Paisaje/Valoración
Territorial, el camino más limpio es igualar esos cuatro tests al patrón de
`PercepcionTest` (contar solo dentro de los radios), no tocar `<x-boton>`.

## Botones dejados sin convertir, y por qué

**Excepciones nombradas en la tarea (no se tocan):**
- `resources/views/components/resumen-lista.blade.php:82-85` — "Validar y
  Cerrar la Lista", verde a propósito.
- `resources/views/components/barra-lateral-formulario.blade.php:94-97` —
  "Guardar Borrador" de la barra lateral.

**Botones pequeños de tablas (mismo criterio que el ejemplo del brief,
extendido a "Editar"/"Ver" del mismo tamaño y peso, no solo "Eliminar"):**
`admin/lugares/index.blade.php`, `admin/users/index.blade.php`,
`operativo/frecuentacion/index.blade.php`,
`operativo/involucrados/index.blade.php`,
`operativo/inventarios/index.blade.php` (vista lista y vista tarjetas) —
enlaces/botones `px-2 py-1` o `py-1.5`, fondo `bg-*-50`/`bg-*-100`, texto
`text-*-600`/`700`. Ninguno usaba `bg-*-600` (el patrón del Paso 1), así que
tampoco entraban por la letra de la tarea.

**Filas de tabla/tarjeta con acción de navegación a tamaño reducido (sin
tamaño equivalente en `<x-boton>`, que solo define `normal` y `grande`):**
`admin/zonas/index.blade.php` — "Abrir zona"/"Editar"/"Eliminar" en la vista
lista y en la vista tarjetas, todas en `px-3 py-1.5`. Convertirlas a
`normal` (`px-4 py-2`) las agranda al lado de "Editar"/"Eliminar", que se
quedan pequeñas por la excepción de arriba — deformaría la fila. Se anota
para la fase de tablas, junto con las de arriba.

**Mismo color pero un tono distinto al de `<x-boton>` (convertir cambiaría
el look, no solo lo unificaría):**
- `operativo/inventarios/create.blade.php` y `edit.blade.php` — "Cancelar"
  en `bg-gray-200` (relleno sólido pálido); `secundario` de `<x-boton>` es
  blanco con borde. Se deja.
- `operativo/evaluacion_paisaje/ponderacion.blade.php:122-125` — "← Volver
  al Formulario" también en `bg-gray-200`, mismo motivo.

**Overrides de `<x-boton-volver>` con clases `!important` (es un
componente ya existente, no un botón escrito a mano; recolorearlo es una
decisión de diseño de otra tarea):**
`operativo/vtt/resultado.blade.php:207-208` (azul),
`operativo/evaluacion_potencialidad/ponderacion.blade.php:383-384` (gris),
`operativo/inventarios/index.blade.php:22-23` (azul),
`operativo/evaluacion_fit/form.blade.php:12-13` (azul claro).

**Sistema de estilos propio, no Tailwind de una vez (clases `pt-btn-all`,
`pt-btn-none`, `pt-btn-draft`, `pt-btn-confirm`):**
`operativo/evaluacion_potencialidad/form.blade.php` — 5 botones con CSS
propio, ninguno coincidía con el grep del Paso 1. Fuera de alcance.

**Familia de componentes de Breeze, no de esta rama (un solo uso, en el
perfil de usuario, con `text-xs uppercase` — que de hecho ya viola las
reglas de esta rama, señal de que nunca se pensó para este sistema de
diseño):**
`components/danger-button.blade.php` (+ `primary-button.blade.php`,
`secondary-button.blade.php` sin usar), usado una vez en
`profile/partials/delete-user-form.blade.php`.

**Revertidos por el conflicto con los tests de "disabled" (ver sección
arriba):** `evaluacion_fet/form.blade.php`,
`evaluacion_fit/form.blade.php`, `evaluacion_paisaje/form.blade.php`,
`evaluacion_valoracion_territorial/form.blade.php`.

## Ampliaciones sobre el grep del Paso 1

El grep literal del brief (`bg-(blue|green|indigo|red|gray)-600[^"]*rounded`
en una sola línea) encontró 30 archivos. Añadí a la conversión, con el mismo
criterio de "mismo botón primario escrito a mano", cuatro sitios que el
grep no cazó por tener las clases en otro orden (`rounded-lg bg-indigo-600`
en vez de `bg-indigo-600 ... rounded-lg`) pero que son exactamente el mismo
patrón:
- `admin/lugares/index.blade.php` y `admin/users/index.blade.php`: el par
  "Buscar" (indigo, `px-4 py-2 rounded-lg`, coincide EXACTO con `normal`
  de `<x-boton>`) / "Limpiar" (`border-gray-300 bg-white`, coincide EXACTO
  con `secundario`).
- `operativo/dashboard.blade.php`: el par "Abrir zona" (indigo)/"Inventario"
  (blanco con borde), repetido en la vista lista y en la vista tarjetas,
  también `px-4 py-2 rounded-lg` exacto.

Los elegí porque el cambio de clases es cero (coinciden literalmente con
las clases que `<x-boton>` ya define), a diferencia de los casos de la
sección anterior donde convertir sí habría cambiado el aspecto. Si esto se
considera fuera de alcance de la Tarea 10, son fáciles de revertir: están
en commits identificables por archivo en el diff.

## Verificación del purgado (`npm run build`)

```
bg-indigo-600: 1
bg-red-600: 1
border-gray-300: 1
px-4: 1
px-6: 1
rounded-xl: 1
bg-amber-100: 1
bg-green-100: 1
```

Las ocho, mayores que 0. `public/build` no se commiteó (sigue en
`.gitignore`, solo se generó para esta comprobación).

## Tests que se pusieron en rojo durante el proceso

Sí: los cuatro descritos arriba (`EvaluacionesTest` ×2, `PaisajeTest`,
`ValoracionTerritorialTest`), los cuatro por el mismo motivo (`disabled:` de
Tailwind inflando un conteo literal de "disabled"). Se resolvieron
revirtiendo la conversión en esos ocho-nueve botones puntuales, no tocando
los tests. Tras el revert, `php artisan test` corrió limpio: 575 passed, 0
failed.

## Dudas para quien revise

1. Las cuatro instancias de `<x-boton-volver ... class="!bg-...">` (colores
   forzados con `!important`) parecen restos de una migración a medias:
   tres de los cuatro simplemente reproducen el mismo azul/gris que
   `<x-boton>` ya definiría con `variante="secundario"` o el default
   `primario`, sin el `!important`. Podrían limpiarse en una tarea aparte
   sin tocar `<x-boton-volver>` ni su lógica de navegación.
2. `admin/zonas/index.blade.php` es la única lista con acciones de fila a
   tamaño reducido usando `bg-indigo-600` (las demás listas usan solo
   colores pálidos de fondo tipo `bg-indigo-100`); si la fase de tablas le
   da un tamaño `pequeño` a `<x-boton>`, esta vista sería la primera
   candidata.
3. No inicié sesión ni escribí contraseñas en ningún formulario; todo lo
   anterior salió de leer las vistas y correr `php artisan test` / `npm run
   build`, sin abrir la aplicación en el navegador.
