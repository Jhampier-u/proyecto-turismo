# Matriz de Involucrados Turísticos Territoriales — diseño

**Fecha:** 7 de agosto de 2026
**Instrumento:** `~/Downloads/fwdmatrices/Matriz de Involucrados Turísticos Territoriales.xlsx`
**Fuente citada en el instrumento:** Mitchell, Agle y Wood (1997), modelo de relevancia de actores.

Octava matriz del sistema, y la primera que **no es un formulario de criterios**.
Las siete anteriores puntúan una lista fija de atributos de la zona; esta puntúa
una lista **variable de actores**, y cada actor es una fila con sus propias
puntuaciones.

---

## Qué mide

Quién tiene algo que decir sobre el turismo en el territorio, y cuánto pesa. A
cada actor —un municipio, una comunidad, una operadora, una asociación— se le
puntúan tres atributos, cada uno con sus criterios:

| Atributo | Criterios | Escala | Grado |
|---|---|---|---|
| **Poder** | Autoridad, Poder (medios coercitivos); Recursos y atractivos, Presupuesto, Tecnológicos, Cadena de valor, Intelectuales (medios utilitarios) | 0-3 | suma, 0-21 |
| **Legitimidad** | Para el territorio, Para la sociedad | 0-3 | suma, 0-6 |
| **Urgencia** | Sensibilidad temporal, Criticidad | 0-3 | suma, 0-6 |

Escala común: 0 no posee, 1 poca, 2 media, 3 máxima. En urgencia el vocabulario
cambia —nada sensible / poco sensible / sensible / alta sensibilidad, y nada
crítico / baja / media / alta criticidad— pero los valores son los mismos.

### Consolidación

El instrumento normaliza cada grado sobre el conjunto de actores evaluados:

```
normalizado = (grado del actor / suma de los grados de todos) × número de actores
relevancia  = poder_normalizado × legitimidad_normalizada × urgencia_normalizada
```

Y clasifica a cada actor en uno de los siete tipos de Mitchell según **cuáles de
los tres atributos posee**, no según cuánto:

| Tiene | Tipo |
|---|---|
| Solo poder | Adormecido |
| Solo legitimidad | Exigente |
| Solo urgencia | Discrecional |
| Poder y urgencia | Peligroso |
| Poder y legitimidad | Dominante |
| Legitimidad y urgencia | Dependiente |
| Los tres | Definitivo |
| Ninguno | No es actor relevante |

---

## Decisiones tomadas

### La normalización se respeta tal cual, y se avisa

Dividir por la suma de todos significa que **añadir un actor cambia el resultado
de todos los demás**. Ninguna de las siete matrices anteriores tiene esa
propiedad: en todas, lo que se puntúa de una zona no depende de lo que se puntúe
de otra cosa.

Se implementa así porque es lo que dice el instrumento, pero la pantalla de
resultados tiene que decirlo con todas las letras: **estos valores son relativos
al conjunto de actores registrados y cambian si se añade o se quita uno.** Sin
ese aviso, alguien comparará el «grado normalizado» de un actor entre dos
momentos y creerá que ha cambiado algo del territorio.

Consecuencia práctica: la relevancia solo significa algo con la lista cerrada.
Por eso el estado `confirmado` importa más aquí que en las otras matrices —es lo
que declara la lista cerrada— y por eso los resultados no se enseñan mientras
haya actores a medias.

### Los tres atributos los marca el evaluador, no un umbral

El instrumento clasifica por «tiene poder / tiene legitimidad / tiene urgencia»,
y esas tres columnas se rellenan a mano: **nunca dice cómo se pasa de un grado
de 14 sobre 21 a "tiene poder"**. No se inventa aquí ese umbral. Cada actor
lleva tres casillas que marca quien evalúa, y el tipo de Mitchell se deriva de
ellas.

Es coherente con lo que el sistema ya hace en otras partes: el grado se calcula,
el juicio se registra. Y evita que un umbral inventado convierta a un actor en
«definitivo» sin que nadie lo haya decidido.

### Errata del instrumento: dos tipos intercambiados

En la tabla de tipos del original, **«Exigentes» está asociado a legitimidad y
«Discrecionales» a urgencia**. En Mitchell es al revés: *demanding* (exigente)
es el que solo tiene urgencia, y *discretionary* (discrecional) el que solo tiene
legitimidad.

Se implementa **según Mitchell**, no según la hoja, y queda escrito aquí para
que la próxima persona no lo tome por un fallo. Mismo criterio que con la tabla
de rangos de Irritación: cuando el instrumento se contradice con su propia
fuente, gana la fuente.

---

## Arquitectura: por qué esta no cabe en el registro tal como está

`App\Matrices\Registro` clasifica sus entradas en tres tipos: `matriz`
(criterios puntuables con estado borrador/confirmado), `inventario` (CRUD sin
estado) y `resultado` (derivado de otras).

Involucrados es **un CRUD con estado**, y no existe. Además, cada entrada de
tipo `matriz` declara `'criterios' => N`, un número fijo que alimenta el «21 de
34 respondidos» de la página de zona. Una lista variable de actores no tiene
denominador fijo: son «cinco actores, tres de ellos a medias».

Se añade un cuarto tipo, `actores`:

- `Registro::ENTRADAS['involucrados']` con `'tipo' => 'actores'` y `'criterios' => null`.
- `EstadoZona::fila()` gana una rama `filaActores()`, junto a las tres que ya
  tiene. Su detalle es «5 actores · 2 sin completar» o «sin actores todavía».
- El progreso de la zona la cuenta como una más: `Registro::matrices()` pasa a
  incluir los tipos `matriz` y `actores`, porque las dos son cosas que se
  validan. Hay que revisar su filtro y el test que fija el recuento.

**Esto toca una pieza compartida por las siete matrices anteriores**, así que el
plan de implementación debe empezar por ahí y dejar la suite en verde antes de
tocar nada de Involucrados.

---

## Modelo de datos

Dos tablas.

**`involucrados_config`** — una fila por zona, con el estado del conjunto:
`zona_id` único, `user_id`, `estado` (`borrador`/`confirmado`), marcas de
tiempo. Es lo que permite validar la lista y lo que lee la página de zona.

**`involucrados`** — una fila por actor:

- `zona_id`, `nombre` (obligatorio), `orden`.
- Once columnas de criterio, `tinyInteger` nullable sin defecto, con el prefijo
  de su atributo: `pod_autoridad`, `pod_poder`, `pod_recursos`,
  `pod_presupuesto`, `pod_tecnologicos`, `pod_cadena_valor`,
  `pod_intelectuales`, `leg_territorio`, `leg_sociedad`, `urg_sensibilidad`,
  `urg_criticidad`.
- Tres booleanos: `tiene_poder`, `tiene_legitimidad`, `tiene_urgencia`.

Nullable por lo mismo que las demás: un criterio sin responder no es un cero, y
aquí el 0 significa «no posee», que es una valoración.

**Nada calculado se guarda.** Los grados son sumas, los normalizados dependen
del conjunto y el tipo de Mitchell se deriva de tres booleanos. Guardar
cualquiera de los tres sería una segunda fuente de verdad que se desincroniza
en cuanto se añade un actor —que es precisamente lo que aquí pasa a menudo—.

---

## Interfaz

**Una pantalla, no un formulario largo.** La lista de actores con su nombre y su
grado en cada atributo, y cada actor se edita en su propia página. Es el patrón
que ya usa Inventario, que es el CRUD que existe.

**El formulario de un actor** lleva sus once criterios agrupados en los tres
atributos, con el componente de escala 0-3 que ya existe, más las tres casillas
de atributo. Las casillas van juntas y con una explicación de qué significan:
son el juicio, no una puntuación más.

**Los resultados** listan a los actores ordenados por relevancia, con sus tres
grados, sus tres normalizados, el producto y su tipo de Mitchell. Encabezando la
tabla, el aviso de que los normalizados son relativos al conjunto.

Con la lista vacía o con algún actor a medias, el componente
`x-matriz-sin-resultados` que ya existe.

---

## Fuera de alcance

- **Las otras dos matrices.** Frecuentación está bloqueada por una fórmula
  ambigua en su hoja original; Concentración, por una decisión de producto sobre
  la taxonomía del inventario.
- **El gráfico** del instrumento original.
- **Reordenar actores** por arrastre. La columna `orden` existe para que se
  pueda añadir después sin migración.
