# Fundación visual — Fase 0 del rediseño de interfaz

**Fecha:** 2026-08-12
**Estado:** diseño aprobado, pendiente de plan de implementación

## De dónde viene

El encargo era un rediseño completo de la interfaz: «se siente vacía, rígida y
desaprovecha casi el 60 % del ancho en monitores estándar; las tarjetas y
elementos gráficos se ven planos». Pedía cinco cosas —layout y navbar,
dashboard, detalle de zona, formularios, y un lenguaje visual— y nombraba
**shadcn/ui** y **React**.

**Son cuatro familias de vistas independientes más un lenguaje visual que las
cruza todas**, así que no caben en un solo diseño. Este documento cubre **solo
la fundación**, que es lo que las otras cuatro consumen.

El orden no es arbitrario. Si se empieza por el dashboard, esa vista inventa sus
bordes, sombras, radios y badges; luego el detalle de zona inventa los suyos y
no coinciden. Es el fallo que este proyecto lleva semanas quitando —el mismo
conocimiento en dos sitios, que se separan— solo que en CSS, donde **nada falla
en rojo cuando divergen**.

## Lo que se midió antes de proponer nada

No es que falte diseño: es que no hay sistema, y cada vista reinventó el suyo
con una variación mínima.

| Medida | Hoy |
|---|---|
| Ficheros con contenedor propio | **39**, en **9 anchos distintos** |
| Tarjetas escritas a mano | **52** repeticiones de `bg-white shadow-sm sm:rounded-lg` |
| Variantes del botón primario | **12** (`py-2 px-5`, `py-2 px-4`, `py-3 px-6`; `rounded`, `rounded-lg`, `rounded-md`; en cuatro colores) |
| Usos de `gray-*` | **1056** |
| Usos de `slate-*` | **0** |

**La queja del ancho es correcta.** El `<main>` de `layouts/app.blade.php` no
tiene contenedor: cada vista pone el suyo, y 24 de ellas se quedan en
`max-w-7xl` (1280 px), que en un monitor de 1920 es el 67 %.

## Lo que se descartó, con el motivo escrito

**React + Inertia + shadcn/ui.** shadcn es React puro y esta aplicación es
**Blade + Alpine.js 3 + Tailwind 3**, sin una línea de React en `package.json`.
Migrar significa reescribir ~40 vistas Blade y rehacer los 553 tests, que
comprueban HTML renderizado por el servidor. Es un proyecto de semanas durante
las cuales el sistema no se puede usar, no un rediseño. **Se adopta el lenguaje
visual de shadcn —bordes, sombras, radios, tipografía, controles segmentados—
en componentes Blade.** El aspecto final es el pedido; la pila no cambia.

**Notificaciones con badge de alertas.** No existe sistema de notificaciones:
solo el `Notifiable` de Breeze y el controlador de verificación de correo. Un
badge exige un dominio nuevo —qué genera una notificación, quién la lee, cuándo
se marca vista—. Es una funcionalidad, no maquetación. **Fuera de todas las
fases hasta que se diseñe como tal.**

**Buscador rápido `Cmd+K`.** Una paleta de comandos sobre 80 rutas de las que el
perfil operativo usa una docena, cuando las listas de admin ya tienen buscador.
**Se replantea en la Fase 1 (navbar), no aquí.**

## Decisiones

| # | Decisión | Elegido |
|---|----------|---------|
| 1 | Pila | Blade + Alpine, con la estética de shadcn |
| 2 | Troceado | Cinco fases; esta es la 0 |
| 3 | Contenedor | Fluido con tope de **1440 px** |
| 4 | Paleta | `gray` se **redefine** como alias de `slate`, sin tocar los 1056 usos |
| 5 | Adopción | Se crean **y se adoptan**: las 52 tarjetas y los 12 botones migran en esta fase |

Sobre la 5 jugaba en contra que es mucha edición mecánica. Pesó más que, si solo
se crean los componentes sin usarlos, **la Fase 0 no se nota al abrir la
aplicación** y quedan dos estéticas conviviendo durante las cuatro fases
siguientes.

---

## El contenedor

`<x-contenedor>` en el `<main>` del layout, y se borran los de las vistas.

```blade
@props(['ancho' => 'normal'])   {{-- normal | estrecho --}}
```

- `normal`: `w-full max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8`
- `estrecho`: `w-full max-w-2xl mx-auto px-4 sm:px-6 lg:px-8`

Fluido con tope: en monitores grandes se comporta como un ancho fijo de 1440, y
en portátiles de 1280-1366 aprovecha todo el ancho en vez de dejar el margen
muerto del contenedor.

Existe `estrecho` porque no todas las vistas mienten al ser estrechas: un
formulario de cuatro campos a 1440 px es **peor**, no mejor.

**La regla para elegir, y no admite interpretación:** `estrecho` va donde el
contenedor de página es **hoy** `max-w-2xl` o menor; todo lo demás va a
`normal`. Son dos ficheros: `admin/lugares/form.blade.php` y
`operativo/frecuentacion/form.blade.php`.

Se dice así de literal a propósito. La regla intuitiva —«los formularios de alta
y edición van estrechos»— habría estrechado `admin/users/form.blade.php` y
`admin/zonas/form.blade.php`, que hoy son `max-w-7xl`. Eso es cambiar la
estructura de una vista, y esta fase no cambia estructuras: si esos dos
formularios se ven mal anchos, se arregla en la fase que les toque, con la
decisión escrita.

### La distinción que hay que hacer fichero a fichero

Los 39 ficheros medidos **no son 39 contenedores de página**. Hay dos cosas
distintas escritas con las mismas clases:

1. **Contenedor de página** — el `max-w-* mx-auto` más externo, justo dentro de
   `<main>`. **Ese es el que se borra**, porque pasa al layout.
2. **Caja estrecha dentro de la página** — un bloque deliberadamente más
   angosto que su página: un cuadro de resumen, una tabla pequeña. **Esas se
   quedan**: son maquetación del contenido, no del documento.

`evaluacion_valoracion_territorial/ponderacion.blade.php` aparece con `5xl`,
`3xl`, `2xl`, `xl` y `sm` a la vez. No son cinco contenedores de página: es uno
más cuatro cajas interiores. **Arrancarlas rompería la vista.** El plan de
implementación debe exigir que se compruebe cuál es cuál en cada fichero, no
que se sustituya por búsqueda y reemplazo.

### El navbar y la cabecera

`layouts/navigation.blade.php` y el bloque `$header` de `layouts/app.blade.php`
llevan su propio `max-w-7xl`. **Los dos pasan al mismo contenedor**: si no, el
contenido de la barra y el título de la página quedan desalineados con el cuerpo
—visible de inmediato, y en todas las páginas—.

## La paleta, sin tocar 1056 líneas

Tailwind deja redefinir la escala de color en la configuración:

```js
// tailwind.config.js
import colors from 'tailwindcss/colors';

theme: {
    extend: {
        colors: { gray: colors.slate },
    },
},
```

Los 1056 `*-gray-*` siguen escritos igual —cero diff— y todos pasan al tinte
azulado de `slate` a la vez. **Descarta por construcción el único riesgo real**,
que era que convivieran los dos grises: `gray` es neutro y `slate` tira a azul,
y mezclados se nota; migrados juntos, no.

El fondo pasa de `bg-gray-100` a **`bg-gray-50`**, que con el alias vale
exactamente `#F8FAFC` —`slate-50`—, el color pedido.

### Se sigue escribiendo `gray`, también en el código nuevo

Tentaba escribir `slate-*` en los componentes nuevos, ya que es lo que de verdad
se pinta. **Sería un segundo nombre para lo mismo**: dos formas de decir el
mismo color, indistinguibles en pantalla y distintas en el fuente, que es el
tipo de duplicación que este documento existe para evitar.

La regla es una sola: **todo el código escribe `gray-*`, y `tailwind.config.js`
es el único sitio que decide qué significa gris.** Cambiar de paleta vuelve a
ser una línea.

## Tipografía

Figtree → **Inter**, en el `<link>` de `fonts.bunny.net` que ya se usa y en
`fontFamily.sans` de `tailwind.config.js`. Dos líneas. Se mantiene bunny.net:
cambiar a fuentes auto-alojadas es una mejora real —una petición externa menos—
pero no es de esta fase.

## Los primitivos

Los tres siguen la regla que ya rige en `<x-barra-lateral-formulario>` y
`<x-resumen-lista>`: **pintan lo que les dan, no lo derivan**. Ninguno consulta
la base de datos ni mira modelos.

### `<x-tarjeta>`

```blade
@props(['padding' => true])
```

`bg-white border border-gray-200/80 rounded-xl shadow-sm`, con `p-6` salvo que
`:padding="false"` —que lo necesitan las tarjetas que envuelven una tabla a
sangre—.

### `<x-badge>`

```blade
@props(['estado'])   {{-- sin_empezar | sin_estado | borrador | validada | bloqueada --}}
```

Los cinco valores **no son inventados**: son exactamente los que ya produce
`App\Servicios\EstadoZona` y consume `<x-fila-matriz>`.

El texto sale del estado —«Borrador», «Validada»— salvo que se pase una ranura,
que lo sustituye conservando el color. Es lo que resuelve «Listo para validar»,
que **no es un estado** sino un `borrador` que además está completo: si fuera un
valor más del mapa, el sistema tendría seis estados en la interfaz y cinco en el
servicio.

### `<x-boton>`

```blade
@props(['variante' => 'primario', 'tamano' => 'normal', 'href' => null])
```

Variantes `primario` / `secundario` / `peligro`; tamaños `normal` / `grande`.
Con `href` renderiza un `<a>` con el mismo aspecto, porque hoy la mitad de los
«botones» son enlaces y la otra mitad `<button>`, y esa diferencia es de
comportamiento, no de estilo.

Clases completas en un `match`, nunca construidas por concatenación: Tailwind
purga las que no aparezcan literalmente en el fuente.

## Una sola fuente para el mapa de estados

`<x-fila-matriz>` ya tiene su propio mapa de `estado => color`. Si `<x-badge>`
escribe el suyo, **son dos tablas del mismo conocimiento**, y el día que se
añada un estado o se cambie un color, una de las dos se queda atrás sin que
nada falle.

El mapa se mueve a **un solo sitio** —una constante `ESTILOS_ESTADO` en
`App\Servicios\EstadoZona`, junto a los estados que ya declara— y los dos
componentes lo consumen. Cada estado lleva tres claves: `icono` y `detalle`, que
son las que `<x-fila-matriz>` ya usa, e `insignia`, que es la que necesita el
badge. Así ninguno de los dos tiene que cambiar lo que pinta para compartir de
dónde lo lee.

La apariencia de `<x-fila-matriz>` **no cambia ni un píxel**: se le cambia la
fuente, no el resultado. Los tests que ya lo cubren tienen que seguir en verde
sin tocarlos, y eso es precisamente la prueba de que el movimiento fue neutro.

## La adopción

- **39 ficheros a inspeccionar** —no 39 contenedores— para quitar el de página
  donde lo haya, dejando intactas las cajas interiores. Varios de esos ficheros
  solo tienen cajas interiores y no se tocan.
- **52 tarjetas** → `<x-tarjeta>`.
- **12 variantes de botón** → `<x-boton>`.

Es edición mecánica y hay bastante; va troceada en tareas por familia de vistas
para que una revisión pueda rechazar una sin bloquear las demás.

## Lo que NO entra en esta fase

**Ninguna vista cambia de estructura.** Ni columnas nuevas, ni KPIs, ni
breadcrumbs, ni el navbar más allá de su contenedor, ni tablas ordenables, ni
tocar `<x-barra-lateral-formulario>` ni `<x-criterio-pildoras>`. Solo ancho,
color, tipografía y sustituir lo repetido por lo compartido.

Esas cinco cosas son las Fases 1 a 4 y tendrán su propio diseño.

## Qué se prueba

**Los 553 tests que ya existen son la red principal**, y a propósito: comprueban
HTML renderizado por el servidor, así que si un componente se traga contenido o
pierde un botón, se ponen rojos sin que haya que escribir nada nuevo. La suite
tiene que seguir en verde **sin modificar ningún test existente**; un test que
haya que tocar es la señal de que la sustitución cambió comportamiento, no solo
aspecto, y hay que entender por qué antes de seguir.

Además, tests propios en aislado para los tres primitivos, como los 11 de
`<x-resumen-lista>`:

- `<x-contenedor>` da el ancho normal por defecto y el estrecho cuando se pide.
- `<x-badge>` pinta los cinco estados, y **revienta con un estado desconocido**
  en vez de renderizar sin color.
- `<x-boton>` da `<button>` sin `href` y `<a>` con él, y las tres variantes
  salen con clases distintas.
- Un test comprueba que `<x-badge>` y `<x-fila-matriz>` **dan el mismo color
  para el mismo estado**. Es el que impide que el mapa vuelva a duplicarse.

Y una comprobación de purgado: tras `npm run build`, las clases de las tres
variantes de botón y de los cinco estados de badge tienen que estar en el CSS
generado.

## Restricciones

- Clases de Tailwind completas, nunca construidas por concatenación.
- Nada por debajo de 14 px salvo insignias. Sin `uppercase` ni `tracking-widest`.
- Comentarios en castellano explicando el **porqué**.
- Suite en verde, **sin tocar tests existentes**. Hoy son **553**.
- `npm run build` limpio y verificado, no solo ejecutado.

## Riesgos conocidos

**El purgado de Tailwind.** Es el riesgo real de esta fase: un color que hoy
aparece literalmente en una vista puede dejar de aparecer al mover esa clase
dentro de un `match` de PHP, y el CSS se genera sin él. No falla ningún test
—el HTML es correcto, solo que sin estilo—. Por eso la comprobación de purgado
es parte del criterio de terminado y no un extra.

**El alias `gray` → `slate` es global de verdad.** Alcanza también a las vistas
de Breeze que nadie ha mirado en meses (`profile/`, `auth/`). Es lo que se
quiere —coherencia— pero conviene abrirlas una vez en el navegador antes de dar
la fase por cerrada.

**El contenedor fluido cambia el punto en que la barra lateral de los
formularios se oculta**, que hoy es `lg` (1024 px). No se toca en esta fase,
pero si al ensanchar se ve raro entre 1024 y 1280, se anota para la Fase 4 en
vez de arreglarlo aquí.
