# Detalle de zona en dos columnas — plan de implementación

> **Para quien lo ejecute:** SUB-SKILL OBLIGATORIA: usa
> `superpowers:subagent-driven-development` (recomendado) o
> `superpowers:executing-plans` para ejecutarlo tarea a tarea. Los pasos usan
> casillas (`- [ ]`) para poder marcarlos.

**Objetivo:** que `operativo/zona/panel` pase de una columna a dos: un panel
lateral fijo con lo que identifica a la zona —lugar, jefe, equipo,
descripción y progreso—, y la columna principal con la misma lista de
matrices agrupadas por fase que ya existe hoy.

**Arquitectura:** `EstadoZona` gana un método de instancia, `desglose()`, que
reparte el progreso de la zona por estado reutilizando las evaluaciones que
el constructor ya carga —sin ninguna consulta nueva—. La vista pasa a un
`<div class="lg:grid lg:grid-cols-[320px_1fr] ...">` escrito directamente en
`panel.blade.php`, igual que ya hacen los ocho formularios de matriz con su
propio aside; no se extrae ningún componente nuevo. El controlador gana un
eager load más (`equipo`).

**Tecnología:** Laravel 12, Blade con componentes anónimos, Tailwind,
PHPUnit. Sin Alpine nuevo: no hay nada que conmutar ni ordenar en esta fase.

**Spec:** `docs/superpowers/specs/2026-08-15-detalle-zona-design.md` — el plan
no reargumenta sus decisiones, solo las ejecuta; quien ejecute lee las dos.

## Restricciones globales

- **Suite base de la rama: 632 tests en verde**, confirmados con
  `php artisan test` (PHP 8.2.33 nativo, ~38 s) antes de escribir esta línea.
- **Ninguna pantalla fuera de `operativo/zona/panel` se toca.** Ni
  `admin/zonas/*`, ni el dashboard, ni ningún formulario de matriz.
- **`<x-desglose-estados>`, `<x-badge>`, `<x-tarjeta>`, `<x-migas>` y
  `<x-fila-matriz>` se usan tal cual.** Ninguno se modifica; ver el
  inventario de la spec.
- **Ningún componente Blade nuevo.** El panel lateral y el envoltorio de dos
  columnas se escriben directamente en `panel.blade.php`, como ya hacen los
  formularios de matriz con el suyo.
- **Clases de Tailwind completas y literales, nunca por concatenación**: el
  purgado se lleva las que no aparecen tal cual en el fuente. Esto incluye
  `lg:grid-cols-[320px_1fr]`, que va escrito entero en el Blade, no compuesto
  desde una variable de PHP.
- **Nada de texto por debajo de 14 px salvo insignias, sin `uppercase`.**
  No hay test automático para esto —`TipografiaUnicaTest` comprueba la
  familia tipográfica, no el tamaño ni las mayúsculas; ver la spec—, así que
  es una revisión de navegador y de lectura del código, en la Tarea 4.
- **`package-lock.json` no entra en ningún commit.** Si aparece modificado:
  `git checkout -- package-lock.json`.
- **No se toca ningún contenedor de Docker.** No hace falta para nada de
  esto.
- Mensajes de commit en español, terminados en
  `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

## Ficheros

| Fichero | Responsabilidad | Tarea |
|---|---|---|
| `app/Servicios/EstadoZona.php` | `desglose()` nuevo; retira `validadas()`/`totalMatrices()` | T1 |
| `tests/Unit/EstadoZonaTest.php` | el desglose, sobre el servicio, sin HTTP | T1 |
| `resources/views/operativo/zona/panel.blade.php` | las dos columnas, el progreso vía `<x-desglose-estados>`, el color de la insignia de rol, equipo y descripción | T2, T3 |
| `app/Http/Controllers/Operativo/ZonaPanelController.php` | carga `equipo` además de `lugar` y `jefe` | T3 |
| `tests/Feature/PaginaZonaTest.php` | el progreso nuevo, las anclas de las dos columnas, el color de la insignia, equipo, descripción, paridad entre roles | T2, T3 |
| `docs/ESTADO-PROYECTO.md` | traspaso al día | T5 |
| `.superpowers/sdd/progress.md` | bitácora de la rama, sobrescrita al empezar | T1 |

## Dos decisiones que el plan añade a la spec

**1. El arreglo del color de la insignia «Equipo» va en la Tarea 2, no en la
3.** La colisión con el verde de `<x-badge estado="validada">` aparece en
cuanto `<x-desglose-estados>` entra en la tarjeta —Tarea 2—, antes de que
exista ningún contenido de equipo o descripción —Tarea 3—. Arreglarlo en la
tarea que lo provoca evita un commit intermedio con la tarjeta ya rota a
propósito.

**2. `EstadoZona::desglose()` se implementa y se retiran `validadas()`/
`totalMatrices()` en la MISMA tarea, no en dos.** Dejar los tres métodos
convivir un commit, aunque fuera transitorio, es la «segunda fuente de
verdad» exacta que la spec explica por qué evitar. La Tarea 1 deja
`EstadoZona` con una sola forma de leer el progreso de una zona, de principio
a fin.

---

### Tarea 1: `EstadoZona::desglose()` sustituye a `validadas()`/`totalMatrices()`

**Ficheros:**
- Modificar: `app/Servicios/EstadoZona.php:97-108`
- Modificar: `tests/Unit/EstadoZonaTest.php:228-239`
- Sobrescribir: `.superpowers/sdd/progress.md`

**Interfaces:**
- Consume: `$this->evaluaciones`, que el constructor ya llena.
- Produce: `EstadoZona::desglose(): array{hechas: int, borradores: int,
  sin_empezar: int, total: int}`. Lo consume la Tarea 2.
- Retira: `EstadoZona::validadas()` y `EstadoZona::totalMatrices()` —
  comprobado que su único consumidor de producción es `panel.blade.php`,
  que la Tarea 2 reescribe.

- [ ] **Paso 1: sobrescribir la bitácora de rama**

`.superpowers/sdd/progress.md` todavía lleva la de la Fase 2, ya volcada en
el traspaso. La regla 3 de `CLAUDE.md` manda sobrescribirla al empezar cada
rama:

```markdown
> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — Detalle de zona en dos columnas (Fase 3 del rediseño de interfaz)

Spec: `docs/superpowers/specs/2026-08-15-detalle-zona-design.md`
Plan: `docs/superpowers/plans/2026-08-15-detalle-zona.md`
Rama: fase-3-detalle-zona
Base de la rama: a625645
Suite en la base: 632 tests

Objetivo: panel lateral con lugar, jefe, equipo, descripción y progreso;
columna principal con la misma lista de matrices agrupadas de siempre.

## Decisiones que vienen del diseño y no se replantean

- **Sin componente Blade nuevo.** El envoltorio de dos columnas se escribe
  directo en panel.blade.php, como ya hacen los formularios de matriz.
- **`<x-desglose-estados>` sustituye la fracción «X de Y validadas»**, igual
  que ya hizo en el dashboard.
- **La insignia de rol «Equipo» pasa de verde a teal**: colisiona con el
  verde de `<x-badge estado="validada">` en la misma tarjeta.
- **El panel lateral dice lo mismo para los tres roles**, salvo la línea de
  rol.
- **Sin icono nuevo para «lugar».** Cualquier icono del catálogo ya es la
  identidad de una de las diez matrices que esta misma página lista.

## Tareas

T1 EstadoZona::desglose() · T2 las dos columnas y el progreso · T3 equipo y
descripción · T4 verificación de navegador · T5 revisión final y traspaso.
```

- [ ] **Paso 2: escribir el test que falla**

En `tests/Unit/EstadoZonaTest.php`, sustituye el método
`test_el_progreso_cuenta_solo_matrices_validadas` (líneas 228-239) por:

```php
    /**
     * El desglose de una zona: una validada, una en borrador y las ocho
     * restantes sin fila ninguna. Sustituye a
     * test_el_progreso_cuenta_solo_matrices_validadas -fijaba
     * validadas()/totalMatrices(), retirados en esta misma tarea: desglose()
     * cuenta lo mismo y además reparte lo que a ellos les faltaba-.
     */
    public function test_el_desglose_reparte_validadas_borradores_y_sin_empezar(): void
    {
        $estado = new EstadoZona($this->zona, $this->jefe);
        $vacio  = $estado->desglose();

        $this->assertSame(0, $vacio['hechas']);
        $this->assertSame(0, $vacio['borradores']);
        $this->assertSame(10, $vacio['sin_empezar']);
        $this->assertSame(10, $vacio['total']);

        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);
        EvaluacionFet::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $estado = new EstadoZona($this->zona, $this->jefe);
        $p      = $estado->desglose();

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
     * Zona sin ninguna evaluación: entera en sin_empezar, con las cuatro
     * claves puestas -quien la consume las lee sin comprobar si existen,
     * mismo contrato que EstadoZona::progresoDe().
     */
    public function test_una_zona_sin_evaluaciones_desglosa_entera_en_sin_empezar(): void
    {
        $estado = new EstadoZona($this->zona, $this->jefe);

        $this->assertSame(
            ['hechas' => 0, 'borradores' => 0, 'sin_empezar' => 10, 'total' => 10],
            $estado->desglose()
        );
    }
```

No hace falta tocar los `use` del fichero: `EvaluacionFit`, `EvaluacionFet`,
`Zona`, `DB` y `EstadoZona` ya están importados arriba de la clase.

- [ ] **Paso 3: correrlo y verlo fallar**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: FALLAN los dos tests nuevos con «Call to undefined method
App\Servicios\EstadoZona::desglose()». El resto de la clase sigue en verde.

- [ ] **Paso 4: implementar `desglose()` y retirar los dos métodos viejos**

En `app/Servicios/EstadoZona.php`, sustituye el bloque completo de
`totalMatrices()` y `validadas()` (hoy en las líneas 97-108) por:

```php
    /**
     * El desglose de ESTA zona por estado: cuántas matrices lleva
     * validadas, cuántas en borrador, y cuántas nadie ha abierto.
     *
     * Sustituye a validadas()/totalMatrices(), que solo tenían un
     * consumidor fuera de esta clase -panel.blade.php- y entre los dos
     * contaban la mitad de lo que este método reparte. No llama a
     * progresoDe(): esa versión existe para resolver MUCHAS zonas con un
     * número fijo de consultas por lote, un problema que aquí no hay -el
     * constructor ya hizo, una por una, las diez consultas de
     * Registro::matrices() que $this->evaluaciones necesita para pintar
     * grupos()-. Repetirlas con progresoDe() sería la misma pregunta dos
     * veces, cada una con su propio viaje a la base.
     *
     * @return array{hechas: int, borradores: int, sin_empezar: int, total: int}
     */
    public function desglose(): array
    {
        $total      = count(Registro::matrices());
        $hechas     = 0;
        $borradores = 0;

        foreach ($this->evaluaciones as $evaluacion) {
            if ($evaluacion === null) {
                continue;
            }

            if ($evaluacion->estado === 'confirmado') {
                $hechas++;
            } else {
                $borradores++;
            }
        }

        return [
            'hechas'      => $hechas,
            'borradores'  => $borradores,
            'sin_empezar' => $total - $hechas - $borradores,
            'total'       => $total,
        ];
    }
```

Comprueba antes de guardar que no queda ningún `validadas()` ni
`totalMatrices()` en el fichero:

```bash
grep -n "function validadas\|function totalMatrices" app/Servicios/EstadoZona.php
```

Esperado: sin resultados.

- [ ] **Paso 5: correr los tests y verlos pasar**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: PASAN todos los de la clase, los dos nuevos y los de antes.

- [ ] **Paso 6: confirmar que no queda ningún otro consumidor**

```bash
grep -rn "\->validadas()\|\->totalMatrices()" --include=*.php --include=*.blade.php .
```

Esperado: sin resultados —si aparece algo fuera de
`EstadoZona.php`/`EstadoZonaTest.php`, para: significa que el paso 6 de la
spec («comprobado, no supuesto») estaba mal y hay que revisar antes de
seguir—.

- [ ] **Paso 7: correr la suite entera**

```bash
php artisan test
```

Esperado: 633 tests en verde (632 − 1 + 2). Nada más debería cambiar:
`panel.blade.php` todavía llama a `validadas()`/`totalMatrices()` en este
punto — la Tarea 2 lo arregla. **Si la suite falla aquí con un error de
`panel.blade.php`, es esperado**: sigue así hasta el paso 8.

Confirma que el único roto es la vista de zona, no un tercer consumidor
oculto:

```bash
php artisan test --filter=PaginaZonaTest
```

Esperado: FALLAN los tests que tocan la tarjeta de progreso, con «Call to
undefined method App\Servicios\EstadoZona::validadas()». Es el fallo que la
Tarea 2 cierra.

- [ ] **Paso 8: commit**

```bash
git add app/Servicios/EstadoZona.php tests/Unit/EstadoZonaTest.php .superpowers/sdd/progress.md
git commit -m "refactor(zona): EstadoZona::desglose() sustituye a validadas()/totalMatrices()

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 2: las dos columnas, y el progreso vía `<x-desglose-estados>`

**Ficheros:**
- Modificar: `resources/views/operativo/zona/panel.blade.php` (entero)
- Modificar: `tests/Feature/PaginaZonaTest.php` (un test existente, tres
  nuevos)

**Interfaces:**
- Consume: `EstadoZona::desglose()` (T1).
- Produce: `<aside id="zona-panel-lateral">` y `<div
  id="zona-panel-matrices">`, anclas que la Tarea 4 usa para la verificación
  de navegador.

- [ ] **Paso 1: escribir los tests que fallan**

En `tests/Feature/PaginaZonaTest.php`, sustituye
`test_muestra_el_progreso_de_la_zona` (líneas 122-129) por:

```php
    public function test_muestra_el_progreso_de_la_zona(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('1 validadas')
            ->assertSee('9 sin empezar');
    }

    /**
     * La fracción vieja no se cuela de vuelta. Un assertDontSee de una sola
     * cara pasaría igual si el panel lateral entero dejara de pintarse, así
     * que lleva su contraparte positiva -que el reemplazo sí está- en el
     * mismo test.
     */
    public function test_el_progreso_ya_no_usa_la_fraccion_de_antes(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();

        $this->assertStringNotContainsString('de 10 validadas', $html);
        $this->assertStringContainsString('1 validadas', $html);
    }

    public function test_la_pagina_tiene_panel_lateral_y_columna_principal(): void
    {
        $html = $this->actingAs($this->jefe)->get($this->url())->assertOk()->getContent();

        $this->assertStringContainsString('id="zona-panel-lateral"', $html);
        $this->assertStringContainsString('id="zona-panel-matrices"', $html);
    }

    /**
     * La insignia de rol «Equipo» no puede volver a bg-green-100: en esta
     * tarjeta convive con <x-badge estado="validada">, que usa ese mismo
     * verde para «matriz validada». No se afirma la AUSENCIA del verde -es
     * un color legítimo aquí, para la insignia de progreso- sino la
     * PRESENCIA del teal nuevo, que es lo que de verdad prueba el arreglo.
     */
    public function test_la_insignia_de_rol_equipo_no_usa_el_verde_de_validada(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        $html = $this->actingAs($equipo)->get($this->url())->assertOk()->getContent();

        $this->assertStringContainsString('bg-teal-100 text-teal-800', $html);
    }
```

- [ ] **Paso 2: correrlos y verlos fallar**

```bash
php artisan test --filter="muestra_el_progreso_de_la_zona|fraccion_de_antes|panel_lateral_y_columna_principal|insignia_de_rol_equipo"
```

Esperado: FALLAN todos. Los tres primeros con «Call to undefined method
EstadoZona::validadas()» —el mismo fallo que dejó pendiente la Tarea 1—; el
cuarto porque hoy la insignia de equipo sigue en verde.

- [ ] **Paso 3: reescribir la vista**

Sustituye `resources/views/operativo/zona/panel.blade.php` entero:

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-10">

            <x-migas :zona="$zona" />

            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded text-base">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded text-base">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ═══ DOS COLUMNAS ═════════════════════════════════════════════════
                 El panel lateral va PRIMERO en el DOM -320px, fijo- y la columna
                 principal después -1fr-: mismo mecanismo que ya usan los ocho
                 formularios de matriz (lg:grid lg:grid-cols-[1fr_256px]), con el
                 aside al revés porque aquí es la columna principal la que crece.

                 Por debajo de lg no hay grid: las dos partes se apilan en el
                 mismo orden del DOM, que es el que la página ya tenía en una
                 sola columna -el panel de identidad, primero-. No hace falta
                 esconder nada en móvil: a diferencia de
                 <x-barra-lateral-formulario>, cuyo botón de guardar está
                 duplicado más abajo en el formulario, aquí no hay ningún otro
                 sitio de la pantalla que repita esta información. --}}
            <div class="lg:grid lg:grid-cols-[320px_1fr] lg:gap-8 lg:items-start">

                <aside id="zona-panel-lateral" class="mb-6 lg:mb-0 lg:sticky lg:top-6 lg:self-start">
                    <x-tarjeta>
                        @php
                            // 'equipo' va en teal, no en verde: verde es el
                            // color de <x-badge estado="validada">
                            // -EstadoZona::ESTILOS_ESTADO-, y las dos
                            // píldoras conviven en esta misma tarjeta desde
                            // que el progreso pasó a <x-desglose-estados>.
                            $etiquetaPapel = [
                                'admin'  => ['texto' => 'Administración', 'clase' => 'bg-blue-100 text-blue-800'],
                                'jefe'   => ['texto' => 'Jefe de zona',   'clase' => 'bg-purple-100 text-purple-800'],
                                'equipo' => ['texto' => 'Equipo',         'clase' => 'bg-teal-100 text-teal-800'],
                            ][$estado->papel()];
                        @endphp
                        <span class="inline-flex text-sm font-medium px-3 py-1 rounded-full {{ $etiquetaPapel['clase'] }}">
                            {{ $etiquetaPapel['texto'] }} · {{ auth()->user()->name }}
                        </span>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Lugar</dt>
                                <dd class="text-gray-800 mt-0.5">📍 {{ $zona->lugar->nombre }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Jefe de zona</dt>
                                <dd class="text-gray-800 mt-0.5">{{ $zona->jefe->name ?? 'Sin asignar' }}</dd>
                            </div>
                        </dl>

                        {{-- El desglose reemplaza la fracción «X de Y
                             validadas» de antes, igual que ya hizo en el
                             dashboard: sigue llevando la misma barra de
                             porcentaje, y debajo las insignias que reparten
                             validadas/borrador/sin empezar. --}}
                        @php $desglose = $estado->desglose(); @endphp
                        <div class="mt-5 pt-5 border-t border-gray-200">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $desglose['total'] > 0 ? round($desglose['hechas'] / $desglose['total'] * 100) : 0 }}%"></div>
                            </div>
                            <x-desglose-estados :progreso="$desglose" class="mt-3" />
                        </div>
                    </x-tarjeta>
                </aside>

                <div id="zona-panel-matrices" class="lg:min-w-0">
                    @foreach($estado->grupos() as $grupo)
                        <x-tarjeta :padding="false" class="mb-6 px-6 pt-5 pb-2">
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $grupo['titulo'] }}</h3>

                            @foreach($grupo['filas'] as $fila)
                                <x-fila-matriz :fila="$fila" />
                            @endforeach
                        </x-tarjeta>
                    @endforeach
                </div>

            </div>

    </div>
</x-app-layout>
```

Nota: esta versión todavía no muestra Equipo ni Descripción en la `<dl>` — la
Tarea 3 los añade. El `<dl>` de este paso solo lleva Lugar y Jefe, igual que
la tarjeta de antes.

- [ ] **Paso 4: correr los tests tocados y verlos pasar**

```bash
php artisan test --filter=PaginaZonaTest
```

Esperado: PASAN todos, incluidos los cuatro de este paso.

- [ ] **Paso 5: construir los assets y correr la suite entera**

`lg:grid-cols-[320px_1fr]`, `lg:sticky`, `lg:self-start` y `bg-teal-100
text-teal-800` son clases que no estaban en el fuente hasta este commit.

```bash
npm run build
php artisan test
```

Esperado: 636 tests en verde (633 + 3). Si `package-lock.json` aparece
modificado: `git checkout -- package-lock.json`.

- [ ] **Paso 6: commit**

```bash
git add resources/views/operativo/zona/panel.blade.php tests/Feature/PaginaZonaTest.php
git commit -m "feat(zona): panel en dos columnas, con el progreso via x-desglose-estados

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 3: equipo y descripción en el panel lateral

**Ficheros:**
- Modificar: `app/Http/Controllers/Operativo/ZonaPanelController.php`
- Modificar: `resources/views/operativo/zona/panel.blade.php` (la `<dl>` del
  panel lateral, dentro de la tarjeta que ya construyó la Tarea 2)
- Modificar: `tests/Feature/PaginaZonaTest.php` (cinco tests nuevos)

**Interfaces:**
- Consume: `Zona::equipo()`, ya existente (`belongsToMany`, pivote
  `asignado_at`); `Zona::$descripcion`, ya existente.
- Produce: nada que consuma otra tarea — es la última pieza de contenido del
  panel lateral.

- [ ] **Paso 1: escribir los tests que fallan**

Añade estos cinco a `tests/Feature/PaginaZonaTest.php`:

```php
    public function test_el_panel_lateral_muestra_el_equipo_de_la_zona(): void
    {
        $ana = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
            'name'    => 'Ana Pérez',
        ]);
        $bruno = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
            'name'    => 'Bruno Ríos',
        ]);
        $this->zona->equipo()->attach([$ana->id, $bruno->id]);

        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Ana Pérez')
            ->assertSee('Bruno Ríos');
    }

    public function test_sin_equipo_asignado_el_panel_lateral_lo_dice(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Sin equipo asignado');
    }

    public function test_el_panel_lateral_muestra_la_descripcion_de_la_zona(): void
    {
        $this->zona->update(['descripcion' => 'Costa rocosa con dos miradores habilitados.']);

        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Costa rocosa con dos miradores habilitados.');
    }

    public function test_sin_descripcion_el_panel_lateral_usa_el_texto_de_reserva(): void
    {
        $this->actingAs($this->jefe)->get($this->url())
            ->assertOk()
            ->assertSee('Sin descripción disponible.');
    }

    /**
     * El panel lateral cuenta la ZONA, no a quien mira: las cuatro cosas de
     * abajo tienen que salir igual para los tres roles. Solo la línea de rol
     * cambia, y a propósito no se comprueba aquí -comprobarla haría fallar
     * el test por el motivo equivocado-.
     */
    public function test_el_panel_lateral_es_igual_para_los_tres_roles(): void
    {
        $equipoMiembro = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipoMiembro->id);
        $this->zona->update(['descripcion' => 'Zona piloto de la costa norte.']);

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        foreach ([$this->jefe, $equipoMiembro, $admin] as $usuario) {
            $html = $this->actingAs($usuario)->get($this->url())->assertOk()->getContent();

            $this->assertStringContainsString($this->zona->lugar->nombre, $html);
            $this->assertStringContainsString($this->jefe->name, $html);
            $this->assertStringContainsString($equipoMiembro->name, $html);
            $this->assertStringContainsString('Zona piloto de la costa norte.', $html);
        }
    }
```

- [ ] **Paso 2: correrlos y verlos fallar**

```bash
php artisan test --filter="panel_lateral_muestra_el_equipo|sin_equipo_asignado|panel_lateral_muestra_la_descripcion|sin_descripcion_el_panel_lateral|panel_lateral_es_igual_para_los_tres_roles"
```

Esperado: FALLAN los cinco. Los cuatro primeros porque la `<dl>` de hoy no
tiene ni Equipo ni Descripción; el quinto por el mismo motivo, en la
comprobación de equipo y descripción.

- [ ] **Paso 3: cargar el equipo en el controlador**

En `app/Http/Controllers/Operativo/ZonaPanelController.php`, cambia la línea
del `with`:

```php
        $zona   = Zona::with('lugar', 'jefe', 'equipo')->findOrFail($zonaId);
```

- [ ] **Paso 4: añadir Equipo y Descripción a la `<dl>`**

En `resources/views/operativo/zona/panel.blade.php`, dentro del `<dl>` del
panel lateral, después del bloque de «Jefe de zona» y antes de `</dl>`:

```blade
                            <div>
                                <dt class="text-gray-500">Equipo</dt>
                                <dd class="text-gray-800 mt-0.5">
                                    {{ $zona->equipo->isNotEmpty() ? $zona->equipo->pluck('name')->join(', ') : 'Sin equipo asignado' }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Descripción</dt>
                                <dd class="text-gray-800 mt-0.5">{{ $zona->descripcion ?? 'Sin descripción disponible.' }}</dd>
                            </div>
```

- [ ] **Paso 5: correr los tests y verlos pasar**

```bash
php artisan test --filter=PaginaZonaTest
```

Esperado: PASAN todos.

- [ ] **Paso 6: correr la suite entera**

```bash
php artisan test
```

Esperado: 641 tests en verde (636 + 5).

- [ ] **Paso 7: commit**

```bash
git add app/Http/Controllers/Operativo/ZonaPanelController.php resources/views/operativo/zona/panel.blade.php tests/Feature/PaginaZonaTest.php
git commit -m "feat(zona): el panel lateral suma equipo y descripcion

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 4: verificación de navegador

**Ficheros:** ninguno se modifica —a menos que esta tarea encuentre algo,
en cuyo caso el arreglo vuelve a T2 o T3 según lo que toque, con su propio
test y su propio commit, antes de continuar aquí.

**Por qué es su propia tarea, no un paso de la revisión final:** en las
cuatro fases anteriores de este mismo rediseño, este paso —mirar la página
de verdad, no solo sus tests— encontró lo que ningún test veía: una franja
verde sobre un estado bloqueado, un selector flotando en mitad de la barra,
un contenedor que doblaba el padding. Playwright no está instalado en esta
máquina —herencia de `restos-fase-0`—, así que esto es manual, y hacerlo su
propia tarea evita que se salte por prisa al llegar a la revisión final.

- [ ] **Paso 1: levantar el servidor**

```bash
npm run build
php artisan serve
```

- [ ] **Paso 2: una zona con datos de verdad**

Con un jefe que tenga una zona con **equipo asignado** (dos o tres personas),
**descripción**, y una mezcla de matrices validadas, en borrador y sin
empezar —para que las tres insignias del desglose se pinten a la vez—,
comprueba:

1. **A 1280 px:** las dos columnas están una junto a la otra; el panel
   lateral queda fijo (`sticky`) al desplazar la columna principal hacia
   abajo, y deja de estarlo cuando la columna principal termina.
2. **A 1280 px, con una zona de pocas matrices** (si la hay a mano) donde la
   columna principal sea más corta que el panel lateral: el panel lateral no
   se estira para igualar la altura de la columna principal —confirma
   `lg:items-start`—.
3. **A 375 px:** el panel lateral se apila arriba, la lista de matrices
   debajo; ni `<body>` ni `<html>` tienen scroll horizontal —revísalo con
   `document.documentElement.scrollWidth` en la consola, o
   `getBoundingClientRect()` sobre el `<body>`, igual que hizo la revisión de
   la Fase 2—.
4. **Las insignias del desglose no desbordan la tarjeta** ni parten una
   palabra a 375 px —mismo tipo de comprobación que ya hizo la revisión de
   la Fase 2 sobre las tarjetas del dashboard—.
5. **El equipo con varios nombres no rompe la tarjeta**: una lista de tres o
   cuatro personas separadas por comas no debería forzar scroll horizontal ni
   desbordar el panel lateral. Si lo hace, es el riesgo que la spec ya
   anotó — el arreglo (envolver, truncar o apilar los nombres) se decide
   aquí, con el caso real delante, no de antemano.
6. **Los tres roles**, en la misma zona: admin, jefe y equipo ven el mismo
   panel lateral salvo la línea de rol; ninguno ve un botón o acción que no
   debería —el panel lateral es información, no acciones, ver la spec—.
7. **Tipografía y mayúsculas**, la regla de 14 px: ningún texto del panel
   lateral usa un tamaño por debajo de `text-sm` salvo insignias, y ninguna
   clase `uppercase` se coló. No hay test automático para esto — es este
   paso, o nada.

- [ ] **Paso 3: si algo falla, arreglarlo donde corresponde**

Un defecto de layout (ítem 1-4) vuelve a la Tarea 2. Un defecto de contenido
(ítem 5-6) vuelve a la Tarea 3. Cada arreglo lleva su propio test —el defecto
que un test no puede ver hoy es exactamente el que este paso existe para
cazar, y dejarlo sin test sería repetir el patrón que esta misma fase señala
en la spec—.

- [ ] **Paso 4: anotar lo encontrado**

Si no hay hallazgos, una línea en `.superpowers/sdd/progress.md` diciéndolo
—«verificación de navegador: sin hallazgos»— es preferible a un silencio que
alguien tenga que interpretar. Si hay hallazgos, quedan detallados ahí para
que la Tarea 5 los vuelque al traspaso.

---

### Tarea 5: revisión final de la rama y traspaso

**Ficheros:**
- Modificar: `docs/ESTADO-PROYECTO.md`
- Modificar: `.superpowers/sdd/progress.md`
- Crear: `.superpowers/sdd/task-5-report.md`

**Interfaces:** ninguna. Es la puerta antes de fusionar.

- [ ] **Paso 1: revisión de la rama entera**

```bash
git diff a625645..HEAD > .superpowers/sdd/review-fase3.diff
```

Usa `superpowers:requesting-code-review` sobre ese diff. Lee lo que devuelva
con `superpowers:receiving-code-review`: hay dos decisiones que un revisor
podría señalar y que ya están respondidas en la spec, no para rehacerse sin
más — el color de la insignia «Equipo» (decisión 8) y el 📍 que se queda tal
cual (sección «Lo que no entra»).

- [ ] **Paso 2: correr la suite entera una vez más, sobre el resultado final**

```bash
php artisan test
```

Esperado: 641 tests en verde. Si sale `Out of memory`, se parte en
`--testsuite=Unit` y `--testsuite=Feature`.

- [ ] **Paso 3: el traspaso al día**

En `docs/ESTADO-PROYECTO.md`:
- Entrada de la rama `fase-3-detalle-zona`: qué se hizo, el recuento nuevo de
  tests (641), y lo que la Tarea 4 encontró —o que no encontró nada—.
- Tacha la Fase 3 de la lista de pendientes del rediseño (§6, punto 14).
- Anota que `validadas()`/`totalMatrices()` ya no existen en `EstadoZona`,
  por si algún documento viejo los sigue nombrando.
- Anota el 📍 sin arreglar, y por qué esta vez sí se miró y se decidió
  dejarlo —no es el mismo «se arregla donde se arreglen los demás» de antes,
  es una comprobación concreta que descarta el icono obvio—.

En `.superpowers/sdd/progress.md`, cierra la bitácora con una línea por
tarea y lo que cada una encontró que el plan no decía.

- [ ] **Paso 4: commit**

```bash
git add docs/ESTADO-PROYECTO.md .superpowers/sdd/progress.md .superpowers/sdd/task-5-report.md
git commit -m "docs(traspaso): la Fase 3 al dia, con lo que encontro su revision

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

**Fusionar se pregunta.** Una vez fusionado, se sube —junto con el
contexto—, y la suite se corre **sobre el resultado fusionado**, no solo
sobre la rama (regla 3 de `CLAUDE.md`).

---

## Recuento de tests esperado

| Tarea | Añade | Total |
|---|---|---|
| base | — | 632 |
| T1 | 1 (−1 retirado, +2 nuevos) | 633 |
| T2 | 3 | 636 |
| T3 | 5 | 641 |
| T4 | 0 (verificación manual) | 641 |
| T5 | 0 | 641 |

Si el número no cuadra al terminar una tarea, para y mira por qué antes de
seguir: en este repositorio un test que desaparece sin que nadie lo note ya
ha pasado.
