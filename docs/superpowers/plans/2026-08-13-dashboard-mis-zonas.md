# Dashboard «Mis Zonas» — plan de implementación

> **Para quien lo ejecute:** SUB-SKILL OBLIGATORIA: usa
> `superpowers:subagent-driven-development` (recomendado) o
> `superpowers:executing-plans` para ejecutarlo tarea a tarea. Los pasos usan
> casillas (`- [ ]`) para poder marcarlos.

**Objetivo:** que la portada del perfil operativo diga de un vistazo cuánto
lleva el trabajo entero, distinga lo que nadie ha abierto de lo que espera
validación, y permita ordenar las zonas por nombre, lugar o progreso.

**Arquitectura:** el desglose por estado sale de `EstadoZona::progresoDe()`,
que ya resuelve todas las zonas con un número fijo de consultas y pasa a
devolver cuatro cifras en vez de dos. La ordenación vive en el
`DashboardController`, que ordena **la colección en PHP** según un parámetro de
URL con lista blanca. La vista pinta lo que recibe: una franja de cifras, una
tabla con cabeceras que son enlaces, y las mismas insignias en las dos
maquetaciones.

**Tecnología:** Laravel 11, Blade con componentes anónimos, Tailwind, Alpine
solo para conmutar maquetación (ya existente), PHPUnit.

**Spec:** `docs/superpowers/specs/2026-08-13-dashboard-mis-zonas-design.md` —
el plan argumenta contra ella; quien ejecute lee las dos.

## Restricciones globales

- **Suite base de la rama: 608 tests en verde.** Se mide con `php artisan test`
  (PHP 8.2.33 nativo, ~110 s). Si sale `Out of memory` —es un problema del
  binario de Windows, no del código— se parte: `php artisan test
  --testsuite=Unit` y `php artisan test --testsuite=Feature`.
- **Ninguna pantalla de `admin/` se toca.** Ni el panel, ni `admin/zonas/index`.
- **`hechas` no se renombra a `validadas`.** Lo consumen `admin/zonas/index`
  (dos veces) y `ConmutadorVistaTest`, y renombrar arrastraría vistas fuera del
  alcance. Las claves nuevas se **añaden**, la vieja se queda.
- **Clases de Tailwind completas, nunca construidas por concatenación**:
  el purgado se lleva las que no aparecen literales en el fuente.
- **`package-lock.json` no entra en ningún commit.** Si aparece modificado:
  `git checkout -- package-lock.json`.
- **No se toca ningún contenedor de Docker.** No hace falta para nada de esto.
- Mensajes de commit en español, terminados en
  `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

## Ficheros

| Fichero | Responsabilidad | Tarea |
|---|---|---|
| `app/Servicios/EstadoZona.php` | `progresoDe()` devuelve el desglose | T1 |
| `app/Http/Controllers/Operativo/DashboardController.php` | orden pedido, orden aplicado, cifras de conjunto | T2, T3 |
| `resources/views/components/desglose-estados.blade.php` | *(nuevo)* las insignias de una zona | T4 |
| `resources/views/components/cabecera-ordenable.blade.php` | *(nuevo)* un `<th>` que ordena, con `aria-sort` | T5 |
| `resources/views/operativo/dashboard.blade.php` | franja, tabla, tarjetas, caso vacío | T2–T6 |
| `tests/Unit/EstadoZonaTest.php` | el desglose, sobre el servicio | T1 |
| `tests/Feature/DashboardTest.php` | coste en consultas, franja de cifras, cero zonas | T1, T3, T6 |
| `tests/Feature/OrdenMisZonasTest.php` | *(nuevo)* el orden, sobre el HTML servido | T2, T5 |
| `tests/Feature/DesgloseEstadosTest.php` | *(nuevo)* el contrato del componente | T4 |
| `tests/Feature/ConmutadorVistaTest.php` | se actualiza dos veces, con motivo | T4, T5 |

## Dos decisiones que el plan añade a la spec

**1. El «hechas / total» desaparece de las DOS maquetaciones, no solo de la
tarjeta.** La spec dice que esa cadena deja de existir en la tarjeta y que las
insignias tienen que estar también en la tabla. Dejar la fracción en la tabla y
no en la tarjeta rompería justo la restricción que `ConmutadorVistaTest`
defiende —que las dos lleven lo mismo—, y además el desglose ya *contiene* la
fracción: «3 validadas» sobre diez es el mismo dato mejor dicho. Las dos siguen
llevando la barra de progreso.

**2. El panel de «siguiente paso» NO cambia al reordenar la tabla.**
`EstadoZona::proximoPaso()` recorre las zonas *en el orden que recibe* y se
detiene en la primera con algo pendiente. Si se le pasara la colección ya
ordenada por la URL, pulsar «Progreso» movería la recomendación de arriba, que
no es una fila de la lista sino un consejo. Así que recibe siempre la colección
en el orden **por defecto** (nombre ascendente) y solo la lista de abajo obedece
al parámetro. Va con test.

---

### Tarea 1: `progresoDe()` devuelve el desglose

**Ficheros:**
- Modificar: `app/Servicios/EstadoZona.php:110-145`
- Modificar: `tests/Unit/EstadoZonaTest.php` (tests nuevos al final de la clase)
- Modificar: `tests/Feature/DashboardTest.php:30-82`
- Sobrescribir: `.superpowers/sdd/progress.md`

**Interfaces:**
- Consume: nada de tareas anteriores.
- Produce: `EstadoZona::progresoDe(Collection $zonas): array<int, array{hechas:
  int, borradores: int, sin_empezar: int, total: int}>`, indexado por `zona_id`.
  Las tareas 2 a 5 leen esas cuatro claves. `hechas` y `total` conservan su
  significado exacto de hoy.

- [ ] **Paso 1: sobrescribir la bitácora de rama**

`.superpowers/sdd/progress.md` todavía lleva la de la Fase 1, que ya está
volcada en el traspaso (commit `55e478f`). La regla 3 de `CLAUDE.md` dice que se
sobrescribe al empezar cada rama. Deja el fichero así, conservando la nota de
cabecera que ya tiene:

```markdown
> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — Dashboard «Mis Zonas» (Fase 2 del rediseño de interfaz)

Spec: `docs/superpowers/specs/2026-08-13-dashboard-mis-zonas-design.md`
Plan: `docs/superpowers/plans/2026-08-13-dashboard-mis-zonas.md`
Rama: dashboard-mis-zonas
Base de la rama: 0cd8ecf
Suite en la base: 608 tests

Lo de la Fase 1 que estaba aquí antes ya está volcado en el traspaso, así que
sobrescribir este fichero no pierde nada.

Objetivo: cifras de conjunto, un orden que se pueda pedir por URL, y un estado
que distinga lo que nadie ha abierto de lo que espera validación.

## Decisiones que vienen del diseño y no se replantean

- **La ordenación va en el servidor**, por parámetro de URL. Playwright no está
  instalado en esta máquina: con Alpine sería invisible para la suite.
- **Desglose por estado, no una insignia de «zona terminada».** Los colores de
  `ESTILOS_ESTADO` significan el estado de una MATRIZ.
- **Ninguna pantalla de admin se toca**, y `hechas` no se renombra.
- **El orden por defecto pasa a ser nombre ascendente.**

## Tareas

T1 el desglose en el servicio · T2 la ordenación en el servidor · T3 la franja
de cifras · T4 el desglose en las dos maquetaciones · T5 la tabla ordenable ·
T6 cero zonas · T7 revisión y traspaso.
```

- [ ] **Paso 2: escribir los tests que fallan**

Añade estos tres al final de `tests/Unit/EstadoZonaTest.php`, dentro de la
clase. `EvaluacionFit` y `EvaluacionFet` ya están importados arriba del fichero;
`Zona` y `DB` también.

```php
    /**
     * El desglose de una zona: una validada, una en borrador y las ocho
     * restantes sin fila ninguna.
     *
     * Las tres cifras suman siempre el total, que es la propiedad por la que
     * se pueden leer como un reparto y no como tres números sueltos. El 10
     * es el número de entradas validables del registro hoy —diez de doce,
     * con `inventario` y `vtt` fuera—: si mañana entra una matriz nueva, este
     * test tiene que enterarse.
     */
    public function test_el_progreso_desglosa_validadas_borradores_y_sin_empezar(): void
    {
        EvaluacionFit::create([
            'zona_id' => $this->zona->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);
        EvaluacionFet::create([
            'zona_id' => $this->zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $p = EstadoZona::progresoDe(collect([$this->zona]))[$this->zona->id];

        $this->assertSame(1, $p['hechas']);
        $this->assertSame(1, $p['borradores']);
        $this->assertSame(8, $p['sin_empezar']);
        $this->assertSame(10, $p['total']);
        $this->assertSame(
            $p['total'],
            $p['hechas'] + $p['borradores'] + $p['sin_empezar'],
            'El desglose tiene que repartir el total, no aproximarlo.'
        );
    }

    /**
     * Una zona sin ninguna evaluación sale entera en «sin empezar», y con
     * las cuatro claves puestas: quien la consume las lee sin comprobar si
     * existen.
     */
    public function test_una_zona_sin_evaluaciones_sale_entera_en_sin_empezar(): void
    {
        $p = EstadoZona::progresoDe(collect([$this->zona]))[$this->zona->id];

        $this->assertSame(0, $p['hechas']);
        $this->assertSame(0, $p['borradores']);
        $this->assertSame(10, $p['sin_empezar']);
        $this->assertSame(10, $p['total']);
    }

    /**
     * El borrador de una zona no aparece en el desglose de la otra.
     *
     * Es lo que rompería una consulta que agrupara mal —y no lo notaría
     * ningún test de los de arriba: las cifras seguirían sumando el total en
     * las dos zonas, solo que en la que no toca.
     */
    public function test_el_desglose_no_mezcla_zonas(): void
    {
        $otra = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona sin tocar',
        ]);

        EvaluacionFit::create([
            'zona_id' => $this->zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $progreso = EstadoZona::progresoDe(collect([$this->zona, $otra]));

        $this->assertSame(1, $progreso[$this->zona->id]['borradores']);
        $this->assertSame(0, $progreso[$otra->id]['borradores']);
        $this->assertSame(10, $progreso[$otra->id]['sin_empezar']);
    }
```

- [ ] **Paso 3: correrlos y verlos fallar**

```bash
php artisan test --filter="desglosa_validadas|sin_evaluaciones_sale_entera|no_mezcla_zonas"
```

Esperado: FALLAN los tres con `Undefined array key "borradores"`.

- [ ] **Paso 4: implementar el desglose**

Sustituye el método completo `progresoDe()` en `app/Servicios/EstadoZona.php`
(hoy en las líneas 110-145) por esto:

```php
    /**
     * Progreso de varias zonas con un número fijo de consultas.
     *
     * El dashboard solo necesita el recuento, no las filas resueltas. Instanciar
     * un EstadoZona por zona costaba seis consultas por zona; esto son diez en
     * total —una por matriz validable—, haya una zona o cincuenta.
     *
     * Devuelve el reparto y no solo el numerador: un «3 / 10» mete en el mismo
     * saco las siete que nadie ha abierto y las siete en borrador esperando
     * validación, que piden cosas distintas.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Zona>  $zonas
     * @return array<int, array{hechas: int, borradores: int, sin_empezar: int, total: int}>  indexado por zona_id
     */
    public static function progresoDe(Collection $zonas): array
    {
        $ids   = $zonas->pluck('id');
        $total = count(Registro::matrices());

        // Arrancan en 0 para que toda zona pedida aparezca en el resultado,
        // incluidas las que no tengan ninguna evaluación todavía.
        $hechasPorZona     = $ids->mapWithKeys(fn(int $id) => [$id => 0])->all();
        $borradoresPorZona = $ids->mapWithKeys(fn(int $id) => [$id => 0])->all();

        foreach (Registro::matrices() as $entrada) {
            $modelo = $entrada['modelo'];

            // Pedir estado además de zona_id no añade una consulta: es la
            // misma de antes trayendo una columna más. `zona_id` es único en
            // las diez tablas -lo garantiza la migración
            // 2026_08_06_000001_add_unique_zona_id_to_evaluaciones-, así que
            // indexar por él no pierde filas.
            $estados = $modelo::whereIn('zona_id', $ids)->pluck('estado', 'zona_id');

            foreach ($estados as $zonaId => $estado) {
                if ($estado === 'confirmado') {
                    $hechasPorZona[$zonaId]++;
                } else {
                    $borradoresPorZona[$zonaId]++;
                }
            }
        }

        return $ids->mapWithKeys(fn(int $id) => [$id => [
            'hechas'     => $hechasPorZona[$id],
            'borradores' => $borradoresPorZona[$id],
            // Sin fila no hay estado: lo que no está validado ni en borrador
            // es lo que nadie ha abierto. Se deriva en vez de preguntarlo,
            // que serían diez consultas más para contar ausencias.
            'sin_empezar' => $total - $hechasPorZona[$id] - $borradoresPorZona[$id],
            'total'       => $total,
        ]])->all();
    }
```

- [ ] **Paso 5: correr los tests y verlos pasar**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: PASAN todos los de la clase, los nuevos y los de antes.

- [ ] **Paso 6: ampliar el test de consultas, no duplicarlo**

`DashboardTest::test_el_numero_de_consultas_no_crece_con_el_numero_de_zonas`
mide hoy sobre zonas **vacías**: la consulta de `progresoDe()` volvía sin filas
y el bucle que cuenta el desglose no se ejecutaba nunca bajo el contador. Se
amplía para que las zonas lleven datos.

**Las dos mediciones tienen que llevar la misma forma de datos**, no solo las
cuatro zonas extra: con la zona única vacía y las extra con evaluaciones,
`proximoPaso()` encontraría «última tocada» solo en la segunda medición e
instanciaría un `EstadoZona` de más —coste fijo, pero ausente en la primera—, y
el test fallaría por una diferencia que no es un N+1.

En `tests/Feature/DashboardTest.php`, añade el helper después de `crearZona()`:

```php
    /**
     * Una zona con una matriz validada y otra en borrador.
     *
     * El desglose de progresoDe() tiene dos ramas -confirmado y todo lo
     * demás- y sobre una zona vacía no se recorre ninguna. Las mediciones de
     * coste que usan este helper lo hacen para contar consultas de verdad,
     * no de un camino que no se pisa.
     */
    private function crearZonaConProgreso(string $nombre): Zona
    {
        $zona = $this->crearZona($nombre);

        \App\Models\EvaluacionFit::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);
        \App\Models\EvaluacionFet::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        return $zona;
    }
```

Y en el cuerpo del test cambia las dos altas de zona:

```php
        $this->crearZonaConProgreso('Zona única');
```

```php
        for ($i = 1; $i <= 4; $i++) {
            $this->crearZonaConProgreso("Zona extra {$i}");
        }
```

Añade además esta frase al docblock del test, después del párrafo que ya
explica el conteo:

```
     * Las cinco zonas llevan datos, y las dos mediciones la misma forma: una
     * zona vacía y otra con evaluaciones no recorren los mismos caminos
     * -proximoPaso() instancia un EstadoZona más cuando encuentra "última
     * tocada"-, así que comparar una contra otras cuatro mediría esa
     * diferencia y no el N+1 que este test vigila.
```

- [ ] **Paso 7: correr la suite entera**

```bash
php artisan test
```

Esperado: 611 tests en verde (608 + 3). Ninguno roto: `admin/zonas/index` y
`ConmutadorVistaTest` siguen leyendo `hechas` y `total`, que no han cambiado.

- [ ] **Paso 8: commit**

```bash
git add app/Servicios/EstadoZona.php tests/Unit/EstadoZonaTest.php tests/Feature/DashboardTest.php .superpowers/sdd/progress.md
git commit -m "feat(progreso): el desglose por estado, no solo el numerador

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 2: la ordenación, en el servidor

**Ficheros:**
- Modificar: `app/Http/Controllers/Operativo/DashboardController.php` (entero)
- Modificar: `resources/views/operativo/dashboard.blade.php:57` y `:93` (solo los `id`)
- Crear: `tests/Feature/OrdenMisZonasTest.php`

**Interfaces:**
- Consume: `EstadoZona::progresoDe()` con sus cuatro claves (T1).
- Produce: la vista recibe dos variables nuevas, `$orden` (uno de `nombre`,
  `lugar`, `progreso`) y `$dir` (`asc` o `desc`), **siempre válidas** —el
  controlador ya las ha normalizado—. La T5 las usa en las cabeceras. Y los dos
  contenedores de maquetación llevan `id="zonas-lista"` e `id="zonas-tarjetas"`.

- [ ] **Paso 1: poner los dos `id` que hacen medible el orden**

Sin un ancla estable, un test de posiciones tendría que partir el HTML por la
expresión de Alpine `vista === 'lista'`, que el conmutador también emite en sus
botones más arriba. Dos `id` cuestan nada y dicen qué es cada bloque.

En `resources/views/operativo/dashboard.blade.php`, línea 57:

```blade
            <x-tarjeta :padding="false" id="zonas-lista" x-show="vista === 'lista'" x-transition
                 class="divide-y divide-gray-200">
```

Y línea 93:

```blade
            <div id="zonas-tarjetas" x-show="vista === 'tarjetas'" x-transition
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
```

- [ ] **Paso 2: escribir el test que falla**

Crea `tests/Feature/OrdenMisZonasTest.php` con este contenido completo:

```php
<?php

namespace Tests\Feature;

use App\Models\EvaluacionFit;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El orden de «Mis Zonas», que vive en el servidor y viaja en la URL.
 *
 * Se afirma sobre posiciones relativas dentro del HTML servido, no sobre el
 * orden de ninguna colección: lo que el usuario ve es la página, y una
 * colección perfectamente ordenada que la vista recorriera al revés pasaría
 * un test de colección sin despeinarse.
 *
 * Que la ordenación sea de servidor es lo que hace posible este fichero:
 * Playwright no está instalado en esta máquina, así que con Alpine no habría
 * ningún test que pudiera ver esta funcionalidad.
 */
class OrdenMisZonasTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);
    }

    private function crearZona(string $nombre, ?int $lugarId = null): Zona
    {
        return Zona::create([
            'lugar_id'     => $lugarId ?? DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => $nombre,
        ]);
    }

    /** El seeder trae un solo lugar, y ordenar por lugar necesita dos. */
    private function crearLugar(string $nombre): int
    {
        return DB::table('lugares')->insertGetId([
            'provincia_id' => DB::table('provincias')->value('id'),
            'nombre'       => $nombre,
        ]);
    }

    private function html(string $url = '/mis-zonas'): string
    {
        return $this->actingAs($this->jefe)->get($url)->assertOk()->getContent();
    }

    /**
     * El trozo de HTML de una maquetación, aislado por su id.
     *
     * Aislar no es cosmética: el panel de «siguiente paso» de arriba también
     * lleva el nombre de una zona, y sin recortar falsearía cualquier
     * comparación de posiciones.
     */
    private function maquetacion(string $html, string $id): string
    {
        $inicio = strpos($html, 'id="' . $id . '"');
        $this->assertNotFalse($inicio, "No se encontró la maquetación «{$id}».");

        $fin = $id === 'zonas-lista'
            ? strpos($html, 'id="zonas-tarjetas"')
            : strlen($html);

        return substr($html, $inicio, $fin - $inicio);
    }

    /** Todo lo que va por encima de las dos maquetaciones: el panel y la franja. */
    private function cabecera(string $html): string
    {
        return substr($html, 0, (int) strpos($html, 'id="zonas-lista"'));
    }

    /** @param  list<string>  $nombres  en el orden en que deberían salir */
    private function assertOrden(array $nombres, string $trozo, string $mensaje): void
    {
        $posiciones = [];

        foreach ($nombres as $nombre) {
            $pos = strpos($trozo, $nombre);
            $this->assertNotFalse($pos, "«{$nombre}» no aparece. {$mensaje}");
            $posiciones[] = $pos;
        }

        $esperadas = $posiciones;
        sort($esperadas);

        $this->assertSame($esperadas, $posiciones, $mensaje);
    }

    /** Tres zonas creadas al revés, para que el id no coincida con el alfabeto. */
    private function tresZonas(): void
    {
        $this->crearZona('Charlie');
        $this->crearZona('Bravo');
        $this->crearZona('Alfa');
    }

    public function test_por_defecto_las_zonas_salen_por_nombre_ascendente(): void
    {
        $this->tresZonas();

        $lista = $this->maquetacion($this->html(), 'zonas-lista');

        $this->assertOrden(
            ['Alfa', 'Bravo', 'Charlie'],
            $lista,
            'Sin parámetros, el orden es nombre ascendente y no el id de la base.'
        );
    }

    public function test_dir_desc_invierte_el_orden(): void
    {
        $this->tresZonas();

        $lista = $this->maquetacion($this->html('/mis-zonas?orden=nombre&dir=desc'), 'zonas-lista');

        $this->assertOrden(['Charlie', 'Bravo', 'Alfa'], $lista, 'dir=desc invierte.');
    }

    public function test_se_puede_ordenar_por_lugar(): void
    {
        $zamora = $this->crearLugar('Zamora');
        $azogues = $this->crearLugar('Azogues');

        $this->crearZona('Alfa', $zamora);
        $this->crearZona('Bravo', $azogues);

        $lista = $this->maquetacion($this->html('/mis-zonas?orden=lugar&dir=asc'), 'zonas-lista');

        $this->assertOrden(
            ['Bravo', 'Alfa'],
            $lista,
            'Por lugar, Azogues va antes que Zamora aunque su zona se llame Bravo.'
        );
    }

    public function test_por_progreso_descendente_va_primero_la_mas_avanzada(): void
    {
        $this->crearZona('Alfa');
        $avanzada = $this->crearZona('Bravo');

        EvaluacionFit::create([
            'zona_id' => $avanzada->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);

        $lista = $this->maquetacion($this->html('/mis-zonas?orden=progreso&dir=desc'), 'zonas-lista');

        $this->assertOrden(
            ['Bravo', 'Alfa'],
            $lista,
            'Con una matriz validada, Bravo va por delante pese a ir después en el alfabeto.'
        );
    }

    /**
     * Un `orden` que no está en la lista blanca no rompe la portada de la
     * aplicación: responde 200 y cae al orden por defecto, en silencio. Un
     * enlace viejo compartido no debería enseñar una pantalla de error.
     */
    public function test_un_orden_desconocido_cae_al_de_por_defecto_con_200(): void
    {
        $this->tresZonas();

        $lista = $this->maquetacion(
            $this->html('/mis-zonas?orden=loquesea&dir=arriba'),
            'zonas-lista'
        );

        $this->assertOrden(['Alfa', 'Bravo', 'Charlie'], $lista, 'Cae al orden por defecto.');
    }

    /** Y tampoco lo rompe un parámetro que ni siquiera es una cadena. */
    public function test_un_orden_que_es_un_array_no_rompe_la_pagina(): void
    {
        $this->tresZonas();

        $this->actingAs($this->jefe)->get('/mis-zonas?orden[]=nombre')->assertOk();
    }

    /**
     * Es la misma colección la que se ordena, así que las dos maquetaciones
     * salen igual. Sin test, eso es una casualidad de la implementación de
     * hoy.
     */
    public function test_las_dos_maquetaciones_se_ordenan_igual(): void
    {
        $this->tresZonas();

        $html = $this->html('/mis-zonas?orden=nombre&dir=desc');

        $this->assertOrden(['Charlie', 'Bravo', 'Alfa'], $this->maquetacion($html, 'zonas-lista'), 'La lista.');
        $this->assertOrden(['Charlie', 'Bravo', 'Alfa'], $this->maquetacion($html, 'zonas-tarjetas'), 'Las tarjetas.');
    }

    /**
     * El panel de «siguiente paso» no obedece al orden de la tabla.
     *
     * proximoPaso() recorre las zonas en el orden que recibe y se detiene en
     * la primera con algo pendiente. Pasarle la colección ya ordenada por la
     * URL haría que pulsar una cabecera moviera la recomendación de arriba,
     * que no es una fila de la lista sino un consejo.
     */
    public function test_el_panel_de_siguiente_paso_no_cambia_al_reordenar(): void
    {
        $this->crearZona('Bravo');
        $this->crearZona('Alfa');

        $cabecera = $this->cabecera($this->html('/mis-zonas?orden=nombre&dir=desc'));

        $this->assertStringContainsString('Alfa', $cabecera);
        $this->assertStringNotContainsString(
            'Bravo',
            $cabecera,
            'El panel señala siempre a la misma zona, ordene la tabla como ordene.'
        );
    }
}
```

- [ ] **Paso 3: correrlo y verlo fallar**

```bash
php artisan test --filter=OrdenMisZonasTest
```

Esperado: FALLAN casi todos. El primero, con las zonas en orden de id
(`Charlie, Bravo, Alfa`), porque hoy no hay ningún orden.

- [ ] **Paso 4: implementar el orden en el controlador**

Sustituye `app/Http/Controllers/Operativo/DashboardController.php` entero:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Los órdenes que la tabla ofrece. Es la lista blanca: lo que no esté
     * aquí cae al de por defecto.
     */
    private const ORDENES = ['nombre', 'lugar', 'progreso'];

    private const DIRECCIONES = ['asc', 'desc'];

    private const ORDEN_POR_DEFECTO = 'nombre';

    private const DIRECCION_POR_DEFECTO = 'asc';

    public function index(Request $request)
    {
        $user = Auth::user();

        // El admin no usa este dashboard — tiene el suyo propio
        if ($user->esAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $zonas = match (true) {
            $user->esJefe()   => $user->zonasComoJefe()->with('lugar')->get(),
            $user->esEquipo() => $user->zonasComoEquipo()->with('lugar')->get(),
            default           => collect(),
        };

        // Antes esto eran cuatro consultas manuales que había que ampliar con
        // cada matriz nueva; una de ellas se olvidó y Paisaje quedó fuera.
        //
        // progresoDe() resuelve todas las zonas con un número fijo de
        // consultas (una por matriz). Instanciar un EstadoZona por zona aquí
        // costaba seis consultas por zona — correcto pero no escalaba con la
        // lista de zonas del jefe.
        $progreso = EstadoZona::progresoDe($zonas);

        // proximoPaso() recibe $progreso ya calculado -pedirlo dos veces
        // duplicaría las consultas de progresoDe() sin ganar nada- y la
        // colección en el orden POR DEFECTO, no en el que pida la URL:
        // recorre las zonas en el orden que recibe y se detiene en la primera
        // con algo pendiente, así que con el orden de la tabla la
        // recomendación de arriba saltaría de zona cada vez que alguien pulsa
        // una cabecera. El panel es un consejo, no una fila de la lista.
        $proximoPaso = EstadoZona::proximoPaso(
            $user,
            $this->ordenar($zonas, $progreso, self::ORDEN_POR_DEFECTO, self::DIRECCION_POR_DEFECTO),
            $progreso
        );

        [$orden, $dir] = $this->ordenPedido($request);

        $zonas = $this->ordenar($zonas, $progreso, $orden, $dir);

        return view('operativo.dashboard', compact('zonas', 'progreso', 'proximoPaso', 'orden', 'dir'));
    }

    /**
     * El orden que pide la URL, o el de por defecto.
     *
     * Lista blanca y caída en silencio, con 200: un `orden` viejo en un
     * enlace compartido, o un `dir=arriba` escrito a mano, no deberían
     * enseñar una pantalla de error en la portada de la aplicación. Y
     * `query()` puede devolver un array -`?orden[]=x`-, que in_array
     * descarta sin reventar.
     *
     * @return array{0: string, 1: string}
     */
    private function ordenPedido(Request $request): array
    {
        $orden = $request->query('orden');
        $dir   = $request->query('dir');

        return [
            in_array($orden, self::ORDENES, true) ? $orden : self::ORDEN_POR_DEFECTO,
            in_array($dir, self::DIRECCIONES, true) ? $dir : self::DIRECCION_POR_DEFECTO,
        ];
    }

    /**
     * Ordena la colección en PHP, no con orderBy en SQL.
     *
     * El progreso no está en ninguna columna: se calcula a partir de las diez
     * matrices. Escribirlo en SQL sería tenerlo en dos idiomas, y son las
     * zonas de un operativo -unas pocas-, así que ordenar en memoria no
     * cuesta nada. Si algún día alguien acumulara cientos, el sitio donde
     * arreglarlo es este mismo método.
     *
     * @param  Collection<int, Zona>  $zonas
     * @param  array<int, array{hechas: int, total: int}>  $progreso
     * @return Collection<int, Zona>
     */
    private function ordenar(Collection $zonas, array $progreso, string $orden, string $dir): Collection
    {
        $clave = match ($orden) {
            'lugar'    => fn(Zona $zona) => $zona->lugar?->nombre ?? '',
            'progreso' => fn(Zona $zona) => $progreso[$zona->id]['total'] > 0
                ? $progreso[$zona->id]['hechas'] / $progreso[$zona->id]['total']
                : 0,
            default    => fn(Zona $zona) => $zona->nombre,
        };

        // Los nombres, en orden natural y sin distinguir mayúsculas: «Zona 10»
        // va después de «Zona 9», y «playa» no se cuela detrás de «Zona» por
        // empezar en minúscula. El progreso es un número y no quiere flags.
        $ordenadas = $orden === 'progreso'
            ? $zonas->sortBy($clave)
            : $zonas->sortBy($clave, SORT_NATURAL | SORT_FLAG_CASE);

        return ($dir === 'desc' ? $ordenadas->reverse() : $ordenadas)->values();
    }
}
```

- [ ] **Paso 5: correr los tests y verlos pasar**

```bash
php artisan test --filter=OrdenMisZonasTest
```

Esperado: PASAN los nueve.

- [ ] **Paso 6: correr la suite entera**

```bash
php artisan test
```

Esperado: 620 tests en verde (611 + 9). `DashboardTest` y `ConmutadorVistaTest`
siguen pasando: solo cambia el orden de unas zonas que en esos tests son una.

- [ ] **Paso 7: commit**

```bash
git add app/Http/Controllers/Operativo/DashboardController.php resources/views/operativo/dashboard.blade.php tests/Feature/OrdenMisZonasTest.php
git commit -m "feat(mis-zonas): el orden se pide por URL y lo resuelve el servidor

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 3: la franja de cifras de conjunto

**Ficheros:**
- Modificar: `app/Http/Controllers/Operativo/DashboardController.php` (método `index`)
- Modificar: `resources/views/operativo/dashboard.blade.php` (bloque nuevo tras el panel de siguiente paso)
- Modificar: `tests/Feature/DashboardTest.php` (tests nuevos)

**Interfaces:**
- Consume: `$progreso` con `hechas` y `total` (T1); el `$zonas` ya ordenado (T2).
- Produce: la vista recibe `$resumen` con las claves `zonas`, `validadas`,
  `matrices` y `terminadas`, todas `int`. El contenedor de la franja lleva
  `id="zonas-kpis"`.

- [ ] **Paso 1: escribir los tests que fallan**

Añade estos tres a `tests/Feature/DashboardTest.php`:

```php
    /** El trozo de la franja de cifras, aislado por su id. */
    private function franja(string $html): string
    {
        $inicio = strpos($html, 'id="zonas-kpis"');
        $this->assertNotFalse($inicio, 'No se encontró la franja de cifras.');

        return substr($html, $inicio, (int) strpos($html, 'id="zonas-lista"') - $inicio);
    }

    /**
     * Con dos zonas, la franja suma: las matrices validadas de las dos sobre
     * el total de las dos. Es la cifra que hoy obliga a sumar barras a ojo.
     */
    public function test_la_franja_suma_las_cifras_de_todas_las_zonas(): void
    {
        $primera = $this->crearZona('Zona primera');
        $segunda = $this->crearZona('Zona segunda');

        \App\Models\EvaluacionFit::create([
            'zona_id' => $primera->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);
        \App\Models\EvaluacionFet::create([
            'zona_id' => $primera->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);
        \App\Models\EvaluacionFit::create([
            'zona_id' => $segunda->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado',
        ]);

        $franja = $this->franja($this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent());

        $this->assertStringContainsString('Zonas asignadas', $franja);
        $this->assertStringContainsString('>2</p>', $franja);
        $this->assertStringContainsString('Matrices validadas', $franja);
        $this->assertStringContainsString('>3</p>', $franja);
        $this->assertStringContainsString('de 20 en total', $franja);
    }

    /**
     * Con una sola zona no hay franja: repetiría lo que su propia tarjeta ya
     * dice, y ocupando el sitio de lo accionable.
     */
    public function test_con_una_sola_zona_no_se_pinta_la_franja(): void
    {
        $this->crearZona('Zona única');

        $this->actingAs($this->jefe)
            ->get('/mis-zonas')
            ->assertOk()
            ->assertDontSee('id="zonas-kpis"', false)
            ->assertDontSee('Zonas asignadas');
    }

    /**
     * «Terminada» es la zona cuyas diez matrices están validadas, y se cuenta
     * sobre el desglose, no sobre una insignia inventada en la tarjeta.
     */
    public function test_la_franja_cuenta_las_zonas_terminadas(): void
    {
        $terminada = $this->crearZona('Zona terminada');
        $this->crearZona('Zona a medias');

        // Mismo patrón que EstadoZonaTest::test_con_todo_validado_no_hay_siguiente,
        // y por el mismo motivo: las columnas de criterio salen del esquema
        // para no repetir aquí una lista de campos que se desincronizaría, y
        // rellenarlas evita chocar con cualquier NOT NULL de las diez tablas.
        foreach (\App\Matrices\Registro::matrices() as $entrada) {
            $modelo = $entrada['modelo'];

            $columnas = array_filter(
                \Illuminate\Support\Facades\Schema::getColumnListing((new $modelo())->getTable()),
                fn(string $columna) => \App\Servicios\EstadoZona::esColumnaDeCriterio($columna)
            );

            $modelo::create(
                ['zona_id' => $terminada->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado']
                + array_fill_keys($columnas, 3)
            );
        }

        $franja = $this->franja($this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent());

        $this->assertStringContainsString('Zonas terminadas', $franja);
        $this->assertStringContainsString('>1</p>', $franja);
    }
```

- [ ] **Paso 2: correrlos y verlos fallar**

```bash
php artisan test --filter="la_franja_suma|una_sola_zona_no_se_pinta_la_franja|franja_cuenta_las_zonas_terminadas"
```

Esperado: FALLAN el primero y el tercero con «No se encontró la franja de
cifras». El segundo PASA ya —todavía no hay franja que pintar—: es la
contraparte, y su valor está en que siga pasando después.

- [ ] **Paso 3: calcular el resumen en el controlador**

Las cifras no se calculan en un `@php` de la vista: es lo mismo que el panel de
admin ya hace con su `$resumen`, y así se pueden afirmar sin pasar por el HTML
si algún día hace falta.

En `DashboardController::index()`, justo después de la línea de `$progreso`,
añade:

```php
        // Las cifras de conjunto, calculadas aquí y no en un @php de la vista:
        // el panel de admin ya recibe su $resumen así, y las dos portadas del
        // sistema se parecen también por dentro.
        $resumen = [
            'zonas'      => $zonas->count(),
            'validadas'  => array_sum(array_column($progreso, 'hechas')),
            'matrices'   => array_sum(array_column($progreso, 'total')),
            'terminadas' => count(array_filter(
                $progreso,
                fn(array $p) => $p['total'] > 0 && $p['hechas'] === $p['total']
            )),
        ];
```

Y añade `'resumen'` al `compact` del `return`:

```php
        return view('operativo.dashboard', compact('zonas', 'progreso', 'proximoPaso', 'resumen', 'orden', 'dir'));
```

- [ ] **Paso 4: pintar la franja**

En `resources/views/operativo/dashboard.blade.php`, entre el `@endif` del panel
de siguiente paso y el `<div class="flex justify-end mb-4">` del conmutador:

```blade
            {{-- ═══ CIFRAS DE CONJUNTO ═══════════════════════════════════════════
                 Debajo de lo accionable, no encima: el siguiente paso sigue
                 siendo lo primero que se lee. Y solo con dos o más zonas —con
                 una, la franja repetiría lo que su propia tarjeta ya dice—.

                 Misma rejilla que el panel de administración (grid
                 md:grid-cols-3 sobre <x-tarjeta>): las dos portadas del
                 sistema quedan con la misma forma sin inventar un primitivo
                 nuevo. --}}
            @if($resumen['zonas'] >= 2)
            <div id="zonas-kpis" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['zonas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Zonas asignadas</h3>
                </x-tarjeta>

                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['validadas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Matrices validadas</h3>
                    <p class="text-sm text-gray-600 mt-2">de {{ $resumen['matrices'] }} en total</p>
                </x-tarjeta>

                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['terminadas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Zonas terminadas</h3>
                    <p class="text-sm text-gray-600 mt-2">de {{ $resumen['zonas'] }} asignadas</p>
                </x-tarjeta>
            </div>
            @endif
```

- [ ] **Paso 5: correr los tests y verlos pasar**

```bash
php artisan test --filter=DashboardTest
```

Esperado: PASAN los ocho de la clase.

- [ ] **Paso 6: correr la suite entera**

```bash
php artisan test
```

Esperado: 623 tests en verde (620 + 3).

- [ ] **Paso 7: commit**

```bash
git add app/Http/Controllers/Operativo/DashboardController.php resources/views/operativo/dashboard.blade.php tests/Feature/DashboardTest.php
git commit -m "feat(mis-zonas): las cifras de conjunto, que antes había que sumar a ojo

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 4: el desglose por estado, en las dos maquetaciones

**Ficheros:**
- Crear: `resources/views/components/desglose-estados.blade.php`
- Crear: `tests/Feature/DesgloseEstadosTest.php`
- Modificar: `resources/views/operativo/dashboard.blade.php` (fila de la lista y tarjeta)
- Modificar: `tests/Feature/ConmutadorVistaTest.php:77-104`

**Interfaces:**
- Consume: `$progreso[$zona->id]` con `hechas`, `borradores` y `sin_empezar` (T1);
  `<x-badge :estado="...">` con su ranura, que ya existe.
- Produce: `<x-desglose-estados :progreso="$p" />`, que pinta entre una y tres
  insignias con los textos `«N validadas»`, `«N en borrador»` y `«N sin
  empezar»`, en ese orden. La T5 lo reutiliza tal cual en la columna Estado.

- [ ] **Paso 1: escribir el test del componente**

Crea `tests/Feature/DesgloseEstadosTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * El contrato de <x-desglose-estados>, fijado sobre el componente y no sobre
 * el dashboard que lo usa.
 *
 * No necesita base de datos: recibe un array de cuatro cifras, que es
 * exactamente lo que devuelve EstadoZona::progresoDe() por zona.
 */
class DesgloseEstadosTest extends TestCase
{
    private function render(int $hechas, int $borradores, int $sinEmpezar): string
    {
        return (string) $this->blade(
            '<x-desglose-estados :progreso="$progreso" />',
            ['progreso' => [
                'hechas'      => $hechas,
                'borradores'  => $borradores,
                'sin_empezar' => $sinEmpezar,
                'total'       => $hechas + $borradores + $sinEmpezar,
            ]]
        );
    }

    public function test_pinta_los_tres_estados_con_su_numero(): void
    {
        $html = $this->render(3, 1, 6);

        $this->assertStringContainsString('3 validadas', $html);
        $this->assertStringContainsString('1 en borrador', $html);
        $this->assertStringContainsString('6 sin empezar', $html);
    }

    /**
     * Un estado a cero no se pinta: «0 en borrador» ocupa sitio para no decir
     * nada.
     */
    public function test_un_estado_a_cero_no_se_pinta(): void
    {
        $html = $this->render(10, 0, 0);

        $this->assertStringContainsString('10 validadas', $html);
        $this->assertStringNotContainsString('en borrador', $html);
        $this->assertStringNotContainsString('sin empezar', $html);
    }

    /**
     * El orden es fijo —validadas, borrador, sin empezar— y no depende de
     * cuál tenga más. Las tres suman el total, así que leerlas siempre en el
     * mismo sitio es lo que las convierte en un reparto y no en tres cifras
     * sueltas.
     */
    public function test_el_orden_de_las_insignias_es_fijo(): void
    {
        $html = $this->render(1, 2, 7);

        $this->assertLessThan(strpos($html, '2 en borrador'), strpos($html, '1 validadas'));
        $this->assertLessThan(strpos($html, '7 sin empezar'), strpos($html, '2 en borrador'));
    }

    /**
     * Los colores salen de <x-badge>, que los lee de
     * EstadoZona::ESTILOS_ESTADO. Aquí no hay ni un color escrito a mano, y
     * este test es lo que lo sostiene: cada insignia lleva el color de SU
     * estado, que es lo que hace honesto no tener una insignia de «zona
     * terminada».
     */
    public function test_cada_insignia_lleva_el_color_de_su_estado(): void
    {
        $html = $this->render(1, 1, 8);

        $estilos = \App\Servicios\EstadoZona::ESTILOS_ESTADO;

        $this->assertStringContainsString($estilos['validada']['insignia'], $html);
        $this->assertStringContainsString($estilos['borrador']['insignia'], $html);
        $this->assertStringContainsString($estilos['sin_empezar']['insignia'], $html);
    }
}
```

- [ ] **Paso 2: correrlo y verlo fallar**

```bash
php artisan test --filter=DesgloseEstadosTest
```

Esperado: FALLAN los cuatro con «Unable to locate a class or view for component
[desglose-estados]».

- [ ] **Paso 3: escribir el componente**

Crea `resources/views/components/desglose-estados.blade.php`:

```blade
@props(['progreso'])

{{--
    Cuántas matrices de una zona hay en cada estado.

    Sustituye al «3 / 10», que metía en el mismo saco lo que nadie ha abierto
    y lo que espera validación: un 3/10 con siete borradores completos y uno
    con siete sin abrir piden cosas distintas y se leían igual.

    Es <x-badge> y su ranura, que existe justo para esto: conserva el color
    del estado y cambia el texto. NO hay una insignia de «zona terminada» a
    propósito —los colores de ESTILOS_ESTADO significan el estado de una
    MATRIZ, y dárselos a una ZONA es el error que este proyecto ya pagó una
    vez con <x-insignia-clasificacion>, cuyo nombre genérico invitaba a pintar
    de verde el peor resultado—. Cada insignia de aquí cuenta matrices de ese
    estado, que es lo que su color dice.

    Solo tres estados, y no los cinco del mapa: el denominador es
    Registro::matrices(), las diez validables. 'bloqueada' y 'sin_estado'
    pertenecen a las dos entradas que no cuentan —vtt, que es derivada, e
    inventario, que no tiene estado—, así que aquí no pueden aparecer.

    Orden fijo, no por cuál tenga más: las tres suman el total, y leerlas
    siempre en el mismo sitio es lo que las hace un reparto en vez de tres
    cifras sueltas.
--}}

@php
    $tramos = [
        ['estado' => 'validada',    'cuantas' => $progreso['hechas'],      'etiqueta' => 'validadas'],
        ['estado' => 'borrador',    'cuantas' => $progreso['borradores'],  'etiqueta' => 'en borrador'],
        ['estado' => 'sin_empezar', 'cuantas' => $progreso['sin_empezar'], 'etiqueta' => 'sin empezar'],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-1.5']) }}>
    @foreach($tramos as $tramo)
        {{-- Un estado a cero no se pinta: «0 en borrador» ocupa sitio para no
             decir nada. --}}
        @if($tramo['cuantas'] > 0)
            <x-badge :estado="$tramo['estado']">
                {{ $tramo['cuantas'] }} {{ $tramo['etiqueta'] }}
            </x-badge>
        @endif
    @endforeach
</div>
```

- [ ] **Paso 4: correr el test del componente**

```bash
php artisan test --filter=DesgloseEstadosTest
```

Esperado: PASAN los cuatro.

Si `3 validadas` no se encuentra: la ranura está en varias líneas y Blade
conserva los saltos. En ese caso pon el contenido de `<x-badge>` en una sola
línea —`<x-badge :estado="$tramo['estado']">{{ $tramo['cuantas'] }} {{ $tramo['etiqueta'] }}</x-badge>`—
y no relajes el test: el HTML debe llevar el texto tal como se lee.

- [ ] **Paso 5: usarlo en las dos maquetaciones**

En `resources/views/operativo/dashboard.blade.php`, en la **fila de la lista**,
sustituye el `<div class="w-40 shrink-0">` con la barra y el `{{ $p['hechas'] }} / {{ $p['total'] }}`
por (los números de línea de este fichero ya han bailado con las tareas 2 y 3,
así que localiza los bloques por su contenido):

```blade
                        <div class="w-56 shrink-0">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                            </div>
                            <x-desglose-estados :progreso="$p" class="mt-2" />
                        </div>
```

Y en la **tarjeta**, sustituye el `<div class="flex items-center gap-3 mt-5">`
con su barra y su `<span>` de fracción por:

```blade
                        @php $p = $progreso[$zona->id]; @endphp
                        <div class="mt-5">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                            </div>
                            <x-desglose-estados :progreso="$p" class="mt-3" />
                        </div>
```

- [ ] **Paso 6: actualizar `ConmutadorVistaTest`, no relajarlo**

En `tests/Feature/ConmutadorVistaTest.php`, dentro de
`test_las_dos_maquetaciones_de_mis_zonas_llevan_los_mismos_datos`, sustituye
las líneas del progreso (hoy 88-91 y el assert de la 103) por:

```php
        // El «3 / 10» dejó de ser el dato que llevan las dos maquetaciones:
        // ahora es el desglose por estado, que distingue lo que nadie ha
        // abierto de lo que espera validación. El test no se relaja -sigue
        // exigiendo el mismo dato en las dos-, se actualiza a lo que el dato
        // pasa a ser.
        //
        // Se compara sobre la insignia que una zona recién creada tiene
        // seguro -las diez sin empezar-, no sobre las tres: las otras dos no
        // se pintan cuando están a cero, y afirmar sobre algo que esta zona
        // no tiene mediría el <x-desglose-estados> de otro caso.
        $p = EstadoZona::progresoDe(collect([$this->zona]))[$this->zona->id];
        $desglose = "{$p['sin_empezar']} sin empezar";
```

```php
        $this->assertSame(2, substr_count($html, $desglose), 'El desglose debe aparecer una vez por maquetación.');
```

- [ ] **Paso 7: correr los tests tocados**

```bash
php artisan test --filter="DesgloseEstadosTest|ConmutadorVistaTest"
```

Esperado: PASAN los nueve.

- [ ] **Paso 8: construir los assets y correr la suite entera**

Las clases de `<x-badge>` (`bg-green-100`, `bg-amber-100`, `bg-gray-100`…) ya
estaban en el fuente porque el componente existía; las de este componente
(`gap-1.5`, `w-56`) puede que no.

```bash
npm run build
php artisan test
```

Esperado: 627 tests en verde (623 + 4). Si `package-lock.json` aparece
modificado: `git checkout -- package-lock.json`.

- [ ] **Paso 9: commit**

```bash
git add resources/views/components/desglose-estados.blade.php resources/views/operativo/dashboard.blade.php tests/Feature/DesgloseEstadosTest.php tests/Feature/ConmutadorVistaTest.php public/build
git commit -m "feat(mis-zonas): el estado de una zona es un desglose, no una fraccion

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 5: la vista lista pasa a ser una tabla ordenable

**Ficheros:**
- Crear: `resources/views/components/cabecera-ordenable.blade.php`
- Modificar: `resources/views/operativo/dashboard.blade.php` (bloque de la lista entero)
- Modificar: `tests/Feature/OrdenMisZonasTest.php` (tests de cabeceras)
- Modificar: `tests/Feature/ConmutadorVistaTest.php:101` (el lugar deja de llevar 📍)

**Interfaces:**
- Consume: `$orden` y `$dir` de la vista (T2); `<x-desglose-estados>` (T4).
- Produce: `<x-cabecera-ordenable columna="nombre" :orden="$orden" :dir="$dir">Zona</x-cabecera-ordenable>`,
  que pinta un `<th scope="col">` con un enlace a `?orden=…&dir=…` y, si es la
  columna activa, `aria-sort="ascending"` o `"descending"`.

- [ ] **Paso 1: escribir los tests que fallan**

Añade estos cinco a `tests/Feature/OrdenMisZonasTest.php`:

```php
    /**
     * La tabla es una tabla: <thead> y <th scope="col">, cinco columnas. Sin
     * scope, un lector de pantalla no sabe a qué se refiere cada cabecera.
     */
    public function test_la_vista_lista_es_una_tabla_con_cabeceras(): void
    {
        $this->crearZona('Alfa');

        $lista = $this->maquetacion($this->html(), 'zonas-lista');

        $this->assertStringContainsString('<thead', $lista);
        $this->assertSame(5, substr_count($lista, '<th scope="col"'), 'Zona, Lugar, Estado, Progreso y Acciones.');
    }

    /**
     * Las cabeceras ordenables son ENLACES, no botones con JavaScript: es lo
     * que hace que el orden se pueda compartir en una URL y que esta suite
     * pueda verlo. Tres ordenan -nombre, lugar y progreso-; descripción y
     * acciones no.
     */
    public function test_solo_tres_cabeceras_ordenan(): void
    {
        $this->crearZona('Alfa');

        $lista = $this->maquetacion($this->html(), 'zonas-lista');

        $this->assertSame(3, substr_count($lista, 'orden='), 'Tres cabeceras ordenables, ni una más.');
        $this->assertStringNotContainsString('<button', $lista);
    }

    /**
     * aria-sort solo en la columna activa: es lo que un lector de pantalla
     * anuncia, y ponerlo en las tres diría que la tabla está ordenada por
     * tres columnas a la vez.
     */
    public function test_solo_la_columna_activa_anuncia_su_orden(): void
    {
        $this->crearZona('Alfa');

        $lista = $this->maquetacion($this->html('/mis-zonas?orden=lugar&dir=desc'), 'zonas-lista');

        $this->assertSame(1, substr_count($lista, 'aria-sort='));
        $this->assertStringContainsString('aria-sort="descending"', $lista);
    }

    /**
     * Pulsar la columna activa invierte el sentido; pulsar otra empieza en
     * ascendente, sea cual sea. Una regla sin excepciones por columna: que
     * «progreso» arrancara en descendente porque «es lo que se querría» haría
     * que la tabla se comportara distinto según dónde pulses.
     *
     * El & del href sale escapado como &amp; porque el enlace se pinta con
     * {{ }}, que es lo correcto en HTML.
     */
    public function test_la_columna_activa_ofrece_invertir_y_las_demas_ascendente(): void
    {
        $this->crearZona('Alfa');

        $lista = $this->maquetacion($this->html(), 'zonas-lista');

        $this->assertStringContainsString('orden=nombre&amp;dir=desc', $lista, 'La activa invierte.');
        $this->assertStringContainsString('orden=lugar&amp;dir=asc', $lista, 'Las demás arrancan en asc.');
        $this->assertStringContainsString('orden=progreso&amp;dir=asc', $lista, 'Progreso también, sin excepción.');
    }

    /** El desglose está también en la tabla: las dos maquetaciones llevan lo mismo. */
    public function test_la_tabla_lleva_el_desglose_por_estado(): void
    {
        $this->crearZona('Alfa');

        $lista = $this->maquetacion($this->html(), 'zonas-lista');

        $this->assertStringContainsString('10 sin empezar', $lista);
    }
```

- [ ] **Paso 2: correrlos y verlos fallar**

```bash
php artisan test --filter=OrdenMisZonasTest
```

Esperado: FALLAN los cuatro primeros de los nuevos (no hay `<thead>`, ni
`th scope`, ni `aria-sort`, ni enlaces de orden). El quinto PASA ya, porque la
T4 puso el desglose en la fila de la lista; sigue haciendo falta para que la
tabla no lo pierda por el camino.

- [ ] **Paso 3: escribir el componente de cabecera**

Crea `resources/views/components/cabecera-ordenable.blade.php`:

```blade
@props(['columna', 'orden', 'dir', 'alineacion' => 'left'])

{{--
    Una cabecera de tabla que ordena.

    Es un ENLACE y no un botón con JavaScript, por tres motivos que apuntan al
    mismo sitio: el orden se puede compartir en una URL, funciona sin JS, y lo
    puede comprobar la suite de esta máquina —donde Playwright no está
    instalado, así que una ordenación con Alpine sería una funcionalidad que
    ningún test podría ver—.

    Pulsar la columna activa invierte el sentido; pulsar otra empieza en
    ascendente, sea cual sea. Una regla sin excepciones por columna.

    `aria-sort` va solo en la activa —es lo que un lector de pantalla
    anuncia—, y por eso vive aquí y no repartido por cada <th> de la vista,
    donde habría que acordarse de quitarlo de las otras dos.

    fullUrlWithQuery conserva el resto de parámetros de la URL: hoy no hay
    ninguno más, pero el día que lo haya, cambiar de orden no debería
    perderlos.
--}}

@php
    $activa = $orden === $columna;

    $siguienteDir = $activa && $dir === 'asc' ? 'desc' : 'asc';

    $ariaSort = match (true) {
        ! $activa      => null,
        $dir === 'asc' => 'ascending',
        default        => 'descending',
    };
@endphp

<th scope="col"
    @if($ariaSort) aria-sort="{{ $ariaSort }}" @endif
    {{ $attributes->merge(['class' => 'px-6 py-3 text-' . $alineacion . ' text-sm font-medium text-gray-600']) }}>
    <a href="{{ request()->fullUrlWithQuery(['orden' => $columna, 'dir' => $siguienteDir]) }}"
       class="inline-flex items-center gap-1 hover:text-gray-900">
        {{ $slot }}
        {{-- La flecha es decorativa: quien no ve la pantalla ya tiene
             aria-sort, y leerle «flecha arriba» sería decirlo dos veces mal. --}}
        <span aria-hidden="true" class="{{ $activa ? 'text-gray-900' : 'text-gray-300' }}">
            {{ $activa && $dir === 'desc' ? '↓' : '↑' }}
        </span>
    </a>
</th>
```

- [ ] **Paso 4: sustituir el bloque de la lista por la tabla**

En `resources/views/operativo/dashboard.blade.php`, sustituye el bloque entero
de la vista lista —desde el comentario `═══ VISTA LISTA ═══` hasta su
`</x-tarjeta>`— por:

```blade
            {{-- ═══ VISTA LISTA ══════════════════════════════════════════════════
                 Una tabla de verdad, porque es una tabla: cinco columnas de la
                 misma naturaleza para varias zonas. En un teléfono no caben, y
                 por eso el contenedor lleva scroll horizontal en vez de
                 esconderse: elegir maquetación es del usuario, su preferencia
                 se guarda, y la de tarjetas -que es la que viene por defecto-
                 sigue siendo la que mejor funciona ahí. --}}
            <x-tarjeta :padding="false" id="zonas-lista" x-show="vista === 'lista'" x-transition
                 class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-cabecera-ordenable columna="nombre" :orden="$orden" :dir="$dir">Zona</x-cabecera-ordenable>
                            <x-cabecera-ordenable columna="lugar" :orden="$orden" :dir="$dir">Lugar</x-cabecera-ordenable>
                            <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-600">Estado</th>
                            <x-cabecera-ordenable columna="progreso" :orden="$orden" :dir="$dir">Progreso</x-cabecera-ordenable>
                            <th scope="col" class="px-6 py-3 text-right text-sm font-medium text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($zonas as $zona)
                            @php $p = $progreso[$zona->id]; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="text-base text-gray-900">{{ $zona->nombre }}</p>
                                    {{-- Misma descripción que la tarjeta, para que cambiar de
                                         formato no le esconda este dato al usuario. --}}
                                    <p class="text-sm text-gray-600 mt-1 line-clamp-1">
                                        {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
                                    </p>
                                </td>

                                {{-- Sin el 📍 de la tarjeta: aquí el lugar es una
                                     columna con su cabecera, y el emoji repetiría
                                     en cada fila lo que el encabezado ya dice. --}}
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $zona->lugar->nombre }}</td>

                                <td class="px-6 py-4">
                                    <x-desglose-estados :progreso="$p" />
                                </td>

                                <td class="px-6 py-4">
                                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500 rounded-full"
                                             style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <x-boton :href="route('operativo.zona.panel', $zona->id)">
                                            Abrir zona
                                        </x-boton>
                                        <x-boton :href="route('operativo.inventarios.index', $zona->id)" variante="secundario">
                                            Inventario
                                        </x-boton>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-tarjeta>
```

- [ ] **Paso 5: actualizar el assert del lugar en `ConmutadorVistaTest`**

El `📍 ` desaparece de la lista y se queda solo en la tarjeta, así que contar
el emoji daría 1. Se compara sobre el **nombre del lugar**, que es el dato de
verdad. En `tests/Feature/ConmutadorVistaTest.php`, sustituye el `assertSame`
que hoy cuenta `'📍 ' . $this->zona->lugar->nombre` por:

```php
        // Sobre el nombre del lugar y no sobre el «📍 »: en la tabla el lugar
        // es una columna con cabecera y el emoji ahí sobra, pero el dato
        // tiene que seguir estando en las dos maquetaciones, que es lo que
        // este test defiende.
        $this->assertSame(2, substr_count($html, $this->zona->lugar->nombre), 'El lugar debe aparecer una vez por maquetación.');
```

- [ ] **Paso 6: correr los tests tocados**

```bash
php artisan test --filter="OrdenMisZonasTest|ConmutadorVistaTest"
```

Esperado: PASAN los diecinueve.

Si `test_solo_tres_cabeceras_ordenan` cuenta más de 3, es que `orden=` aparece
también fuera de las cabeceras (por ejemplo en un enlace de la fila): mira
dónde antes de tocar el número.

- [ ] **Paso 7: construir los assets y correr la suite entera**

```bash
npm run build
php artisan test
```

Esperado: 632 tests en verde (627 + 5). Si `package-lock.json` aparece
modificado: `git checkout -- package-lock.json`.

- [ ] **Paso 8: commit**

```bash
git add resources/views/components/cabecera-ordenable.blade.php resources/views/operativo/dashboard.blade.php tests/Feature/OrdenMisZonasTest.php tests/Feature/ConmutadorVistaTest.php public/build
git commit -m "feat(mis-zonas): la vista lista es una tabla que se puede ordenar

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 6: cero zonas — el aviso, y nada más

**Ficheros:**
- Modificar: `resources/views/operativo/dashboard.blade.php` (el `@if` del aviso pasa a `@if/@else`)
- Modificar: `tests/Feature/DashboardTest.php` (test nuevo)

**Interfaces:**
- Consume: todo lo anterior. No produce nada nuevo.

- [ ] **Paso 1: escribir el test que falla**

Añade a `tests/Feature/DashboardTest.php`:

```php
    /**
     * Sin zonas, el aviso ámbar y nada más: hoy quedan debajo una tarjeta
     * vacía y un conmutador que no conmuta nada.
     *
     * Es la misma doctrina que este fichero ya aplica al panel de siguiente
     * paso —si no hay nada que decir, no se dice nada—, aplicada al resto de
     * la página.
     */
    public function test_sin_zonas_no_se_pinta_ni_el_conmutador_ni_las_maquetaciones(): void
    {
        $sinZona = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($sinZona)
            ->get('/mis-zonas')
            ->assertOk()
            ->assertSee('No tienes zonas asignadas actualmente. Contacta al administrador.')
            ->assertDontSee('id="zonas-lista"', false)
            ->assertDontSee('id="zonas-tarjetas"', false)
            ->assertDontSee('id="zonas-kpis"', false)
            ->assertDontSee('Tarjetas');
    }
```

- [ ] **Paso 2: correrlo y verlo fallar**

```bash
php artisan test --filter=sin_zonas_no_se_pinta_ni_el_conmutador
```

Esperado: FALLA en el primer `assertDontSee`: hoy los dos contenedores se
pintan vacíos.

- [ ] **Paso 3: envolver el resto de la página**

En `resources/views/operativo/dashboard.blade.php`, cambia el bloque
`@if($zonas->isEmpty())` del aviso ámbar por su versión con `@else`:

```blade
            @if($zonas->isEmpty())
            {{-- Y nada más: sin zonas no hay cifras que sumar, ni orden que
                 elegir, ni maquetación que conmutar. Un conmutador que no
                 conmuta nada es una pregunta sin respuesta posible. --}}
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-700">No tienes zonas asignadas actualmente. Contacta al administrador.</p>
            </div>
            @else
```

Y cierra el `@else` con un `@endif` justo antes del `</div>` que cierra el
`x-data`, después del bloque de tarjetas:

```blade
            @endif

    </div>
</x-app-layout>
```

El panel de siguiente paso puede quedarse fuera del `@else` —ya tiene su propia
guarda y con cero zonas `proximoPaso()` devuelve las tres claves vacías—, pero
métele dentro **la franja, el conmutador y las dos maquetaciones**.

- [ ] **Paso 4: correr los tests y verlos pasar**

```bash
php artisan test --filter=DashboardTest
```

Esperado: PASAN los nueve.

- [ ] **Paso 5: correr la suite entera**

```bash
php artisan test
```

Esperado: 633 tests en verde (632 + 1).

- [ ] **Paso 6: commit**

```bash
git add resources/views/operativo/dashboard.blade.php tests/Feature/DashboardTest.php
git commit -m "fix(mis-zonas): sin zonas no se pinta un conmutador que no conmuta nada

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 7: revisión de rama, la página de verdad y el traspaso

**Ficheros:**
- Modificar: `docs/ESTADO-PROYECTO.md`
- Modificar: `.superpowers/sdd/progress.md`
- Crear: `.superpowers/sdd/task-7-report.md`

**Interfaces:** ninguna. Es la puerta antes de fusionar.

- [ ] **Paso 1: mirar la página de verdad, no solo sus tests**

En las cuatro últimas ramas, este paso encontró lo que ningún test veía —una
franja verde sobre un estado bloqueado, un selector flotando en mitad de la
barra—. Levanta el servidor y mira:

```bash
npm run build
php artisan serve
```

Con un jefe que tenga **tres o más zonas** en estados distintos, comprueba una
por una:

1. La franja de cifras cuadra con lo que suman las tarjetas.
2. Las tres insignias caben en la tarjeta sin desbordarla ni romper la línea a
   media palabra.
3. Pulsar cada cabecera ordena, y la flecha señala el sentido correcto.
4. En una ventana estrecha (~375 px), la tabla hace scroll horizontal **dentro
   de su tarjeta** y no empuja la página entera.
5. Con una sola zona no hay franja; con ninguna, solo el aviso.
6. El panel de «siguiente paso» señala a la misma zona ordenes como ordenes.

- [ ] **Paso 2: revisión de la rama entera**

```bash
git diff 0cd8ecf..HEAD > .superpowers/sdd/review-fase2.diff
```

Usa `superpowers:requesting-code-review` sobre ese diff. Lee lo que devuelva con
`superpowers:receiving-code-review`: en esta rama hay dos cosas que un revisor
debería mirar con lupa y que el plan ya sabe que son decisiones y no descuidos
—el `hechas` sin renombrar y el desglose sin insignia de «terminada»—; si
alguien las señala, la respuesta está escrita en la spec, no se rehacen.

- [ ] **Paso 3: correr la suite entera una vez más, sobre el resultado final**

```bash
php artisan test
```

Esperado: 633 tests en verde. Si sale `Out of memory`, se parte en
`--testsuite=Unit` y `--testsuite=Feature`.

- [ ] **Paso 4: el traspaso al día**

En `docs/ESTADO-PROYECTO.md`:
- Entrada de la rama `dashboard-mis-zonas`: qué se hizo, el recuento nuevo de
  tests (633) y **lo que la revisión encontró**, si encontró algo.
- Tacha la Fase 2 de la lista de pendientes del rediseño.
- Anota los dos restos que esta rama deja escritos y no toca: **renombrar
  `hechas` a `validadas`** cuando una fase entre de verdad en admin, y el
  **`📍` de la tarjeta**, que es de antes de la Fase 0 y se arregla donde se
  arreglen los demás.
- Anota la premisa comprobada que no generó trabajo: «Mis Zonas» elige por rol
  y el selector de la barra hace la unión; hoy coinciden porque
  `Admin\ZonaController` valida los roles, y el día que esa validación se
  afloje discreparán.

En `.superpowers/sdd/progress.md`, cierra la bitácora con una línea por tarea y
lo que cada una encontró que el plan no decía.

- [ ] **Paso 5: commit y fusión**

```bash
git add docs/ESTADO-PROYECTO.md .superpowers/sdd/progress.md .superpowers/sdd/task-7-report.md
git commit -m "docs(traspaso): la Fase 2 al dia, con lo que encontro su revision

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

**Fusionar se pregunta.** Una vez fusionado, se sube —y se sube junto con el
contexto—, y la suite se corre **sobre el resultado fusionado**, no solo sobre
la rama (regla 3 de `CLAUDE.md`).

---

## Recuento de tests esperado

| Tarea | Añade | Total |
|---|---|---|
| base | — | 608 |
| T1 | 3 | 611 |
| T2 | 9 | 620 |
| T3 | 3 | 623 |
| T4 | 4 | 627 |
| T5 | 5 | 632 |
| T6 | 1 | 633 |

Si el número no cuadra al terminar una tarea, para y mira por qué antes de
seguir: en este repositorio un test que desaparece sin que nadie lo note ya ha
pasado.
