# Tarea 5 — revisión final de la rama y traspaso

Rama: `fase-3-detalle-zona` · Base: `a625645` · Suite final: **646 en verde**

## Qué se revisó

`git diff a625645..d56ced0` — los tres commits de implementación, más la spec
y el plan. La revisión se pidió con `superpowers:requesting-code-review` y se
recibió con `superpowers:receiving-code-review`, que es lo que evitó
implementar a ciegas: de los diez puntos que devolvió, **dos se verificaron
primero y resultaron ciertos**, y el resto se clasificaron sin tocarlos.

## Lo que la revisión confirmó como correcto

Vale la pena dejarlo escrito, porque son las preguntas que se volverán a
hacer:

- **`desglose()` y `progresoDe()` cuentan igual.** Los dos derivan el total de
  `count(Registro::matrices())` y aplican la misma regla binaria
  (`=== 'confirmado'` → hechas, el resto → borradores). Un `estado` nulo o
  fuera del enum cae en borradores en los dos.
- **El `?? 'Sin asignar'` asimétrico está bien.** `lugar_id` es
  `constrained()` sin `nullable()`; `jefe_user_id` sí es `nullable()`. La
  vista refleja el esquema, no se le olvidó una guardia.
- **La clase arbitraria sobrevive al purgado, comprobado sobre el CSS
  construido**, no supuesto: `320px_1fr\]{grid-template-columns:320px 1fr` está
  en `public/build/assets/app-*.css`.
- Restricciones globales del plan: ninguna pantalla fuera de
  `operativo/zona/panel` tocada, ningún componente modificado ni creado, sin
  `text-xs` ni `uppercase`, `package-lock.json` fuera de los commits.

## Defecto 1 — el panel se contradecía con la fila que tiene al lado

**Verificado antes de arreglarlo**, con un test de usar y tirar que imprimía
las dos lecturas a la vez. Las dos direcciones divergían:

| Situación, alcanzable con dos clics | Panel decía | Fila decía |
|---|---|---|
| ST guardada antes de dar de alta ningún sitio | `1 en borrador · 9 sin empezar` | `sin empezar` (gris) |
| Un sitio dado de alta antes de guardar la ST | `10 sin empezar` | `Borrador · 1 sitios, 1 sin DET` |

La causa es que `desglose()` decidía **solo por la fila de configuración**,
mientras `filaActores()`/`filaSitios()` decidían por la configuración **y** el
recuento de filas hijas. En las otras ocho matrices no puede pasar: allí la
evaluación **es** el trabajo, así que su ausencia sí significa «sin empezar».
En estas dos el trabajo son las filas hijas y la configuración es aparte —solo
la crean `guardarSt()` y `validar()`—.

**El arreglo, en dos commits porque son dos lados distintos:**

- `39ac023` — el lado de la fila. El estado de la configuración manda sobre el
  recuento, que es lo que los docblocks de `filaActores()`/`filaSitios()` ya
  razonaban **para `validada`** y se habían dejado fuera para `borrador`.
- `a381089` — el lado del contador. No se puede arreglar en la fila: llamar
  «sin empezar» a una lista con un sitio dado de alta escondería trabajo real.
  `desglose()` y `progresoDe()` pasan a contar empezada una entrada con
  configuración **o** con al menos una fila hija.

La lista de cuáles son esas dos entradas vive **una sola vez**, en
`EstadoZona::LISTAS_CON_FILAS_HIJAS`, y hay un test que exige que los dos
caminos devuelvan el mismo array para la misma zona. Sin él, la tarjeta de una
zona en el dashboard y el panel lateral de esa misma zona podrían dar cifras
distintas del mismo progreso — que es exactamente la segunda fuente de verdad
que este servicio existe para no tener.

**Coste:** dos consultas de existencia en `desglose()`, y una agrupada por
lista en `progresoDe()`, que sigue siendo de coste fijo y no por zona.

**Un tercer defecto salió de arreglar el primero:** con cero filas,
`$incompletos === 0` se cumple por vacuidad, así que la fila habría dicho «0
sitios completos» y habría **ofrecido validar** — que
`FrecuentacionController::validar()` e `InvolucradosController::validar()`
rechazan sin al menos una fila. Es el desajuste entre lo que se ofrece y lo que
el controlador acepta que `filaMatriz()` ya documenta evitar. Arreglado en el
mismo commit.

**Comprobado en el navegador además de en la suite:** dashboard y panel dan
`3 validadas / 4 en borrador / 3 sin empezar` para la misma zona, y las dos
filas de lista dicen «Borrador», que es lo que la insignia cuenta.

## Defecto 2 — un test intermitente de la propia rama

`test_el_panel_lateral_es_igual_para_los_tres_roles` comparaba nombres de
`UserFactory` contra el HTML **ya renderizado** con
`assertStringContainsString`. Blade escapa con `e()`, así que un apóstrofo sale
como `&#039;` y el nombre crudo no aparece nunca.

**Medido, no estimado:** 280 de 20 000 nombres del catálogo `en_US` de Faker
llevan apóstrofo — `O'Kon`, `D'Amore`, `O'Connell` — o sea el **1,4 %**. El
test miraba dos nombres, así que fallaba **una de cada ~35 corridas**.

Y comprobado de punta a punta: con `Dr. Joseph O'Connell`, el HTML servido
**no** contiene el nombre crudo y **sí** `Dr. Joseph O&#039;Connell`.

El arreglo es `assertSee`, que escapa la aguja antes de buscarla —y que es lo
que ya usaba el test hermano de la línea 236, por eso ese nunca falló—. Además
el miembro de equipo lleva ahora un apóstrofo **fijo**, para que el test no
solo deje de fallar por azar sino que falle de verdad si alguien vuelve a
compararlo sin escapar.

**Merece mirarse si el patrón está en más sitios**: lo que lo hacía invisible
es justo que pasa el 97 % de las veces.

## Los dos puntos que la spec ya respondía

Se comprobó que estuvieran **ejecutados**, no se rediscutieron:

- El teal de «Equipo» (`bg-teal-100 text-teal-800`) no colisiona con ningún
  valor de `ESTILOS_ESTADO`. Medido en la misma tarjeta: `rgb(204,251,241)`
  contra `rgb(220,252,231)`.
- El `📍` se queda. Esta vez con motivo comprobado y no como aplazamiento:
  cada icono del catálogo ya es la identidad de una de las diez matrices que
  la propia página lista debajo.

## Menores anotados y no arreglados

Están en el traspaso, en «Restos que la rama deja escritos y no toca»: el
orden no determinista del equipo, la `descripcion` vacía (`''`) que no cae en
el texto de reserva —y que si se arregla hay que arreglar en las tres
ocurrencias a la vez—, y el N+1 de `firma()`, anterior a esta rama.

Uno más, de forma: `lg:items-start` y `lg:self-start` son redundantes entre sí.
Se dejan porque el plan pedía las dos y son inofensivas.

## Estado final

- **646 tests en verde**, corridos sobre el resultado de la rama.
- Árbol limpio salvo `.superpowers/brainstorm/`, que ya estaba sin seguir
  antes de empezar.
- **Falta fusionar** — y fusionar se pregunta (regla 3 de `CLAUDE.md`).
