# Fase 1 del rediseño de interfaz — navbar y migas

**Fecha:** 13 de agosto de 2026
**Fase:** 1 de 4. La 0 —la fundación visual— está fusionada.
**Alcance:** la navegación. Ninguna página cambia de contenido.

## El problema, que no es «hacer breadcrumbs»

El encargo heredado decía «navbar y breadcrumbs», que es una solución. Los
problemas que hay debajo son cuatro, y los cuatro entran en esta fase:

1. **No se sabe dónde se está.** Dentro de una matriz no hay nada que diga a
   qué zona pertenece ni qué nivel se ocupa. El título lo dice a medias y el
   botón «Volver» es la única pista de jerarquía.
2. **Cuesta llegar.** El operativo solo tiene «Mis Zonas» en la barra: cambiar
   de zona obliga a subir al primer nivel y volver a bajar.
3. **La barra se ve vieja.** Es la de Breeze con el logo cambiado, y desde la
   Fase 0 desentona con las tarjetas y los botones del sistema.
4. **El admin y el operativo compiten** en la misma barra, resuelta con un
   `@if` duplicado entre escritorio y móvil.

## Decisiones

| # | Decisión | Elegido | Por qué |
|---|----------|---------|---------|
| 1 | Atajos | **Selector de zona** en la barra | El árbol tiene tres niveles y el salto que se repite es el primero |
| 2 | `Cmd+K` | **Descartado**, no aplazado | Ver abajo |
| 3 | Estructura | **Una barra**, contenido según perfil | No introduce una segunda maquetación de página |
| 4 | «Volver» | **Las migas lo sustituyen** | Un solo control para subir de nivel |
| 5 | Cómo sabe la miga dónde está | **Cada vista lo declara** | Es el patrón que `<x-pestanas-matriz>` ya estableció |

### El `Cmd+K` se descarta, y esta vez con motivo escrito

La Fase 0 lo apartó por desproporcionado y lo mandó aquí «para replantearlo».
Replanteado: **no entra, y no vuelve a aplazarse.** Una paleta de comandos
resuelve saltos arbitrarios en un árbol ancho; este tiene tres niveles y diez
matrices por zona, y el único salto que de verdad se repite —cambiar de zona—
lo resuelve un desplegable que además funciona en móvil, donde una paleta de
teclado no sirve de nada. Si algún día el árbol se ensancha, la decisión se
revisa con ese hecho delante y no con la intuición de hoy.

### Por qué «cada vista lo declara» y no derivarlo de la ruta

Derivar el rastro del nombre de ruta sería una sola fuente de verdad, y es la
opción que se elegiría en un repositorio nuevo. Aquí no, por dos razones:

- **El patrón explícito ya existe y está adoptado.**
  `<x-pestanas-matriz clave="irritacion" :zona="$zona" activa="formulario" />`
  declara exactamente los tres datos que la miga necesita, y está puesto en 18
  vistas. Añadir un mecanismo derivado dejaría **dos formas de responder a la
  misma pregunta**, que es el defecto que la Fase 0 estuvo quitando.
- **Los nombres de ruta no son regulares.** Normalizarlos es una fase propia,
  y hacerlo de paso dentro de esta escondería un cambio de rutas dentro de un
  cambio de maquetación.

El riesgo del patrón explícito es que una vista declare una miga que no
corresponde a su ruta. Se ata con un test, no con disciplina — ver §Cómo se ata.

## Lo que se crea

### `<x-migas>`

Tres props, las tres opcionales: `:zona`, `clave` (la matriz, tal como la
nombra `App\Matrices\Registro::ENTRADAS`) y `actual` (la hoja, texto libre).

**Hereda la lógica de raíz de `<x-boton-volver>` sin reescribirla.** Ese
componente ya decide que el destino de arriba es `admin.zonas.index` («Zonas»)
para el admin y `operativo.dashboard` («Mis Zonas») para el resto. Es la misma
decisión, ya probada, y se muda tal cual. El comentario que la justifica —«un
ternario replicado es exactamente la forma que tomó el fallo que dejó al admin
viendo enlaces de edición durante toda una rama»— viaja con ella.

El nombre de la matriz sale de `Registro::ENTRADAS[$clave]['nombre']`, nunca
escrito en la vista: si se escribiera, la miga y la pestaña podrían decir cosas
distintas del mismo criterio.

El último tramo **no es enlace**. Los anteriores sí, y eso es lo que hace que
subir un nivel deje de necesitar un botón.

**Los grupos del `Registro` no entran en el rastro.** Cada entrada tiene
`grupo` ('base', 'vocacion', 'valoracion', 'social', 'presion') con su título,
así que la tentación es meter un nivel más. No se hace: **ningún grupo tiene
ruta**, así que sería un tramo intermedio no navegable, y una miga que no lleva
a ningún sitio es peor que ausente — enseña una jerarquía que la aplicación no
tiene.

### Selector de zona

Desplegable en la barra con las zonas del usuario, unión de `zonasComoJefe` y
`zonasComoEquipo`. Cambiar de zona deja de exigir subir a «Mis Zonas».

**Solo para el operativo.** El admin ya tiene la sección «Zonas» con su
buscador, y un desplegable con todas las zonas del sistema crece sin techo.
Decidido a propósito y anotado aquí para que no se lea como olvido.

## Lo que desaparece

- **Las 22 llamadas a `<x-boton-volver>`**, y el componente con ellas — igual
  que los tres botones de Breeze en `restos-fase-0`, un componente sin un solo
  uso vivo se borra.

  **Son 22 en 20 ficheros, y un `grep` va a devolver 23.** La ocurrencia de más
  está dentro de un comentario de `vtt/resultado.blade.php`, el que explica que
  FV11 arregló el componente y no las llamadas. Queda dicho aquí porque este
  repositorio ya se tropezó dos veces con esa diferencia: un barrido de
  hexadecimales contó comentarios como código en `restos-fase-0`, y antes de
  eso un barrido por `class` no encontró un botón escrito con `style=`. **Al
  contar, hay que descontar los comentarios; y al buscar, no fiarse de un solo
  atributo.**
- **La duplicación del navbar.** Los enlaces están hoy escritos dos veces,
  escritorio y móvil, en dos `@if` paralelos. **No es un riesgo hipotético:**
  el propio fichero lleva el comentario de que el bloque móvil llegó a tener
  solo `dashboard` y «la app era inservible en móvil». Los destinos pasan a
  decidirse en un sitio y los dos bloques los consumen.
- `<x-nav-link>` y `<x-responsive-nav-link>`, que la Fase 0 dejó fuera
  anotando explícitamente que esta fase rehace la barra.

## El rastro, por tipo de página

| Página | Migas |
|---|---|
| Panel de zona | Mis Zonas › **Chanduy** |
| Formulario de matriz | Mis Zonas › Chanduy › **Factores intrínsecos (FIT)** |
| Resultados de matriz | Mis Zonas › Chanduy › Factores intrínsecos (FIT) › **Resultados** |
| Listado de inventarios | Mis Zonas › Chanduy › **Inventario de recursos** |
| Detalle de inventario | Mis Zonas › Chanduy › Inventario de recursos › **El faro** |
| Zona vista por el admin | Zonas › **Chanduy** |

Los nombres largos —«Factores intrínsecos (FIT)»— son los del `Registro`, y se
usan tal cual: acortarlos aquí crearía un segundo nombre para el mismo criterio,
que es el defecto que el punto 11 de §6 del traspaso costó dos ramas cerrar.

## Cómo se ata

Tres guardianes. El segundo es el que justifica la fase:

1. **`MigasTest`** — la raíz según rol, que la hoja no es enlace, y que con
   `clave` el nombre sale del `Registro` y no de la vista.
2. **Ninguna página bajo `operativo/zona` se queda sin migas.** Es el riesgo
   real de quitar 22 botones: dejar una pantalla sin salida. Un test que
   recorre las vistas lo caza; abrirlas de una en una, no. Mismo patrón que
   `TipografiaUnicaTest`, que ya afirma sobre el fuente de las 84 vistas por
   esta misma razón.
3. **Escritorio y móvil ofrecen los mismos destinos.** Es el fallo que ya
   ocurrió una vez, y hoy nada lo impide.

Ningún test anterior a la rama se modifica. Uno que se ponga rojo es la señal
de que la sustitución cambió comportamiento y no aspecto, y se para.

## Lo que no entra

- **KPIs, tarjetas de zona y tablas ordenables** — Fase 2. `<x-badge>` sigue
  esperando ahí a su primer consumidor.
- **Detalle de zona en dos columnas** — Fase 3.
- **Consolidar el banner de borrador y la escala** — Fase 4.
- **El badge de notificaciones** — fuera de las cuatro fases: no hay dominio de
  notificaciones, solo el `Notifiable` de Breeze. Es funcionalidad, no
  maquetación.

## Riesgos

- **Quitar 22 salidas a la vez.** Es el cambio con más superficie de la fase.
  Lo cubre el guardián 2, y el orden de implementación debe poner las migas
  **antes** de quitar ningún botón, nunca al revés.
- **`<x-matriz-sin-resultados>` usa `<x-boton-volver>` en un estado vacío**, y
  lo comparten cinco matrices. Al quitarlo queda un solo botón, «Ir al
  formulario». Hay que mirar esa pantalla de verdad, no solo sus tests.
- **`zona/panel` llama a `<x-boton-volver>` sin zona**, que es el caso de
  primer nivel. Es el único sitio donde la miga tiene un solo tramo por encima.
