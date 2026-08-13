# Informe — Tarea 2 del plan de guardado parcial: migración de criterios a nullable

## Estado: DONE_WITH_CONCERNS

La sesión tuvo dos partes. Primero paré en el gate del Step 2 porque un
número no cuadraba (ver diagnóstico íntegro más abajo, sin editar). El
coordinador verificó los dos hallazgos de forma independiente, confirmó que
el fallo estaba en el plan y no en mi lectura, y autorizó explícitamente las
dos correcciones propuestas. Con esa luz verde, escribí ambas migraciones ya
corregidas, las apliqué, corrí la suite completa y commiteé.

**Concern único y ya anotado explícitamente: el Step 6 (verificación en
PostgreSQL) queda pendiente.** No hay `psql` ni servidor PostgreSQL nativo
accesible en esta máquina, y Docker está prohibido sin excepciones. No se
improvisó ninguna alternativa.

### Resultado final

- Migraciones escritas, aplicadas en SQLite y commiteadas:
  `f5ff57f feat(db): criterios nullable para permitir guardar matrices a medias`.
- Conteos finales: `evaluaciones_percepcion: 16`, `evaluaciones_paisaje: 34`,
  `evaluaciones_valoracion_territorial: 21`, `Potencialidad criterios: 156` —
  los cuatro coinciden exactamente con lo esperado.
- Ninguna columna calculada (`media_*`, `pond_*`, `*_promedio`, `*_total`,
  `val_*`, `fn_total`, `fx_total`) fue tocada; se verificó antes y después que
  quedan con el mismo `notnull`/tipo que tenían.
- Suite completa: **185 passed (955 assertions)**, igual que la línea base.
  `PotencialidadCalculoTest` (14 tests, la red de seguridad de los cálculos)
  pasa completo.
- Los tests corren contra `DB_DATABASE=:memory:` (ver `phpunit.xml`), así que
  cada corrida migra desde cero — es evidencia de extremo a extremo, no solo
  de que la base de dev ya migrada funciona.

---

## Diagnóstico original (Step 2), tal como se reportó antes de la autorización

El bloque que sigue es el informe que escribí **antes** de que el coordinador
revisara y autorizara las correcciones. Lo dejo sin editar como evidencia de
que el hallazgo se hizo por verificación real contra el esquema, no
retroactivamente para justificar el resultado final.

---

## Step 2 — Conteo de columnas de las tres matrices generadas

Ejecuté literalmente el script de tinker del brief (Step 2) contra el esquema
real, **antes de escribir ningún fichero**:

```
evaluaciones_percepcion: 0        (esperado: 16)
evaluaciones_paisaje: 34          (esperado: 34)   ✓
evaluaciones_valoracion_territorial: 21  (esperado: 21)   ✓
```

`evaluaciones_paisaje` y `evaluaciones_valoracion_territorial` cuadran exacto.
`evaluaciones_percepcion` da **0**, no 16. La migración de Step 1, aplicada
literalmente como la trae el brief, no tocaría ni un solo criterio de
Percepción — dejaría sus 16 columnas `NOT NULL`, y el guardado a medias
seguiría siendo imposible para esa matriz. Es un fallo silencioso: nada en la
suite de 185 tests lo detectaría hoy (la validación actual sigue exigiendo
todos los criterios), y solo se notaría en la Tarea 3, cuando se implemente el
guardado parcial de verdad.

### Causa raíz (verificada con el esquema real, no supuesta)

Volqué `Schema::getColumnListing('evaluaciones_percepcion')` completo. Las 16
columnas de criterio están numeradas dentro de cada categoría:

```
ds1_posicion_turistica, ds2_interes_participar, ds3_contribucion_social,
pl1_conoc_recursos, pl2_conoc_atractivos, pl3_conoc_motivo_visita,
pl4_conoc_flujo_visitantes, pl5_sentimiento_visitantes, pl6_necesidad_visitantes,
pe1_incidencia_ingresos, pe2_beneficios_esperados, pe3_disposicion_inversion,
no1_organizacion_colectiva, no2_lideres_sociales, no3_opinion_lideres, no4_conflictos_sociales
```

El filtro del brief comprueba `str_starts_with($c, $prefijo.'_')`, es decir
busca `ds_`, `pl_`, `pe_`, `no_` — con el guión bajo pegado al prefijo de dos
letras. Pero aquí el guión bajo llega después de un dígito de ítem
(`ds1_`, `pl4_`, etc.), así que ninguna columna coincide. Esto es distinto de
`evaluaciones_paisaje` y `evaluaciones_valoracion_territorial`, donde las
columnas sí van `prefijo_nombre` sin numerar — por eso esas dos tablas dan
bien con el filtro tal cual viene.

### Corrección propuesta (verificada, no aplicada)

Cambiar la comparación de `str_starts_with($c, $prefijo.'_')` a una regex que
tolere el dígito opcional: `preg_match('/^'.$prefijo.'\d*_/', $c)`.

Repetí el script de Step 2 con este único cambio, contra el esquema real:

```
evaluaciones_percepcion: 16 -> ds1_posicion_turistica,ds2_interes_participar,ds3_contribucion_social,
  pl1_conoc_recursos,pl2_conoc_atractivos,pl3_conoc_motivo_visita,pl4_conoc_flujo_visitantes,
  pl5_sentimiento_visitantes,pl6_necesidad_visitantes,pe1_incidencia_ingresos,pe2_beneficios_esperados,
  pe3_disposicion_inversion,no1_organizacion_colectiva,no2_lideres_sociales,no3_opinion_lideres,no4_conflictos_sociales

evaluaciones_paisaje: 34 -> (sin cambios, misma lista de 34 verificada contra la migración original)

evaluaciones_valoracion_territorial: 21 -> (sin cambios, misma lista de 21 verificada contra la migración original)
```

Los tres números cuadran exactamente con lo esperado, y en `evaluaciones_percepcion`
ninguna columna calculada (`media_ds`, `pond_ds`, `media_pl`, `pond_pl`,
`media_pe`, `pond_pe`, `media_no`, `pond_no`, `percepcion_total`) entra en la
lista — ninguna de ellas empieza por `ds`, `pl`, `pe` ni `no`, así que no hay
riesgo de convertir una columna decimal calculada a `tinyInteger`.

**No apliqué este cambio al fichero de migración.** Lo dejo aquí como
propuesta verificada porque la instrucción del encargo para este gate
concreto es pararse y reportar, no autocorregir.

---

## Step 4 — Conteo y precisión de Potencialidad

### Conteo de criterios

```
criterios: 156   (esperado: 156)   ✓
calculados val_: 21
```

Cuadra exacto. La lista de columnas fijas (`id, zona_id, user_id, estado,
fn_total, fx_total, created_at, updated_at`) del brief es correcta para esta
tabla.

### Precisión decimal de las columnas calculadas — antes

`PRAGMA table_info` de SQLite no expone precisión/escala (todo aparece como
`numeric`), así que la verificación real es contra la migración original
declarada, tal como pide el brief:
`database/migrations/2025_12_07_000001_create_evaluaciones_potencialidad_table.php`.

Leyendo esa migración línea por línea:

- Los 21 `val_*` (`val_rn_litoral`, `val_rn_montana`, ... `val_superestructura`):
  **`decimal(6, 4)`**, `nullable()` (ya nullable desde el origen).
- `fn_total`, `fx_total`: **`decimal(8, 4)`**, `nullable()` (ya nullable desde
  el origen).

### Discrepancia encontrada en el borrador de Step 3

El código de ejemplo del brief para Step 3 aplica `decimal($columna, 8, 4)`
**uniformemente** a todos los "calculados" (`val_*` junto con `fn_total` y
`fx_total`, mismo bucle, misma línea). Eso ensancharía los 21 `val_*` de
`(6,4)` a `(8,4)` — cambia la precisión declarada (2 dígitos enteros más) sin
necesidad, exactamente lo que el propio Step 4 del brief pide comprobar y
evitar: *"Ajustar `decimal($columna, 8, 4)` a lo que declare la migración
original ... para no cambiar la precisión de paso."*

A diferencia del hallazgo de Percepción, aquí el brief **sí instruye
explícitamente a ajustar** antes de aplicar, así que la corrección
correspondiente (separar el bucle: `val_*` → `decimal(6,4)`, `fn_total`/
`fx_total` → `decimal(8,4)`) está sancionada por el propio encargo. La dejo
igualmente sin aplicar porque no quise escribir ningún fichero de migración
mientras el Step 2 seguía sin resolver — prefiero entregar ambos ficheros
juntos, ya correctos, en una sola pasada, en vez de dejar una migración a
medias en el repo.

### Precisión decimal — después (propuesta)

- `val_*` (21 columnas): `decimal(6, 4)->nullable()->default(null)` — mismo
  ancho, solo se retira el valor por defecto (ya eran nullable, así que este
  paso es un no-op funcional salvo por quitar cualquier `default()` residual).
- `fn_total`, `fx_total`: `decimal(8, 4)->nullable()->default(null)` — igual,
  ya eran nullable.

Nota: como `val_*`, `fn_total` y `fx_total` **ya eran `nullable()` desde la
migración original** (creada así porque son resultados calculados que no
existen hasta que se computan), tocarlos en esta tarea es defendible pero no
estrictamente necesario para el objetivo de "criterios nullable" — el propio
comentario del brief para Step 3 lo admite ("mientras falte algún criterio
activo no hay resultado que enseñar, y un 0 ahí sería otra mentira"), así que
lo mantengo en el paquete propuesto, solo con la precisión corregida.

---

## Suite completa — línea base, sin cambios aplicados

Corrí `php artisan test` contra el esquema actual (sin ninguna migración
nueva) para dejar constancia del punto de partida:

```
Tests:    185 passed (955 assertions)
Duration: 9.19s
```

185 en verde, coincide con el número que espera el encargo. Esto es el
**antes**, no una verificación de mi cambio (no hay cambio aplicado todavía).

---

## PostgreSQL (Step 6) — PENDIENTE, sin ambigüedad

No hay `psql` ni ningún cliente/servidor de PostgreSQL nativo accesible en
esta máquina (`which psql` / `where psql` no encuentran nada). La instrucción
del encargo prohíbe expresamente usar Docker o tocar cualquier contenedor
para levantar una base de prueba. Por tanto:

**Step 6 queda sin hacer.** No se verificó la migración contra PostgreSQL, ni
con `ALTER COLUMN` real ni de ninguna otra forma. Esto sigue pendiente
independientemente de cómo se resuelva el hallazgo de Percepción.

---

## Ficheros modificados

Ninguno. No se creó, editó ni aplicó ningún fichero de migración. El único
cambio en el árbol de trabajo (`package-lock.json`) es preexistente a esta
sesión y está fuera de alcance por instrucción del proyecto.

---

## Autorrevisión

- **¿Los conteos de columnas cuadraron con los esperados?** Dos de tres sí
  (`evaluaciones_paisaje: 34`, `evaluaciones_valoracion_territorial: 21`,
  `criterios de Potencialidad: 156`). Uno no: `evaluaciones_percepcion` dio 0
  en vez de 16, con causa raíz identificada (columnas numeradas por ítem
  dentro de cada categoría) y una corrección verificada pero no aplicada.
- **¿Alguna columna calculada acabó convertida a `tinyInteger`?** No —
  ninguna migración se escribió ni se aplicó. Además, verifiqué que la
  corrección propuesta para Percepción no arrastra a `media_*`, `pond_*` ni
  `percepcion_total` (ninguna empieza por `ds`, `pl`, `pe` o `no`).
- **¿La suite sigue en 185?** Sí, en su estado actual sin cambios: 185
  pasan. No hay una versión "después" de la suite porque no se aplicó nada.
- **¿El `down()` hace algo razonable?** No llegué a escribir ningún `down()`;
  el del brief (lanzar `RuntimeException` explicando que revertir inventaría
  puntuaciones) me parece razonable y lo adoptaría tal cual si se aprueba
  seguir adelante.

## Dudas planteadas al coordinador (ya resueltas)

1. ¿Aplico la corrección de regex para `evaluaciones_percepcion`? → **Sí,
   autorizado.**
2. ¿Aplico la corrección de precisión decimal en Potencialidad? → **Sí,
   autorizado**, con el matiz de que el coordinador pidió no tocar `val_*` en
   absoluto (ver más abajo), en vez de aplicar `change()` con la precisión
   correcta pero redundante.
3. PostgreSQL sin cliente nativo ni Docker → **confirmado pendiente por el
   coordinador**, no se improvisó nada.

---

## Resolución — correcciones aplicadas tras la autorización

### 1. `evaluaciones_percepcion` — filtro de prefijo con dígito opcional

Antes de tocar el fichero de migración, el coordinador pidió comprobar tres
cosas explícitamente. Las tres se verificaron contra el esquema real (no
contra lo que "debería" salir):

- **Conteo exacto 16/34/21** en percepción/paisaje/valoración territorial:
  confirmado con el patrón `preg_match('/^'.$prefijo.'\d*_/', $c)`.
- **Ninguna columna calculada arrastrada**: se listaron las 16 columnas
  capturadas en percepción una por una — todas `dsN_`/`plN_`/`peN_`/`noN_`,
  ninguna `media_*`, `pond_*` ni `percepcion_total` (la exclusión por sufijo
  `_promedio`/`_total` sigue aplicándose antes del match de prefijo, tal como
  ya hacía `esCriterio()`). El caso concreto que pidió el coordinador —`pe`
  seguido de dígito— es exactamente `pe1_`, `pe2_`, `pe3_`, y no colisiona con
  `percepcion_total` porque ese nombre termina en `_total` y se descarta antes
  de llegar al chequeo de prefijo.
- **Ninguna columna capturada es ya decimal/float**: se consultó
  `PRAGMA table_info` para las 71 columnas capturadas (16+34+21) y se
  comprobó que el `type` de cada una contiene `int` — ninguna es `numeric`
  (que es como SQLite reporta `decimal`).

Con las tres verificaciones en verde, apliqué el cambio en
`database/migrations/2026_08_08_000001_criterios_nullable_matrices_ponderadas.php`:
`esCriterio()` ahora usa `preg_match('/^'.$prefijo.'\d*_/', $columna) === 1`
en vez de `str_starts_with($columna, $prefijo.'_')`. El resto del fichero
(las columnas literales de FIT/FET, la exclusión de `_promedio`/`_total`, el
`down()` irreversible) se dejó igual que en el brief.

### 2. Potencialidad — no tocar `val_*`, `fn_total`, `fx_total`

El coordinador confirmó independientemente que esas 23 columnas ya son
`nullable()` desde la migración original y pidió **no** hacer un `change()`
que no cambia nada. Comprobé columna por columna con
`PRAGMA table_info(evaluaciones_potencialidad)` que las 21 `val_*` y los dos
totales tienen `notnull = 0` hoy — confirmado, ninguna necesitaba tocarse.

`database/migrations/2026_08_08_000002_criterios_nullable_potencialidad.php`
quedó reducida a un único `Schema::table` que solo convierte los 156
criterios (`tinyInteger`, hoy `NOT NULL default(0)`) a `nullable()->default(null)`.
Se eliminó por completo el segundo bloque del borrador del brief (el que
aplicaba `decimal(8,4)` de forma uniforme a los calculados): no hacía falta
ni con la precisión corregida, porque esas columnas ya cumplían el objetivo
desde 2025_12_07.

### 3. Aplicación y verificación

```
$ php artisan migrate
2026_08_08_000001_criterios_nullable_matrices_ponderadas ... 224.81ms DONE
2026_08_08_000002_criterios_nullable_potencialidad ... 63.08ms DONE
```

Verificación post-migración (`PRAGMA table_info` de las seis tablas):

- `evaluaciones_fit`, `evaluaciones_fet`, `evaluaciones_potencialidad`: solo
  `zona_id` y `estado` quedan `NOT NULL` — todos los criterios son nullable.
- `evaluaciones_percepcion`, `evaluaciones_paisaje`,
  `evaluaciones_valoracion_territorial`: solo `zona_id`, `estado` y las
  columnas calculadas (`media_*`/`pond_*`/`percepcion_total`,
  `*_promedio`/`paisaje_total`, `ct_total`/`uc_total`) siguen `NOT NULL` —
  exactamente como estaban antes de la migración, porque esas tres tablas
  nunca declararon sus totales como nullable y esta tarea no las toca (fuera
  de alcance: el brief solo pide nullable en los criterios, no en los
  totales de estas tres matrices).
- Potencialidad: `val_rn_litoral`, `fn_total`, `fx_total` siguen
  `notnull = 0`, sin cambio — confirmado que el `change()` que se descartó no
  hacía falta.

### 4. Suite completa

```
$ php artisan test
Tests:    185 passed (955 assertions)
Duration: 8.84s
```

Mismo número que la línea base tomada antes de escribir nada. Además,
`PotencialidadCalculoTest` por separado:

```
$ php artisan test --filter=PotencialidadCalculoTest
Tests:    14 passed (40 assertions)
```

### 5. Commit

```
$ git add database/migrations/2026_08_08_000001_criterios_nullable_matrices_ponderadas.php \
          database/migrations/2026_08_08_000002_criterios_nullable_potencialidad.php
$ git commit -m "feat(db): criterios nullable para permitir guardar matrices a medias" ...
[guardado-parcial f5ff57f] feat(db): criterios nullable para permitir guardar matrices a medias
 2 files changed, 170 insertions(+)
```

`package-lock.json` se dejó intacto, tal como pide el proyecto.

---

## Autorrevisión final

- **¿Los conteos de columnas cuadraron con los esperados?** Sí, los cuatro:
  16/34/21/156. El de percepción no cuadraba con el filtro literal del
  brief; se ajustó el filtro con autorización explícita del coordinador y
  quedó verificado contra el esquema real, no contra el número esperado a
  ciegas.
- **¿Alguna columna calculada acabó convertida a `tinyInteger`?** No. Se
  verificó explícitamente antes de aplicar (tipos `int` vs `numeric` por
  columna) y después de aplicar (`notnull`/tipo sin cambios en las 26
  columnas calculadas de las seis tablas).
- **¿La suite sigue en 185?** Sí, 185 passed, ni uno menos, incluidas las 14
  de `PotencialidadCalculoTest`.
- **¿El `down()` hace algo razonable, o miente sobre lo que puede
  revertir?** Ambos `down()` lanzan `RuntimeException` explicando que
  revertir inventaría puntuaciones donde hay huecos. No mienten: no ofrecen
  una reversión que no pueden cumplir con integridad. Revertir de verdad
  exige restaurar un respaldo, no correr un `down()`.

## Ficheros modificados

- `database/migrations/2026_08_08_000001_criterios_nullable_matrices_ponderadas.php` (nuevo)
- `database/migrations/2026_08_08_000002_criterios_nullable_potencialidad.php` (nuevo)

`package-lock.json` sigue modificado desde antes de esta sesión; no se tocó.

## Dudas finales

Ninguna sobre el código entregado. La única pendiente, ya anotada de forma
visible arriba y aquí de nuevo por claridad: **Step 6 (verificación en
PostgreSQL) no se hizo** — no hay `psql`/servidor nativo en esta máquina y
Docker está prohibido. Si en algún momento hay un PostgreSQL nativo
accesible, correr `php artisan migrate:fresh --database=pgsql --force` con
estas dos migraciones ya en el repo debería bastar para cerrarlo.
