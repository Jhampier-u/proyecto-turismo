# Fase 4 del rediseño de interfaz — la franja de estado de los formularios

**Fecha:** 15 de agosto de 2026
**Fase:** la cuarta y última del rediseño de interfaz. Las tres anteriores
—fundación, navbar y migas, dashboard, detalle de zona— están fusionadas.
**Alcance:** los ocho formularios de matriz. Ninguna otra pantalla.

## El encargo, contra lo que hay hoy

El encargo original decía: «consolidar el banner de borrador y la escala de
valoración en una sola franja compacta», y además pedía una barra lateral con
índice, progreso y botón de guardar, y un control segmentado para los
criterios.

**Las dos últimas ya existen** —`<x-barra-lateral-formulario>` y
`<x-criterio-pildoras>`, de fases anteriores—, así que de las tres cosas que
pedía el encargo solo queda una sin hacer. Esta fase hace esa.

Lo que hay hoy encima del primer criterio, en los ocho:

1. Las migas.
2. Las pestañas formulario/resultados.
3. Una línea suelta: «Última edición: Fulano, hace 3 días».
4. El banner de estado, ámbar si es borrador y verde si está validada.
5. La tarjeta de la escala de valoración, con su párrafo de tres líneas.

De la 3 a la 5 son tres cajas apiladas que dicen cosas sobre la misma
evaluación. La fase las funde en una.

## Inventario, antes de proponer nada

Comprobado leyendo las ocho vistas, no supuesto:

| Formulario | ¿escala? | Cómo la recibe | Variable de estado |
|---|---|---|---|
| FIT | sí, **4 niveles** (`0 Nulo · 1 Bajo · 2 Medio · 3 Alto`) | `$niveles` del controlador | `$estaConfirmado` / `$bloqueado` |
| FET | sí, **4 niveles** (`0 Nulo · 1 Bajo · 2 Medio · 3 Alto`) | `$niveles` del controlador | `$estaConfirmado` / `$bloqueado` |
| Paisaje | sí, 0/3/5 | array literal en la vista | `$estaConfirmado` / `$bloqueado` |
| Percepción | sí | `$niveles` del controlador | `$estaConfirmado` / `$bloqueado` |
| Potencialidad | sí | `$niveles` del controlador | `$isConfirmado` / `$soloLectura` |
| Valoración Territorial | sí, 0/1/2 | **el valor por defecto del componente** | `$estaConfirmado` / `$bloqueado` |
| Concentración | **no** | — | `$estaConfirmado` / `$bloqueado` |
| Irritación | **no** | — | `$estaConfirmado` / `$bloqueado` |

Tres hallazgos de ese inventario, que el encargo no anticipaba:

- **Siete redacciones distintas para «validada»**: «Evaluación Validada»,
  «Evaluación FET Validada», «Matriz de Paisaje Validada», «Matriz Validada»,
  «Índice de Irritación Validado», «Índice de Concentración Validado»,
  «Valoración Territorial Validada». Es la deriva que produce copiar el mismo
  marcado ocho veces, y es la prueba de que aquí toca componente.
- **Potencialidad no se convirtió nunca al sistema de diseño**: tiene un
  bloque `<style>` dentro de la vista, 16 atributos `style=` en línea y sus
  propias clases `.pt-*`. Es además el único donde el jefe activa y desactiva
  campos, así que arrastra avisos que los otros siete no tienen.
- **Valoración Territorial depende de un valor por defecto** para su escala:
  llama a `<x-leyenda-escala />` sin argumentos y se queda con el 0/1/2 del
  componente. Hoy acierta porque su escala es esa. El día que ese defecto
  cambie, VT enseñará una escala falsa y ningún test lo verá.

### Dos huecos que aparecieron al mirar, y no estaban anotados

**1. La barra lateral guarda sin avisar de lo que hace.**
`<x-aviso-reapertura>` —«guardarla la devolverá a borrador»— vive solo al
final del formulario, junto a los botones de allí. La barra lateral tiene su
propio «Guardar Borrador», que es el que está siempre a la vista, y no lleva
ningún aviso. **El jefe que use ese botón reabre una matriz validada sin que
nada se lo haya advertido.** Es anterior a esta fase.

**2. El aviso de solo lectura falta en tres de los ocho.**
`<x-aviso-bloqueo-matriz>` —«Solo el Jefe de Zona puede reabrir o editar una
matriz validada»— está en cinco formularios. Paisaje, Potencialidad y
Valoración Territorial nunca lo tuvieron.

## Decisiones

**1. Un componente nuevo, `<x-franja-matriz>`, y uno que se retira,
`<x-leyenda-escala>`.** El neto de componentes no sube. Las fases anteriores
prohibieron componentes nuevos cuando el marcado aparecía una o dos veces;
aquí aparece ocho, y las siete redacciones distintas son el resultado
documentado de no haberlo hecho antes.

**2. La franja deriva su estado, no lo recibe.** Con `$evaluacion->estado` y
`auth()->user()->esJefe()`, no con un prop booleano. Motivo concreto: siete
vistas llaman a eso `$estaConfirmado`/`$bloqueado` y Potencialidad lo llama
`$isConfirmado`/`$soloLectura`. Un prop convertiría esa incoherencia de
nombres en ocho oportunidades de pasar el booleano equivocado.

**3. `:niveles` es `null` por defecto, y `null` significa «sin escala».**
Me aparto a propósito de `<x-leyenda-escala>`, cuyo defecto es 0/1/2: con ese
defecto, «no tengo escala» y «tengo la escala corriente» se escriben igual, y
Concentración e Irritación recibirían una escala inventada. Con `null`, las
seis que sí tienen escala la pasan explícitamente —**incluida Valoración
Territorial**, que deja de depender de un valor por defecto—.

**4. De los dos párrafos explicativos, sobrevive uno, recortado.**
No valen lo mismo:

- El del banner explica **cómo funciona el sistema** («el Jefe de Zona debe
  revisar y confirmar para generar los resultados oficiales»). Se aprende una
  vez y luego se repite en los ocho formularios para siempre. **Se va.**
- El de la escala explica **cómo se puntúa bien** («elige la descripción que
  coincide con el territorio, no el número»). Eso no es funcionamiento, es
  método: si alguien lo ignora, el dato sale peor y nada lo detecta. **Se
  queda**, recortado a esa frase.

  El texto exacto que sobrevive, para que el plan no lo reinvente:

  > Elige la descripción que coincide con el territorio, no el número.

  Se van las otras dos partes del párrafo actual: «Cada criterio define qué
  significan sus niveles:», que es preámbulo, y «Estas etiquetas son solo una
  guía del sentido general de la escala», que repite lo mismo con otras
  palabras.

**5. El estado de la matriz y lo que tú puedes hacer con ella son dos cosas,
y se pintan distinto.** Hoy una matriz validada pinta el mismo verde para
todos: el del equipo lee «todo correcto» y descubre que no puede tocar nada
al bajar hasta el final. Desde esta fase el verde queda para «validada y
todavía puedes editarla»; cerrada se pinta neutro y se dice en la primera
línea de la página.

Esto no es una preferencia estética: `CLAUDE.md` recuerda que una revisión de
una fase anterior encontró **«una franja que pintaba en verde un estado
bloqueado»**. Hacerlo al revés aquí sería reintroducir a sabiendas un defecto
que este proyecto ya pagó.

**6. «Validada» a secas, sin el nombre de la matriz.** Ese nombre ya aparece
tres veces antes de llegar a la franja: en la cabecera, en las migas y en la
pestaña activa. Las siete redacciones se funden en una.

**7. La franja describe; la advertencia acompaña al botón.** El aviso
«guardarla la devolverá a borrador» no dice *qué es* la matriz, sino *qué va
a hacer tu clic*, así que va junto a cada control que lo provoca: se queda al
final del formulario y **se añade a la barra lateral**, que es el hueco 1. La
franja se queda descriptiva.

**8. `<x-aviso-bloqueo-matriz>` se retira, absorbido por el tercer estado.**
El «Validada · solo lectura» de la franja dice lo mismo, antes de bajar, y en
los ocho en vez de en cinco. Cierra el hueco 2.

## Los tres estados

| Estado | Cuándo | Color | Contenido |
|---|---|---|---|
| Borrador | no confirmada | ámbar | «Borrador» · escala · la frase de método · última edición |
| Validada | confirmada + eres el jefe | verde | «Validada» · escala · quién y cuándo |
| Validada · solo lectura | confirmada + no eres el jefe | neutro | «Validada · solo lectura» · escala · quién y cuándo |

La escala aparece en los tres, también en los de solo lectura: las píldoras de
criterio siguen mostrando valores, y sin la escala no se sabe qué significa
un 2.

## Dónde se coloca

Dentro de la columna izquierda de la rejilla `lg:grid-cols-[1fr_256px]` que
los ocho ya tienen, justo donde está hoy el banner. **No** por encima de la
rejilla a todo lo ancho: la barra lateral empieza a esa misma altura, y una
franja de ancho completo la empujaría hacia abajo —perder por un lado lo que
se gana por otro—.

## Casos límite

- **Concentración e Irritación** no pasan `:niveles`: su franja lleva estado y
  última edición y nada más. Con el `null` por defecto eso no hay que
  escribirlo, es lo que sale de no decir nada.
- **Potencialidad** recibe la franja como los demás. Sus dos avisos propios
  —«solo el Jefe puede activar o desactivar campos» y el de modo jefe— se
  quedan **fuera** de la franja y donde están: no son el estado de la matriz,
  son permisos sobre su configuración, que solo existe en esa pantalla.
- **Una evaluación que no existe todavía** (`$evaluacion->exists === false`):
  no hay ni autor ni fecha, así que la franja pinta «Borrador» y la escala sin
  la parte de última edición, igual que hoy hace el `@if($evaluacion?->exists
  && $evaluacion->user)` de cada vista.
- **El flash de éxito** se queda separado. Aparece una vez tras guardar y
  desaparece; meterlo en una franja permanente sería mezclar dos ciclos de
  vida distintos.

## Cómo se ata

**Siete aserciones existentes se migran, no se borran.**
`ConcentracionTest:235`, `EvaluacionesTest:265,330,413`, `IrritacionTest:426`
y `ReabrirMatrizTest:271,283` comprueban hoy la frase «Solo el Jefe de Zona
puede reabrir o editar una {matriz|evaluación} validada». La frase se va con
el componente, pero lo que comprueban —que el equipo ve que está cerrada, y
que **deja de verlo** tras reabrir— es exactamente lo que el tercer estado
tiene que seguir garantizando. Pasan a apuntar al texto nuevo.

Tests nuevos:

- Un recorrido por `Registro` que exija la franja en **las ocho entradas de
  `'tipo' => 'matriz'`** —comprobado: son exactamente ocho, y son estos ocho
  formularios; Involucrados y Frecuentación son `'actores'` y `'sitios'` y no
  tienen formulario de criterios—. Hermano del
  `test_la_pagina_muestra_todas_las_entradas_del_registro` que ya existe. Es
  el que hace que no dependa de que nadie se acuerde de la octava vista.
- Los tres estados, por rol.
- **Que el estado de solo lectura no lleve la clase verde.** Es el defecto que
  `CLAUDE.md` recuerda; sin un test que lo fije, vuelve.
- Que las seis con escala la pinten y las dos sin escala no.
- Que los textos retirados no reaparezcan en ninguna de las ocho vistas.
- Que la barra lateral avise de la reapertura cuando la matriz está validada
  y quien mira es el jefe.

## Verificación de navegador

Lo que ningún test verá, y que tiene su propia tarea:

- Si la franja envuelve mal a 375 px. **FIT y FET tienen cuatro niveles de
  escala, no tres**, así que son el caso ancho y hay que mirarlos a ellos.
- Si el neutro y el verde se distinguen de verdad al mirarlos, no solo en el
  nombre de la clase.
- Si el CSS a medida de Potencialidad pelea con la franja.
- La regla de siempre: nada por debajo de 14 px salvo insignias, sin
  `uppercase`.

No hace falta Playwright: la Fase 3 cerró su verificación con el navegador de
la propia sesión, midiendo sobre el DOM.

## Lo que no entra, y por qué

- **La conversión del CSS de Potencialidad.** Su `<style>` y sus 16 `style=`
  se quedan salvo el trozo del banner que la franja sustituye. Convertirla
  entera haría de la última fase la más grande de las cuatro, y sobre la única
  pantalla sin tests de maquetación que la sujeten. Queda como deuda con
  nombre propio.
- **`<x-barra-lateral-formulario>`**, más allá de añadirle el aviso de
  reapertura. Ni el índice, ni el progreso, ni el botón.
- **`<x-criterio-pildoras>`**, que ya es el control segmentado que el encargo
  pedía.
- **El badge de notificaciones**, que no es maquetación: no hay sistema de
  notificaciones que mostrar.
- **Los tres restos de la Fase 3** —orden del equipo, `descripcion` vacía,
  N+1 de `firma()`—, anotados en el traspaso.

## Riesgos

- **Ocho vistas tocadas a la vez.** El riesgo no es cada una, es que la octava
  se haga con menos atención que la primera. El recorrido por `Registro` está
  para eso: no depende de que nadie se acuerde de la octava.
- **Potencialidad.** Es la única sin tests de maquetación y la única con CSS
  propio; su franja puede verse bien en las otras siete y mal ahí. La
  verificación de navegador la mira aparte.
- **Retirar dos componentes.** `<x-leyenda-escala>` (6 vistas) y
  `<x-aviso-bloqueo-matriz>` (5 vistas + una mención en el docblock de su
  hermano). Comprobado que no los usa nadie más; si al ejecutar aparece un
  consumidor nuevo, es señal de parar y revisar, no de dejar el componente
  «por si acaso».
