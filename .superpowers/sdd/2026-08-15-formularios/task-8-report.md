# Tarea 8 — revisión final de la rama y traspaso

Rama: `fase-4-formularios` · Base: `2938e23` · Suite final: **662 en verde**
(646 en la base). 18 commits.

## Qué se revisó

`git diff 2938e23..HEAD` entero, con `superpowers:requesting-code-review` y
recibido con `superpowers:receiving-code-review`. Los hallazgos se
**verificaron uno a uno antes de tocar nada**, no se implementaron a ciegas.

## Lo que la revisión final confirmó como correcto

- **La derivación del estado coincide con el predicado de autorización.** El
  componente decide con `$evaluacion->estado === 'confirmado'` y
  `auth()->user()->esJefe()`, que es exactamente lo que usa
  `EvaluacionZonaController` para bloquear. De ahí que el **admin** reciba la
  franja neutra: tampoco él puede reabrir una matriz validada. Un prop
  booleano lo habría acertado en siete vistas y fallado en alguna.
- **Las ocho vistas convergieron en vez de separarse.** Misma posición, misma
  indentación, mismo cálculo de `$bloqueado`, mismo guardado del pie.
- **Ninguna clase de Tailwind construida por concatenación**, nada por debajo
  de 14 px, ningún `uppercase`, `package-lock.json` en cero commits, y los 18
  mensajes en español con el trailer.

## Los dos defectos que encontró, los dos en los tests

Ninguno era de código de producción. Los dos eran **tests que no podían
fallar**, que en este repositorio es peor que no tenerlos.

### 1. Un barrido doblemente inerte

`test_ningun_formulario_ofrece_actualizar_datos_a_quien_no_es_jefe` recorría
las ocho vistas comprobando que no ofrecieran un botón inalcanzable. Pero:

- **nunca confirmaba ninguna matriz**, así que `$bloqueado` era falso y la
  rama del pie que el test decía vigilar no se pintaba en ninguna de las
  ocho; y
- el texto `Actualizar Datos` **ya no existe en `resources/` ni en `app/`**,
  así que la aserción no podía fallar aunque alguien restaurara el código
  muerto entero.

Es de mi plan, no de quien lo ejecutó: el plan especificó el barrido con esa
forma exacta. Y es **la segunda vez en esta rama** que aparece la misma
especie de defecto —antes fue una aserción sobre una clase de Tailwind que
`<x-criterio-pildoras>` también pinta, y que pasaba con la franja borrada de
la vista—.

Reescrito como `test_los_ocho_llegan_cerrados_a_quien_no_es_jefe`: ahora
confirma cada matriz dentro del bucle y comprueba el estado de solo lectura.
**Verificado en las dos direcciones, por el subagente y por mí por separado**,
rompiendo el tercer estado del componente y viendo el test ponerse rojo.

### 2. Y de paso cerró el hueco de cobertura que dejaba

Ese arreglo cubre algo que no cubría nada: el estado «Validada · solo
lectura» **a través de una página real** en `paisaje`, `valoracion_territorial`
y `potencialidad`. Son justo las tres que nunca tuvieron el aviso de bloqueo
viejo, o sea las tres donde ese estado es **comportamiento nuevo**. Estaban
cubiertas solo por el test del componente en aislado.

### 3. La línea de autoría no la vigilaba nadie

`franja-matriz.blade.php` pinta quién editó por última vez y cuándo —lo que
queda de la vieja «Última edición»—, pero **ningún test ponía `user_id`** en
una evaluación, así que ese bloque no se ejecutaba nunca y se podía borrar sin
que la suite se enterara. Peor: sí había un test exigiendo que la frase
«Última edición» **no** apareciera, así que lo único que tocaba este contenido
vigilaba su forma vieja y no la nueva.

Cubierto con un test que fija un autor **de nombre explícito**, no el del
`setUp`: los nombres de `UserFactory` salen de Faker en `en_US`, un 1,4 %
llevan apóstrofo, y Blade los escapa —el intermitente que ya costó un arreglo
en la Fase 3—.

## Menores aplicados

- Tres comentarios de producción en `app/Matrices/Percepcion.php` y
  `Potencialidad.php` nombraban `<x-leyenda-escala>`, borrado en esta rama.
  Renombrados a `<x-franja-matriz>`, que heredó ese comportamiento tal cual.
- Se rescataron dos frases de los docblocks de los componentes retirados: por
  qué la escala va una vez por formulario y no una por criterio, y la trampa
  de que una frase partida en dos líneas de fuente no la encuentra un
  `assertSee()`.
- `data-franja` pasa a estar fijado en sus tres valores, no solo en
  `borrador`.

## Lo que se decidió no arreglar

- **La consulta extra de `$evaluacionValidada`** en la barra lateral duplica,
  en siete de ocho llamantes, una que el mismo componente hace diez líneas más
  arriba. Es una columna, una vez por render, y la alternativa —pasar un prop
  por ocho vistas que no se ponen de acuerdo en los nombres— es justo lo que
  este diseño evita. Queda documentada como conocida y aceptada.
- **Paisaje pinta su flash una tarjeta más abajo** que las demás, porque tiene
  una tarjeta de metadatos entre medias. Es anterior a esta rama y la
  invariante que la rama sí fija —la franja antes del flash— se cumple en las
  siete.

## Sobre los informes por tarea

Esta rama no deja un `task-N-report.md` por tarea, a diferencia de
`2026-08-13-dashboard-mis-zonas`. La ejecución fue con subagentes, y el
detalle de cada tarea —qué se verificó, qué falló primero, qué encontró cada
revisión— está en la bitácora `.superpowers/sdd/progress.md` y en los mensajes
de commit, que en esta rama son largos a propósito. **Si la próxima rama
quiere los informes por tarea, el plan tiene que pedirlos explícitamente**: el
de esta solo pidió el de la Tarea 8.

## Estado final

- **662 tests en verde**, corridos sobre el resultado de la rama.
- Árbol limpio.
- **Falta fusionar** — y fusionar se pregunta (regla 3 de `CLAUDE.md`).
