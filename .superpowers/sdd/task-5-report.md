# Task 5: Conmutador lista/tarjetas en zonas — Informe

## Estado: DONE

Steps 1-6 completos. La duda planteada en la primera pasada (aserción de
`test_el_enlace_a_la_zona_esta_en_las_dos_maquetaciones` contando por
casualidad enlaces que no debía) se resolvió con indicación del coordinador:
ver "Resolución de la duda" más abajo. Suite completa en verde, `npm run
build` correcto, commit hecho.

## Qué implementé

1. **`tests/Feature/ConmutadorVistaTest.php`** — creado tal cual lo trae el
   brief (Step 1). Ajustado después, ya con indicación del coordinador, en
   `test_el_enlace_a_la_zona_esta_en_las_dos_maquetaciones` (ver "Resolución
   de la duda").

2. **`resources/views/components/conmutador-vista.blade.php`** — nuevo
   componente, copiado literalmente del Step 3. Recibe `modelo` (nombre de la
   variable Alpine) y pinta los dos botones; no sabe dónde se guarda la
   preferencia.

3. **`resources/views/components/icono.blade.php`** — añadido el trazo
   `cuadricula` al mapa `$trazos`, tal cual lo trae el brief.

4. **`resources/views/operativo/inventarios/index.blade.php`** — sustituido
   el marcado a mano del conmutador (antiguas líneas 26-44) por
   `<x-conmutador-vista modelo="vista" />`. La clave `inventario_vista` en
   `x-data`/`x-init` no se tocó.

5. **`resources/views/operativo/dashboard.blade.php`** (`/mis-zonas`) —
   envuelto el contenido en `x-data="{ vista: localStorage.getItem('zonas_vista')
   || 'tarjetas' }"` con su `x-init` de guardado. Añadido el conmutador y una
   maquetación de **lista** nueva (antes solo había tarjetas). Las tarjetas
   existentes pasaron a `x-show="vista === 'tarjetas'"`. Los dos enlaces de
   cada zona («Abrir zona», «Inventario») son idénticos en las dos
   maquetaciones.

6. **`resources/views/admin/zonas/index.blade.php`** (`/admin/zonas`) —
   envuelto en `x-data="{ vista: localStorage.getItem('zonas_vista') || 'lista' }"`.
   La tabla existente pasó a `x-show="vista === 'lista'"`. Añadida una
   maquetación de **tarjetas** nueva con foto/inicial, nombre, lugar, jefe
   asignado, número de miembros, progreso y los tres botones (Abrir zona,
   Editar, Eliminar) — mismos hrefs que la tabla. El paginador
   (`{{ $zonas->links() }}`) quedó **fuera** de los dos `x-show`, así que se ve
   igual en ambos formatos y la consulta sigue paginando de 10 en 10 (no la
   toqué).

## Resultado de la suite tras el Step 3, antes de tocar zonas

Checkpoint obligatorio del brief: extraer el control de Inventario al
componente no debía cambiar su comportamiento.

```
$ php artisan test
...
Tests:    3 failed, 424 passed (2625 assertions)
Duration: 26.05s
```

Los 3 fallos eran exactamente los 3 tests de `ConmutadorVistaTest` que
dependen de las vistas de zonas (`mis_zonas_trae_las_dos_maquetaciones`,
`la_lista_del_admin_trae_las_dos_maquetaciones`,
`el_admin_ve_jefe_y_miembros_en_las_dos_maquetaciones`), que en ese punto aún
no estaban implementadas — nada relacionado con Inventario. Confirma que
Inventario se comporta igual que antes de extraer el componente.

## Evidencia TDD

**ROJO** — `php artisan test --filter=ConmutadorVistaTest`, antes de crear el
componente y tocar las vistas de zonas:

```
⨯ mis zonas trae las dos maquetaciones
✓ el enlace a la zona esta en las dos maquetaciones   (pasaba por casualidad,
                                                         ver "Dudas")
⨯ la lista del admin trae las dos maquetaciones
⨯ el admin ve jefe y miembros en las dos maquetaciones
✓ inventario conserva su propia preferencia

Tests: 3 failed, 2 passed
```

Coincide con lo esperado por el Step 2 del brief ("pasa solo
`test_inventario_conserva_su_propia_preferencia`" — de hecho también pasaba
`el_enlace...`, por la razón que explico en "Dudas").

**VERDE parcial** — mismo filtro, después de los Steps 3-5 (antes del ajuste
de la aserción, ver "Resolución de la duda"):

```
$ php artisan test --filter=ConmutadorVistaTest
✓ mis zonas trae las dos maquetaciones
⨯ el enlace a la zona esta en las dos maquetaciones
   Failed asserting that 4 is identical to 2.
✓ la lista del admin trae las dos maquetaciones
✓ el admin ve jefe y miembros en las dos maquetaciones
✓ inventario conserva su propia preferencia

Tests: 1 failed, 4 passed (15 assertions)
```

**VERDE total** — mismo filtro, tras ajustar la aserción a `$url . '"'`:

```
$ php artisan test --filter=ConmutadorVistaTest
✓ mis zonas trae las dos maquetaciones
✓ el enlace a la zona esta en las dos maquetaciones
✓ la lista del admin trae las dos maquetaciones
✓ el admin ve jefe y miembros en las dos maquetaciones
✓ inventario conserva su propia preferencia

Tests: 5 passed (15 assertions)
```

## Salida de la suite completa (tras el ajuste del test, Step 6)

```
$ php artisan test
...
Tests:    427 passed (2629 assertions)
Duration: 25.37s
```

422 (base) + 5 (ConmutadorVistaTest) = 427 tests totales, todos en verde.

`npm run build` corrió sin errores (56 módulos, `built in 12.51s`).

Commit: `f000219` — "feat(zonas): conmutador lista/tarjetas con preferencia
propia". `package-lock.json` se dejó fuera del staging a propósito (venía
modificado desde antes de esta tarea, ajeno a este cambio).

## Ficheros modificados

- `tests/Feature/ConmutadorVistaTest.php` (nuevo)
- `resources/views/components/conmutador-vista.blade.php` (nuevo)
- `resources/views/components/icono.blade.php` (trazo `cuadricula` añadido)
- `resources/views/operativo/inventarios/index.blade.php` (conmutador extraído al componente)
- `resources/views/operativo/dashboard.blade.php` (maquetación de lista añadida)
- `resources/views/admin/zonas/index.blade.php` (maquetación de tarjetas añadida, paginador movido fuera de los `x-show`)

`package-lock.json` aparecía modificado desde antes de empezar (según el
estado de git al inicio de la conversación) — no lo he tocado en ningún commit
propio.

## Autorrevisión (checklist del encargo)

- **¿Las dos maquetaciones de cada vista llevan los mismos datos y enlaces?**
  Sí: mismo `$progreso[$zona->id]`, mismas rutas (`operativo.zona.panel`,
  `operativo.inventarios.index` en `/mis-zonas`; `operativo.zona.panel`,
  `admin.zonas.edit`, `admin.zonas.destroy` en `/admin/zonas`) en ambas
  maquetaciones de cada vista.
- **¿El paginador del admin quedó fuera de los `x-show`?** Sí, es un
  `<div>` hermano de los dos `x-show`, después de ambos.
- **¿Inventario conserva su clave `inventario_vista`, separada de
  `zonas_vista`?** Sí, verificado por
  `test_inventario_conserva_su_propia_preferencia` (verde).
- **¿Alguna clase de Tailwind quedó construida por concatenación?** No, todas
  las clases nuevas están escritas completas.
- **¿Queda alguna consulta dentro de un bucle Blade?** No. `$progreso` se
  resuelve antes del `@foreach` (por controlador, con `EstadoZona::progresoDe()`)
  y solo se indexa por `$zona->id` dentro del bucle; no hay llamadas Eloquent
  nuevas dentro de `@foreach`.

## Qué queda sin cubrir por tests y por qué

- El propio conmutador (clic en los botones, transición Alpine) no tiene test
  de servidor — Alpine corre en el navegador, y el comentario del propio
  `ConmutadorVistaTest` ya lo explica: "un test de servidor no puede
  pulsarlo". No he añadido un test de navegador (Dusk/Playwright) porque el
  brief no lo pide y el proyecto no parece tener esa infraestructura montada.
- No hay test de navegador (Dusk/Playwright) para el propio conmutador, más
  allá de la verificación de servidor ya descrita. El brief no lo pide y el
  proyecto no parece tener esa infraestructura montada.

## Resolución de la duda (indicación del coordinador)

**Problema, recapitulando:** `operativo.zona.panel` resuelve a
`.../operativo/zona/{zona}` y `operativo.inventarios.index` resuelve a
`.../operativo/zona/{zona}/inventarios` — la segunda URL empieza exactamente
por la primera. `substr_count($html, $url)` contaba también el `href` de cada
enlace «Inventario» (que empieza igual), así que con las dos maquetaciones
implementadas el conteo real era 4 (2 enlaces reales «Abrir zona» + 2
coincidencias de rebote dentro de «Inventario»), no 2.

**Decisión del coordinador:** mantener el `2` esperado y hacer la aserción
precisa en vez de subirla a 4, porque el 4 sería correcto por casualidad —
contaría dos enlaces que el test no pretende medir, y se rompería sin razón
si algún día se quita el atajo a Inventario. Lo que el test quiere afirmar es
"el enlace a la zona aparece una vez en cada maquetación", y eso se comprueba
exigiendo que la URL termine ahí, no que sea solo un prefijo.

**Cambio aplicado** en
`tests/Feature/ConmutadorVistaTest.php::test_el_enlace_a_la_zona_esta_en_las_dos_maquetaciones`:

```php
// La comilla de cierre es a propósito: la URL del panel de zona
// ('.../operativo/zona/{zona}') es prefijo exacto de la de
// Inventario ('.../operativo/zona/{zona}/inventarios'). Sin la
// comilla, substr_count también contaría cada enlace a Inventario
// -que empieza igual- y el conteo real (2 reales + 2 de rebote) no
// diría nada sobre si el enlace a la zona está una vez por
// maquetación, que es lo que este test quiere afirmar.
$this->assertSame(
    2,
    substr_count($html, $url . '"'),
    'El enlace a la zona debe aparecer una vez en cada maquetación.'
);
```

**Búsqueda de defectos similares:** repasé todos los usos de `substr_count`
en `tests/` (`ConcentracionTest`, `EvaluacionesTest`, `InvolucradosTest`,
`IrritacionTest`, `PaginaZonaTest`, `PercepcionTest`). Ninguno cuenta una URL
generada con `route()` — cuentan cadenas literales sin relación de prefijo
entre sí (`'disabled'`, `'disabled>'`, `'type="radio"'`,
`'class="text-gray-400"'`, `'<option '`, `'Lista para validar'`). No
encontré ningún otro caso con el mismo defecto; no hizo falta tocar nada más.

Con el ajuste, las 5 aserciones de `ConmutadorVistaTest` pasan y la suite
completa quedó en 427/427 verde (ver arriba). Se completó el Step 6:
`npm run build`, suite completa en primer plano, y commit
(`f000219`, `package-lock.json` excluido).

---

## Cierre de hallazgo de revisión — informe

### Estado: DONE

Dos mitades, las dos cerradas: el dato que faltaba en la vista y el test que
no lo habría pillado.

### Hallazgo 1 — campo a campo, qué faltaba

Comparé `/mis-zonas` (`resources/views/operativo/dashboard.blade.php`) campo
a campo entre sus dos maquetaciones:

| Campo | Lista (antes) | Tarjetas |
|---|---|---|
| nombre | sí | sí |
| lugar | sí | sí |
| **descripción** | **no** | sí |
| progreso (hechas/total) | sí | sí |
| enlace «Abrir zona» | sí | sí |
| enlace «Inventario» | sí | sí |
| imagen/inicial | no (decorativa, no es dato — el nombre ya está en texto en ambas) | sí |

Solo faltaba la **descripción**, tal cual apuntaba el hallazgo. La imagen de
zona la dejé fuera del análisis de "dato perdido": no aporta ninguna
información que no esté ya como texto (nombre), es puramente decorativa, y
así se leyó también el propio diseño original de la maquetación de lista
(fila compacta, sin miniaturas).

`resources/views/admin/zonas/index.blade.php` no muestra `descripcion` en
ninguna de sus dos maquetaciones (ni tabla ni tarjetas) — no hay asimetría
ahí, coincide con lo que el encargo decía que ya estaba verificado. No
encontré ningún otro campo desalineado al repasarla de paso.

### Hallazgo 2 — el test reforzado

Añadí `test_las_dos_maquetaciones_de_mis_zonas_llevan_los_mismos_datos` a
`tests/Feature/ConmutadorVistaTest.php`. Compara, con `substr_count`
esperando 2 (una vez por maquetación):

- **nombre** de la zona
- **lugar** (con el prefijo `📍 ` para no enganchar el nombre de lugar si
  apareciera suelto en otro sitio)
- **descripción**, con un texto explícito (`'Zona costera con senderos y
  miradores.'`) asignado en el propio test — no el texto de reserva. Si
  hubiera usado el texto de reserva ("Sin descripción disponible."), el test
  habría dado el mismo resultado con el campo ausente en una maquetación y
  presente en la otra pero mostrando fallback en las dos, así que no habría
  demostrado que el dato real viaja en ambas.
- **progreso**, calculado con `EstadoZona::progresoDe()` (el mismo servicio
  que usa el controlador) para no inventar un número que luego no cuadre con
  lo que pinta la vista.

No hubo trampa de prefijo aquí (el defecto que sí mordió al enlace de zona en
la ronda anterior, línea 62-68 del fichero): ninguno de estos cuatro
literales es prefijo de otro dato de la página.

### Evidencia del rojo antes del verde

```
$ php artisan test --filter=ConmutadorVistaTest
...
⨯ las dos maquetaciones de mis zonas llevan los mismos datos
   La descripción debe aparecer una vez por maquetación.
   Failed asserting that 1 is identical to 2.

Tests:    1 failed, 5 passed (19 assertions)
```

Falló exactamente donde tenía que fallar — la aserción de descripción, con
1 en vez de 2 (solo estaba en tarjetas) — y ninguna otra aserción del test
nuevo falló, confirmando que nombre/lugar/progreso ya tenían paridad y el
único problema real era la descripción.

### Arreglo aplicado

En `resources/views/operativo/dashboard.blade.php`, dentro de la fila de la
maquetación de lista (mismo bloque `<div class="flex-1 min-w-0">` que ya
tiene nombre y lugar), añadida:

```blade
{{-- Misma descripción que la tarjeta, para que cambiar de
     formato no le esconda este dato al usuario. --}}
<p class="text-sm text-gray-600 mt-1 line-clamp-1">
    {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
</p>
```

Mismo texto de reserva que la tarjeta (`'Sin descripción disponible.'`).
Usé `line-clamp-1` (no `line-clamp-2` como la tarjeta) porque la fila de
lista es de una sola línea de alto fijo con `items-center`; una descripción
de dos líneas empujaría la barra de progreso y los botones fuera de
alineación en pantallas estrechas. `line-clamp-1` ya es utilidad núcleo de
Tailwind (el proyecto tiene Tailwind 3.4.18 instalado; `line-clamp-2` ya se
usa en la propia tarjeta de esta vista, así que la utilidad ya estaba en
uso).

### Verde

```
$ php artisan test --filter=ConmutadorVistaTest
✓ mis zonas trae las dos maquetaciones
✓ el enlace a la zona esta en las dos maquetaciones
✓ las dos maquetaciones de mis zonas llevan los mismos datos
✓ la lista del admin trae las dos maquetaciones
✓ el admin ve jefe y miembros en las dos maquetaciones
✓ inventario conserva su propia preferencia

Tests:    6 passed (20 assertions)
```

### Suite completa

```
$ php artisan test
...
Tests:    428 passed (2634 assertions)
Duration: 24.24s
```

427 (base) + 1 test nuevo = 428, todos en verde.

`npm run build`: sin errores, 56 módulos, `built in 1.75s`.

### Commit

`1c78c81` — "fix(zonas): la lista de mis-zonas muestra la descripcion de la
zona". Incluye `resources/views/operativo/dashboard.blade.php` y
`tests/Feature/ConmutadorVistaTest.php`. `package-lock.json` quedó fuera del
staging (venía modificado desde antes de esta tarea, según el estado de git
al inicio de la conversación; no lo he tocado).

### Autorrevisión

- **¿El test nuevo falla de verdad sin el arreglo?** Sí, verificado en rojo
  (arriba): falla únicamente en la aserción de descripción, con 1 en vez de
  2, antes de tocar la vista.
- **¿Queda algún campo en una maquetación y no en la otra?** No, tras el
  arreglo: nombre, lugar, descripción, progreso y los dos enlaces están en
  las dos maquetaciones de `/mis-zonas`. `/admin/zonas` ya tenía paridad
  completa y no se tocó.
- **¿La descripción encaja en la fila sin romper la maquetación en pantallas
  estrechas?** Sí: `line-clamp-1` la recorta a una línea dentro del mismo
  `<div class="flex-1 min-w-0">` que ya contenía nombre y lugar, sin afectar
  al ancho fijo (`w-40 shrink-0`) de la barra de progreso ni a los botones
  (`shrink-0`) que están al lado.
