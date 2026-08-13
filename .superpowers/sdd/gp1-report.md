# GP Tarea 1: Tests de caracterización de Potencialidad — Informe

## Qué se escribió

`tests/Feature/PotencialidadCalculoTest.php` — 10 tests que congelan el
comportamiento actual de `EvaluacionPotencialidadController::calcular()`,
copiados literalmente del brief (`task-1-brief.md`, Step 1), sin ningún
ajuste de código de producción.

Cada test congela lo siguiente:

| Test | Congela |
|---|---|
| `test_todo_al_maximo_con_todos_los_campos_activos_da_dos` | Con los 156 campos activos a 2, `fn_total` y `fx_total` = 2.0 |
| `test_todo_a_cero_da_cero` | Con todo a 0, ambos totales dan 0.0 |
| `test_los_pesos_de_fx_son_40_30_30` | FX pondera Afluencia 0.40 / Marketing 0.30 / Superestructura 0.30 |
| `test_desactivar_grupos_renormaliza_los_pesos_de_fx` | Con solo Afluencia activa, su peso 0.40 se renormaliza a 1.0 (0.80 → 2.0) — la redistribución de pesos, lo más frágil de la función |
| `test_los_pesos_de_fn_son_40_20_20_20` | FN pondera RT 0.40 / PT 0.20 / TT 0.20 / Infraestructura 0.20 |
| `test_recursos_turisticos_promedia_naturales_y_culturales` | RT = (RN + RC) / 2 cuando ambos tienen campos activos; RN en sí es la media de sus 4 subgrupos (litoral/montaña/ANP/agua) |
| `test_sin_recursos_culturales_activos_rt_es_solo_rn` | Sin RC activo, RT = RN sin promediar con 0 |
| `test_comportamiento_actual_un_campo_no_enviado_cuenta_como_cero` | **El fallo real, congelado a propósito**: un campo activo no enviado se guarda como 0 y entra en la media del grupo |
| `test_desactivar_un_campo_conserva_su_valor_anterior` | Un campo desactivado retiene su valor previo (`$actual->$campo`), no se pisa con 0 |
| `test_la_configuracion_de_campos_activos_se_persiste` | `PotencialidadCamposActivos.campos_activos` guarda exactamente la lista enviada en `campos` |

## Discrepancias entre el brief y el código real

**Ninguna.** Tracé manualmente `calcular()` línea por línea (el método completo
de 120 líneas en
`app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`,
líneas 342-461) contra cada aserción del brief antes de ejecutar nada, y los
10 tests pasaron **en el primer intento**, sin tocar ni un carácter de
`app/`. Puntos que verifiqué con más cuidado por ser los más fáciles de
malinterpretar:

- **Redistribución de pesos (FN y FX):** el código arma `$fn_pesos` /
  `$fx_pesos` como arrays asociativos que solo incluyen las claves de los
  grupos con al menos un campo activo (`hasCampos()`), divide cada peso por
  `array_sum($pesos)`, y solo suma al total los términos de los grupos
  presentes. Confirma exactamente la renormalización que describe el brief.
- **RT como media condicional de RN/RC:** el código evalúa `$hasRN` y `$hasRC`
  por separado (no por si el valor es >0, sino por si hay campos *activos*
  en esas listas) y solo promedia 50/50 cuando ambas ramas tienen campos
  activos; si solo una tiene campos activos, `$val_rt` es esa rama sin
  diluir. Igual para los 4 subgrupos de RN (litoral/montaña/ANP/agua) dentro
  de `$val_rn`.
- **El fallo de campo no enviado:** la función `avg()` interna filtra
  candidatos por `in_array($c, $camposActivos) && isset($v[$c])`. Como
  `prepararDatos()` construye `$v` para **todos** los campos conocidos y usa
  `$request->input($campo, 0)` para los activos, un campo activo que no
  llega en el POST siempre tiene `isset($v[$c]) === true` con valor `0`, así
  que entra en la media. Validación: el rule-set (`integer|min:0|max:2`) no
  incluye `required` ni `present`, así que un campo ausente pasa la
  validación de Laravel sin error — de ahí que
  `assertSessionHasNoErrors()` funcione tal como asume el brief.
- **Conservación de campos desactivados:** confirmado en
  `EvaluacionZonaController::update()` (líneas 55-85): `$actual` se lee
  *antes* de calcular los nuevos datos y se pasa a `prepararDatos()`, que
  usa `$actual->$campo ?? 0` para cualquier campo fuera de
  `$camposActivos`.

No hubo que ajustar ningún número esperado del brief.

## Salida — `php artisan test --filter=PotencialidadCalculoTest`

```
 PASS  Tests\Feature\PotencialidadCalculoTest
 ✓ todo al maximo con todos los campos activos da dos                                                           0.59s
 ✓ todo a cero da cero                                                                                          0.15s
 ✓ los pesos de fx son 40 30 30                                                                                 0.15s
 ✓ desactivar grupos renormaliza los pesos de fx                                                                0.04s
 ✓ los pesos de fn son 40 20 20 20                                                                              0.15s
 ✓ recursos turisticos promedia naturales y culturales                                                          0.15s
 ✓ sin recursos culturales activos rt es solo rn                                                                0.04s
 ✓ comportamiento actual un campo no enviado cuenta como cero                                                   0.04s
 ✓ desactivar un campo conserva su valor anterior                                                               0.16s
 ✓ la configuracion de campos activos se persiste                                                               0.03s

 Tests:    10 passed (29 assertions)
 Duration: 1.73s
```

## Salida — suite completa (`php artisan test`)

Resultado: **181 passed (944 assertions)**, duración 9.25s — sin fallos, sin
warnings, sin deprecations. La base (según el brief) era 171 tests; 171 + 10
nuevos = 181, cuadra exactamente.

Cola de la salida completa:

```
 ✓ el admin ve los resultados de valoracion territorial en modo lectura                                         0.05s
 ✓ el jefe ve el enlace al formulario en los resultados de valoracion territorial                               0.04s
 ✓ la pagina de resultados muestra el aporte de un criterio                                                     0.04s
 ✓ la pagina de resultados refleja un cuadrante distinto                                                        0.04s

 Tests:    181 passed (944 assertions)
 Duration: 9.25s
```

Grep de `warning|deprecat|error|fail` sobre la salida completa: el único
resultado es el nombre de un test existente que contiene la palabra "error"
("el admin recibe un mensaje en vez de un error 500") — no hay avisos reales.

## `app/` sin cambios — confirmación explícita

```
$ git status --porcelain
 M package-lock.json
?? tests/Feature/PotencialidadCalculoTest.php
```

(`package-lock.json` ya aparecía modificado antes de empezar esta tarea, tal
como advertían las restricciones del proyecto; no se tocó.)

Tras el commit:

```
$ git diff --stat HEAD~1 HEAD -- app/
(sin salida — ningún fichero de app/ cambió en el commit)
```

## Commit

```
87d9b3d test(potencialidad): caracteriza el calculo actual antes de cambiarlo
 1 file changed, 217 insertions(+)
 create mode 100644 tests/Feature/PotencialidadCalculoTest.php
```

## Dudas o preocupaciones

Ninguna especial. Dos notas para quien aborde la Task 5 (el arreglo del
fallo):

1. El test `test_comportamiento_actual_un_campo_no_enviado_cuenta_como_cero`
   está diseñado para romperse cuando se corrija el fallo — eso es lo
   esperado y el diff de ese test mostrará el cambio de comportamiento con
   claridad.
2. Los demás 9 tests dependen de que los campos activos SÍ se envíen
   explícitamente (el helper `guardar()` siempre manda un valor para cada
   campo activo). Si la corrección del fallo cambia cómo se determinan los
   campos "realmente respondidos" — por ejemplo exigiendo `required` en vez
   de solo default a 0 — estos 9 tests deberían seguir pasando sin cambios,
   porque ninguno de ellos deja huecos.

---

# GP Tarea 1 (cierre de hueco): renormalización de FN, RT sin recursos y cero campos activos

## Contexto

Una revisión de código encontró que el commit anterior (los 10 tests de
arriba) solo congeló la renormalización de pesos de **FX**
(`test_desactivar_grupos_renormaliza_los_pesos_de_fx`), pero no la de **FN**
— y FN es estructuralmente más arriesgada porque `$fn_pesos` (líneas
409-422 de `calcular()`) anida la redistribución de RT entre Recursos
Naturales y Culturales (líneas 379-387). También señaló dos huecos menores:
la rama `val_rt = 0` sin RN ni RC activos, y el caso de cero campos activos
en absoluto.

Se añadieron **4 tests nuevos** a `tests/Feature/PotencialidadCalculoTest.php`
(el fichero pasa de 10 a 14 tests), sin tocar nada de `app/`.

## Qué congela cada test nuevo

| Test | Congela |
|---|---|
| `test_desactivar_grupos_renormaliza_los_pesos_de_fn` | Equivalente exacto del test de FX pero para FN: con solo Infraestructura activa, su peso 0.20 se renormaliza a 1.0 (0.40 → 2.0) |
| `test_renormalizacion_de_fn_respeta_proporciones_desiguales` | La renormalización reparte **proporcionalmente** a los pesos originales (0.40 de RT vs 0.20 de Infraestructura), no a partes iguales entre los grupos que queden activos — el caso que de verdad distingue una redistribución correcta de una ingenua |
| `test_val_recursos_turisticos_es_cero_sin_recursos_naturales_ni_culturales_activos` | La rama `else { $val_rt = 0; }` (línea ~386), sin RN ni RC activos, con aserción explícita sobre `val_recursos_turisticos` |
| `test_cero_campos_activos_no_revienta_y_todo_da_cero` | Con `campos_activos = []`, ningún fallback de `array_sum(...) ?: 1` revienta por división entre cero, y `fn_total`/`fx_total`/`val_recursos_turisticos` quedan en 0.0 |

## Derivación a mano de cada valor esperado

Todas las derivaciones se hicieron **antes** de ejecutar los tests, leyendo
`calcular()` línea por línea
(`app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`,
líneas 342-461). Los 14 tests pasaron en el primer intento, así que ninguna
derivación tuvo que corregirse después de ver un fallo.

### 1. `test_desactivar_grupos_renormaliza_los_pesos_de_fn`

Activos: solo los 11 campos de `Infraestructura`, todos a 2.

- `$hasCampos($allRT)` (línea 411): `array_intersect($camposActivos, $allRT)`
  vacío → no se añade `rt`.
- `$hasCampos(aloj)||...` (línea 412): vacío → no se añade `pt`.
- `$hasCampos($tt_campos)` (línea 414): vacío → no se añade `tt`.
- `$hasCampos($i_campos)` (línea 415): cierto → `$fn_pesos = ['i' => 0.20]`.
- `$fn_sum_pesos = array_sum(['i'=>0.20]) ?: 1 = 0.20` (línea 417).
- `$val_i = avg($i_campos) = 2` (todos los 11 campos activos a 2).
- `$fn_total = 2 * (0.20 / 0.20) = 2.0` (línea 422).

Contraste con `test_los_pesos_de_fn_son_40_20_20_20` (test preexistente, sin
tocar): con **todos** los campos activos y solo Infraestructura a 2, ese
mismo `val_i = 2` da `fn_total = 0.40` porque `$fn_sum_pesos = 1.0` (los 4
grupos activos). Mismo `val_i`, denominador distinto → 0.40 vs 2.0. Es
exactamente el mecanismo de renormalización que había que congelar.

### 2. `test_renormalizacion_de_fn_respeta_proporciones_desiguales`

Activos: los 5 campos de `RN — Cuerpos de Agua` (a 2) más los 11 de
`Infraestructura` (a 0, por `relleno` por defecto de `guardar()`).

- RN: de los 4 subgrupos (litoral/montaña/ANP/agua), solo `agua` tiene
  campos activos → `$rn_grupos = [rn_agua]`, `$val_rn = rn_agua =
  avg(agua) = 2` (líneas 358-362).
- RC: ningún campo de RC activo → `$val_rc = 0`, y de hecho `$rc_grupos =
  []` (líneas 369-372) — de ahí la aserción `val_recursos_culturales =
  0.0`.
- `$hasRN = true`, `$hasRC = false` → rama `elseif ($hasRN) { $val_rt =
  $val_rn; }` (línea 382) → `$val_rt = 2`.
- `$fn_pesos`: `hasCampos($allRT)` cierto (los campos de agua son
  subconjunto de `$allRN ⊂ $allRT`) → `rt = 0.40`; PT y TT sin campos
  activos → no se añaden; `hasCampos($i_campos)` cierto → `i = 0.20`.
  `$fn_pesos = ['rt' => 0.40, 'i' => 0.20]`, `$fn_sum_pesos = 0.60`.
- `$val_i = avg(Infraestructura) = 0` (los 11 campos activos, todos a 0).
- `$fn_total = 2 * (0.40/0.60) + 0 * (0.20/0.60) = 2 * (2/3) = 4/3 ≈
  1.333333`.

Este es el test que de verdad distingue una redistribución proporcional de
una que repartiera 50/50 entre los grupos activos: con 50/50 el resultado
habría sido `2*0.5 + 0*0.5 = 1.0`, no `4/3`. Como los pesos de RT (0.40) e
Infraestructura (0.20) son desiguales, cualquier implementación futura que
"simplifique" la redistribución a partes iguales rompería este test sin
tocar los otros tres nuevos (que, al usar pesos iguales entre sí — 0.20 vs
0.20 en el primero, o un solo grupo en el resto —, no lo habrían detectado).

### 3. `test_val_recursos_turisticos_es_cero_sin_recursos_naturales_ni_culturales_activos`

Mismo escenario que el test 1 (solo Infraestructura activa). Como ningún
campo de `$allRN` ni `$allRC` está en `$camposActivos`, `$hasRN = false` y
`$hasRC = false` → cae en el `else` final (línea 385-386):
`$val_rt = 0`. Aserción directa: `val_recursos_turisticos == 0.0`.

### 4. `test_cero_campos_activos_no_revienta_y_todo_da_cero`

`$camposActivos = []` (se llama `guardar([], [])`, que solo envía `campos
=> []` sin ningún otro campo).

- Validación: `$reglas` queda vacío (el `foreach ($camposActivos as
  $campo)` en `prepararDatos()` no itera nada) → `$request->validate([])`
  no falla.
- `$valores`: para los 156 campos conocidos, `in_array($campo,
  $camposActivos)` es siempre falso (array vacío) → todos toman
  `$actual->$campo ?? 0`; como es el primer guardado, `$actual` es `null`
  y el operador `??` evita el warning de "leer propiedad de null",
  devolviendo `0` para los 156.
- Dentro de `calcular()`: `avg()` filtra por `in_array($c,
  $camposActivos)`, que es siempre falso → **toda** media (`rn_litoral`,
  `rn_montana`, ..., `val_tt`, `val_i`, `val_afluencia`, ...) da `0`.
- `$hasCampos($lista)` es `!empty(array_intersect([], $lista))`, siempre
  falso → ningún grupo se añade a `$rn_grupos`/`$rc_grupos`/`$pt_grupos`
  → `$val_rn = $val_rc = $val_pt = 0` por la rama `empty(...) ? 0 : ...`
  (líneas 362, 372, 400); y `$hasRN = $hasRC = false` → `$val_rt = 0` por
  el `else` (línea 386).
- `$fn_pesos = []` y `$fx_pesos = []` (ningún `hasCampos(...)` es cierto)
  → `$fn_sum_pesos = array_sum([]) ?: 1 = 1` y lo mismo para
  `$fx_sum_pesos` (líneas 417, 439) — el fallback evita la división entre
  cero, pero como ningún `isset($fn_pesos['rt'])` (etc.) es cierto,
  **ningún término se suma nunca** a `$fn_total`/`$fx_total` (líneas
  419-422, 441-443), así que ambos quedan en `0` de todas formas — el
  fallback a `1` es defensivo pero en este caso concreto no llega a
  ejercitarse como divisor de nada.

Resultado: `fn_total = 0.0`, `fx_total = 0.0`, `val_recursos_turisticos =
0.0`, sin excepciones ni errores de validación.

## Comportamiento sorprendente

**Ninguno.** Los 14 tests (10 preexistentes + 4 nuevos) pasaron en el
primer intento, sin necesidad de ajustar ni un solo valor esperado tras
verlo fallar. El caso de cero campos activos, que parecía el más propenso a
sorpresas (posible división entre cero, posible excepción por acceder a
`$actual->$campo` con `$actual = null`), resultó ser manejado con
elegancia por el código existente gracias al patrón `?: 1` en
`$fn_sum_pesos`/`$fx_sum_pesos` y al operador `??` de PHP, que suprime el
warning de leer una propiedad de `null`. No hay ninguna señal de un fallo
oculto en estas rutas — el hueco de cobertura era real, pero el código que
cubría resultó ser correcto.

## Salida — `php artisan test --filter=PotencialidadCalculoTest`

```
 PASS  Tests\Feature\PotencialidadCalculoTest
 ✓ todo al maximo con todos los campos activos da dos                                                          0.60s
 ✓ todo a cero da cero                                                                                         0.15s
 ✓ los pesos de fx son 40 30 30                                                                                0.15s
 ✓ desactivar grupos renormaliza los pesos de fx                                                               0.04s
 ✓ los pesos de fn son 40 20 20 20                                                                             0.15s
 ✓ desactivar grupos renormaliza los pesos de fn                                                               0.04s
 ✓ renormalizacion de fn respeta proporciones desiguales                                                       0.04s
 ✓ recursos turisticos promedia naturales y culturales                                                         0.15s
 ✓ sin recursos culturales activos rt es solo rn                                                               0.04s
 ✓ val recursos turisticos es cero sin recursos naturales ni culturales activos                                0.04s
 ✓ comportamiento actual un campo no enviado cuenta como cero                                                  0.04s
 ✓ desactivar un campo conserva su valor anterior                                                              0.16s
 ✓ la configuracion de campos activos se persiste                                                              0.04s
 ✓ cero campos activos no revienta y todo da cero                                                              0.03s

 Tests:    14 passed (40 assertions)
 Duration: 1.88s
```

## `app/` sin cambios — confirmación explícita

Antes del commit:

```
$ git status --short
 M package-lock.json
 M tests/Feature/PotencialidadCalculoTest.php
$ git diff --stat -- app/
(sin salida — ningún fichero de app/ tiene cambios)
```

(`package-lock.json` ya aparecía modificado antes de empezar esta tarea; no
se tocó, tal como piden las restricciones del proyecto.)

## Salida — suite completa (`php artisan test`)

Resultado: **185 passed (955 assertions)**, duración 9.13s — sin fallos. La
base era 181 (10 tests de la tarea anterior); 181 + 4 nuevos = 185, cuadra
exactamente.

```
 Tests:    185 passed (955 assertions)
 Duration: 9.13s
```

## Commit

```
5b6d200 test(potencialidad): congela la renormalizacion de pesos de FN
 1 file changed, 82 insertions(+)
```

Rama: `guardado-parcial`. Solo se añadió `tests/Feature/PotencialidadCalculoTest.php`
al stage (`git add tests/Feature/PotencialidadCalculoTest.php`); `package-lock.json`
se dejó sin tocar, tal como piden las restricciones del proyecto.
