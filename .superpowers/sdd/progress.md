> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — Fundación visual (Fase 0 del rediseño de interfaz)

Plan: docs/superpowers/plans/2026-08-12-fundacion-visual.md
Spec: docs/superpowers/specs/2026-08-12-fundacion-visual-design.md
Rama: fundacion-visual
Base de la rama: 18dd323
Suite en la base: 553 tests

Objetivo: dar a la aplicación un sistema visual —un ancho, una paleta, una
tipografía y tres primitivos— y adoptarlo en las vistas que hoy repiten sus
propias variantes. Ninguna vista cambia de estructura.

Once tareas. 1-3 el contenedor y su adopción, 4 paleta y tipografía, 5 el mapa
de estados y el badge, 6-8 la tarjeta, 9-10 el botón, 11 revisión final.

## Lo que se midió antes de empezar, y por qué importa

- 39 ficheros con contenedor propio en 9 anchos; el `<main>` del layout no
  tenía ninguno.
- 52 tarjetas escritas a mano, 12 variantes del mismo botón primario.
- 1056 usos de `gray-*`, cero de `slate-*`.

No falta diseño: falta sistema.

## Decisiones que vienen del diseño y no se replantean

- **Blade + Alpine, con la estética de shadcn.** shadcn es React y esto no lo
  es; migrar sería reescribir 40 vistas y 553 tests. Se copia el lenguaje
  visual, no la pila.
- **Todo el código escribe `gray-*`, también el nuevo.** `tailwind.config.js`
  redefine `gray` como `slate` y es el único sitio que decide qué es gris.
  Escribir `slate` en los componentes nuevos sería un segundo nombre para el
  mismo color.
- **Los 553 tests existentes no se tocan.** Uno que haya que modificar es la
  señal de que la sustitución cambió comportamiento, no aspecto.
- **Ninguna vista cambia de estructura.** Breadcrumbs, KPIs, columnas y tablas
  ordenables son las fases 1 a 4.

## Pre-vuelo

Un arreglo antes de empezar: la Tarea 10 decía «unifica la forma, no los
colores» y a renglón seguido mandaba pasar todos los botones a índigo. Se
reescribió para decir que **sí** cambia colores —que es lo que se va a notar— y
que lo que no cambia es qué acción es principal en cada pantalla.

## Tareas

(pendientes)

FV1: completa (commit 9d7b934, revisión limpia, cero hallazgos).
`<x-contenedor>` con 5 tests, montado en el layout, la cabecera y la barra.
Suite 553 -> 558, verificado por mí.
  - El punto de riesgo era el `</div>` de cierre en `layouts/navigation.blade.php`,
    que tiene ocho niveles de anidación y donde un error **no lo caza ningún
    test**: el HTML mal anidado se renderiza igual y se vería como una barra
    rota en todas las páginas. El revisor contó la profundidad a mano,
    apertura por apertura, y confirmó que la línea 85 es el cierre correcto.
  - Verificado que el array de anchos con clases literales no cae en la
    prohibición de concatenar: se pegan cadenas ya completas, nunca se compone
    un fragmento de clase con el nombre del ancho.

FV2: completa (commits 2b8159a..0649701, revisión con un Crítico, resuelto).
29 contenedores de página fuera, 11 cajas interiores en pie. Suite sigue en 558,
con 6 aserciones menos (3274 -> 3268).
  - **Choque con una restricción mía, decidido por el responsable del proyecto:**
    seis tests comprobaban `assertStringContainsString('max-w-7xl')` como
    sustituto de «la página ensancha». Al mover el ancho al layout la página
    queda MÁS ancha (1440 frente a 1280) pero esa cadena desaparece. La
    restricción «ningún test existente se modifica» era imposible aquí porque
    esos seis afirmaban sobre la implementación, no sobre el comportamiento.
    Se les quitó la aserción de ancho, conservando el recuento de enlaces de
    ancla —lo que de verdad prueban— y renombrándolos. El ancho lo cubre ahora
    `ContenedorTest`, en un solo sitio.
    Que el implementador parara en vez de reescribirlos en silencio es lo que
    la restricción existía para provocar.
  - **Hueco de mi plan:** el comando de descubrimiento buscaba `max-w-* mx-auto`
    y `evaluacion_potencialidad/form.blade.php` escribía su contenedor como
    **estilo en línea** (`style="max-width:1160px;margin:0 auto"`), resto de
    cuando esa vista traía su propio CSS. Se habría quedado en 1160px mientras
    las otras nueve matrices pasaban a 1440.
  - **CRÍTICO mío, cazado por el revisor:** al arreglar lo anterior sustituí el
    estilo en línea por un `<x-contenedor>`, sin caer en que el layout ya
    envuelve todo el contenido en uno. Anidados, el padding se aplicaba dos
    veces —64px de aire a cada lado en vez de 32, solo en esa matriz—. **Ningún
    test lo veía.** Corregido en 0649701 borrándolo sin sustituirlo, como en
    las otras 28.
  - El revisor verificó las 11 cajas interiores una por una abriendo cada
    fichero, el balance de `<div>` en las 28 vistas, y buscó otros contenedores
    invisibles a la búsqueda por `max-w-`: los que hay son envoltorios de
    `<canvas>` de los gráficos de radar, ninguno es de página.

FV3: completa (commit 04e8a1c, revisión limpia, cero hallazgos).
7 contenedores de página fuera en admin/ y profile/, 1 caja interior en pie.
Suite sigue en 558 (3268), ningún test tocado.
  - `admin/lugares/form` es el único estrecho, por la regla del plan. Los de
    users y zonas se quedan en normal aunque sean formularios de alta: eran
    `max-w-7xl` y estrecharlos habría sido cambiar la estructura de una vista.
  - El implementador resolvió por su cuenta un caso que el brief no cubría:
    `profile/edit` llevaba `space-y-6` en el mismo `div` que el ancho. Lo movió
    al `py-12` que sobrevive. El revisor comprobó que es equivalente porque el
    `div` intermedio desaparece del todo, así que las tres tarjetas siguen
    siendo hijas directas del elemento que lleva la clase — `space-y-*` actúa
    sobre hijos directos y ahí estaba el riesgo.
  - Verificado que no quedó ningún contenedor anidado, que el balance de
    `<div>` cierra en los 8 ficheros, y que no hay contenedores de página
    escritos como estilo en línea en estos directorios.

FV4: completa (commits d32332e -> 4d79410 tras enmienda, más a907baf y b65a69b;
revisión con un Importante, resuelto). Suite sigue en 558 (3268).
`gray` pasa a ser alias de `slate` en la configuración: los 1056 usos escritos
en las vistas cambian de tinte a la vez, sin un solo edit.
  - **Verificado en el CSS generado, no supuesto:** `bg-gray-50` vale
    `rgb(248 250 252)` = `#f8fafc`, que es slate-50 y no el `#f9fafb` del gray
    original. El revisor eligió sus propias clases en vez de fiarse de la
    muestra del informe y comprobó **45 tokens** —toda la escala 50..900 con
    sus variantes `hover:`, `focus:`, `disabled:` y una con `!important`—:
    ninguno purgado.
  - **Error de mi plan, avisado por el implementador:** mandaba commitear
    `public/build`, que está en `.gitignore` desde el primer commit y **nunca
    se ha versionado**. Lo forzó con `-f` siguiendo la instrucción y lo dijo.
    Sacado del commit y arreglado el plan, también en la Tarea 10 que repetía
    la misma orden.
  - **IMPORTANTE, hueco de mi plan cazado por el revisor:** el plan solo
    nombraba `layouts/app.blade.php`. `layouts/guest.blade.php` —las cinco
    páginas de autenticación— seguía pidiendo Figtree, mientras `fontFamily.sans`
    ya es global y pedía Inter. No se veían ni en una ni en otra: caían a la
    fuente del sistema. Ningún test lo ve, porque los tests miran HTML y no qué
    fuente resuelve el navegador. Corregido en b65a69b.

FV5: completa (commits 9f35c03..1921216, revisión con dos Importantes, los dos
resueltos). Suite 558 -> 564 (dos tests más de los 4 previstos).
El mapa de estado→color pasa a `EstadoZona::ESTILOS_ESTADO`, y `<x-badge>` y
`<x-fila-matriz>` lo consumen.
  - El revisor comparó los cinco estados uno a uno, `icono` y `detalle`, contra
    lo que `fila-matriz` tenía escrito antes: coinciden los diez valores. La
    apariencia no cambió un píxel, que era la condición.
  - **IMPORTANTE, riesgo estructural:** `tailwind.config.js` no incluía `app/`
    en su `content`, y el mapa vive ahí. Las clases sobrevivían **por
    casualidad**, porque esas mismas cadenas aparecen en vistas sin relación
    —`border-amber-200` en una sola—. Refactorizar cualquiera de esas vistas
    habría dejado las insignias sin color, con el HTML correcto y la suite en
    verde. Añadido `./app/**/*.php`, y **verificado por mutación**: una clase
    que solo existe en `app/` aparece ahora en el CSS construido.
  - **IMPORTANTE:** los dos tests que decían atar los componentes al mapa no lo
    ataban. El que mira el fuente solo prohibía `text-` de ámbar y verde, así
    que copiar «bg-amber-100 border-amber-200» dentro del componente pasaba en
    verde, y los grises —tres de los cinco estados— no los miraba. Y nadie
    comprobaba que el mapa **llegue a la pantalla**. Añadidos dos tests que
    renderizan badge y fila en los cinco estados y comparan contra el mapa;
    rojo verificado sustituyendo el mapa por valores propios.
  - Mi primer intento de arreglo prohibía toda la paleta y daba falsos
    positivos: `<x-fila-matriz>` tiene grises propios legítimos —el
    `border-gray-200` de su separador, el `text-gray-900` de su título— que no
    son del mapa.

FV6: completa (commit 7fb869f). `<x-tarjeta>` con 4 tests en aislado, rojo
verificado antes de escribirla. Suite 564 -> 568.
Hecha en la sesión de control, no por subagente: el plan traía su código
completo y era transcripción más tests.

FV7: completa (commit df26f85, revisión aprobada, un Menor sin efecto en código).
48 cajas convertidas en 29 vistas de operativo/ y components/. Suite sigue en
568 (3302), ningún test tocado.
  - **Trampa de Blade que encontró el implementador, y es lo único del diff que
    cambia comportamiento:** `@js(...)` **no se compila dentro del atributo de
    un `<x-componente>`**. Blade compila la etiqueta del componente antes que
    las directivas, así que `@js()` queda como texto literal y **rompe la
    hidratación de Alpine**. Salió como cuatro tests rojos en FET, FIT, Paisaje
    y Valoración Territorial. Sustituido por `{{ Illuminate\Support\Js::from() }}`.
    El revisor lo verificó en el código de Laravel —`@js($x)` compila a
    `Js::from($x)->toHtml()`, y `{{ Js::from($x) }}` a `e(Js::from($x))`, que
    con un `Htmlable` es el mismo `toHtml()`— y **empíricamente byte a byte**
    con comillas simples, dobles, `<script>` y `&`.
  - Verificado que están **todos** los sitios: de los 7 `@js(` de la aplicación,
    los 3 que no se tocaron están sobre un `<div>` nativo, donde el fallo de
    orden de compilación no aplica.
  - El revisor leyó completos los 29 ficheros y comprobó el cierre de etiqueta
    uno a uno, la conservación de `flex`/`space-y-*`/`divide-y` —que actúan
    sobre hijos directos— y la regla de `:padding="false"` en las 28
    conversiones donde aplicaba.
  - MENOR: la aritmética del informe del implementador («562 + 4 = 568») está
    mal; el número final es correcto, la frase no.

FV8: completa (commit 28984f4). 11 cajas en 8 vistas de admin/ y profile/.
Suite sigue en 568 (3302), ningún test tocado.
Revisada en la sesión de control, no por subagente.
  - Balance de etiquetas comprobado en los ocho ficheros: `<x-tarjeta>` cierra
    tantas veces como abre y los `<div>` cuadran uno a uno.
  - `profile/edit` llevaba `p-4 sm:p-8` —padding adaptativo, no `p-6`—, así que
    va con `:padding="false"` conservando el original. Es la misma regla de la
    tarea anterior.
  - Las tres tarjetas de `admin/dashboard` tenían `border-gray-200` a opacidad
    completa y pasan al `/80` del componente. Es la unificación que persigue la
    fase, no una regresión: la diferencia es imperceptible y el objetivo era
    justo que dejara de haber una variante por vista.
  - Ningún `@js(` en estos directorios, así que la trampa de la tarea anterior
    no aplicaba.
  - **Error de consola reportado por el implementador: PREEXISTENTE, no de esta
    rama.** `TypeError: ...[n] is not a function` en el bundle, visible en la
    portada pública y en /login. Comprobado que `git diff main...HEAD` sobre
    `resources/js/`, `package.json` y `vite.config.js` está **vacío**: la rama
    no toca una línea de JavaScript. Queda anotado como pendiente propio; huele
    a que `resources/js/app.js` llama a `Alpine.start()` en la línea 7 y hace
    `import './inventario_categoria.js'` en la 9, y los imports de ES se izan,
    así que ese módulo corre antes del arranque de Alpine.
  - **A revisar por quien mire esta rama:** el subagente de esta tarea inició
    sesión con las credenciales sembradas para verificar en el navegador. Yo no
    lo hago y así se lo dije al usuario; conviene que el encargo de los
    subagentes lo diga también.

FV9: completa (commit 4a8023e). `<x-boton>` con 7 tests, rojo verificado.
Suite 568 -> 575. Hecha en la sesión de control.
  - Siete tests y no los seis del plan: añadido uno para el `type="submit"` por
    defecto. Es lo que hace que sustituir un `<button type="submit">` no cambie
    nada, y que sea sobreescribible es lo que permite los `type="button"` de
    Alpine. Sin esa garantía, un botón que solo debía abrir un menú enviaría su
    formulario, y **ningún test de vista lo vería** porque el HTML sigue siendo
    válido. Es justo el tipo de fallo que la Tarea 10 puede introducir 12 veces.

FV10: completa (commits 20983c1..65e0c0e). 49 botones convertidos en 25 vistas.
Suite sigue en 575 (3320).
  - **Segundo choque con la restricción «ningún test existente se modifica», y
    de la misma familia que el primero.** `<x-boton>` lleva
    `disabled:opacity-50`, y cuatro tests hacían `substr_count($html,
    'disabled')` o `assertSee('disabled')` para afirmar cuántos controles están
    deshabilitados. Cuentan **la palabra, no el atributo**, así que funcionaban
    solo mientras ninguna clase de Tailwind la contuviera. El implementador
    paró y revirtió nueve botones en FIT, FET, Paisaje y Valoración
    Territorial, que es lo correcto dado el encargo.
  - Resuelto contando el atributo de verdad: un `contarDeshabilitados()` en
    `Tests\TestCase` con un patrón que exige espacio delante —para no casar con
    `x-bind:disabled`— y prohíbe `:` o guion detrás. **No afloja nada**: siguen
    exigiéndose los mismos 0, 72 y 36, y ahora significan lo que su nombre
    dice. Después se convirtieron los nueve botones revertidos.
  - Los tres tipos de excepción se respetaron: el validar verde de
    `<x-resumen-lista>`, el «Guardar Borrador» de la barra lateral, y los
    botones pequeños de borrar en tablas.
  - Ningún `type="button"` entre los convertidos, comprobado uno a uno: era el
    riesgo silencioso —`<x-boton>` pone `type="submit"` por defecto, así que
    dejarse el `type` habría hecho que un botón de Alpine enviara su
    formulario, sin que ningún test lo viera—.
  - Purgado verificado tras `npm run build`: las nueve clases de las tres
    variantes y los dos tamaños están en el CSS.
  - Un intento mío de hacer la conversión con una barrida de expresiones
    regulares dejó las etiquetas descuadradas a medio camino; revertido y
    rehecho fichero a fichero.

FV11: revisión final hecha (paquete 18dd323..65e0c0e, 20 commits). Cuatro
Importantes, tres arreglados en 61ddd58. Suite 575 -> 576.

**El hallazgo de más recorrido:** el ancho no lo probaba ninguna página. Al
quitar de los seis tests la aserción de `max-w-7xl`, `ContenedorTest` quedó
probando el componente en aislado y nadie el montaje: se podía **borrar
`<x-contenedor>` del layout con los 575 tests en verde** y la aplicación
entera sin ancho ni márgenes. Añadido un test que afirma sobre el `<main>` —no
sobre la página, que pasaba igual porque la barra y la cabecera llevan su
propio contenedor: comprobado quitándolo—. Rojo verificado.

Arreglado también:
  - **La pantalla de login estaba fuera del sistema.** Se había convertido
    `auth-card`, que **no lo usa nadie** (cero referencias, ahora borrado),
    mientras `layouts/guest` seguía con `bg-gray-100` y su propia caja de
    `shadow-md` sin borde. Primera pantalla de la aplicación, y la única con
    otro fondo y otra tarjeta.
  - **Las tarjetas de zona perdían la esquina:** caja a `rounded-xl` (12px) y
    cabecera de imagen en `rounded-t-lg` (8px) sin `overflow-hidden`, así que
    la imagen asomaba por encima del borde. Es la vista **por defecto** del
    dashboard operativo.
  - **«Regresar» era un cuarto sistema de botón.** Sus clases eran
    `<x-boton variante="secundario">` letra por letra; seis matrices lo
    repintaban de azul con `!important` y dos lo dejaban tal cual.

## Lo que la revisión encontró y NO se arregló, con su motivo

- **Breeze sigue vivo:** `primary-button` y `danger-button` los usan las cinco
  vistas de `auth/` y los tres parciales de `profile/`. Solapan con `primario`
  y `peligro`. El plan no los nombró; van a la Fase 1.
- **Cuatro botones sueltos sin convertir:** `evaluacion_paisaje/ponderacion:122`,
  `evaluacion_valoracion_territorial/ponderacion:33` (el único amarillo del
  sistema), `inventarios/show:13`, y los dos pequeños de `admin/zonas/index`.
- **Dos `<x-contenedor ancho="estrecho">` anidados** dentro del del layout, en
  `admin/lugares/form` y `operativo/frecuentacion/form`. **Impuesto por mi
  plan**, que lo prescribe literalmente. En escritorio el ancho útil sale
  idéntico; por debajo del tope el padding se aplica dos veces (311px en vez de
  343 a 375px de ancho).
- **`<x-badge>` no se usa en ninguna parte.** Seis tests y cero adopción: la
  clave `insignia` y `NOMBRES_ESTADO` no llegan a ninguna pantalla. El diseño
  no prometía adoptarlo, pero conviene decirlo.
- **La excepción de `<x-resumen-lista>` se apoyaba en una premisa falsa mía:**
  dije que convertirla exigía mover su `flex` a un hijo, y no es cierto —
  `$attributes->merge` concatena sobre el mismo `<div>`—.
- **`evaluacion_potencialidad/form` es el único fichero entero fuera del
  sistema:** cero `<x-tarjeta>`, un `<style>` en línea con `#e2e8f0` y
  `#1e293b` copiados a mano, y `class="pt-area area-{{ $color }}"` construida
  por concatenación. Hoy no se purga porque `area-*` es CSS propio; el día que
  se migre a Tailwind, los seis colores desaparecen en silencio.
- **`storage/framework/views` está en el `content` de Tailwind:** una caché
  rancia mantiene viva en el CSS local una clase ya borrada del Blade. La
  comprobación fiable es `php artisan view:clear` antes de `npm run build`.
- **`resources/js/**/*.js` no está en el `content`:** hoy inofensivo, pero la
  primera clase que alguien escriba en JS se purga en silencio.
