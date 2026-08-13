# Tarea 2 — la ordenación, en el servidor

## Qué implementé

1. **`resources/views/operativo/dashboard.blade.php`** (líneas 57 y 93): añadidos
   los `id="zonas-lista"` e `id="zonas-tarjetas"` a los dos contenedores de
   maquetación, tal cual el brief.
2. **`tests/Feature/OrdenMisZonasTest.php`**: creado con el contenido íntegro
   del brief, sin modificaciones.
3. **`app/Http/Controllers/Operativo/DashboardController.php`**: sustituido
   entero por el código del brief. Añade:
   - Lista blanca de órdenes (`nombre`, `lugar`, `progreso`) y direcciones
     (`asc`, `desc`), con caída silenciosa a `nombre`/`asc` si el parámetro de
     la URL no está en la lista o no es una cadena (`in_array(..., true)`
     descarta arrays como `?orden[]=x`).
   - `ordenar()`: ordena la colección en PHP (no en SQL, porque el progreso
     se calcula en memoria a partir de las diez matrices), con
     `SORT_NATURAL | SORT_FLAG_CASE` para nombre/lugar y orden numérico plano
     para progreso.
   - `proximoPaso()` sigue recibiendo la colección en el orden **por
     defecto**, no en el que pida la URL, para que el panel de arriba no
     salte de zona al pulsar una cabecera de columna.
   - La vista recibe ahora `$orden` y `$dir`, ya normalizadas, además de
     `$zonas`, `$progreso` y `$proximoPaso`.
   - El comentario sobre el coste de instanciar `EstadoZona` por zona quedó
     tal como venía en el brief («costaba una consulta por matriz Y por
     zona»), la corrección ya aplicada que se me indicó.

## Qué probé y con qué resultado

- `php artisan test --filter=OrdenMisZonasTest` antes del controlador: **rojo**
  (evidencia abajo).
- `php artisan test --filter=OrdenMisZonasTest` después del controlador:
  **verde**, 8 tests / 43 assertions.
- `php artisan test` completo: **619 passed (3912 assertions)**, sin errores
  ni avisos, en 44.81 s. No hizo falta partir en Unit/Feature; no hubo
  `Out of memory`.
- `php artisan test --filter="DashboardTest|ConmutadorVistaTest"`: los 16
  tests mencionados en el encargo como "deben seguir pasando" pasan.

## Evidencia TDD

### ROJO

Comando:

```
php artisan test --filter=OrdenMisZonasTest
```

Salida (resumen; el fichero de test aún no tenía implementación de orden en
el controlador):

```
 FAIL  Tests\Feature\OrdenMisZonasTest
 ⨯ por defecto las zonas salen por nombre ascendente             2.40s
 ✓ dir desc invierte el orden                                    0.05s
 ⨯ se puede ordenar por lugar                                    0.04s
 ⨯ por progreso descendente va primero la mas avanzada           0.05s
 ⨯ un orden desconocido cae al de por defecto con 200             0.06s
 ✓ un orden que es un array no rompe la pagina                   0.07s
 ✓ las dos maquetaciones se ordenan igual                        0.05s
 ⨯ el panel de siguiente paso no cambia al reordenar              0.05s

Tests:    5 failed, 3 passed (42 assertions)
Duration: 3.06s
```

Ejemplo de fallo (el primero):

```
FAILED  Tests\Feature\OrdenMisZonasTest > por defecto las zonas salen por nombre ascendente
Sin parámetros, el orden es nombre ascendente y no el id de la base.
Failed asserting that two arrays are identical.
--- Expected
+++ Actual
@@ @@
 Array &0 [
-    0 => 235,
+    0 => 4019,
     1 => 2128,
-    2 => 4019,
+    2 => 235,
 ]
```

Se esperaba justo esto: sin ordenación en el controlador, las zonas salían en
orden de creación (id) — Charlie, Bravo, Alfa — y no en orden alfabético.
Tres de los ocho pasaban de casualidad (`dir_desc` porque el orden por id
invertido coincide parcialmente, `array no rompe la página` porque solo
comprueba 200, y `las dos maquetaciones se ordenan igual` porque ambas
maquetaciones ya compartían el mismo orden, aunque no el correcto).

### VERDE

Comando:

```
php artisan test --filter=OrdenMisZonasTest
```

Salida:

```
 PASS  Tests\Feature\OrdenMisZonasTest
 ✓ por defecto las zonas salen por nombre ascendente              0.55s
 ✓ dir desc invierte el orden                                     0.05s
 ✓ se puede ordenar por lugar                                     0.04s
 ✓ por progreso descendente va primero la mas avanzada            0.05s
 ✓ un orden desconocido cae al de por defecto con 200              0.04s
 ✓ un orden que es un array no rompe la pagina                    0.04s
 ✓ las dos maquetaciones se ordenan igual                         0.05s
 ✓ el panel de siguiente paso no cambia al reordenar               0.04s

Tests:    8 passed (43 assertions)
Duration: 1.17s
```

Suite entera:

```
php artisan test
...
Tests:    619 passed (3912 assertions)
Duration: 44.81s
```

## Ficheros cambiados

- `app/Http/Controllers/Operativo/DashboardController.php` (sustituido
  entero, como pedía el brief).
- `resources/views/operativo/dashboard.blade.php` (solo los dos `id`, líneas
  57 y 93).
- `tests/Feature/OrdenMisZonasTest.php` (nuevo).

Commit: `7ffe6a6` — `feat(mis-zonas): el orden se pide por URL y lo resuelve
el servidor`.

## Hallazgos de mi auto-revisión

- El diff coincide exactamente con el código del brief; no añadí ni quité
  nada del controlador ni de la vista.
- `$orden` y `$dir` llegan a la vista pero la vista de esta tarea no los usa
  todavía (las cabeceras ordenables son la Tarea 5) — es lo esperado, el
  brief lo dice explícitamente («la T5 las usa»).
- `package-lock.json` no aparece en el `git status`, no hubo que revertirlo.
- No toqué nada bajo `admin/` ni sus tests.
- No toqué ningún otro fichero de `.superpowers/sdd/2026-08-13-dashboard-mis-zonas/`
  salvo este informe.

## Duda / discrepancia encontrada (no bloqueante)

El **texto narrativo** del brief dice en el Paso 5 «Esperado: PASAN los
nueve» y en el Paso 6 «620 tests en verde (611 + 9)». Pero el **contenido
literal** del fichero de test que el mismo brief da en el Paso 2 solo
declara **8** métodos `test_*` (verificado con
`grep -c "public function test_"` → 8). Como se me indicó copiar el test tal
cual, sin reescribirlo ni mejorarlo, no añadí un noveno test inventado. El
resultado real y correcto es:

- `OrdenMisZonasTest`: 8 tests, 43 assertions, todos en verde.
- Suite completa: **619** tests en verde (611 + 8), no 620.

No afecta a nada funcional — todo pasa, nada se rompe — pero dejo
constancia por si el recuento de 620 se usa como referencia en el traspaso o
en la tarea 3.
