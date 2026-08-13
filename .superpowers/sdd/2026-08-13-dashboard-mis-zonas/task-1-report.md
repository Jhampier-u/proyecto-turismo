# Tarea 1: `progresoDe()` devuelve el desglose — informe

## Qué implementé

1. Sobrescribí `.superpowers/sdd/progress.md` con la bitácora de la rama
   `dashboard-mis-zonas` (paso 1 del brief), conservando la nota de cabecera.
2. Añadí los tres tests nuevos al final de `tests/Unit/EstadoZonaTest.php`,
   literales del brief.
3. Los corrí y los vi fallar por la razón esperada (ver evidencia ROJO).
4. Sustituí el método `EstadoZona::progresoDe()` (líneas 110-145) por la
   versión que calcula `hechas`, `borradores`, `sin_empezar` y `total` con
   diez consultas fijas (una por matriz validable), literal del brief.
5. Corrí `EstadoZonaTest` completo y lo vi pasar (33/33).
6. Amplié `DashboardTest::test_el_numero_de_consultas_no_crece_con_el_numero_de_zonas`:
   añadí el helper `crearZonaConProgreso()` después de `crearZona()`, cambié
   las dos altas de zona del test para usarlo, y añadí la frase del docblock
   que explica por qué las dos mediciones necesitan la misma forma de datos.
7. Corrí la suite entera: 611/611 en verde.
8. Commit con el mensaje literal del brief, tocando solo los cuatro ficheros
   que el brief lista en el `git add`.

## Qué probé y con qué resultado

- `php artisan test --filter="desglosa_validadas|sin_evaluaciones_sale_entera|no_mezcla_zonas"`
  antes de tocar `EstadoZona.php`: los tres fallan con
  `Undefined array key "borradores"` (ROJO, ver evidencia abajo).
- `php artisan test --filter=EstadoZonaTest` tras implementar: 33/33 pasan.
- `php artisan test --filter=DashboardTest` tras ampliar el test de
  consultas: 10/10 pasan (incluye `RedireccionDashboardTest`, que corre en
  el mismo filtro por nombre de clase).
- `php artisan test` (suite entera, dos veces): 611/611 pasan, 3869
  assertions, exit code 0, sin warnings ni deprecations en la salida.

## Evidencia TDD

### ROJO

Comando:
```
php artisan test --filter="desglosa_validadas|sin_evaluaciones_sale_entera|no_mezcla_zonas"
```

Salida (resumen — los tres fallan igual):
```
FAILED  Tests\Unit\EstadoZonaTest > el progreso desglosa validadas borradores y sin empezar   ErrorException
  Undefined array key "borradores"
  at tests\Unit\EstadoZonaTest.php:668
    667▕         $this->assertSame(1, $p['hechas']);
  ➜ 668▕         $this->assertSame(1, $p['borradores']);

FAILED  Tests\Unit\EstadoZonaTest > una zona sin evaluaciones sale entera en sin empezar   ErrorException
  Undefined array key "borradores"
  at tests\Unit\EstadoZonaTest.php:688

FAILED  Tests\Unit\EstadoZonaTest > el desglose no mezcla zonas   ErrorException
  Undefined array key "borradores"
  at tests\Unit\EstadoZonaTest.php:714

Tests:    3 failed (2 assertions)
Duration: 16.52s
```

Por qué se esperaba ese fallo: `progresoDe()` todavía solo devolvía
`['hechas', 'total']`; los tres tests nuevos leen `$p['borradores']` antes de
tocar el código de producción, así que el array no tiene esa clave.

### VERDE

Comando:
```
php artisan test --filter=EstadoZonaTest
```
Salida (cola):
```
✓ el progreso desglosa validadas borradores y sin empezar    0.03s
✓ una zona sin evaluaciones sale entera en sin empezar        0.02s
✓ el desglose no mezcla zonas                                 0.03s

Tests:    33 passed (93 assertions)
Duration: 2.63s
```

Comando:
```
php artisan test --filter=DashboardTest
```
Salida (cola):
```
Tests:    10 passed (29 assertions)
Duration: 11.19s
```

Comando (suite entera, antes del commit):
```
php artisan test
```
Salida (cola):
```
Tests:    611 passed (3869 assertions)
Duration: 93.84s
```
Repetida una segunda vez para confirmar exit code y ausencia de warnings:
`exit:0`, 611 líneas con `✓`, ninguna coincidencia de
`warning|deprecat|error|fail` (case-insensitive) en toda la salida.

## Ficheros cambiados

- `app/Servicios/EstadoZona.php` — `progresoDe()` reescrito (líneas
  110-145 originales), literal del brief.
- `tests/Unit/EstadoZonaTest.php` — tres tests nuevos al final de la clase,
  literales del brief.
- `tests/Feature/DashboardTest.php` — helper `crearZonaConProgreso()` y el
  test de consultas ampliado, literales del brief.
- `.superpowers/sdd/progress.md` — sobrescrito con la bitácora de
  `dashboard-mis-zonas`, literal del brief.

Commit: `7b3c247` — `feat(progreso): el desglose por estado, no solo el
numerador`.

## Hallazgos de mi auto-revisión

- El comentario del código de producción cita la migración
  `2026_08_06_000001_add_unique_zona_id_to_evaluaciones` como garantía de que
  `pluck('estado', 'zona_id')` no pierde filas por `zona_id` duplicado.
  Verifiqué que el fichero de esa migración existe en
  `database/migrations/`, así que la afirmación del comentario es cierta y
  no una promesa suelta.
- `git status` mostró `.superpowers/sdd/.gitignore` modificado (de una lista
  explícita `*.diff` / `*-brief.md` a un `*` genérico). Yo no toqué ese
  fichero y el brief no lo menciona ni en «Ficheros» ni en el `git add` del
  paso 8, así que lo dejé fuera del commit deliberadamente — no es mío que
  decidir si ese cambio es correcto.
- `package-lock.json` no aparece modificado en ningún momento; no hizo falta
  el `git checkout --`.
- Confirmé que `hechas` y `total` conservan exactamente su significado de
  antes: mismo cálculo de `hechas` (contador de `estado === 'confirmado'`),
  mismo `$total = count(Registro::matrices())`. Las dos vistas de admin que
  el brief dice que están fuera de alcance no las toqué.
- El test ampliado de consultas (`DashboardTest`) sigue verificando lo mismo
  que antes (coste no crece con el número de zonas) pero ahora sobre datos
  reales, cerrando el hueco de falso negativo que el brief describía en el
  paso 6.

## Dudas o preocupaciones

Ninguna. El brief se aplicó literal en cada paso, la evidencia ROJO/VERDE es
la esperada, y la suite completa cierra en 611/611 sin efectos colaterales
visibles.
