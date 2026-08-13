# Fase 2 del rediseño de interfaz — dashboard «Mis Zonas»

**Fecha:** 13 de agosto de 2026
**Fase:** 2 de 4. La 0 —la fundación visual— y la 1 —navbar y migas— están
fusionadas.
**Alcance:** `operativo/dashboard`, la portada del perfil operativo. Ninguna
pantalla de admin se toca.

## El encargo heredado estaba medio hecho, y por eso se reescribe

El traspaso describe esta fase como «KPIs en rejilla, tarjetas de zona con
cabecera visual y métricas, y una tabla ordenable para la vista de lista». Al ir
al fichero, **dos de las tres cosas ya existen**:

- Las tarjetas ya tienen **cabecera visual**: la foto de la zona, o sus dos
  iniciales sobre un degradado cuando no hay foto.
- Ya tienen **métricas**: barra de progreso y la fracción `hechas / total`.
- Y hay un panel de **siguiente paso** que la rama `barra-lateral` añadió
  *después* de escribirse ese encargo.

Es el mismo caso que el propio traspaso ya avisa para la Fase 4 —«el encargo
original pedía las dos cosas como si no existieran»—. Así que el encargo se
reescribe contra lo que hay hoy:

1. **No hay ninguna cifra de conjunto.** Con cinco zonas abiertas, saber cuánto
   lleva el trabajo entero obliga a sumar cinco barras a ojo.
2. **La «vista lista» no es una lista ordenable, es una pila de filas.** No hay
   forma de preguntar «¿cuál llevo más adelantada?» ni «¿cuál no he tocado?».
3. **`hechas / total` mete en el mismo saco lo que no ha empezado y lo que está
   a medias.** Un `3 / 10` no distingue entre siete sin abrir y siete en
   borrador esperando validación, que son dos situaciones distintas.

## Decisiones

| # | Decisión | Elegido | Por qué |
|---|----------|---------|---------|
| 1 | Alcance | **Solo «Mis Zonas»** | El panel de admin ya tiene sus cifras en rejilla y su aviso de zonas sin jefe: es lo que esta fase pide, ya hecho |
| 2 | KPIs | **Debajo del siguiente paso, y solo con dos o más zonas** | Lo accionable sigue siendo lo primero; con una zona la franja repetiría su tarjeta |
| 3 | Ordenación | **En el servidor, por parámetro de URL** | Se cubre con tests de HTTP, funciona sin JavaScript y ordena las dos maquetaciones |
| 4 | Estado en la tarjeta | **Desglose por estado, una insignia por estado presente** | Cada insignia dice literalmente lo que su color significa |
| 5 | De dónde salen los datos | **`progresoDe()` devuelve el desglose** | Una sola fuente para «cuánto lleva esta zona» |

### Por qué la ordenación va en el servidor

En este repositorio pesa un hecho concreto: **Playwright no está instalado** —el
traspaso lo deja escrito en `restos-fase-0`— así que una ordenación hecha con
Alpine no la vería **ningún test que esta máquina pueda correr**. La rama
anterior cerró tres defectos que ningún test veía; añadir a propósito una
funcionalidad invisible para la suite va en la dirección contraria.

Con el parámetro en la URL, además, el orden se puede compartir en un enlace y
**afecta a las dos maquetaciones**, porque ordena la colección y no la tabla.

### Por qué el desglose y no una insignia de «zona terminada»

`<x-badge>` lleva desde la Fase 0 escrito sin usar, con el motivo en su propio
fichero: sus colores salen de `EstadoZona::ESTILOS_ESTADO` y significan **el
estado de una matriz**. Una insignia «Terminada» en verde sobre una tarjeta
estaría usando el color de `validada` para una semántica nueva —el estado de una
*zona*—, que es exactamente el error que ya se pagó una vez con
`<x-insignia-clasificacion>`, cuyo nombre genérico invitaba a pintar de verde el
peor resultado.

El desglose no tiene ese problema: cada insignia cuenta matrices de ese estado,
que es lo que el color dice.

Y encaja con el denominador que ya existe: `Registro::matrices()` son las **diez
validables**, con `inventario` fuera —no tiene estado— y `vtt` fuera —es
derivada—. Así que los únicos estados posibles en el desglose son `validada`,
`borrador` y `sin_empezar`. Ni `bloqueada` ni `sin_estado` pueden aparecer,
porque pertenecen a las dos entradas que no cuentan.

## Lo que cambia en los datos

`EstadoZona::progresoDe()` pasa de devolver `['hechas', 'total']` a devolver
`['hechas', 'borradores', 'sin_empezar', 'total']` por zona.

**El coste no cambia.** El método ya recorre los diez modelos con una consulta
cada uno; lo que cambia es que en vez de pedir solo las confirmadas pide
`zona_id` y `estado`. Siguen siendo diez consultas, haya una zona o cincuenta —
que es la propiedad por la que este método existe—. `sin_empezar` se deriva sin
preguntar nada: `total − hechas − borradores`, o sea, las matrices sin fila.

**`hechas` no se renombra a `validadas`**, aunque el nombre nuevo sería mejor.
Lo consumen `admin/zonas/index` —dos veces— y `ConmutadorVistaTest`, y esta fase
decidió no tocar las pantallas de admin. Renombrar arrastraría dos vistas ajenas
al alcance a cambio de cosmética. Queda anotado como candidato para cuando una
fase entre de verdad en admin.

## Lo que se crea

- **La franja de KPIs.** Tres cifras en `<x-tarjeta>` sobre `grid md:grid-cols-3`,
  que es **la misma rejilla del panel de admin**: las dos portadas del sistema
  quedan con la misma forma sin inventar un primitivo nuevo.
  - Zonas asignadas.
  - Matrices validadas del total sumado — la suma de `hechas` sobre la suma de
    `total`.
  - Zonas terminadas: aquellas cuyo `hechas` iguala su `total`.
- **La tabla de la vista lista.** `<table>` de verdad, con `<thead>` y
  `<th scope="col">`. Columnas: **Zona** (nombre y descripción), **Lugar**,
  **Estado**, **Progreso**, **Acciones**.
- **El desglose por estado**, en la tarjeta y en la columna Estado de la tabla,
  con `<x-badge>` y su ranura —que existe justo para esto: conserva el color del
  estado y cambia el texto—.

## La ordenación, en concreto

- Cabeceras ordenables: **nombre, lugar y progreso**. Descripción y acciones no.
- Son **enlaces** a `?orden=nombre&dir=asc`, no botones con JavaScript.
- La columna activa lleva `aria-sort="ascending"` o `"descending"`, que es lo que
  hace que un lector de pantalla anuncie el orden.
- Pulsar la columna activa **invierte** el sentido; pulsar una columna nueva
  empieza en **ascendente**, sea cual sea. Una regla, sin excepciones por
  columna: que «progreso» arrancara en descendente porque «es lo que se querría»
  hace que la tabla se comporte distinto según dónde pulses.
- **Lista blanca**: un `orden` o un `dir` que no esté en ella cae al orden por
  defecto **en silencio**, con 200 y sin excepción. La portada de la aplicación
  no se rompe por un enlace viejo ni por alguien probando qué pasa.
- **El orden por defecto pasa a ser nombre ascendente.** Hoy no hay ninguno:
  llega el que decida la base, que es el `id`.
- Se ordena **la colección en PHP**, no con `orderBy` en SQL: el progreso no está
  en ninguna columna. Son las zonas de un usuario —unas pocas—, así que ordenar
  en memoria no cuesta nada y evita que «qué es progreso» acabe escrito en dos
  idiomas.

## Casos límite

- **Cero zonas:** se pinta el aviso ámbar que ya existe y **nada más** — ni
  conmutador ni maquetaciones. Hoy quedan una tarjeta vacía y un conmutador que
  no conmuta nada. Es la doctrina que el propio fichero aplica al panel de
  siguiente paso: si no hay nada que decir, no se dice nada.
- **Una zona:** sin franja de KPIs. Su tarjeta ya lo dice todo.
- **Estados a cero:** no se pintan. Un «0 Borrador» ocupa sitio para no decir
  nada.
- **Zona sin descripción:** el texto de reserva que ya existe.

## Una premisa comprobada, que no genera trabajo

**«Mis Zonas» y el selector de zona de la barra no construyen su lista igual.**
El dashboard elige por rol —`zonasComoJefe` *o* `zonasComoEquipo`— y el selector
que puso la Fase 1 hace la **unión** de las dos.

Comprobado antes de tratarlo como defecto: `Admin\ZonaController` valida
`equipo.*` contra usuarios de rol `equipo` y el jefe contra rol `jefe_zona`, así
que un jefe no puede estar en el equipo de otra zona y las dos expresiones dan
hoy el mismo conjunto. **No se toca nada.**

Queda escrito porque el día que esa validación se afloje las dos discrepan, y el
síntoma sería raro de diagnosticar: el selector ofrece una zona que «Mis Zonas»
no lista, y las migas de esa zona suben a una lista que no la contiene.

## Cómo se ata

1. **El desglose, sobre el servicio:** una zona con una validada, una en
   borrador y el resto sin fila devuelve `hechas: 1, borradores: 1,
   sin_empezar: 8, total: 10`.
2. **El coste sigue sin crecer con las zonas.** El test que ya vigila el número
   de consultas de `progresoDe()` se amplía; no se duplica.
3. **Orden:** nombre ascendente por defecto; `?orden=progreso&dir=desc` pone
   primero la más avanzada; `orden=loquesea` responde 200 y en el orden por
   defecto. Se afirma sobre **posiciones relativas en el HTML**.
4. **Las dos maquetaciones ordenan igual.** Es la misma colección, y sin test eso
   es una casualidad.
5. **KPIs:** con dos zonas se pintan y suman; con una, no aparecen.
6. **Insignias:** los estados presentes salen con su número; los que están a cero
   no se pintan.
7. **`aria-sort` en la columna activa**, que es la parte de una tabla ordenable
   que se olvida siempre.

### Un test anterior se pondrá rojo, y hay que leerlo

`ConmutadorVistaTest::test_las_dos_maquetaciones_de_mis_zonas_llevan_los_mismos_datos`
compara la cadena `hechas / total` esperando encontrarla una vez por maquetación.
Esa cadena deja de existir en la tarjeta.

**No se relaja: se actualiza a lo que pasa a ser el dato**, el desglose. Y la
comparación del lugar pasa del `📍 ` al **nombre del lugar**, que es el dato de
verdad: en la tabla el lugar es una columna con cabecera y el emoji ahí sobra.

La restricción que ese test defiende —que las dos maquetaciones lleven lo mismo—
es la que obliga a que las insignias estén también en la tabla, y se respeta.

## Lo que no entra

- **Las pantallas de admin.** El panel ya cumple el encargo de esta fase y
  `admin/zonas/index` es otra conversación —ahí viven los seis botones pequeños
  que la Fase 0 dejó anotados—.
- **Paginación.** Un operativo tiene unas pocas zonas.
- **Cambiar el `📍` de la tarjeta por `<x-icono>`.** Es un resto de antes de la
  Fase 0 y se arregla donde se arreglen los demás, no de tapadillo aquí.
- **Renombrar `hechas`.** Ver arriba.

## Riesgos

- **La tabla en móvil.** Cinco columnas no caben en un teléfono; va dentro de un
  contenedor con scroll horizontal y **no se esconde**, porque elegir maquetación
  es del usuario y su preferencia ya se guarda en `localStorage`. La vista de
  tarjetas sigue siendo la que mejor funciona ahí, y es la que viene por defecto.
- **El desglose puede leerse como tres cifras sueltas.** Suman siempre el total,
  así que el orden de las insignias es fijo —validadas, borrador, sin empezar— y
  no cambia según cuál tenga más.
- **Ordenar en PHP deja de ser gratis si un usuario acumulara cientos de zonas.**
  Hoy no pasa y no se diseña para un futuro inventado; si pasara, el sitio donde
  arreglarlo es el mismo método.
