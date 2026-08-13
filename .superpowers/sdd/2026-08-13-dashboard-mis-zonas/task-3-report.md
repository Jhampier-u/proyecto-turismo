# Informe — Tarea 3: la franja de cifras de conjunto

## Qué implementé

Siguiendo el brief al pie de la letra:

1. **`app/Http/Controllers/Operativo/DashboardController.php`** — en `index()`,
   justo después de la línea de `$progreso`, se calcula `$resumen` con las
   cuatro claves (`zonas`, `validadas`, `matrices`, `terminadas`, todas
   `int`), y se añade `'resumen'` al `compact` del `return`.

2. **`resources/views/operativo/dashboard.blade.php`** — entre el `@endif`
   del panel de «siguiente paso» y el `<div class="flex justify-end mb-4">`
   del conmutador, se pinta la franja: `<div id="zonas-kpis"
   class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">` con tres
   `<x-tarjeta>`, solo si `$resumen['zonas'] >= 2`. Marcado literal del
   brief, sin espacios ni saltos dentro de las etiquetas `<p>` que los tests
   buscan.

3. **`tests/Feature/DashboardTest.php`** — añadidos el helper privado
   `franja()` y los tres tests: `test_la_franja_suma_las_cifras_de_todas_las_zonas`,
   `test_con_una_sola_zona_no_se_pinta_la_franja`,
   `test_la_franja_cuenta_las_zonas_terminadas`.

No toqué nada de `admin/`, ni `package-lock.json`, ni ningún otro fichero de
`.superpowers/sdd/2026-08-13-dashboard-mis-zonas/` salvo este informe.

## Qué probé y con qué resultado

- `DashboardTest` completo: **9/9 en verde** (los 6 tests previos + los 3
  nuevos). El brief hablaba de «ocho» tests en la clase tras la tarea, pero
  la clase ya traía 6 antes de esta tarea (no 5), así que con los 3 nuevos
  quedan 9 — coherente con el recuento real de la clase, no con el número
  del brief.
- Suite entera: **622 tests, 3926 aserciones, en verde**. Coincide con la
  base corregida indicada en el encargo (619 + 3 = 622), no con la cifra del
  brief (623).

## Evidencia TDD

### ROJO

```
php artisan test --filter="la_franja_suma|una_sola_zona_no_se_pinta_la_franja|franja_cuenta_las_zonas_terminadas"
```

```
 FAIL  Tests\Feature\DashboardTest
 ⨯ la franja suma las cifras de todas las zonas                        0.54s
 ✓ con una sola zona no se pinta la franja                             0.04s
 ⨯ la franja cuenta las zonas terminadas                                0.06s
────────────────────────────────────────────────────────────────────────────
 FAILED  Tests\Feature\DashboardTest > la franja suma las cifras de todas las zonas
  No se encontró la franja de cifras.
  Failed asserting that false is not false.
  at tests\Feature\DashboardTest.php:209

 FAILED  Tests\Feature\DashboardTest > la franja cuenta las zonas terminadas
  No se encontró la franja de cifras.
  Failed asserting that false is not false.
  at tests\Feature\DashboardTest.php:209

Tests:    2 failed, 1 passed (7 assertions)
Duration: 0.92s
```

Falló exactamente lo esperado: el primer y el tercer test, con «No se
encontró la franja de cifras» — todavía no existía `id="zonas-kpis"` en la
vista. El segundo (`test_con_una_sola_zona_no_se_pinta_la_franja`) **pasó ya**
en este punto: es la contraparte del comportamiento, y antes de pintar nada
la franja en efecto no aparece con una sola zona. Su valor está en que siga
pasando después de pintar la franja condicional — y así fue.

### VERDE

```
php artisan test --filter=DashboardTest
```

```
 PASS  Tests\Feature\DashboardTest
 ✓ el numero de consultas no crece con el numero de zonas               2.33s
 ✓ sin zonas no se pinta el panel de siguiente paso                     0.04s
 ✓ una zona recien creada ofrece empezar por fit                        0.04s
 ✓ una matriz tocada que no es la primera ofrece las dos tarjetas       0.05s
 ✓ la matriz tocada y la siguiente sin terminar no se repiten           0.04s
 ✓ el panel dice a que zona pertenece cada tarjeta                      0.05s
 ✓ la franja suma las cifras de todas las zonas                        0.05s
 ✓ con una sola zona no se pinta la franja                              0.04s
 ✓ la franja cuenta las zonas terminadas                                0.06s

 PASS  Tests\Feature\RedireccionDashboardTest
 ✓ el admin aterriza en el panel de administracion                     0.03s
 ✓ el jefe de zona aterriza en sus zonas                                0.04s
 ✓ el equipo aterriza en sus zonas                                     0.02s
 ✓ los ids que siembra el seeder son los que asumen las constantes      0.02s

Tests:    13 passed (43 assertions)
Duration: 3.10s
```

```
php artisan test
```

```
Tests:    622 passed (3926 assertions)
Duration: 42.70s
```

## Ficheros cambiados

- `C:\proyecto-turismo\app\Http\Controllers\Operativo\DashboardController.php`
- `C:\proyecto-turismo\resources\views\operativo\dashboard.blade.php`
- `C:\proyecto-turismo\tests\Feature\DashboardTest.php`

Commit: `272453c` — `feat(mis-zonas): las cifras de conjunto, que antes había
que sumar a ojo`

## Hallazgos de mi auto-revisión

- El marcado de las tres `<x-tarjeta>` es literal al brief, sin saltos
  dentro de las etiquetas `<p>` que los tests buscan (`>2</p>`, `>3</p>`,
  `>1</p>`, `de 20 en total`). Verificado con los propios tests en verde.
- Las tres claves de `$resumen` que se exponen a la vista son `int`:
  `$zonas->count()` es `int`; `array_sum()` sobre una lista de `int` produce
  `int` (no hay floats en `hechas`/`total`); `count(array_filter(...))` es
  `int`. Cumple la interfaz declarada.
- La condición `$p['total'] > 0 && $p['hechas'] === $p['total']` para
  «terminada» reutiliza exactamente el criterio de «todo validado» que ya usa
  `EstadoZona`/`proximoPaso()` en otras partes del código (comparación con
  `===`, no `>=`), así que no introduce un criterio nuevo de «terminado».
  Además usa `total > 0` para no contar como terminada una zona sin
  matrices (caso que no debería darse en la práctica, pero deja el cálculo a
  salvo de una división implícita rara).
- No encontré casos límite sin cubrir dentro del alcance de la tarea: cero
  zonas ya no entra en la condición `>= 2` (queda fuera, correcto); una zona
  queda cubierta por el test que ya pasaba antes de tocar código; dos o más
  zonas, con mezcla de confirmadas/borradores/zona terminada, están cubiertas
  por los tres tests nuevos.
- No añadí nada fuera de lo pedido (YAGNI): no se tocó `admin/`, ni se creó
  ningún componente ni helper nuevo — la franja reutiliza `<x-tarjeta>` tal
  cual.
- Until mid-commit, detecté un cambio en `.superpowers/sdd/.gitignore` que
  **no hice yo** (reduce el fichero a una sola línea `*`, cambiando qué
  viaja de esa carpeta). No lo toqué ni lo incluí en mi commit — quedó fuera
  del `git add` explícito de los tres ficheros del brief. Lo señalo como
  hallazgo, no como algo que haya corregido: no es mi tarea y no sé qué otro
  proceso lo tocó.

## Dudas o preocupaciones

- El brief dice «PASAN los ocho de la clase» en el Paso 5 y «623 tests en
  verde (620 + 3)» en el Paso 6. Ninguna de las dos cifras cuadra con el
  estado real del árbol (la clase ya tenía 6 tests antes de esta tarea, no
  5; y la suite partía de 619, no 620, como ya me advertías en el encargo).
  Apliqué la corrección aritmética que me diste (619 + 3 = 622) y confirmé
  que la suite entera cae exactamente en 622. Para la cifra de la clase (9,
  no 8) no tenía una corrección explícita en el encargo, pero es la misma
  clase de error aritmético y el resultado observado (9/9 en verde) es
  correcto y consistente con lo que ya había en el árbol antes de mi tarea.
