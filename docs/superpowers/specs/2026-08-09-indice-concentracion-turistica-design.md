# Índice de Concentración Turística — diseño

**Fecha:** 9 de agosto de 2026
**Instrumento:** `~/Downloads/fwdmatrices/Índice de Concentración Turística.xlsx`
**Fuente citada en el instrumento:** Illingworth, 2011.

Novena matriz del sistema. Es la primera que **cuenta cosas** en vez de puntuar
criterios en una escala acotada.

---

## Qué mide

Cómo se reparte la oferta turística del territorio entre sus categorías. Son dos
índices en un mismo instrumento, con la misma mecánica y sin combinarse:

**Atractivos turísticos** — dos tablas paralelas, manifestaciones culturales
(22 subtipos: arquitectura, folklore, realizaciones técnicas,
acontecimientos programados) y atractivos naturales (55: montañas,
planicies, ambientes lacustres, ríos, bosques, costas). Por cada subtipo, una
**cantidad**, y su porcentaje sobre el total de su tabla.

**Planta turística** — diez sectores con 36 subcategorías: alojamiento,
restauración, intermediación, transportación, guianza, organizadores de eventos,
centros de convenciones, turismo comunitario, parques temáticos y balnearios.
Por cada subcategoría, una **cantidad de establecimientos**. Por sector:

```
Sia = suma de las cantidades de sus subcategorías
Pi  = 100 / √Sia
ICT = Sia / total de todos los sectores
```

---

## Decisiones tomadas

### Es un formulario de conteos, no se deriva del inventario

El sistema ya tiene un módulo de Inventario que registra recursos del
territorio con su categoría, y la tentación evidente es calcular la mitad de
atractivos a partir de él, sin que nadie teclee nada.

**No se hace, y el motivo no es el coste.** El inventario está hoy vacío, así
que profundizar su taxonomía sería barato. El motivo es lo que significaría cada
número:

- El denominador del ICT es **todos los atractivos del territorio**.
- El del inventario es **lo que el equipo ha llegado a registrar**.

Derivar uno del otro convierte «cuánto trabajo de campo llevamos» en «qué tiene
el territorio», y lo hace en silencio: un equipo que ha inventariado 12 de 40
atractivos vería un índice calculado sobre 12 y presentado como una medición.
Es el mismo patrón que este proyecto lleva meses quitando —la media parcial que
parece un resultado, el cero fabricado, la normalización sobre un conjunto que
se movió— y aquí sería peor, porque el número saldría solo y nadie habría
afirmado nada.

**Qué lo haría correcto en el futuro:** que un inventario pudiera declararse
cerrado por zona, como la lista de actores de Involucrados declara la suya con
su configuración. Ese día, derivar los atractivos del inventario pasa a ser
mejor que teclearlos. Este diseño no cierra esa puerta.

Además, aunque se derivara, **solo cubriría la mitad del instrumento**: la
planta turística —hoteles, restaurantes, agencias, operadores— no está en el
inventario ni en ninguna otra parte del sistema. No son recursos del territorio.

### Una matriz con dos bloques

Como Irritación y Valoración Territorial: el instrumento es uno y se valida
entero. Partirlo dejaría medio instrumento validable, que es lo que ya se
descartó al diseñar Irritación.

### `Pi` no se calcula cuando el sector está vacío

`Pi = 100/√Sia` divide por cero en cuanto un sector no tiene establecimientos, y
eso no es un caso raro: una zona rural no tiene centros de convenciones ni
parques temáticos. Lo normal es que varios sectores estén a cero.

`Pi` queda en `null` para ese sector, y la pantalla lo pinta como que **no
aplica**, no como un cero ni como un guion sin explicar. Un sector sin
establecimientos no tiene un `Pi` bajo: no tiene `Pi`.

La lección de Involucrados aplica entera: la decisión tiene dos partes —qué
devuelve la aritmética y qué se enseña— y no son la misma. Aquí, a diferencia de
allí, no hay un neutro que salve nada: `Pi` simplemente no existe para un sector
vacío, y el `ICT` de ese sector sí vale 0, que es correcto y significa «no
aporta nada al reparto».

### La escala no está acotada, y eso rompe un supuesto de la clase base

`MatrizPonderadaController::reglaValor()` construye la validación como
`integer|min:X|max:Y` a partir de `escala()`. **Aquí no hay máximo**: una zona
puede tener tres hoteles o cuatrocientos.

Se resuelve como ya lo hace Paisaje, que sobreescribe `reglaValor()` porque su
escala no es contigua: esta matriz lo sobreescribe para devolver
`integer|min:0`, sin tope. Es el mecanismo previsto y no hace falta tocar la
clase base.

---

## Modelo de datos

Tabla `evaluaciones_concentracion`, con la forma de las demás: `zona_id` único,
`user_id`, `estado`, marcas de tiempo.

Las **113 columnas de conteo**, `unsignedInteger` nullable sin defecto, con el
prefijo de su bloque y su categoría —`at_mc_museos`, `at_nat_montana_volcanes`,
`pt_al_hotel`, `pt_rs_cafeteria`—. Nullable por lo de siempre: un subtipo sin
contar no es un cero, y aquí el 0 significa «no hay ninguno», que es un dato.

`unsignedInteger` y no `tinyInteger`: son conteos, no puntuaciones.

**Nada calculado se guarda.** Porcentajes, `Sia`, `Pi` e `ICT` se derivan
siempre: son función directa de los conteos y guardarlos sería una segunda
fuente de verdad. Es la primera matriz sin ninguna columna calculada, así que
la clase base recibirá un `calcular()` que devuelve un array vacío.
**Comprobado:** `columnasCalculadasVacias()` da `[]` y su unión con los valores
es inocua, así que no hay que tocar nada compartido.

**El número de columnas es el riesgo de esta matriz**, y por eso los nombres se
generan desde el Excel con un script, como se hizo con Valoración Territorial:
un nombre mal copiado no rompe ningún test, solo desvía un conteo en silencio.

El generador garantiza además que ningún nombre pase de **63 bytes**, que es el
límite de identificador de PostgreSQL. Cuatro los pasaban —el más largo medía
82— y Postgres los habría truncado **en silencio**: el mismo patrón de «SQLite
pasa, Postgres hace otra cosa» que este proyecto ya se ha comido dos veces.

---

## Interfaz

**El formulario es largo y hay que asumirlo.** Dos secciones —atractivos y
planta—, y dentro, una subsección por categoría o sector, con un campo numérico
por subtipo. Sin desplegables: son cantidades.

Cada sector muestra su subtotal en vivo, que es la información que el evaluador
necesita para saber si va bien.

**Los resultados** son dos tablas. La de atractivos, con la cantidad y el
porcentaje de cada subtipo sobre su tabla. La de planta, con `Sia`, `Pi` e `ICT`
por sector, y los sectores vacíos marcados como que no aplican.

Con la matriz a medias, `x-matriz-sin-resultados`.

---

## Fuera de alcance

- **Derivar los atractivos del inventario.** Requiere antes que un inventario
  pueda declararse cerrado por zona; ver arriba.
- **La columna de jerarquía** de la hoja de atractivos, que el instrumento
  incluye pero no usa en ninguna fórmula.
- **Índice Espacial de Frecuentación**, la última que queda, bloqueada por una
  fórmula que se contradice en su propia hoja y que hay que aclarar con el autor
  del instrumento.
