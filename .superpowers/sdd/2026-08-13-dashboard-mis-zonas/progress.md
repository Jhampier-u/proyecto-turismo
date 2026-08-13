# SDD ledger — plan: docs/superpowers/plans/2026-08-13-dashboard-mis-zonas.md

Spec: docs/superpowers/specs/2026-08-13-dashboard-mis-zonas-design.md (leída)
Rama: dashboard-mis-zonas (no es main; es la práctica de este repositorio, las
cuatro fases anteriores salieron así). Base de rama: 0cd8ecf. Suite base: 608.
Cabeza al empezar: 06fc5a3.

Ruling: `scripts/task-brief` no encuentra los encabezados —el plan está en
español y el script busca «Task N»—. Briefs extraídos a mano con awk a
`task-N-brief.md` en este mismo directorio. Coste si me equivoco: ninguno, es
el mismo texto del plan recortado; se compara con `### Tarea N` del plan.

Ruling: no se crea worktree. La rama `dashboard-mis-zonas` ya aísla el trabajo
y es como se hicieron las cuatro ramas anteriores; un worktree además rompería
el `public/build` compartido que las tareas 4 y 5 reconstruyen. Coste si me
equivoco: el árbol de trabajo principal queda ocupado por esta rama mientras
dure la ejecución.

Ojo: `.superpowers/sdd/.gitignore` es `*`, y sin embargo `progress.md` y los
`*-report.md` del proyecto están **rastreados** (se añadieron con `-f`). Este
directorio de trabajo del plan NO se rastrea, y el `progress.md` del proyecto
—el de la raíz de `sdd/`, que es la bitácora de rama de CLAUDE.md— es otro
fichero distinto de este ledger. No confundirlos: la Tarea 1 sobrescribe
**aquel**, no este.

## Escrutinio previo del plan

### Pares de tareas que comparten fichero o interfaz

| Tareas | Produce → consume | Hallazgo |
|---|---|---|
| T1 → T2, T3, T4, T5 | `progresoDe()` con `hechas`/`borradores`/`sin_empezar`/`total` | Limpio. T2 y T3 solo leen `hechas` y `total`, que no cambian de significado; T4 y T5 leen las cuatro. |
| T1 → admin (fuera de alcance) | `hechas`/`total` para `admin/zonas/index` y `ConmutadorVistaTest` | Limpio. Las claves nuevas se añaden, ninguna se renombra: es la restricción global. |
| T2 → T3 | las dos editan `DashboardController::index()` | Limpio. T3 inserta `$resumen` tras `$progreso` y reescribe la línea del `compact` entera, que el brief da literal. |
| T2 → T5 | `$orden` y `$dir`, ya normalizados, a la vista | Limpio. T5 los pasa a `<x-cabecera-ordenable>` sin volver a validarlos. |
| T2 → T4, T5, T6 | las cuatro editan `dashboard.blade.php` | Limpio, pero **los números de línea envejecen**: T3 inserta un bloque por encima de la lista. Los briefs de T4, T5 y T6 localizan por contenido, no por línea. Verificado en los cuatro. |
| T2 → T3, T5 (tests) | `id="zonas-lista"` como ancla para recortar el HTML | Limpio. Lo ponen T2 y lo conservan T5 (la tabla lo lleva en su `<x-tarjeta>`) y T6 (dentro del `@else`). |
| T4 → T5 | `<x-desglose-estados :progreso="$p" />` | Limpio. T5 lo reutiliza tal cual en la columna Estado. |
| T4 → T5 (tests) | `ConmutadorVistaTest` se toca dos veces | Limpio y **deliberado**: T4 cambia el assert del progreso (fracción → desglose), T5 el del lugar (📍 → nombre). Cada tarea cambia un eje y ninguna relaja el test. |
| T3 → T6 | la franja dentro del `@else` de «cero zonas» | Limpio. El test de T3 con una zona (`assertDontSee`) sigue siendo cierto después de T6. |
| T2 (test del panel) → T3 | `cabecera()` recorta todo lo anterior a la lista, que tras T3 incluye la franja | Limpio: la franja no lleva nombres de zona, así que el `assertStringNotContainsString('Bravo')` no se falsea. |

### Coherencia interna de cada tarea

| Tarea | Sus tests contra su código | Hallazgo |
|---|---|---|
| T1 | tests afirman `total = 10`; el código lo deriva de `Registro::matrices()` | Limpio. Diez validables verificadas contra el registro (12 entradas, `inventario` y `vtt` fuera). |
| T1 | el test de consultas da datos a las **cinco** zonas | Limpio, y es el punto delicado: con la única vacía, `proximoPaso()` instanciaría un `EstadoZona` de más solo en la segunda medición. El brief lo explica. |
| T2 | nueve tests sobre HTML servido; el código ordena la colección | Limpio. `sortBy` con `SORT_NATURAL\|SORT_FLAG_CASE` para texto, sin flags para el número. |
| T2 | `?orden[]=nombre` | Limpio. `in_array` con un array como aguja devuelve false, no revienta. |
| T3 | `>2</p>`, `>3</p>`, `>1</p>` contra el marcado de la franja | Limpio: `<p class="text-3xl…">{{ … }}</p>` renderiza esos literales exactos. Frágil ante un cambio de marcado, que es lo que se quiere que salte. |
| T3 | el test de «terminadas» crea las diez matrices | Limpio tras el arreglo del plan: usa el patrón probado de `EstadoZonaTest`, que rellena las columnas de criterio desde el esquema y así no choca con ningún NOT NULL. |
| T4 | el test del componente no toca la base de datos | Limpio. `$this->blade()` con un array de cuatro cifras, que es lo que devuelve el servicio. |
| T4 | «un estado a cero no se pinta» | Limpio, y con contraparte: el mismo test exige que el que no está a cero sí salga. |
| T5 | `substr_count($lista, 'orden=') === 3` | Limpio: los `<x-boton>` de las filas pintan `<a href>` sin ese parámetro. Verificado en `boton.blade.php`. |
| T5 | `<th scope="col"` cinco veces | Limpio: tres del componente y dos escritas a mano. |
| T5 | `orden=nombre&amp;dir=desc` | Limpio: el href se pinta con `{{ }}`, que escapa el `&`. |
| T6 | `assertDontSee('Tarjetas')` para el conmutador | Limpio: esa palabra solo la pinta `<x-conmutador-vista>`. |
| T7 | no tiene tests propios | Es la puerta: revisión, la página de verdad y traspaso. |

**Nada que reglar antes de empezar.** El escrutinio no encontró contradicciones
entre tareas ni entre una tarea y las restricciones globales; los dos puntos
que más podían morder —el envejecimiento de los números de línea y la doble
edición de `ConmutadorVistaTest`— están cubiertos en los briefs.

## Ejecución

BASE de T1: 06fc5a3. HEAD tras T1: 7b3c247. Suite 611/611, salida limpia.

Ruling: `scripts/sdd-workspace` machacó `.superpowers/sdd/.gitignore` —lo dejó
en `*`, borrando los quince comentarios que explican qué de esa carpeta viaja
con el repositorio y qué no—. Restaurado con `git checkout --`. Coste si me
equivoco: ninguno; el fichero original está en git y el directorio de trabajo
de este plan sigue fuera del alcance de `*.diff` y `*-brief.md`, que es lo que
de verdad se quiere ignorar.

Ruling: **este directorio de trabajo NO se borra al terminar**, contra lo que
dice la skill. La regla 3 de `CLAUDE.md` manda que los `*-report.md` de cada
tarea viajen con el repositorio —son el rastro de por qué el código quedó como
quedó—, y la skill los borraría. Se quedan aquí, en el subdirectorio del plan,
y se añaden con `git add -f` en T7. No se copian a la raíz de `sdd/`: allí ya
viven los `task-N-report.md` de la Fase 1 y copiarlos encima destruiría
justamente ese rastro. Coste si me equivoco: un directorio de más en el
repositorio, con los informes dentro.

Task 1: revisión — cumplimiento ✅, calidad aprobada. El revisor verificó tabla
por tabla que la unicidad de `zona_id` que el comentario nuevo afirma es cierta
en las diez, y comprobó que los dos consumidores de `hechas`/`total` fuera del
alcance (`admin/zonas/index`, `ConmutadorVistaTest`) siguen leyendo solo esas
dos claves.
Task 1: minor (deferred): `EstadoZona.php:127-129` cita una sola migración para
la unicidad de `zona_id` «en las diez tablas», pero esa migración retrofitea
cuatro; las otras seis ya traían el `unique()` de fábrica. La afirmación es
cierta, la cita es imprecisa.
Task 1: minor (deferred): `Admin\ZonaController.php:43` dice «seis consultas»,
que este cambio dejó desfasado. Es una pantalla de admin, fuera del alcance de
la rama por restricción global; se anota para el traspaso.
Ruling: el mismo desfase en `Operativo\DashboardController.php:31` SÍ se
arregla, porque T2 reescribe ese comentario entero de todos modos. Corregido en
el brief de T2 («una consulta por matriz Y por zona»), no en el plan, que se
queda como el argumento que fue. Coste si me equivoco: un comentario histórico
redactado de otra manera.
Task 1: complete (commits 06fc5a3..7b3c247, review clean)

BASE de T2: 7b3c247. HEAD tras T2: 7ffe6a6. Suite 619/619 en 44,8 s.
Ruling: el implementador levantó que el brief lista **ocho** tests mientras el
texto del plan dice «nueve» y «620». Contados: ocho. Es un error aritmético
mío en la tabla de recuento del plan, no un test que falte —los ocho cubren
los cuatro puntos que la spec exige del orden (por defecto, invertir, progreso
descendente, `orden` desconocido con 200) más los dos que el plan añadió (las
dos maquetaciones, el panel que no se mueve) y dos casos límite (lugar,
`?orden[]=`)—. Las cifras esperadas del resto de la rama bajan en uno: T3 622,
T4 626, T5 631, T6 **632**. Coste si me equivoco: un test de orden sin escribir,
que la revisión de rama entera volvería a levantar.

Task 2: revisión — cumplimiento ✅, calidad aprobada. El revisor verificó los
dos riesgos que le nombré: los tests recortan el HTML por maquetación antes de
comparar posiciones (`OrdenMisZonasTest::maquetacion()`), y `proximoPaso()`
recibe el orden por defecto porque se calcula **antes** de leer la petición
(`DashboardController.php:57-63`).
Task 2: minor (deferred): `DashboardController.php:122` hace `sortBy()->reverse()`
para `dir=desc`, así que dos zonas empatadas —mismo lugar, mismo progreso—
quedan en orden de creación al ascender y en el inverso al descender. Ningún
requisito lo exige y ningún test lo cubre.
Task 2: complete (commits 7b3c247..7ffe6a6, review clean)

BASE de T3: 7ffe6a6. HEAD tras T3: 272453c. Suite 622/622.
Ruling: `scripts/review-package` vuelve a dejar `.superpowers/sdd/.gitignore`
en `*` cada vez que se ejecuta. Restaurado otra vez y **se deja de usar el
script**: los paquetes de revisión se arman a mano con `git log --oneline`,
`git diff --stat` y `git diff -U10` redirigidos al mismo fichero del
directorio de trabajo. Coste si me equivoco: el paquete no lleva algún
encabezado que el script pusiera; el revisor recibe lo mismo que necesita
—lista de commits, resumen y diff con contexto— y así lo he verificado en el
fichero.

Task 3: revisión — cumplimiento ✅, calidad aprobada. El revisor comparó clase
a clase el marcado de la franja con el del panel de admin y confirmó que los
tres tests afirman sobre el trozo recortado, no sobre la página entera.
Resuelvo su ⚠️ yo, que tengo el contexto que le faltaba: `progresoDe()` fija
`total = count(Registro::matrices())`, que son diez siempre, así que el
guardián `$p['total'] > 0` de `terminadas` no puede dispararse hoy. Es
defensivo y se queda: cuesta nada y evita que una zona sin matrices contara
como terminada por `0 === 0`.
Task 3: minor (deferred): la franja lleva `mb-8` que el panel de admin no
tiene. Justificado —debajo hay contenido y en admin no—, se anota por si la
revisión de rama lo ve de otro modo.
Task 3: complete (commits 7ffe6a6..272453c, review clean)

Ruling: el plan mandaba `git add … public/build` en T4 y T5, y `public/build`
está en `.gitignore` desde siempre (línea 17, `/public/build`): el `git add`
habría abortado el commit con «paths are ignored». Quitado de los dos briefs.
`npm run build` **sí** se sigue corriendo —hace falta para comprobar que las
clases nuevas de Tailwind no las purga el build, y para mirar la página en T7—,
pero lo construido no viaja: se genera en el despliegue. Coste si me equivoco:
un despliegue tendría que construir los assets, que es justo lo que ya hace.

BASE de T4: 272453c. HEAD tras T4: c0910ed. Suite 626/626.
**Hueco de mi plan, encontrado al implementar.** El barrido de consumidores de
`hechas / total` que escribí en la spec y el plan encontró `admin/zonas/index`
y `ConmutadorVistaTest`, y se dejó dos: `PaisajeTest` y
`ValoracionTerritorialTest` tienen cada uno un
`test_la_zona_aparece_en_el_dashboard_con_su_progreso` que afirmaba
`assertSee('0 / 10')` contra el dashboard. **Es la cuarta vez en este
repositorio que un barrido falla por cómo se buscó y no por lo que había** —las
tres anteriores están en CLAUDE.md—: busqué la clave `'hechas'` en el código y
no la **cadena renderizada** en los tests.
Ruling: la corrección estaba obligada (sin ella la suite queda roja) y el
criterio es el del paso 6 del brief: actualizar al dato que pasa a ser
(`'10 sin empezar'`), no relajar. El revisor verificó que los dos tests siguen
afirmando lo que su nombre dice —la zona es nueva, así que las diez sin empezar
son un dato real que fallaría con el dashboard roto—. Coste si me equivoco: dos
tests que afirman sobre una cadena distinta de la que su autor eligió. Va al
traspaso.
Task 4: revisión — cumplimiento ✅, calidad aprobada. Verificado que el
componente no lleva ni un color a mano, que el orden de las insignias está
fijado por posiciones y que el `assertSame(2, …)` de `ConmutadorVistaTest`
sigue siendo un `assertSame(2, …)`.
Task 4: minor (deferred): `dashboard.blade.php:102` fija `w-56` a ojo en la
columna de progreso de la lista; la tarjeta no tiene ancho fijo. Lo mandaba el
brief y T5 reescribe ese bloque entero.
Task 4: complete (commits 272453c..c0910ed, review clean)

BASE de T5: c0910ed. HEAD tras T5: 3fb0732. Suite 631/631.
Task 5: revisión — cumplimiento ✅, calidad aprobada. El revisor comprobó que
`assertStringNotContainsString('<button')` no pasa por casualidad —`<x-boton>`
con `href` pinta un `<a>`— y que `orden=` solo lo emite la cabecera, así que el
conteo de tres es una afirmación real.
Su ⚠️ —que la suite cierre en 631— la resuelvo con la corrida de T6, que vuelve
a pasarla entera.
**Defecto de mi plan que la revisión rozó sin nombrar.** El revisor marcó como
menor que la prop `alineacion` de `<x-cabecera-ordenable>` no la usa nadie. Lo
peor no es eso: la prop se interpola en
`'px-6 py-3 text-' . $alineacion . ' text-sm …'`, o sea, **una clase de
Tailwind construida por concatenación**, que es exactamente lo que la
restricción global del plan prohíbe porque el purgado se las lleva. Hoy no se
rompe de milagro: `text-left` aparece literal en `admin/lugares/index` y en
`admin/zonas/index`, así que el purgado la conserva por ellas.
Ruling: se arregla en T6 —quitar la prop y escribir `text-left` literal—, en
vez de dejarlo a la revisión de rama. Es una violación de una restricción
global, no un pulido, y muere con dos líneas. Coste si me equivoco: T6 toca un
fichero más de los que su brief nombra, y lo dice en su commit.
Task 5: minor (deferred): la celda de descripción, la barra y los botones de la
tabla duplican el marcado de la tarjeta. Duplicación **preexistente** —ya
estaba entre la lista vieja y las tarjetas—, anotada por si una fase futura
quiere un parcial común de fila de zona.
Task 5: complete (commits c0910ed..3fb0732, review clean)

BASE de T6: 3fb0732. HEAD tras T6: 1bcffd1. Suite 632/632 en ~60 s.
T6 hizo las dos cosas: el `@if/@else` de cero zonas —el panel de «siguiente
paso» se queda FUERA del `@else` porque tiene su propia guarda— y el arreglo
que le encargó T5: `<x-cabecera-ordenable>` pierde la prop `alineacion` que no
usaba nadie y escribe `text-left` literal en vez de construir la clase por
concatenación. Con eso muere la violación de la restricción global; se
verificó con `npm run build` que el purgado no se lleva `text-left`.
El recuento cierra en **632, no 633**: la base real era 631 por el error
aritmético del plan ya señalado en T2, no por un test que falte.
Task 6: complete (commits 3fb0732..1bcffd1, paquete de revisión armado a mano
en `review-3fb0732..1bcffd1.diff`)

## Aquí se cortó la sesión (13 de agosto, ~14:55)

El paquete de revisión de T6 quedó escrito y **la sesión murió antes de que
volviera el revisor**. No hay `task-6` review entry por eso, no porque la
revisión fallara. Lo que la auto-revisión del implementador comprobó está en
`task-6-report.md`; lo que falta es el par de ojos ajeno.

**Queda entera la Tarea 7**, que es la puerta antes de fusionar
(`task-7-brief.md`, y el texto largo en la Tarea 7 del plan):

1. **Mirar la página de verdad**, no solo sus tests. En las cuatro últimas
   ramas este paso encontró lo que ningún test veía. Seis comprobaciones
   listadas en el brief, con un jefe de tres o más zonas en estados distintos.
2. **Revisión de la rama entera** (`git diff 0cd8ecf..HEAD`). Dos cosas que un
   revisor mirará con lupa y que son decisiones, no descuidos: `hechas` sin
   renombrar y el desglose sin insignia de «terminada». La respuesta está en la
   spec; no se rehacen.
3. **Suite sobre el resultado final** — esperado 632.
4. **Traspaso**: entrada de rama en `docs/ESTADO-PROYECTO.md`, tachar la Fase 2
   del punto 14, y anotar los restos (ver más abajo).
5. Commit del traspaso, y **fusionar se pregunta**.

**Menores aplazados que la revisión de rama debe ver** (todos con su razón en
la entrada de su tarea, más arriba):

- T1: `EstadoZona.php:127-129` cita una sola migración para la unicidad de
  `zona_id` en las diez tablas; esa migración retrofitea cuatro.
- T1: `Admin\ZonaController.php:43` dice «seis consultas», desfasado. Admin
  está fuera del alcance por restricción global.
- T2: `DashboardController.php:122` hace `sortBy()->reverse()`, así que dos
  zonas empatadas quedan en orden de creación al ascender y en el inverso al
  descender.
- T3: la franja lleva `mb-8` que el panel de admin no tiene (justificado:
  debajo hay contenido).
- T4: `PaisajeTest` y `ValoracionTerritorialTest` cambiaron su `assertSee` de
  `'0 / 10'` a `'10 sin empezar'`. Fue obligado y no relaja nada, pero son dos
  tests afirmando sobre una cadena distinta de la que eligió su autor. **Va al
  traspaso.**
- T5: la fila de la tabla duplica el marcado de la tarjeta. Duplicación
  preexistente; candidata a parcial común en una fase futura.

**Restos que esta rama deja escritos y no toca**, para el traspaso: renombrar
`hechas` a `validadas` cuando una fase entre de verdad en admin, y el `📍` de
la tarjeta, que es anterior a la Fase 0.

**Premisa comprobada que no generó trabajo:** «Mis Zonas» elige por rol y el
selector de la barra hace la unión; hoy coinciden porque `Admin\ZonaController`
valida los roles, y el día que esa validación se afloje discreparán.

