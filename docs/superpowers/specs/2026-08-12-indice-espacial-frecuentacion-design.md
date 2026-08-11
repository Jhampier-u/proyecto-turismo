# Índice Espacial de Frecuentación Turística — diseño

**Fecha:** 12 de agosto de 2026
**Instrumento:** `Documentación/Índice Espacial de Frecuentación Turística.xlsx`
**Fuente citada en el instrumento:** Gunn, 1995. Mismos autores que Concentración e
Involucrados (Lic. Numa Sebastián Calle Lituma y Lic. Ronal Edison Chaca Espinoza).

Décima y última matriz del sistema. La especificación acordada con el
responsable del proyecto está en `docs/ESTADO-PROYECTO.md`, punto 3 de §6, y en
el commit `3fc32a2` que la desbloqueó. Este documento no la repite entera: la da
por buena, la contrasta contra la hoja real, y decide lo que esa especificación
deja abierto.

---

## Qué mide

Cuánto concentra cada sitio turístico del territorio la frecuentación, medida
contra la superficie del territorio entero:

```
ÍETP = DET ÷ ST          (por sitio)
ÍEFT = Σ ÍETP            (del territorio, = (Σ DET) ÷ ST)
```

- **DET** — un número por sitio. La hoja no dice su unidad.
- **ST** — un número, **uno solo para todo el territorio**, no uno por sitio.
- **ÍETP** — el índice de un sitio.
- **ÍEFT** — el índice del territorio: la suma de los ÍETP de todos sus sitios.

---

## Contraste con la hoja real

El encargo pedía abrir el instrumento y contrastarlo con la especificación ya
escrita, no fiarse de ella de segunda mano. Abierto con `openpyxl`
(`data_only=False` para las fórmulas, `data_only=True` para los valores en
caché), la hoja `ÍEFT` es exactamente lo que el punto 3 de §6 describe, sin
ninguna discrepancia nueva:

- **`G6:G14`, nueve filas** con el nombre del sitio. Solo las dos primeras
  traen un nombre real de ejemplo (`Malecón 2000`, `Cerro las Peñas`, dos
  lugares de Guayaquil); las otras siete dicen literalmente «Sitio de
  Análisis». Confirma que las nueve son maquetación, no un tope: son casillas
  de ejemplo de una plantilla, no un límite del instrumento.
- **`J6:J14` es un único rango combinado** (`ws.merged_cells.ranges` lo
  confirma). Combinada, una celda solo guarda valor en su esquina superior
  izquierda: `J6`. Las nueve fórmulas de la columna K (`=I6/$J$6`,
  `=I7/$J$6`, ..., `=I14/$J$6`) apuntan siempre a esa misma celda con
  referencia absoluta. Hay una sola ST para las nueve filas, tal como dice la
  especificación.
- **`G17`/`H17`: «Superficie Territorial» = 1**, celda que ninguna fórmula usa
  —confirmado buscando `H17` en el resto del libro—. `H18` (`Índice Espacial
  de Frecuentación Turística`) es `=K15`, la suma tal cual, no `=K15/H17`.
  Igual que dice la especificación: esa celda es un resto de ejemplo, no se
  implementa.
- **La plantilla está vacía**: con `data_only=True`, `I6:I14` y `J6` no tienen
  valor, y las nueve `K` y el propio `H18` se evalúan como `#DIV/0!` en la
  copia que trae Excel. Es la prueba más directa posible de que dividir por
  una ST sin rellenar no es un caso de esquina hipotético: es el estado en el
  que llega la propia plantilla, y es exactamente el escenario del punto 2 de
  este documento.

**Un detalle que la especificación no mencionaba, y que no cambia el cálculo
pero sí cómo se enseña:** las celdas de entrada (`I6:I14`, `J6`) tienen
formato `General`, pero la columna de salida (`K`, y `H18`) está formateada
como **porcentaje** (`0.00%`), igual que `H17` (`0%`). Es un formato de
presentación de Excel, no una restricción del cálculo —nada acota `DET` a no
superar `ST`, así que un ÍETP por encima de 100 % sería tan válido como uno
por debajo—. Se decide **no** heredar ese formato: mostrar el resultado como
un número decimal simple, no como porcentaje, para no insinuar un tope que el
instrumento no impone. Es una decisión de presentación, del mismo tipo que las
unidades de DET/ST: no bloquea y se puede revisar si el autor del instrumento
la pide.

**Conclusión: nada en la hoja contradice el punto 3 de §6.** Este diseño lo da
por firme y se dedica a lo que ese punto deja abierto a propósito.

---

## Decisiones tomadas

### 1. Dónde vive la ST

Una sola ST por zona, no por sitio, así que vive en la tabla de
**configuración de la zona**, no en cada fila de sitio — el mismo sitio donde
`InvolucradosConfig` guarda el estado del conjunto de actores (`zona_id`
único, `user_id`, `estado`). Aquí se le añade una columna más: `st`.

**No es lo mismo que Involucrados, y la diferencia importa:**
`InvolucradosConfig` no guarda ningún dato del cálculo, solo el estado de la
lista —los grados normalizados se derivan siempre de los propios actores—.
Aquí la ST **es un dato del cálculo**, y no tiene fila propia: no es un sitio,
no tiene DET, y guardarla junto a los sitios obligaría a inventar una fila
especial o a repetirla en cada una. Vive en la configuración porque es
exactamente lo que la configuración ya es: un dato de la zona entera, uno
solo, junto al estado que también es de la zona entera.

`FrecuentacionConfig`: `zona_id` único, `user_id`, `estado`
(`borrador`/`confirmado`), **`st`** (decimal, nullable, sin valor por
defecto), marcas de tiempo. Mismo patrón que `InvolucradosConfig`, ensanchado
en una columna.

### 2. Qué pasa cuando ST es cero o está sin rellenar

**Hay jurisprudencia y se sigue: nunca un número que parezca un resultado.**
`ConcentracionCalculo::pi()` ya resolvió el mismo problema de forma —un
sector sin establecimientos no tiene «un Pi bajo», tiene un Pi que no
existe— y la pantalla de Concentración lo marca como que no aplica, nunca
como cero. Es la misma raíz que el hallazgo de guardado parcial (GP5): un
hueco no entra en ningún promedio ni en ningún total, porque descontar el
factor que falta da un número medido sobre otra escala.

Aquí la aritmética es más tajante todavía: en PHP 8, `$det / 0` no da `INF` ni
un aviso silencioso, **lanza `DivisionByZeroError`**. No hay ni siquiera la
posibilidad de que un cero cuele un número raro sin que nadie lo note; hay que
guardar explícitamente contra él o el sistema revienta con un 500 la primera
vez que alguien confirme una ST vacía. La plantilla del propio Excel, vista
arriba, confirma que este no es un caso de esquina: es el estado por defecto.

**Decisión:** `Frecuentacion::ietp(float $det, ?float $st): ?float` devuelve
`null` cuando `$st` es `null` o `0.0`, nunca lanza ni devuelve `INF`/`NAN`. La
pantalla lo trata igual que Concentración trata un Pi nulo: sin cifra, con una
frase que diga por qué.

**Dónde aparece esta condición, y por qué no es «por fila» como en
Concentración.** En Concentración, cada sector tiene su propio Sia, así que
un sector puede estar vacío mientras otros no lo están: el «no aplica» es una
anotación fila a fila, dentro de una tabla que por lo demás se pinta entera.
Aquí ST es **una sola, compartida por todos los sitios**: si falta o es cero,
**ningún** sitio de la zona tiene ÍETP, no solo uno. Anotar «no aplica» fila
por fila en una tabla de nueve o veinte sitios repetiría la misma frase tantas
veces como sitios haya, y sugeriría que el problema es de cada fila cuando es
del territorio entero.

Por eso la condición se resuelve **una vez, a nivel de página**: la vista de
resultados decide si hay algo que enseñar —todos los sitios con DET
respondido, y una ST mayor que cero— **antes** de calcular ningún ÍETP, con
el mismo componente `<x-matriz-sin-resultados>` que ya usan Concentración e
Involucrados cuando su matriz está a medias. Si no se cumple, no hay tabla con
guiones: hay el aviso de siempre, con la frase que distingue «faltan sitios
por responder» de «falta la Superficie Territorial, o es cero» — son motivos
distintos y conviene que el aviso lo diga.

**`validar()` bloquea sobre el mismo criterio.** No se puede confirmar una
configuración cuya ÍEFT no se puede calcular: además de exigir al menos un
sitio y que ninguno tenga el DET sin responder (igual que Involucrados exige
actores completos), se exige `st !== null && st > 0`. El formulario, además,
valida el campo ST con `gt:0` para que no se pueda guardar un cero o un
negativo desde la interfaz; el guardián en `Frecuentacion::ietp()` es una
segunda defensa, no la única, por si algún dato llega por otro camino (una
migración, un factory, una prueba).

### 3. En qué se parece a Involucrados y en qué no

Se parece en la forma: un CRUD de filas de longitud variable (sitios, no
actores) con un estado de conjunto aparte (`FrecuentacionConfig`, no
`InvolucradosConfig`). Por eso el registro ya tiene un tipo con esta forma
general —`actores`— y la primera pregunta razonable es si sirve tal cual.

**Pero la fórmula no se parece, y es lo más importante de este diseño.**
Comprobado con la propia aritmética:

| | Involucrados | Frecuentación |
|---|---|---|
| Fórmula de una fila | `normalizado = (grado_i ÷ Σgrado) × n` | `ÍETP = DET_i ÷ ST` |
| ¿Depende de las OTRAS filas? | **Sí, siempre** — el denominador es la suma de todos los grados | **No** — el denominador es ST, que no sale de ningún sitio |
| ¿Añadir una fila cambia las demás? | Sí: cambia la suma, cambia todos los normalizados | **No** cambia el ÍETP de ninguna fila existente |
| ¿Hay un resultado único del conjunto? | No: un ranking de actores, cada uno con su relevancia | Sí: **ÍEFT = Σ ÍETP**, un solo número |
| Caso degenerado | Por atributo: suma 0, o un único actor → normalizado neutro `1.0`, con nota | Por ST: null o 0 → **ningún** ÍETP existe, gate de página completa |

`Involucrados::normalizar()` divide cada grado por la suma **de los propios
grados que se están normalizando**: el conjunto se referencia a sí mismo, y
por eso tocar el conjunto cambia el resultado de todos. `ÍETP = DET ÷ ST` no
tiene esa auto-referencia: ST no es una función de los DET de los sitios, es
un dato aparte de la zona. El ÍETP de un sitio se puede calcular con un único
DET y una ST, sin mirar ningún otro sitio. **Se comprueba y se deja escrito
aquí a propósito**, porque es justo el tipo de detalle que un revisor futuro
copiaría de Involucrados sin volver a mirar la fórmula.

**Entonces, ¿por qué SÍ hace falta reabrir la matriz al tocar la lista, si
cada ÍETP es independiente?** Porque lo que se valida no es «cada ÍETP por
separado», es **ÍEFT, la suma**. Editar el DET de un sitio no cambia el ÍETP
de ningún otro sitio, pero sí cambia la suma total —un sumando distinto da una
suma distinta—, y añadir o borrar un sitio añade o quita un término de esa
misma suma. Cambiar la ST cambia **todos** los ÍETP a la vez, porque todos
comparten el mismo divisor. En los tres casos, el número que se certificó al
validar (`ÍEFT`) deja de ser el que sale de los datos actuales. La regla
—tocar el CRUD de una lista ya validada la devuelve a borrador— **sí aplica
aquí**, pero el motivo no es «la normalización depende del conjunto» como en
Involucrados: es que **el total es una suma sensible a cada término, y la ST
es un divisor compartido por todos**. Vale la pena dejarlo escrito así de
explícito para que nadie concluya, por analogía superficial con Involucrados,
que como «el ÍETP de cada sitio no depende de los demás» entonces tampoco
hace falta reabrir: sí hace falta, por una razón distinta.

**Consecuencia práctica:** `store()`, `update()` y `destroy()` de sitios, y el
guardado de la ST, llaman a un `reabrirSiConfirmada()` dentro de la misma
transacción que la escritura —mismo mecanismo que
`InvolucradosController`, sin reinterpretarlo—.

### 4. Si `actores` sirve tal cual, o hace falta un tipo nuevo

**No sirve tal cual. Hace falta un tipo nuevo: `sitios`.** No es una cuestión
de vocabulario —«sitio» en vez de «actor»—, es que `App\Servicios\EstadoZona`
y `pestanas-matriz.blade.php` no tratan `actores` como un tipo genérico:
tratan **Involucrados en concreto**, con su nombre de relación escrito a
fuego. Comprobado, no supuesto:

```php
// EstadoZona::filaActores() — el `entrada` que recibe se ignora aquí:
$cuantos = $this->zona->involucrados()->count();
// ...
$incompletos = $this->zona->involucrados()->incompletos()->count();
```

```php
// pestanas-matriz.blade.php, dentro del `elseif($entrada['tipo'] === 'actores')`:
$actores  = $zona->involucrados();
$completa = $actores->count() > 0 && ! $actores->incompletos()->exists();
```

Si la entrada de Frecuentación declarara `'tipo' => 'actores'`, las dos
piezas de arriba seguirían preguntando por `$zona->involucrados()` —los
actores de Involucrados, no los sitios de Frecuentación—. La página de zona
mostraría el recuento de actores en la fila de Frecuentación, y la pestaña de
resultados de Frecuentación decidiría su candado mirando la lista de
actores. No es un fallo hipotético: es literalmente lo que pasaría si se
reutilizara el tipo sin tocar estos dos sitios, porque `match ($entrada['tipo'])`
enruta por el **valor del tipo**, y las dos funciones de arriba no vuelven a
mirar `$entrada` para decidir qué relación consultar.

Añadir un tipo nuevo, en vez de generalizar `filaActores()` para que reciba
el nombre de la relación por parámetro, seguimos el mismo criterio que ya
fijó `Potencialidad::SECCIONES` frente a `Fit::BLOQUES` (§6 de
`ESTADO-PROYECTO.md`, rama `potencialidad-componentes`): forzar una segunda
matriz dentro de la forma de la primera cuando tienen una diferencia real
—aquí, una condición de completitud a dos partes (sitios sin DET, **y** una ST
propia que Involucrados no tiene) que `filaActores()` no contempla— es el
riesgo de comportamiento que este proyecto evita sistemáticamente, no una
economía de líneas. `filaInventario()` ya hardcodea `$this->zona->inventarios()`
del mismo modo; es el patrón establecido, no una excepción.

**Qué gana el tipo nuevo, concretamente:**

- `Registro::TIPOS_VALIDABLES` pasa a `['matriz', 'actores', 'sitios']`: sigue
  contando para el progreso de la zona exactamente igual que `actores`
  —es un CRUD con estado, la razón por la que `actores` cuenta ya aplica
  aquí—, sin tocar el filtro genérico de `matrices()`.
- `EstadoZona::fila()` gana una rama `'sitios' => $this->filaSitios(...)`,
  hermana de `filaActores()`, no una reescritura de ella. Su detalle
  distingue los dos motivos de bloqueo: sitios sin DET y ST sin definir.
- `pestanas-matriz.blade.php` gana un `elseif($entrada['tipo'] === 'sitios')`
  con la misma comprobación de dos partes.

**Y `criterios => null`, igual que Involucrados, por el mismo motivo:** no
hay un número fijo de sitios ni de campos por sitio que alimente un «21 de 34
respondidos» — la lista es de longitud variable y cada sitio tiene un único
dato que responder (DET), así que el denominador natural sería siempre 1,
que no dice nada útil. El detalle de la fila lo cuenta con palabras («3
sitios, 1 sin DET»), no con una fracción.

### 5. Escala de DET y ST: decimal, sin tope

Ni DET ni ST tienen una escala acotada que el instrumento declare —a
diferencia de Paisaje o Potencialidad, que traen 0-3, 0-5 con su propio
vocabulario—. Concentración ya resolvió el caso de «esto cuenta algo sin
techo natural» sobreescribiendo `reglaValor()` a `integer|min:0`; aquí el
mismo argumento aplica a DET (`nullable|numeric|min:0`, sin máximo), y a ST
con la adición de `gt:0` explicada en el punto 2.

**Decimal, no entero, y es una diferencia real con Concentración.**
Concentración cuenta establecimientos —una cantidad discreta, siempre un
entero—. DET y ST no tienen unidad confirmada (§6 de `ESTADO-PROYECTO.md`
las deja como «números sin unidad»), y **ST es case casi con toda seguridad
una superficie** (km², hectáreas): forzarla a entero descartaría cualquier
territorio cuya superficie no sea un número redondo, que es la mayoría.
Ambas columnas se guardan como `decimal`, con dígitos generosos —de sobra
para no repetir el aprieto que dejó anotado Irritación (`decimal(5,3)` «va
justo de dígitos»)—. **Con PostgreSQL, una columna `decimal`/`numeric` vuelve
como cadena si el modelo no la castea a `float`** — es el motivo documentado
en el propio `EvaluacionFit` (`protected function casts()`), y aplica igual
aquí: sin el cast, `Frecuentacion::ietp()` recibiría una cadena donde espera
un `float`, y `'3.5' / '2'` sigue funcionando en PHP por coerción automática,
pero comparaciones como `$st === 0.0` dejarían de reconocer un `'0.0000'`
recién leído de Postgres. Los dos campos (`st`, `det`) llevan su
`'campo' => 'float'` explícito en sus modelos.

### 6. Dónde vive el cálculo

**Directamente en `App\Matrices\Frecuentacion`, un único fichero, sin
partirlo como `ConcentracionCalculo`.** Concentración separó su cálculo
porque `Concentracion.php` es generado desde un script y una regeneración lo
reescribe entero; cualquier método a mano dentro desaparecería. Frecuentación
no se genera: son dos campos por fila (nombre, DET) y un escalar (ST), sin
ningún riesgo de nombres que un script deba producir con seguridad. Con eso
fuera de la ecuación, el patrón que aplica es el de `Involucrados.php`, que
también guarda su aritmética pura (`normalizar()`, `tipoDe()`,
`esAtributoDegenerado()`) en el mismo fichero que la definición del
instrumento.

`Frecuentacion::ietp()` e `Frecuentacion::ieft()` son aritmética pura sobre
escalares —reciben `float`/`array<float>`, no modelos ni colecciones de
Eloquent—, en la línea que dejaron tanto Involucrados (que tuvo que deshacer
una dependencia circular instrumento↔modelo) como Concentración: quien reúne
los DET de los sitios de una zona en un array es el controlador, no
`Frecuentacion`.

### 7. Grupo e icono

**Grupo `presion`** (Presión y uso), junto a Irritación y Concentración. El
propio comentario de `Registro::GRUPOS` ya lo anticipaba: «Los grupos
'social' y 'presión' ya están declarados aunque les falten matrices: sus
entradas llegan cuando se implementen Involucrados, Concentración y
Frecuentación» — Involucrados fue a `social`, Concentración e Irritación
están en `presión`, y Frecuentación —cuánto concentra la afluencia el
territorio— encaja temáticamente con las otras dos: presión y uso del
espacio, no dimensión social.

**Icono nuevo, `ubicacion`** (un marcador de posición): ninguno de los once
existentes queda libre para una matriz sobre puntos del territorio, y
`test_cada_entrada_declara_un_icono_existente_y_sin_repetir` exige que sea
uno nuevo, sin repetir.

---

## Arquitectura: piezas que toca, y en qué orden

Mismo espíritu que Involucrados: lo compartido primero y solo, con la suite
en verde, antes de construir el CRUD encima.

1. **`Registro::ENTRADAS`** — la entrada `frecuentacion`, con `tipo =>
   'sitios'`. **Se registra antes que el controlador**: sin ella,
   `RegistroMatricesTest::test_toda_ruta_de_matriz_pertenece_a_una_entrada_del_registro`
   —el guardián que ya existe, añadido en `deudas-registro`— falla en cuanto
   `routes/web.php` declare las rutas de Frecuentación sin una entrada que
   las represente.
2. **`Registro::TIPOS_VALIDABLES`** — se le añade `'sitios'`.
3. **`EstadoZona::fila()`** — rama `'sitios' => $this->filaSitios(...)`.
4. **`pestanas-matriz.blade.php`** — rama `elseif($entrada['tipo'] === 'sitios')`.
5. **`tests/Unit/EstadoZonaTest.php`** — el `assertSame(9, ...totalMatrices())`
   pasa a 10; test nuevo `test_una_entrada_de_sitios_sin_empezar_lo_dice`,
   hermano del que ya existe para `actores` (se salta hasta que la entrada
   exista de verdad).
6. **`tests/Feature/RegistroMatricesTest.php`** — el único número que cambia
   es `assertCount(9, Registro::matrices())` → `10`. El resto de esa suite ya
   es genérico (recorre `Registro::ENTRADAS` sin asumir cuántas hay ni de qué
   tipo son) y no necesita tocarse.
7. **`tests/Feature/PermisosAdminTest.php`** — el guardián de rutas de
   escritura (`test_toda_ruta_de_escritura_del_grupo_zona_esta_clasificada`)
   exige clasificar cada ruta nueva. Van a `permitidas`: crear/editar/borrar
   sitio y guardar la ST (el admin escribe borradores, igual que en
   Involucrados). Va a `prohibidas`: validar (solo el Jefe de Zona).

Solo entonces: modelos, migración, controlador, rutas, vistas.

---

## Modelo de datos

Dos tablas, mismo reparto que Involucrados y por el mismo motivo: lo que se
valida es el conjunto entero (todos los sitios más la ST), no una fila
suelta.

**`frecuentacion_config`** — una fila por zona:

- `zona_id` (único), `user_id` (nullable), `estado`
  (`borrador`/`confirmado`, por defecto `borrador`).
- **`st`** — `decimal`, nullable, sin valor por defecto. Es el dato nuevo
  frente a `InvolucradosConfig`: la superficie territorial, compartida por
  todos los sitios de la zona.
- Marcas de tiempo.

**`frecuentacion_sitios`** — una fila por sitio:

- `zona_id`, `nombre` (obligatorio, `string(200)`), `orden`
  (`unsignedInteger`, por defecto 0, para poder ordenar manualmente el día
  que se implemente arrastre; no se usa todavía, igual que en Involucrados).
- **`det`** — `decimal`, nullable, sin valor por defecto: un sitio recién
  creado no tiene «cero frecuentación», tiene «sin responder todavía»,
  exactamente la misma razón por la que los criterios de Involucrados nacen
  `nullable`.
- Marcas de tiempo.

**Nada calculado se guarda.** ÍETP e ÍEFT se derivan siempre de `det` y
`st`: guardarlos sería una segunda fuente de verdad que se desincroniza en
cuanto se edita un sitio o la ST, que es precisamente lo que aquí pasa a
menudo.

---

## Interfaz

**Una pantalla de listado, como Involucrados.** La lista de sitios con su
nombre y su DET, un pequeño formulario de un solo campo para la ST de la
zona —edita el mismo botón «Guardar» que ya usa el resto de formularios
bloqueados por `bloqueoSiCerrada()`—, y el botón de validar cuando el
conjunto está completo. Cada sitio se edita en su propia página, con un
único campo numérico (DET) y su nombre.

**Los resultados** son una tabla de sitios con su DET y su ÍETP, y el ÍEFT
del territorio al pie, como un total que se lee de un vistazo —no como una
fila más—. Con la lista vacía, con algún sitio sin DET, o con la ST sin
definir o en cero, `<x-matriz-sin-resultados>`, con una frase que distingue
cuál de los dos motivos es. No hay una anotación «no aplica» fila por fila:
según el punto 2, la condición de ST es del territorio entero, no de un
sitio, así que se resuelve una vez, antes de la tabla.

**El aviso de reapertura sigue el patrón de Involucrados, no el de las ocho
matrices de formulario:** se compone dentro del mensaje de éxito de guardar
un sitio o la ST (`mensajeConReapertura()`), no con el componente
`<x-aviso-reapertura>` —que asume un único formulario visible con un botón
de guardar, y aquí no hay uno solo: hay varias acciones (alta, edición,
borrado de sitio, guardado de ST) que pueden reabrir la matriz—.

---

## Fuera de alcance

- **Reordenar sitios por arrastre.** La columna `orden` existe para
  añadirlo después sin migración, igual que en Involucrados.
- **Las unidades de DET y ST.** Siguen sin confirmar y no bloquean —números
  sin unidad, como el resto del sistema—, tal como fija el punto 3 de §6.
- **El formato de porcentaje** que trae la hoja de Excel en la columna de
  ÍETP. Se decide no reproducirlo (ver «Contraste con la hoja real»); si el
  responsable del proyecto lo pide más adelante, es un cambio de vista, no
  de cálculo.
- **Cualquier gráfico** del instrumento original.
- **Un umbral o clasificación de ÍEFT** (bajo/medio/alto, o similar). El
  instrumento no trae ninguno —a diferencia de Irritación, que sí clasifica
  su resultado—, así que no se inventa uno aquí.
