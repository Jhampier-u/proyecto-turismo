# Revisión final — permisos y navegación del admin

Rama `permisos-y-navegacion`. Cubre los cinco hallazgos de la revisión final antes
de integrar la rama.

## Resumen de estado

| Hallazgo | Cambio | Rojo antes del fix |
|---|---|---|
| 1 — agujero de permisos en `reconfigurarCampos()` | `esEquipo()` → `! esJefe()`, tercera copia del predicado | Sí |
| 2 — el test de rutas no cazaba el hallazgo 1 | Lista blanca explícita de las 16 rutas de escritura del grupo | Sí (el nuevo test de comportamiento) |
| 3 — el admin no tiene camino a un formulario desde el panel | `EstadoZona` deja de tratarlo como observador; franja "Modo consulta" retirada | Sí |
| 4 — cinco tests fijaban el comportamiento viejo | Adaptados, no borrados: ahora afirman lo contrario | Sí |
| 5 — Potencialidad validada se queda sin resultados en la pestaña | `<x-pestanas-matriz>` prioriza `estado` sobre el recuento | Sí |
| Menores | Docblock de `criteriosRespondidos()`, test de pestañas reforzado, rama de actores incompletos cubierta, gemelos admin en páginas de resultados | — |

## Hallazgo 1 — agujero de permisos en `reconfigurarCampos()`

**Causa.** `EvaluacionPotencialidadController::reconfigurarCampos()` (línea 213) seguía
usando `$user->esEquipo()` para bloquear, la tercera copia del mismo predicado de
cierre por rol. Las otras dos ya se habían migrado a `! $user->esJefe()` en esta
rama (`EvaluacionZonaController:79`, `InvolucradosController:331`). Con el
middleware `PerteneceAZona` abierto para el admin (cambio de esta misma rama), el
admin dejaba de estar bloqueado por `esEquipo()` —no es equipo— y podía resetear en
silencio la selección de campos activos del Jefe de Zona a los 156 completos,
mientras el propio mensaje de error del controlador seguía diciendo "Solo el Jefe
de Zona puede reconfigurar los campos", justo lo contrario de lo que hacía.

**Arreglo.** `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php:213`:
`if ($user->esEquipo())` → `if (! $user->esJefe())`, con un comentario que explica
la tercera copia y remite a las otras dos.

**Test (rojo → verde):** `tests/Feature/PermisosAdminTest.php::test_el_admin_no_puede_usar_ninguna_ruta_prohibida`.
No usa `assertForbidden()` para `reconfigurar` —a diferencia de `involucrados.validar`,
que sí aborta con 403— porque esta guarda degrada igual que el resto de guardas de
rol del controlador (`back()->with('error', ...)`, no un 403). La prueba de que el
admin no pudo es que la configuración de campos activos del jefe sobrevive intacta
tras el intento del admin:

```
PotencialidadCamposActivos::create(['zona_id' => ..., 'campos_activos' => $seleccionDelJefe /* 3 campos */]);
actingAs($admin)->post(route('...reconfigurar', ...));
assertSame($seleccionDelJefe, PotencialidadCamposActivos::...->value('campos_activos'));
```

**Evidencia del rojo** (ejecutado antes de tocar el controlador):
```
Tests\Feature\PermisosAdminTest > el admin no puede usar ninguna ruta prohibida   FAILED
Failed asserting that two arrays are identical.
 [3 campos del jefe]  vs  [156 campos: la reconfiguración del admin sí se aplicó]
Tests:    1 failed, 19 passed (201 assertions)
```

**Después del arreglo:** `php artisan test --filter=PermisosAdminTest` → **20 passed**.

## Hallazgo 2 — el test de rutas de validación no cazaba el hallazgo 1

**Causa.** `test_toda_ruta_de_validacion_sigue_exigiendo_jefe` filtraba las rutas
del grupo por `str_contains($nombre, 'validar')`. La ruta del hallazgo 1 se llama
`operativo.evaluacion_potencialidad.reconfigurar` — no valida nada por nombre, pero
solo el jefe puede tocarla — así que quedaba fuera del barrido sin que nada lo
avisara.

**Arreglo.** Se reemplaza por tres tests en `tests/Feature/PermisosAdminTest.php`
que comparten una lista blanca explícita (`rutasDeEscrituraClasificadas()`) con las
16 rutas de escritura reales (POST/PUT/PATCH/DELETE) del grupo `operativo/zona`,
clasificadas a mano:

- **14 permitidas**: las 8 `evaluacion_*.update`, `inventarios.store/update/destroy`,
  `involucrados.store/update/destroy`.
- **2 prohibidas**: `involucrados.validar`, `evaluacion_potencialidad.reconfigurar`.

1. `test_toda_ruta_de_escritura_del_grupo_zona_esta_clasificada` — recorre
   `Route::getRoutes()` filtrado por nombre `operativo.*` y método de escritura, y
   exige que cada ruta real esté en una de las dos listas (y viceversa: que ninguna
   entrada de las listas sea un nombre que ya no existe). **Una ruta de escritura
   nueva sin clasificar hace fallar este test explícitamente**, con el nombre de la
   ruta en el mensaje.
2. `test_el_admin_puede_usar_todas_las_rutas_de_escritura_permitidas` — ejercita las
   14 permitidas de verdad (las ocho matrices vía `instrumentosDeMatriz()`, más
   crear/editar/borrar un recurso de inventario y un actor), comprobando
   `assertSessionHasNoErrors()`.
3. `test_el_admin_no_puede_usar_ninguna_ruta_prohibida` — cubre el Hallazgo 1.

Este test es estructural, no dependía del bug del Hallazgo 1, así que no tuvo un
"rojo" propio; el rojo que demuestra que el filtro viejo tenía el punto ciego es el
propio Hallazgo 1 (arriba): con el filtro por `'validar'`, ese test nunca habría
detectado el agujero.

## Hallazgo 3 — el admin no tiene camino a un formulario desde el panel

**Causa.** `EstadoZona::filaMatriz()`, `filaActores()` y `filaInventario()` seguían
tratando al admin como observador de solo lectura:
- Matriz `sin_empezar`: `url: $esAdmin ? null : ...` — sin `url` no se pinta ningún
  botón.
- Matriz en `borrador` (aunque estuviera completa): al admin se le mandaba a `ver`
  en vez de `editar`.
- Actores en `sin_empezar`/`borrador`: mismo patrón.
- Inventario: `accion: 'Ver'` para el admin, con un comentario que decía que el
  middleware le cortaba cualquier POST — ya no es cierto en esta rama.
- `panel.blade.php` mostraba una franja azul "Modo consulta — puedes ver los
  resultados, no modificarlos" para el admin.

El único camino del admin a una zona es `admin/zonas` → "Abrir zona" → ese panel.
Con las once entradas mostrando `null`/`Ver` salvo el inventario, no había ningún
enlace a un formulario: para rellenarlo tendría que escribir la URL a mano.

**Arreglo.**
- `app/Servicios/EstadoZona.php`: se quita la variable `$esAdmin` y sus ramas
  condicionales de `filaMatriz()`, `filaActores()` y `filaInventario()`. El admin
  recibe ahora `url`/`accion` idénticos al equipo: `'Empezar'` sin empezar,
  `'Continuar'` en borrador (completa o no), `'Abrir'` en inventario. **Lo único que
  no cambia:** la rama de matriz **validada** ya construía `url`/`accion` de `'Ver'`
  sin distinguir por rol —esa guarda vivía fuera del `$esAdmin` que se quita—, así
  que una matriz validada sigue llevando al admin a resultados, nunca a editar.
- `resources/views/operativo/zona/panel.blade.php`: se retira la franja "Modo
  consulta" por completo. No se sustituye por otro texto: el admin ya no es
  distinto del equipo más que en no poder validar, y eso ya se comunica solo (no
  hay botón de validar para ninguno de los dos).
- `app/Http/Controllers/Operativo/ZonaPanelController.php`: el docblock citaba "el
  middleware 'zona' ya limita al admin a métodos seguros" como razón de no
  necesitar rutas de solo lectura aparte; corregido para reflejar que
  `PerteneceAZona` ya no lo hace.

**Tests (rojo → verde), Hallazgo 4 — cinco tests que fijaban el comportamiento viejo:**

Se adaptaron, sin borrarlos, para afirmar la regla nueva:

| Test viejo | Test nuevo | Fichero |
|---|---|---|
| `test_el_admin_no_recibe_acciones_de_edicion` | `test_el_admin_recibe_accion_de_empezar_en_una_matriz_sin_empezar` (+ `test_el_admin_recibe_continuar_en_una_matriz_en_borrador`, `test_el_admin_no_recibe_edicion_sobre_una_matriz_validada`, `test_el_admin_recibe_accion_de_empezar_en_actores_sin_empezar`) | `tests/Unit/EstadoZonaTest.php` |
| `test_el_admin_recibe_ver_en_el_inventario_...` (justificado con el middleware) | `test_el_admin_recibe_abrir_en_el_inventario_igual_que_jefe_y_equipo` | `tests/Unit/EstadoZonaTest.php` |
| `test_el_admin_entra_en_modo_consulta` | `test_el_admin_recibe_el_enlace_para_empezar_una_matriz_sin_empezar` | `tests/Feature/PaginaZonaTest.php` |
| `test_el_admin_consulta_la_zona_en_modo_lectura` | `test_el_admin_recibe_el_enlace_de_editar_con_paisaje_en_borrador` (+ `test_el_admin_no_recibe_edicion_con_paisaje_validado`) | `tests/Feature/PaisajeTest.php` |
| `test_el_admin_consulta_la_zona_en_modo_lectura` | `test_el_admin_recibe_el_enlace_de_editar_con_valoracion_territorial_en_borrador` | `tests/Feature/ValoracionTerritorialTest.php` |

**Evidencia del rojo** (los ocho tests de arriba, ejecutados antes de tocar
`EstadoZona.php`/`panel.blade.php`):
```
FAILED  Tests\Unit\EstadoZonaTest > el admin recibe accion de empezar en una matriz sin empezar
FAILED  Tests\Unit\EstadoZonaTest > el admin recibe continuar en una matriz en borrador
FAILED  Tests\Unit\EstadoZonaTest > el admin recibe accion de empezar en actores sin empezar
FAILED  Tests\Unit\EstadoZonaTest > el admin recibe abrir en el inventario igual que jefe y equipo
FAILED  Tests\Feature\PaginaZonaTest > el admin recibe el enlace para empezar una matriz sin empezar
FAILED  Tests\Feature\PaisajeTest > el admin recibe el enlace de editar con paisaje en borrador
FAILED  Tests\Feature\PaisajeTest > el admin no recibe edicion con paisaje validado
FAILED  Tests\Feature\ValoracionTerritorialTest > el admin recibe el enlace de editar con valoracion territorial…
Tests:    8 failed, 76 passed (539 assertions)
```
(`test_el_admin_no_recibe_edicion_sobre_una_matriz_validada` no aparece en rojo:
esa rama de código no cambió, así que nació en verde — es la red de seguridad de
que la parte que no debía cambiar, no cambió).

**Después del arreglo:** `php artisan test --filter="EstadoZonaTest|PaginaZonaTest|PaisajeTest|ValoracionTerritorialTest"` → **84 passed**.

**Hallazgo menor — gemelos del jefe en las páginas de resultados.** Ningún test
afirmaba que el admin ve el enlace al formulario en una página de resultados
(`<x-pestanas-matriz>` no distingue por rol, pero no había cobertura). Se añadieron
los gemelos de los tests `test_el_jefe_ve_el_enlace_al_formulario_en_los_resultados_de_*`
ya existentes, para FIT, FET y Percepción (`tests/Feature/EvaluacionesTest.php`),
Paisaje (`tests/Feature/PaisajeTest.php`) y Valoración Territorial
(`tests/Feature/ValoracionTerritorialTest.php`). Los cinco nacieron en verde: el
componente ya no gateaba por rol, era solo un hueco de cobertura.

## Hallazgo 5 — una Potencialidad validada se queda sin sus resultados en la pestaña

**Causa.** `resources/views/components/pestanas-matriz.blade.php` decidía si había
resultados contando criterios (`$respondidos >= $entrada['criterios']`, 156 para
Potencialidad) sin mirar el estado. Potencialidad es configurable: el jefe puede
activar 20 de 156 campos, responderlos y validar. La pestaña seguía mostrando
candado y "20 de 156 criterios" sobre una matriz **validada, con resultados
calculados**, y el formulario de Potencialidad ya no tiene las salidas alternativas
que tenía antes en esta rama — el jefe se quedaba sin ningún enlace a sus propios
resultados.

**Arreglo.** El estado manda sobre el recuento: si `$evaluacion->estado ===
'confirmado'`, `$completa = true` sin más. El recuento (o, para actores, "hay al
menos un actor y ninguno a medias") solo decide cuando la matriz sigue sin validar.
Esto deja coherentes tres respuestas a la misma pregunta: `EstadoZona::filaMatriz()`
ya miraba el estado primero, y `evaluacion_potencialidad/ponderacion.blade.php` mira
si `fn_total`/`fx_total` son nulos.

**Test (rojo → verde):** `tests/Feature/PestanasMatrizTest.php::test_potencialidad_validada_con_campos_desactivados_ofrece_el_enlace_a_resultados`
— activa 20 de 156 campos, los responde, valida (`accion_estado=confirmado`), y
comprueba que el formulario de edición contiene el enlace a `ponderacion` y ya no
el texto "de 156 criterios".

**Evidencia del rojo** (verificado revirtiendo momentáneamente solo el componente,
vía `git stash` del fichero, con el resto de la rama ya aplicado):
```
Tests\Feature\PestanasMatrizTest > potencialidad validada con campos desactivados ofrece el enlace a resultados   FAILED  (1.09s)
```
**Después de restaurar el arreglo:** `php artisan test --filter=PestanasMatrizTest` → **8 passed**.

**Hallazgos menores cerrados en el mismo paso:**
- `app/Servicios/EstadoZona.php::criteriosRespondidos()` — se restauró la
  explicación perdida de por qué cuenta filtrando `getAttributes()` en vez de
  mantener una lista de campos ("el registro sabe cuántos criterios tiene cada
  matriz, pero no cuáles..."), junto a la razón nueva (por qué es pública y
  estática) que ya estaba.
- `tests/Feature/PestanasMatrizTest.php::test_todas_las_matrices_muestran_las_pestanas_en_su_formulario`
  — buscaba solo el literal "Resultados", que habría pasado en verde aunque el
  componente no existiera. Ahora comprueba, por cada entrada, la ausencia del
  enlace a `$entrada['rutas']['ver']` y la presencia del texto de candado ("X de N
  criterios" o "sin actores completos") — la zona de la prueba está recién creada,
  así que todo debe estar bloqueado.
- `test_actores_incompletos_no_ofrecen_enlace_a_resultados` (nuevo) — la rama de
  actores del candado solo se había ejercido con la lista vacía (`cuantos === 0`);
  este test crea un actor a medias para ejercer el otro motivo de "no completa"
  (`incompletos()->exists()`).

## Autorrevisión

- **¿Cuarta copia de `esEquipo()` donde debería ser `! esJefe()`?** Se buscó con
  `grep -rn "esEquipo()" app/`. Quedan tres usos de `esEquipo()` fuera del ya
  corregido: `DashboardController` (routing del dashboard, no un gate de escritura),
  y dos usos de `avisoValidacion` (`EstadoZona.php` y `InvolucradosController.php`)
  que deciden si mostrar el texto "avísale a tu Jefe" — es una decisión de
  presentación (para quién tiene sentido el aviso), no un gate de permisos, y ya
  estaba así antes de esta rama. No se encontró una cuarta copia del predicado de
  seguridad.
- **¿El test de rutas falla si se añade una ruta de escritura nueva sin
  clasificar?** Sí: `test_toda_ruta_de_escritura_del_grupo_zona_esta_clasificada`
  recorre `Route::getRoutes()` en vivo (no una lista fija) y falla con el nombre de
  la ruta si no está en `permitidas` ni en `prohibidas`.
- **¿El admin puede llegar a un formulario desde el panel, y sigue sin poder editar
  una matriz validada?** Sí a ambas — cubierto por
  `PaginaZonaTest::test_el_admin_recibe_el_enlace_para_empezar_una_matriz_sin_empezar`
  y `PaisajeTest::test_el_admin_no_recibe_edicion_con_paisaje_validado` /
  `EstadoZonaTest::test_el_admin_no_recibe_edicion_sobre_una_matriz_validada`.
- **¿Algún comentario sigue citando el middleware como razón de un bloqueo que ya no
  existe?** Se revisó con `grep` en `app/` y `resources/views/`. Los tres
  comentarios que mencionan el middleware ahora describen correctamente el estado
  actual (que ya NO restringe al admin): `EstadoZona.php:192`,
  `PerteneceAZona.php:38`, `ZonaPanelController.php:14` (este último corregido en
  esta tarea).

No apareció ningún caso "que no encaja" durante el Hallazgo 3: la franja "Modo
consulta" no tenía otro propósito que anunciar el antiguo modo de solo lectura, así
que se retiró sin más, tal como contemplaba el propio hallazgo.

## Suite completa

```
php artisan test
```
**444 passed (2842 assertions)**, 0 failed. Duración ~23-30s. (Base antes de esta
tarea: **431 passed**.)

```
npm run build
```
Build de Vite correcto: `✓ 56 modules transformed`, `✓ built in 10.68s`.

## Commits creados

1. `013619b` — `fix(permisos): cierra el agujero de reconfigurar campos de Potencialidad`
   (Hallazgos 1 y 2: `EvaluacionPotencialidadController.php`, `PermisosAdminTest.php`)
2. `f273549` — `feat(navegacion): el admin recibe las mismas acciones que el equipo`
   (Hallazgos 3 y 4: `EstadoZona.php`, `ZonaPanelController.php`, `panel.blade.php`,
   `EstadoZonaTest.php`, `PaginaZonaTest.php`, `PaisajeTest.php`,
   `ValoracionTerritorialTest.php`, `EvaluacionesTest.php`)
3. `612e316` — `fix(pestanas): el estado manda sobre el recuento de criterios`
   (Hallazgo 5 y menores: `EstadoZona.php`, `pestanas-matriz.blade.php`,
   `PestanasMatrizTest.php`)

## Ficheros modificados

**App:**
- `app/Http/Controllers/Operativo/EvaluacionPotencialidadController.php`
- `app/Http/Controllers/Operativo/ZonaPanelController.php`
- `app/Servicios/EstadoZona.php`

**Vistas:**
- `resources/views/components/pestanas-matriz.blade.php`
- `resources/views/operativo/zona/panel.blade.php`

**Tests:**
- `tests/Feature/PermisosAdminTest.php`
- `tests/Unit/EstadoZonaTest.php`
- `tests/Feature/PaginaZonaTest.php`
- `tests/Feature/PaisajeTest.php`
- `tests/Feature/ValoracionTerritorialTest.php`
- `tests/Feature/EvaluacionesTest.php`
- `tests/Feature/PestanasMatrizTest.php`

**No modificado (intencional):** `package-lock.json` ya aparecía modificado antes
de empezar esta tarea; se dejó tal cual, fuera de los tres commits.
