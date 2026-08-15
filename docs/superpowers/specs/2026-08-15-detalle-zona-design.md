# Fase 3 del rediseño de interfaz — detalle de zona en dos columnas

**Fecha:** 15 de agosto de 2026
**Fase:** 3 de 4. La 0 —fundación—, la 1 —navbar y migas— y la 2 —dashboard
«Mis Zonas»— están fusionadas.
**Alcance:** `operativo/zona/panel` (`resources/views/operativo/zona/panel.blade.php`),
la página que sirven los tres roles para trabajar una zona. Ninguna otra
pantalla se toca.

## El encargo, contra lo que hay hoy

El traspaso describe esta fase como «detalle de zona en dos columnas, con
panel lateral de información de la zona». Al ir al fichero, la página de hoy
es de **una sola columna**: una tarjeta de identidad arriba (lugar, jefe,
insignia de rol, barra de progreso) y debajo, una tarjeta por cada fase del
estudio (`Registro::GRUPOS`) con sus filas de matriz.

A diferencia de la Fase 2 —donde dos de las tres cosas del encargo ya
existían—, aquí **la propia estructura de dos columnas no existe todavía**.
Sí existen, y se reutilizan, dos piezas que la Fase 2 construyó pensando en
esto: `<x-badge>` («su primer consumidor natural son las tarjetas de zona de
la Fase 2» decía el propio componente, y ahora tiene un segundo consumidor
aquí) y `<x-desglose-estados>`, que hoy solo pinta el dashboard.

## Inventario de primitivos, antes de proponer nada

El encargo pide inventariar qué existe antes de tocar la vista, porque «si
una vista se rediseña antes de que exista el primitivo que necesita, inventa
el suyo y aparece la segunda fuente de verdad de siempre». Este es el
resultado de mirar, no de suponer:

| Primitivo | Qué hace hoy | Para la Fase 3 |
|---|---|---|
| `<x-tarjeta>` | La caja blanca | **Tal cual.** Envuelve el panel lateral y cada grupo de matrices, como ya hace |
| `<x-migas>` | Rastro de navegación | **Tal cual.** Vive por encima del split de columnas, sin cambios |
| `<x-badge>` | Una insignia de estado, 5 colores fijos en `EstadoZona::ESTILOS_ESTADO` | **Tal cual**, indirectamente, a través de `<x-desglose-estados>` |
| `<x-desglose-estados>` | Las tres insignias de una zona (validadas/borrador/sin empezar) | **Tal cual, adoptado por segunda vez.** Sustituye la fracción «X de Y validadas» del panel lateral, igual que ya sustituyó a «hechas / total» en el dashboard |
| `<x-fila-matriz>` | Una fila de la lista de matrices | **Tal cual.** Sigue viviendo en la columna principal, sin `:zona` (una sola zona en esta página) |
| `<x-resumen-lista>` | Franja de conteo sobre una tabla (actores, sitios) | **No aplica.** Es de otra naturaleza —resume una lista de filas propia, no el estado de las diez matrices— y esta página no la usa hoy ni la necesita |
| `<x-barra-lateral-formulario>` | La barra lateral de un FORMULARIO de matriz | **No aplica, y no hay que confundirla.** Indexa bloques de criterios dentro de una matriz; el panel lateral de esta fase indexa datos de la ZONA. Se parecen en la pantalla —las dos son un `<aside>` fijo a la derecha o la izquierda— y significarían cosas distintas, que es el error que este proyecto ya nombró una vez a propósito de `<x-resumen-lista>` |
| `<x-icono>` | Los iconos de instrumento, uno por matriz | **Tal cual**, sin uso nuevo — ver más abajo por qué no se usa uno nuevo para «lugar» |
| `EstadoZona` | Traduce zona + usuario a filas y a progreso | **Se ensancha**: un método de instancia nuevo, `desglose()`. Ver la sección siguiente |

**Ningún componente Blade nuevo hace falta.** El panel lateral no se extrae a
su propio `<x-...>`: tiene un solo consumidor (esta página), y este proyecto
ya tiene un precedente de esperar a que un primitivo tenga un segundo
consumidor de verdad antes de nombrarlo —`<x-badge>` estuvo seis ramas sin
usar, documentado, hasta que lo tuvo—. El envoltorio de dos columnas tampoco
se extrae: `evaluacion_fit/form.blade.php` y sus siete hermanos ya resuelven
el mismo problema —contenido principal + aside fijo— con un `<div
class="lg:grid lg:grid-cols-[...]">` escrito directamente en la vista, no un
componente. Esta fase sigue esa misma forma.

### El precedente que el encargo pide vigilar, comprobado aquí

El aviso explícito es el de `<x-barra-lateral-formulario>`: calculaba su
cabecera («X de Y respondidos») dando por hecho que ese número es común a las
diez matrices, y era falso para Potencialidad, cuyo denominador lo elige el
Jefe de Zona. Comprobado si algo parecido acecha en esta fase:

**`EstadoZona::desglose()` NO puede repetir ese error, y por un motivo
estructural, no por cuidado.** No deriva nada por su cuenta a partir de un
supuesto: recorre `$this->evaluaciones`, el mismo array que el constructor ya
llena consultando `Registro::matrices()` fila por fila, y que `grupos()` y
`filaDeClave()` ya usan para pintar cada matriz individual. Si Potencialidad
tuviera un denominador distinto para el CONTEO de matrices validadas —no lo
tiene: cuenta como una más, sea cual sea su número interno de criterios—,
`grupos()` ya estaría mal desde antes de esta fase. `desglose()` no inventa
una segunda forma de leer el estado de una matriz: lee `$evaluacion->estado`,
que es el mismo campo que decide todo lo demás en esta clase.

## Decisiones

| # | Decisión | Elegido | Por qué |
|---|----------|---------|---------|
| 1 | Estructura | **`<div class="lg:grid lg:grid-cols-[320px_1fr] lg:gap-8 lg:items-start">`**, sin componente nuevo | Mismo mecanismo que ya usan los ocho formularios de matriz; nada que aprender de nuevo |
| 2 | Qué va en el panel lateral | **Lugar, jefe, equipo, descripción, y el progreso vía `<x-desglose-estados>`** | Las cinco preguntas que el encargo nombra explícitamente, cada una con veredicto propio (ver abajo) |
| 3 | Orden en el panel lateral | **Rol de quien mira → lugar → jefe → equipo → descripción → progreso** | De «quién eres aquí» a «qué falta», terminando justo antes de la columna que enseña qué falta |
| 4 | Posición del panel | **Izquierda, primero en el HTML**, columna principal a la derecha | Conserva el orden de lectura de hoy: la tarjeta de identidad ya es lo primero que se ve, arriba, antes de la lista. Cambiar a mano el orden con `order-*` para que aparezca visualmente distinto en escritorio que en el DOM no aporta nada aquí y sí lo aportaría confusión |
| 5 | Progreso | **Se sustituye «X de Y validadas» por `<x-desglose-estados>` + la misma barra de porcentaje** | Es literalmente el mismo cambio que la Fase 2 ya justificó y hoy este panel es la única pantalla operativa que se quedó con la fracción vieja |
| 6 | Móvil | **Se apila arriba: el panel lateral, en su sitio de siempre, luego la lista** | Es el mismo orden que la página ya tiene hoy en una sola columna; no hay nada que esconder porque no hay contenido redundante en otro sitio de la pantalla —al contrario que `<x-barra-lateral-formulario>`, cuyo botón de guardar SÍ está duplicado en el formulario y por eso ella se puede permitir ocultarse entera bajo `lg:` |
| 7 | Los tres roles | **El panel lateral dice lo mismo para los tres**, salvo la línea de rol | Es información de la ZONA, no de quien mira. Que dijera cosas distintas por rol es la clase de fuente doble que ya causó bugs de admin en este proyecto (ver «Trampas» más abajo) |
| 8 | El color de la insignia de rol «Equipo» | **Cambia de verde a `teal`** | Colisiona con el verde de `<x-badge estado="validada">` en cuanto las dos aparecen en la misma tarjeta por primera vez — hallazgo propio, ver abajo |

### Lugar, jefe, equipo, descripción, progreso — veredicto uno por uno

- **Lugar: sí, panel lateral.** Ya estaba en la tarjeta de identidad; se
  traslada tal cual.
- **Jefe: sí, panel lateral.** Igual que lugar, ya existía.
- **Equipo: sí, panel lateral, y es contenido nuevo.** Hoy la página de zona
  no dice quién es el equipo en absoluto —ni el jefe ni el equipo mismo lo
  pueden consultar desde aquí—. `Zona::equipo()` ya existe como relación
  (`belongsToMany` con pivote `asignado_at`); solo falta cargarla en el
  controlador y pintarla. `admin/zonas/index` ya muestra un `{{
  $zona->equipo_count }}` —un número, para escanear muchas zonas de un
  vistazo—; esta página es la de detalle de UNA zona, donde saber los
  nombres, no solo el número, es justo la información que falta.
- **Descripción: sí, panel lateral, y es contenido nuevo aquí.** Existe en
  el modelo (`Zona::$descripcion`) y ya se enseña en las tarjetas del
  dashboard, con línea de reserva `'Sin descripción disponible.'` cuando es
  `null`. Que se pueda leer en la tarjeta de fuera y desaparezca al entrar en
  la zona es un hueco, no una decisión: se rellena con la misma frase de
  reserva que ya usa el dashboard, para no inventar una segunda.
- **Progreso: sí, panel lateral**, pero cambia de forma —ver decisión 5—.

**Ruido, no incluido:** el recuento de inventario
(`$zona->inventarios()->count()`), que ya tiene su propia fila dentro del
grupo «Base territorial» en la columna principal —repetirlo en el panel
lateral sería el mismo dato en dos sitios sin que ninguno mande—.

## Un hallazgo que esta fase provoca, no que hereda

La tarjeta de identidad de hoy ya pinta una insignia de rol escrita a mano
—no `<x-badge>`, un `<span>` con su propio mapa de tres colores—:
`admin` → azul, `jefe` → morado, `equipo` → **verde**
(`bg-green-100 text-green-800`).

Hasta hoy eso convivía sin problema porque esta página **no usa insignias de
estado en ningún sitio** —`<x-fila-matriz>` pinta icono y texto en color,
nunca una píldora—. Al adoptar `<x-desglose-estados>` en el mismo panel
lateral, aparece por primera vez una píldora `bg-green-100 text-green-800
border-green-200` que significa **«N matrices validadas»**, en la misma
tarjeta que puede llevar la píldora de rol `bg-green-100 text-green-800` que
significa **«estás viendo esto como Equipo»**. Dos píldoras del mismo verde,
mismo tono exacto, mismo componente visual, para dos cosas que no tienen nada
que ver — en la tarjeta de un usuario de equipo con matrices validadas, las
dos aparecerían juntas.

Es la misma familia de error que este proyecto ya pagó con
`<x-insignia-clasificacion>` —un nombre y un color genéricos invitando a una
lectura que no tocaba— y que `EstadoZona::ESTILOS_ESTADO` existe para evitar
en un solo sitio. La corrección es mínima y no toca el mapa de estados en
absoluto: **la insignia de rol «Equipo» pasa de verde a `teal`**
(`bg-teal-100 text-teal-800`). `teal` no aparece en `ESTILOS_ESTADO` ni en
ningún otro color con significado de estado de matriz; sí se usa ya en dos
sitios de la aplicación (`insignia-tipo-mitchell-involucrados`,
`evaluacion_potencialidad/ponderacion`), pero en pantallas distintas de esta
—el conflicto que importa es el de una misma pantalla, no el de la paleta
global—. Admin (azul) y Jefe (morado) no colisionan con ningún color de
`ESTILOS_ESTADO` y se quedan igual.

## Lo que cambia en `EstadoZona`

**`desglose(): array`, un método de instancia nuevo.** Devuelve
`{hechas, borradores, sin_empezar, total}`, la misma forma que ya produce
`progresoDe()` para el dashboard, pero para la zona de ESTA instancia y sin
ninguna consulta nueva: recorre `$this->evaluaciones`, que el constructor ya
llena una vez por matriz validable. No llama a `progresoDe()` —que existe
para resolver MUCHAS zonas con un número fijo de consultas por lote, un
problema que aquí no hay: instanciar `EstadoZona` para una sola zona ya paga
esas diez consultas, y son las mismas diez que ya hacían falta para pintar
`grupos()`—.

**`validadas()` y `totalMatrices()` desaparecen.** Se ha comprobado —no
supuesto— que su único consumidor fuera de los tests de la propia clase es
`panel.blade.php`, la vista que esta fase reescribe:

```
grep -rn "->validadas()\|->totalMatrices()" --include=*.php --include=*.blade.php .
```

da exactamente dos sitios de producción: las dos líneas de
`panel.blade.php` que esta fase sustituye, y nada más. Mantener los dos
métodos junto a `desglose()`, que cuenta lo mismo (`hechas` es `validadas()`,
`total` es `totalMatrices()`) más el desglose que a ellos les falta, sería
la definición exacta de la «segunda fuente de verdad» que
`EstadoZona::ESTILOS_ESTADO` lleva un comentario entero explicando por qué
se evita. Se retiran junto con la vista que los usaba, no antes ni
separado.

## Casos límite

- **Sin jefe asignado:** `'Sin asignar'` — ya existía, se conserva la
  frase.
- **Sin equipo asignado:** `'Sin equipo asignado'` — nuevo, mismo patrón de
  reserva que jefe y descripción.
- **Sin descripción:** `'Sin descripción disponible.'` — la misma frase
  que ya usa el dashboard, copiada literal para que sea la misma frase y no
  una parecida.
- **Zona recién creada, sin ninguna evaluación:** el desglose sale entero en
  `sin_empezar` — comportamiento ya cubierto por `progresoDe()` y ahora
  replicado para una sola zona por `desglose()`; con test.
- **Las diez validadas:** ningún «0 en borrador» ni «0 sin empezar» se
  pinta —regla que `<x-desglose-estados>` ya trae hecha, no hace falta
  reimplementarla—.

## Cómo se ata

1. **`desglose()`, sobre el servicio, sin HTTP.** Una zona con una validada,
   una en borrador y ocho sin fila devuelve el reparto correcto —mismo test
   que ya existe para `progresoDe()`, adaptado a la instancia—.
2. **Los tres roles ven el mismo panel lateral.** Un test recorre admin,
   jefe y equipo sobre la misma zona y compara el HTML del panel lateral
   quitando solo la línea de rol.
3. **Equipo y descripción, con y sin datos.** Cuatro combinaciones: equipo
   vacío/con gente, descripción nula/con texto.
4. **El progreso viejo no se cuela.** `assertDontSee('validadas')` de la
   forma antigua —cadena exacta «de 10 validadas»— no sirve por sí sola
   —ver «Trampas» más abajo—: se comprueba en positivo que aparece
   `<x-desglose-estados>` (por sus insignias) y en negativo que no aparece
   ya el patrón `{{ $hechas }} de {{ $total }}`.
5. **El nombre de la zona no se repite de más.** `test_la_pagina_de_zona_no_
   repite_el_nombre_de_la_zona_en_cada_fila` sigue pasando sin tocarlo: el
   panel lateral no vuelve a escribir `$zona->nombre` en ningún sitio —ya lo
   dicen el `<h2>` del layout y las migas—.
6. **Verificación de navegador, manual —Playwright no está instalado en
   esta máquina, herencia de `restos-fase-0`—:** a 375 px el panel lateral
   se apila arriba sin encoger la columna principal ni producir scroll
   horizontal en `<body>`; a 1280 px las dos columnas están una junto a la
   otra y el panel lateral quedas fijo (`sticky`) al bajar por una zona con
   los cinco grupos; con una zona de pocas matrices sin grupos suficientes
   para desbordar la ventana, `sticky` no debe partir nada.

## Trampas de este proyecto que aplican aquí

- **`assertDontSee` de una sola cara.** Comprobar solo que «de 10 validadas»
  desaparezca no basta: si por un error el panel lateral entero dejara de
  pintarse, ese mismo assert pasaría en verde. Todo `assertDontSee` de esta
  fase lleva su contraparte positiva en el mismo test o en uno vecino.
- **Frases partidas en dos líneas.** El HTML de Blade parte `{{ $hechas }}
  de {{ $total }} validadas` en tokens separados por interpolación;
  `assertSee` con cadena exacta ya fallaba para esto en el código actual y
  seguiría fallando si algo se dejara a medio quitar. El test de «no se cuela
  el patrón viejo» compara contra el texto ya renderizado (`getContent()`),
  no contra el Blade fuente.
- **`Str::between()` sin delimitador.** No se usa en esta fase; queda dicho
  porque el encargo pide nombrarlo y no hay ningún fragmento de esta vista
  que dependa de él.
- **La regla de 14 px / sin `uppercase` / Tailwind sin concatenar no la
  comprueba ningún test automático — corrigiendo lo que el propio encargo
  da por hecho.** El encargo dice que `TipografiaUnicaTest` la comprueba;
  **no es cierto, y conviene decirlo antes de que alguien se apoye en un
  test que no existe**. Se ha leído `TipografiaUnicaTest.php` completo: sus
  cuatro tests comprueban una cosa distinta —que ninguna vista declare su
  propia familia tipográfica ni pida una fuente a otro proveedor—, no
  tamaños ni mayúsculas ni construcción de clases. La regla de 14 px es real
  y está escrita en cada spec de esta serie desde `fundacion-visual`, pero
  su propio plan (`2026-08-12-dashboard-y-formularios.md`, Tarea 4) ya lo
  dice explícito: **«No hay test automático para esto en el proyecto: es
  una revisión manual»**. Esta fase no usa ningún tamaño por debajo de
  `text-sm` (14 px) salvo la insignia de rol, no añade `uppercase`, y las
  clases de Tailwind del plan van completas y literales — y esa
  comprobación, aquí también, es de navegador y de lectura del código, no
  de suite.

## Lo que no entra, y por qué

- **La foto o el degradado de iniciales de la tarjeta del dashboard.** El
  encargo nombra lugar, jefe, equipo, descripción y progreso; la imagen no
  está en esa lista. Añadirla sería decorativa, no informativa, y esta
  fase sigue la disciplina de no ensanchar el alcance con candidatos que
  nadie pidió.
- **Un icono nuevo para «lugar», sustituyendo el 📍.** El traspaso lo deja
  anotado como resto pendiente («se arregla donde se arreglen los demás»),
  y esta página es justo ese sitio — pero se ha comprobado, no asumido, que
  el icono obvio ya está tomado: `'ubicacion'` es el icono de Frecuentación,
  que aparece como fila en la columna principal de esta misma pantalla.
  Ponerlo también en el panel lateral con un significado distinto —«dónde
  está la zona» en vez de «esta es la matriz de Frecuentación»— es el mismo
  error que ya se evitó a propósito dándole a Irritación su propio icono
  (`enfado`) para no compartir `brujula` con Percepción en la misma página.
  Los demás iconos del catálogo están igual de ocupados: cada uno es la
  identidad de una de las diez matrices que esta misma página lista. Se deja
  el 📍 como está, y se anota que el día que se quiera un icono de lugar
  hace falta uno nuevo, no uno prestado.
- **Renombrar o mover `EstadoZona::papel()`.** Sigue calculando lo mismo,
  solo cambia dónde se pinta su resultado.
- **Cualquier cambio a `<x-desglose-estados>` o `<x-badge>`.** Los dos se
  adoptan tal cual; no hace falta ensancharlos —a diferencia de lo que sí
  hizo falta con `<x-barra-lateral-formulario>`, que sí tenía una suposición
  falsa dentro—.
- **Las pantallas de admin** (`admin/zonas/index`, `admin/zonas/form`).
  Fuera de alcance, como en la Fase 2.
- **Tocar `<x-fila-matriz>` o el contenido de los grupos.** La columna
  principal es la misma lista de siempre, solo cambia de ancho.
- **Un botón o acción nueva en el panel lateral.** Es información, no
  acciones: validar sigue viviendo en el formulario de cada matriz
  (`accion_estado=confirmado`), como decidió `reabrir-matriz`, y el panel
  lateral no inventa un atajo que no existe en el resto del sistema.

## Riesgos

- **El panel lateral puede quedar más alto que la columna principal en una
  zona con pocos grupos visibles**, invirtiendo la relación de alturas
  habitual. `lg:items-start` en el contenedor evita que la columna corta se
  estire para igualar a la otra; no hay ningún test que pueda verlo, solo
  la verificación de navegador.
- **`sticky` sobre una tarjeta con contenido variable (equipo puede tener
  muchos nombres).** Un equipo largo alarga el panel lateral y podría
  empujarlo más allá de la ventana antes de que la columna principal
  termine de desplazarse. No es un defecto —el contenido manda sobre el
  efecto visual—, pero conviene mirarlo con una zona de equipo grande en la
  verificación de navegador.
- **La insignia de rol ya no es la única píldora verde-vs-otro-color de esta
  pantalla una vez que compite semánticamente con `<x-badge>`.** Se corrige
  aquí (decisión 8), pero es la clase de colisión que solo aparece al unir
  dos primitivos que antes vivían en pantallas separadas — vale la pena
  repasarla de nuevo si una fase futura junta más primitivos en una misma
  vista.
