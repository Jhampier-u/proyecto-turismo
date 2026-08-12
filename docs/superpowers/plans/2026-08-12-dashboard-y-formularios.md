# Dashboard y espacio de los formularios — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Dos mejoras de interfaz independientes: (A) el dashboard de `/mis-zonas` pasa de índice a punto de partida, con un panel de "lo siguiente que toca hacer"; (B) los siete formularios de matriz de tipo "un criterio por fila" ensanchan a `max-w-7xl` y ganan una barra lateral fija con el progreso, el índice de bloques y el botón de guardar.

**Architecture:** (A) añade tres métodos estáticos a `EstadoZona` -reutilizando `progresoDe()` y la propia clase, coste fijo, no por zona- y un panel nuevo en `dashboard.blade.php` que reutiliza `<x-fila-matriz>`. (B) añade un componente Blade nuevo, `<x-barra-lateral-formulario>`, que es deliberadamente "tonto": cada vista sigue resolviendo su propio índice de bloques -las diez matrices no comparten una forma para ellos- y el componente solo lo pinta, exactamente como ya hace `<x-criterio-pildoras>` con sus datos.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Alpine.js, Tailwind CSS 3, SQLite en desarrollo y tests, PostgreSQL 16 en producción, PHPUnit 11.

**Diseño:** `docs/superpowers/specs/2026-08-12-dashboard-y-formularios-design.md`

---

## Antes de empezar: lo que el diseño supone y el código no siempre confirma

El diseño da por hecho que "los diez formularios de matriz usan `max-w-5xl`". Comprobado archivo por archivo, no es así:

| Matriz | `form.blade.php` hoy | Tipo (Registro) |
|---|---|---|
| FIT | `max-w-5xl` | matriz |
| FET | `max-w-5xl` | matriz |
| Paisaje | `max-w-5xl` | matriz |
| Valoración Territorial | `max-w-5xl` | matriz |
| Irritación | `max-w-6xl` | matriz |
| Concentración | `max-w-6xl` (ya con `id="form-concentracion"`) | matriz |
| **Percepción** | **`max-w-7xl` -ya ensanchada-** | matriz |
| **Potencialidad** | **sin `max-w-*`: layout propio `.pt-root`/`.pt-layout`, con su propia barra lateral fija de 252 px, ya construida con CSS en línea** | matriz |
| Involucrados (`index`) | `max-w-6xl` | actores |
| Involucrados (`form`, alta/edición de un actor) | `max-w-4xl` | actores |
| Frecuentación (`index`) | `max-w-6xl` | sitios |
| Frecuentación (`form`, alta/edición de un sitio) | `max-w-2xl` | sitios |

Esto cambia el alcance de la Parte B respecto a "diez formularios": ver la sección "Qué queda fuera y por qué" más abajo. El resto del diseño -la decisión de ensanchar y añadir la barra lateral, y los descartes de dos columnas- sigue en pie tal cual está escrito y no se reabre aquí.

Las maquetas del brainstorming (`.superpowers/brainstorm/38-1786504293/content/{dashboard,formularios}.html`, ignoradas por git pero presentes en disco) se revisaron para este plan: confirman el boceto de la barra lateral -recuento arriba, índice de bloques con `✓`/fracción/color, botón "Guardar" fijo abajo- y no añaden nada que contradiga lo que sigue.

---

# Parte A — El Dashboard

## Los tres puntos que el diseño dejó abiertos, resueltos

### 1. De dónde sale "la última matriz que tocaste"

**Para las ocho matrices de tipo `matriz`, `updated_at`/`user_id` bastan y son fiables sin excepción.** Comprobado en `EvaluacionZonaController::update()` (línea 94-97): todo guardado -borrador o confirmado, equipo o jefe- pasa por `$modelo::updateOrCreate(['zona_id' => $zonaId], $datos + ['user_id' => $user->id, 'estado' => $estado])`. Los ocho controladores de matriz (`EvaluacionFitController`, `Fet`, `Paisaje`, `ValoracionTerritorial`, `Percepcion`, `Irritacion`, `Concentracion`, `Potencialidad`) heredan de `MatrizPonderadaController` o directamente de `EvaluacionZonaController`, así que ninguno se escapa.

**Para `actores` (Involucrados) y `sitios` (Frecuentación), NO bastan, y es una limitación real, no una suposición:**

- `Involucrado` y `SitioFrecuentacion` -las filas de detalle- **no tienen columna `user_id`** (comprobado en sus migraciones y en `$fillable`). Solo tienen `updated_at`, que dice *cuándo* pero no *quién*.
- Las tablas de configuración (`InvolucradosConfig`, `FrecuentacionConfig`), que sí tienen `user_id`, **solo lo actualizan al validar, reabrir tras validar, o -en Frecuentación- guardar la Superficie Territorial** (`InvolucradosController::validar()`/`reabrirSiConfirmada()`, `FrecuentacionController::actualizarSuperficie()`). Añadir, editar o borrar un actor o un sitio mientras la lista sigue en borrador **sin haber sido validada nunca** no toca esa fila en absoluto.

Consecuencia práctica: un jefe que lleva media hora añadiendo actores a una lista que nunca ha validado no tiene, hoy, ningún dato fiable de "tú tocaste esto" para esa matriz.

**Decisión de este plan:** no se inventa la atribución que falta. `EstadoZona::proximoPaso()` (Tarea 1) usa `user_id`/`updated_at` donde son fiables -las ocho matrices, y las dos de configuración cuando sí tienen `user_id`- y simplemente no hace de esas dos su "última tocada" en el caso común de edición en borrador nunca validada. La otra mitad del panel -"siguiente sin terminar"- sí las cubre igual que a las demás, así que una lista de actores a medias sigue apareciendo en el dashboard, solo que como "sin terminar" y no como "tocada por ti hace 5 minutos".

**Coste de la alternativa completa**, si se quisiera cerrar el hueco: una migración por tabla (`involucrados`, `frecuentacion_sitios`) añadiendo `user_id` nullable con `nullOnDelete()`, fijarlo en `datosDe()` de los dos controladores (ver `app/Http/Controllers/Operativo/InvolucradosController.php:463-481` y `FrecuentacionController.php:363-369`), y ajustar los tests de esos dos ficheros que crean actores/sitios a mano. Estimación: 2 migraciones + 2 controladores + ~4 tests tocados, medio día. Queda fuera de este plan porque el diseño no lo pide y amplía el radio de cambio a dos matrices ya cerradas y en producción.

### 2. Qué se enseña cuando no hay nada empezado

Dos casos, tratados distinto a propósito:

- **Cero zonas asignadas**: se mantiene el aviso amarillo que ya existe ("No tienes zonas asignadas..."). El panel nuevo **no se pinta en absoluto** -ni con las dos tarjetas vacías, ni con una sola-, porque un panel de "continuar" sin nada que continuar es exactamente el "peor que lo actual" que el diseño pide evitar.
- **Zonas asignadas, pero cero actividad propia todavía** (jefe/equipo recién creado): no hay "última tocada" -`ultima` sale `null`-, pero sí hay "siguiente sin terminar" -la primera matriz sin validar de la primera zona con algo pendiente, casi siempre FIT, por ser la primera declarada en `Registro::ENTRADAS`-. El panel muestra solo esa tarjeta, con el texto de "Empieza por aquí" en vez de "Sigue por aquí".

Un tercer caso, que el diseño no menciona pero que la propia lógica produce: **todo validado en todas las zonas**. Aquí `siguiente` también sale `null` -no hay nada sin terminar-, y si además `ultima` sale `null` (nunca debería, si ya está todo validado alguien lo tocó) el panel no se pinta; si `ultima` sí tiene algo, se muestra solo esa tarjeta con un tono de cierre ("Todo al día").

### 3. Qué ve el admin

**Nada nuevo.** `admin.zonas.index` no se toca. `DashboardController::index()` ya redirige al admin a `admin.dashboard` sin pasar por `/mis-zonas` (línea 16-18): el panel que este plan añade vive en `dashboard.blade.php`, que el admin nunca ve.

Razón, no solo constatación: la relación del admin con las matrices ya es distinta -"escribe borradores pero no valida"-, y su pantalla es una tabla de gestión de *todas* las zonas del sistema, paginada, no un espacio de trabajo personal sobre una o dos zonas propias. La justificación del panel -"con una o dos zonas la pantalla se siente vacía"- no aplica a una tabla que ya está llena de filas por construcción. Si el flujo del admin cambia en el futuro, esto se reconsidera; hoy se deja fuera explícitamente.

### Y el Inventario

No necesita ninguna rama nueva: `Registro::matrices()` ya filtra a `TIPOS_VALIDABLES` (`matriz`, `actores`, `sitios`), y tanto `inventario` como `vtt` quedan fuera de ese filtro desde antes de este plan. Todo lo que este plan construye se apoya en `Registro::matrices()`, así que el Inventario nunca entra en juego.

---

## Estructura de ficheros — Parte A

**Modificar:**
- `app/Servicios/EstadoZona.php` — tres métodos nuevos: `proximoPaso()`, `filaDeClave()`, y dos privados de soporte.
- `app/Http/Controllers/Operativo/DashboardController.php`
- `resources/views/operativo/dashboard.blade.php`
- `resources/views/components/fila-matriz.blade.php` — prop `zona` opcional.

**Test:**
- `tests/Unit/EstadoZonaTest.php`
- `tests/Feature/DashboardTest.php`

---

### Tarea 1: `EstadoZona::proximoPaso()`

Lógica pura sobre modelos, sin HTTP: se prueba directamente contra `EstadoZona`, igual que ya se prueba `progresoDe()`.

**Files:**
- Modify: `app/Servicios/EstadoZona.php`
- Test: `tests/Unit/EstadoZonaTest.php`

**Interfaces:**
- Produce: `EstadoZona::proximoPaso(User $usuario, Collection $zonas, array $progreso): array{ultima: ?array, siguiente: ?array, fusionado: bool}`
- Produce: `EstadoZona::filaDeClave(string $clave): FilaMatriz` (público; ya existía como lógica privada dentro de `fila()`)

- [ ] **Step 1: Escribir los tests, en rojo**

Añadir a `tests/Unit/EstadoZonaTest.php` (usa el `$this->jefe`/`$this->zona` del `setUp()` existente, y crea una segunda zona donde haga falta):

```php
    /**
     * Sin ninguna actividad del usuario y sin nada validado: "última" sale
     * null, y "siguiente" señala a la primera matriz declarada -FIT- de la
     * primera zona. Es el caso del jefe recién creado.
     */
    public function test_sin_actividad_siguiente_senala_a_la_primera_matriz_declarada(): void
    {
        $progreso = EstadoZona::progresoDe(collect([$this->zona]));

        $resultado = EstadoZona::proximoPaso($this->jefe, collect([$this->zona]), $progreso);

        $this->assertNull($resultado['ultima']);
        $this->assertNotNull($resultado['siguiente']);
        $this->assertSame($this->zona->id, $resultado['siguiente']['zona']->id);
        $this->assertSame('fit', $resultado['siguiente']['fila']->clave);
        $this->assertFalse($resultado['fusionado']);
    }

    /**
     * Un borrador de FIT guardado por el propio jefe: "última tocada" lo
     * encuentra por su user_id/updated_at. Como FIT sigue sin validar y es
     * la primera matriz declarada, "siguiente" apunta a la misma entrada:
     * se fusionan en una sola tarjeta.
     */
    public function test_una_matriz_tocada_que_tambien_es_la_siguiente_sin_terminar_se_fusiona(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador']);

        $progreso = EstadoZona::progresoDe(collect([$this->zona]));
        $resultado = EstadoZona::proximoPaso($this->jefe, collect([$this->zona]), $progreso);

        $this->assertNotNull($resultado['ultima']);
        $this->assertSame('fit', $resultado['ultima']['fila']->clave);
        $this->assertNull($resultado['siguiente']);
        $this->assertTrue($resultado['fusionado']);
    }

    /**
     * Tocar FET (una matriz que no es la primera declarada) deja "siguiente"
     * señalando a FIT, todavía sin empezar: las dos tarjetas son distintas
     * y las dos se muestran.
     */
    public function test_tocar_una_matriz_que_no_es_la_primera_no_fusiona(): void
    {
        EvaluacionFet::create(['zona_id' => $this->zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador']);

        $progreso = EstadoZona::progresoDe(collect([$this->zona]));
        $resultado = EstadoZona::proximoPaso($this->jefe, collect([$this->zona]), $progreso);

        $this->assertSame('fet', $resultado['ultima']['fila']->clave);
        $this->assertSame('fit', $resultado['siguiente']['fila']->clave);
        $this->assertFalse($resultado['fusionado']);
    }

    /**
     * Con todas las matrices validables validadas, no queda nada "sin
     * terminar": siguiente sale null. Se apoya en fitCompleta() -ya
     * existente en este fichero- y confirma las ocho matrices restantes a
     * mano por brevedad: lo que importa es el caso límite (total = hechas),
     * no repetir el alta de las diez.
     */
    public function test_con_todo_validado_no_hay_siguiente(): void
    {
        foreach (\App\Matrices\Registro::matrices() as $clave => $entrada) {
            $modelo = $entrada['modelo'];
            $columnas = array_filter(
                \Illuminate\Support\Facades\Schema::getColumnListing((new $modelo())->getTable()),
                fn(string $c) => EstadoZona::esColumnaDeCriterio($c)
            );

            $modelo::create(
                ['zona_id' => $this->zona->id, 'user_id' => $this->jefe->id, 'estado' => 'confirmado']
                + array_fill_keys($columnas, 3)
            );
        }

        $progreso = EstadoZona::progresoDe(collect([$this->zona]));
        $resultado = EstadoZona::proximoPaso($this->jefe, collect([$this->zona]), $progreso);

        $this->assertNull($resultado['siguiente']);
    }

    /** Sin zonas, las tres claves salen vacías sin disparar ninguna consulta de más. */
    public function test_sin_zonas_proximo_paso_sale_vacio(): void
    {
        $resultado = EstadoZona::proximoPaso($this->jefe, collect(), []);

        $this->assertNull($resultado['ultima']);
        $this->assertNull($resultado['siguiente']);
        $this->assertFalse($resultado['fusionado']);
    }
```

Añadir el `use` que falte al principio del fichero: `use App\Models\EvaluacionFet;` (`EvaluacionFit` ya está importado).

- [ ] **Step 2: Ejecutar y comprobar que fallan**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: los cinco tests nuevos fallan con `Call to undefined method App\Servicios\EstadoZona::proximoPaso()`.

- [ ] **Step 3: Implementar**

En `app/Servicios/EstadoZona.php`, añadir junto a `progresoDe()`:

```php
    /**
     * Una sola fila, por clave. grupos() ya resuelve todas para pintar la
     * página de zona entera; esto es la misma resolución cuando solo hace
     * falta una -el caso de proximoPaso(), que no necesita el resto-.
     */
    public function filaDeClave(string $clave): FilaMatriz
    {
        return $this->fila($clave);
    }

    /**
     * «Lo siguiente que toca hacer»: la matriz que el usuario tocó por
     * última vez y la primera que todavía no ha terminado, cada una en su
     * zona. Alimenta el panel de arriba del dashboard operativo.
     *
     * Dos preguntas distintas -"¿por dónde sigo?" y "¿qué no he ni
     * empezado?"- que casi siempre señalan a matrices distintas. Cuando
     * coinciden -lo normal si se trabaja en el orden del registro- se
     * fusionan en una sola entrada para no repetir la misma tarjeta dos
     * veces.
     *
     * $progreso se recibe ya calculado -progresoDe($zonas)- en vez de
     * volver a pedirlo aquí: el dashboard ya lo necesita para las tarjetas
     * de zona de más abajo, y pedirlo dos veces duplicaría sus consultas
     * sin ganar nada.
     *
     * Coste fijo, no por zona: como mucho una consulta por cada uno de los
     * diez modelos validables para "última tocada", y como mucho dos
     * instancias de EstadoZona -coste fijo cada una, ver su constructor-
     * para resolver la fila completa de "última" y de "siguiente". El
     * número de zonas no multiplica el número de consultas, mismo
     * principio que progresoDe().
     *
     * @param  Collection<int, Zona>  $zonas
     * @param  array<int, array{hechas: int, total: int}>  $progreso  de progresoDe($zonas)
     * @return array{ultima: ?array{zona: Zona, fila: FilaMatriz}, siguiente: ?array{zona: Zona, fila: FilaMatriz}, fusionado: bool}
     */
    public static function proximoPaso(User $usuario, Collection $zonas, array $progreso): array
    {
        if ($zonas->isEmpty()) {
            return ['ultima' => null, 'siguiente' => null, 'fusionado' => false];
        }

        $ultima    = self::ultimaTocadaPor($usuario, $zonas);
        $siguiente = self::siguientePendiente($usuario, $zonas, $progreso);

        $fusionado = $ultima !== null
            && $siguiente !== null
            && $ultima['zona']->id === $siguiente['zona']->id
            && $ultima['fila']->clave === $siguiente['fila']->clave;

        return [
            'ultima'    => $ultima,
            'siguiente' => $fusionado ? null : $siguiente,
            'fusionado' => $fusionado,
        ];
    }

    /**
     * La matriz validable con el updated_at más reciente entre las que este
     * usuario guardó él mismo (user_id), dentro de sus zonas.
     *
     * Fiable para las ocho matrices de tipo 'matriz': EvaluacionZonaController
     * ::update() fija user_id y toca updated_at en cada guardado, borrador o
     * confirmado, sin excepción -ver su Step 96-. NO es igual de fiable para
     * 'actores' e 'sitios': sus tablas de configuración solo fijan user_id
     * al validar, reabrir, o -en Frecuentación- guardar la Superficie
     * Territorial, nunca por el simple alta/edición/borrado de una fila
     * mientras la lista sigue en borrador sin validar nunca; y las filas de
     * detalle (Involucrado, SitioFrecuentacion) no tienen columna user_id en
     * absoluto. Es una limitación conocida -documentada en el plan, no un
     * bug- y no la ensancha ninguna consulta de más: si no hay user_id que
     * mirar, esa matriz simplemente no compite por "última tocada".
     */
    private static function ultimaTocadaPor(User $usuario, Collection $zonas): ?array
    {
        $idsZonas   = $zonas->pluck('id');
        $zonasPorId = $zonas->keyBy('id');

        $mejorClave = null;
        $mejorZona  = null;
        $mejorFecha = null;

        foreach (Registro::matrices() as $clave => $entrada) {
            $modelo = $entrada['modelo'];

            $fila = $modelo::where('user_id', $usuario->id)
                ->whereIn('zona_id', $idsZonas)
                ->whereNotNull('updated_at')
                ->orderByDesc('updated_at')
                ->first(['zona_id', 'updated_at']);

            if ($fila === null) {
                continue;
            }

            if ($mejorFecha === null || $fila->updated_at->gt($mejorFecha)) {
                $mejorClave = $clave;
                $mejorZona  = $zonasPorId[$fila->zona_id];
                $mejorFecha = $fila->updated_at;
            }
        }

        if ($mejorClave === null) {
            return null;
        }

        $estado = new self($mejorZona, $usuario);

        return ['zona' => $mejorZona, 'fila' => $estado->filaDeClave($mejorClave)];
    }

    /**
     * La primera matriz validable, en el orden de declaración del registro,
     * que no está validada -explorando las zonas en el orden recibido y
     * deteniéndose en la primera que tenga algo pendiente-.
     *
     * $progreso -de coste fijo, ya calculado por el dashboard- decide SOLO
     * por qué zona empezar; resolver la fila en sí -con su nombre, su
     * detalle y su enlace- solo cuesta una instancia de EstadoZona, y como
     * mucho una, no una por zona.
     */
    private static function siguientePendiente(User $usuario, Collection $zonas, array $progreso): ?array
    {
        foreach ($zonas as $zona) {
            $p = $progreso[$zona->id] ?? null;

            if ($p === null || $p['hechas'] >= $p['total']) {
                continue;
            }

            $estado = new self($zona, $usuario);

            foreach (Registro::matrices() as $clave => $entrada) {
                $fila = $estado->filaDeClave($clave);

                if ($fila->estado !== 'validada') {
                    return ['zona' => $zona, 'fila' => $fila];
                }
            }
        }

        return null;
    }
```

- [ ] **Step 4: Ejecutar y comprobar que pasan**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: PASS, incluidos los tests preexistentes de este fichero (no deben cambiar de comportamiento).

- [ ] **Step 5: Commit**

```bash
git add app/Servicios/EstadoZona.php tests/Unit/EstadoZonaTest.php
git commit -m "feat(dashboard): EstadoZona resuelve la ultima matriz tocada y la siguiente sin terminar"
```

---

### Tarea 2: `<x-fila-matriz>` acepta una zona opcional

El dashboard reutiliza este componente -ya construido y probado- en vez de escribir una tarjeta nueva desde cero. Como abarca varias zonas a la vez, cada fila necesita decir a cuál pertenece; `zona.panel.blade.php`, que solo pinta filas de UNA zona, no lo necesita y no debe cambiar de aspecto.

**Files:**
- Modify: `resources/views/components/fila-matriz.blade.php`
- Test: `tests/Feature/PaginaZonaTest.php` (comprobar que no cambia sin la prop), `tests/Feature/DashboardTest.php` (comprobar que sí aparece con la prop)

- [ ] **Step 1: Test de que la zona es opcional y no rompe la página de zona**

Añadir a `tests/Feature/PaginaZonaTest.php` (mirar su `setUp()` existente para el usuario/zona de prueba):

```php
    /**
     * zona.panel.blade.php nunca pasa :zona a <x-fila-matriz> -no lo
     * necesita, la página entera ya es de una sola zona-. Este test fija
     * que seguir sin pasarla no pinta ningún nombre de zona de más: es la
     * garantía de que la Tarea 2 no le cambia el aspecto a esta página.
     */
    public function test_la_pagina_de_zona_no_repite_el_nombre_de_la_zona_en_cada_fila(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.zona.panel', $this->zona->id))
            ->assertOk()
            ->getContent();

        // El nombre aparece una vez, en el encabezado -no una vez por fila-.
        $this->assertSame(1, substr_count($html, $this->zona->nombre));
    }
```

- [ ] **Step 2: Ejecutar y comprobar que pasa ya hoy (test de partida en verde, no en rojo)**

```bash
php artisan test --filter=test_la_pagina_de_zona_no_repite_el_nombre_de_la_zona_en_cada_fila
```

Esperado: PASS. Este test no ejerce código nuevo -fija el comportamiento ANTES del cambio-, así que sirve de red de seguridad para el resto de la tarea, no de test guía.

- [ ] **Step 3: Añadir la prop**

En `resources/views/components/fila-matriz.blade.php`, cambiar la línea 1 y añadir el bloque de zona:

```php
@props(['fila', 'zona' => null])
```

Y justo antes del nombre de la matriz (después de `<div class="flex-1 min-w-0">`):

```blade
    <div class="flex-1 min-w-0">
        @if($zona)
            <p class="text-sm text-gray-500">{{ $zona->nombre }}</p>
        @endif
        <p class="text-base {{ $bloqueada ? 'text-gray-400' : 'text-gray-900' }}">
            {{ $fila->nombre }}
        </p>
```

- [ ] **Step 4: Test de que SÍ aparece cuando se pasa la zona**

Se cubre en la Tarea 3, sobre el dashboard real: no hace falta un test aislado del componente aquí, porque `zona.panel.blade.php` no pasará nunca esta prop y el único consumidor nuevo (`dashboard.blade.php`) todavía no existe.

- [ ] **Step 5: Ejecutar toda la suite y commit**

```bash
php artisan test
```

Esperado: PASS, sin ningún test existente movido.

```bash
git add resources/views/components/fila-matriz.blade.php tests/Feature/PaginaZonaTest.php
git commit -m "feat(fila-matriz): admite una zona opcional, para pintarse fuera de la pagina de una sola zona"
```

---

### Tarea 3: El panel del dashboard

**Files:**
- Modify: `app/Http/Controllers/Operativo/DashboardController.php`
- Modify: `resources/views/operativo/dashboard.blade.php`
- Test: `tests/Feature/DashboardTest.php`

**Trampa a evitar, explícita:** `test_el_numero_de_consultas_no_crece_con_el_numero_de_zonas` (ya existe en este fichero) compara el número de consultas de `/mis-zonas` con 1 zona contra 5 zonas, con un margen de +3. El diseño de la Tarea 1 es de coste fijo -no escala con el número de zonas-, así que este test debe seguir en verde sin tocar su margen: si al terminar esta tarea hay que subirlo, algo en la implementación está recorriendo zonas en vez de usar `$progreso` ya calculado.

- [ ] **Step 1: Tests HTTP, en rojo**

Añadir a `tests/Feature/DashboardTest.php`:

```php
    /**
     * Cero zonas: el aviso de siempre, y NADA de panel nuevo -ver el punto 2
     * del plan: un panel de "continuar" vacío sería peor que no tenerlo-.
     */
    public function test_sin_zonas_no_se_pinta_el_panel_de_siguiente_paso(): void
    {
        $sinZona = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->actingAs($sinZona)
            ->get('/mis-zonas')
            ->assertOk()
            ->assertDontSee('Sigue por aquí')
            ->assertDontSee('Empieza por aquí');
    }

    /**
     * Zona nueva, sin ninguna evaluación: el panel ofrece "Empieza por
     * aquí" señalando a FIT -la primera matriz declarada-, y NO ofrece
     * "Sigue por aquí" -no hay nada que continuar todavía-.
     */
    public function test_una_zona_recien_creada_ofrece_empezar_por_fit(): void
    {
        $this->crearZona('Zona nueva');

        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString('Empieza por aquí', $html);
        $this->assertStringNotContainsString('Sigue por aquí', $html);
        $this->assertStringContainsString(
            route('operativo.evaluacion_fit.edit', \App\Models\Zona::where('nombre', 'Zona nueva')->value('id')),
            $html
        );
    }

    /**
     * Con un borrador de FET guardado por el jefe, "Sigue por aquí" señala
     * a FET y "Todavía sin empezar" señala a FIT: dos tarjetas, no una.
     */
    public function test_una_matriz_tocada_que_no_es_la_primera_ofrece_las_dos_tarjetas(): void
    {
        $zona = $this->crearZona('Zona con FET a medias');

        \App\Models\EvaluacionFet::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString('Sigue por aquí', $html);
        $this->assertStringContainsString('Todavía sin empezar', $html);
    }

    /**
     * Tocar FIT y no tener nada más pendiente por delante: se fusiona en
     * una sola tarjeta -"Sigue por aquí"-, sin repetir FIT como "Todavía
     * sin empezar" justo debajo. Es la trampa concreta de las tarjetas
     * duplicadas, fijada por test.
     */
    public function test_la_matriz_tocada_y_la_siguiente_sin_terminar_no_se_repiten(): void
    {
        $zona = $this->crearZona('Zona con FIT a medias');

        \App\Models\EvaluacionFit::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $html = $this->actingAs($this->jefe)->get('/mis-zonas')->assertOk()->getContent();

        $this->assertStringContainsString('Sigue por aquí', $html);
        $this->assertStringNotContainsString('Todavía sin empezar', $html);
        $this->assertSame(1, substr_count($html, route('operativo.evaluacion_fit.edit', $zona->id)));
    }

    /**
     * El panel muestra en qué zona está cada tarjeta -imprescindible con
     * más de una zona-, reutilizando la prop nueva de <x-fila-matriz>.
     */
    public function test_el_panel_dice_a_que_zona_pertenece_cada_tarjeta(): void
    {
        $zona = $this->crearZona('Zona identificable');

        \App\Models\EvaluacionFit::create([
            'zona_id' => $zona->id, 'user_id' => $this->jefe->id, 'estado' => 'borrador',
        ]);

        $this->actingAs($this->jefe)
            ->get('/mis-zonas')
            ->assertOk()
            ->assertSee('Zona identificable');
    }
```

Este fichero ya tiene `crearZona()` en su `setUp()`; si `Role`/`User` no están importados, añadir `use App\Models\Role;` y `use App\Models\User;` (siguiendo la cabecera de `PestanasMatrizTest.php` como referencia).

- [ ] **Step 2: Ejecutar y comprobar que fallan**

```bash
php artisan test --filter=DashboardTest
```

Esperado: los cinco tests nuevos fallan (no existe todavía "Sigue por aquí"/"Empieza por aquí" en la vista). El test de coste fijo debe seguir pasando -no lo toca esta tarea todavía-.

- [ ] **Step 3: Controlador**

En `app/Http/Controllers/Operativo/DashboardController.php`:

```php
<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Servicios\EstadoZona;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
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

        $progreso = EstadoZona::progresoDe($zonas);

        // proximoPaso() recibe $progreso ya calculado: pedirlo dos veces
        // duplicaría las consultas de progresoDe() sin ganar nada.
        $proximoPaso = EstadoZona::proximoPaso($user, $zonas, $progreso);

        return view('operativo.dashboard', compact('zonas', 'progreso', 'proximoPaso'));
    }
}
```

- [ ] **Step 4: Vista**

En `resources/views/operativo/dashboard.blade.php`, insertar el panel nuevo justo antes del conmutador de vista (después del `@if($zonas->isEmpty())` existente, antes de `<div class="flex justify-end mb-4">`):

```blade
            {{-- ═══ SIGUIENTE PASO ═══════════════════════════════════════════════
                 El dashboard como punto de partida, no como índice: arriba, lo
                 siguiente que toca hacer. Con cero actividad todavía, solo se
                 pinta "siguiente sin terminar" -no hay nada que "seguir"-; con
                 todo validado, no se pinta ninguna de las dos. Nunca un panel
                 vacío: si no hay nada que decir, no se dice nada. --}}
            @if($proximoPaso['ultima'] || $proximoPaso['siguiente'])
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                @if($proximoPaso['ultima'])
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 px-6 pt-5 pb-2">
                    <h3 class="text-base font-semibold text-gray-800 mb-1">
                        {{ $proximoPaso['fusionado'] ? 'Sigue por aquí — es donde lo dejaste' : 'Sigue por aquí' }}
                    </h3>
                    <x-fila-matriz :fila="$proximoPaso['ultima']['fila']" :zona="$proximoPaso['ultima']['zona']" />
                </div>
                @endif

                @if($proximoPaso['siguiente'])
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 px-6 pt-5 pb-2">
                    <h3 class="text-base font-semibold text-gray-800 mb-1">
                        {{ $proximoPaso['ultima'] ? 'Todavía sin empezar' : 'Empieza por aquí' }}
                    </h3>
                    <x-fila-matriz :fila="$proximoPaso['siguiente']['fila']" :zona="$proximoPaso['siguiente']['zona']" />
                </div>
                @endif
            </div>
            @endif

```

**Trampa concreta de esta vista:** `x-fila-matriz` ya pinta un enlace con `$fila->url`. No añadir NINGÚN otro enlace a esa misma ruta en el panel -ni un título clicable, ni un "ver más"-, porque un test que compruebe "el panel enlaza a FIT" tiene que poder confiar en que el único sitio donde aparece ese `href` es dentro de la tarjeta que se está probando. El test `test_la_matriz_tocada_y_la_siguiente_sin_terminar_no_se_repiten` ya vigila esto contando ocurrencias.

- [ ] **Step 5: Ejecutar y comprobar que pasan**

```bash
php artisan test --filter=DashboardTest
```

Esperado: PASS, los cinco tests nuevos y `test_el_numero_de_consultas_no_crece_con_el_numero_de_zonas` sin margen ampliado.

- [ ] **Step 6: Suite completa**

```bash
php artisan test
```

Esperado: PASS. Prestar atención especial a `PermisosAdminTest.php` y `RedireccionDashboardTest.php` -verifican que el admin no llega a esta vista- y a `ConmutadorVistaTest.php` -el conmutador de tarjetas/lista sigue debajo del panel nuevo, no dentro de él-.

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Operativo/DashboardController.php resources/views/operativo/dashboard.blade.php tests/Feature/DashboardTest.php
git commit -m "feat(dashboard): panel de siguiente paso, con la ultima matriz tocada y la primera sin terminar"
```

---

# Parte B — Barra lateral fija de los formularios

## Los cuatro puntos que el diseño dejó abiertos, resueltos

### 5. ¿Puede ser un componente compartido?

**Sí, pero "compartido" significa que el componente pinta, no que deriva.** Comprobadas las siete matrices en alcance, ninguna comparte con las demás el nombre de la clave que envuelve sus criterios:

| Matriz | Constante | Forma del bloque |
|---|---|---|
| FIT | `Fit::BLOQUES` | `['nombre','peso','criterios' => [campo => ['nombre','niveles']]]` |
| FET | `Fet::BLOQUES` | igual que FIT |
| Paisaje | `Paisaje::CATEGORIAS` | igual que FIT |
| Percepción | `Percepcion::$categorias` | `['nombre','peso','items' => [campo => [...]]]` -**`items`, no `criterios`**- |
| Irritación | `Irritacion::BLOQUES` | `['titulo','subtitulo','campos' => [campo, campo, ...]]` -**lista plana de nombres, no mapa campo⇒etiqueta, y sin `peso`**- |
| Concentración | `Concentracion::ATRACTIVOS` + `::PLANTA` | `categoria => [tipo => [campo => etiqueta]]` (3 niveles) y `sector => [campo => etiqueta]` (2 niveles) -**sin envoltorio `nombre`/`peso` en absoluto: la clave del array ES el nombre**- |
| Valoración Territorial | `ValoracionTerritorial::CT` + `::UC` | `campo => criterio`, plano, **sin ningún agrupador**: los títulos "RTT"/"UC" están escritos a mano en el `.blade.php` |

Con esta variedad -confirmada leyendo las siete clases y sus vistas, no supuesta-, un componente que recorriera `$bloques`/`$categorias`/`$items`/`$campos` por su cuenta necesitaría un `match` distinto por matriz, es decir, dejaría de ser un componente y pasaría a ser siete. **La solución que sí generaliza:** cada vista construye un array normalizado -`[['ancla', 'etiqueta', 'respondidos', 'total'], ...]`- en su propio `@php`, y el componente `<x-barra-lateral-formulario>` solo recibe y pinta ese array. Es la misma división de trabajo que ya usa `<x-criterio-pildoras>` -no sabe de dónde viene su criterio, solo lo pinta- y evita el antipatrón que documenta `<x-boton-volver>`: la lógica de "qué bloques hay" vive en cada vista, una vez, no repetida con variaciones dentro del componente.

El total de cabecera (`X de Y respondidos`) **sí** se deriva dentro del componente a partir de `clave`+`zona`, igual que ya hace `<x-pestanas-matriz>`: ese número es el mismo para las diez matrices (`Registro::ENTRADAS[$clave]['criterios']` + `EstadoZona::criteriosRespondidos()`), así que ahí sí hay una fuente única que consultar en vez de que cada vista lo repita.

**Alcance: se aplica a FIT, FET, Paisaje, Valoración Territorial, Percepción, Irritación y Concentración -siete de las diez-.** Las tres que se quedan fuera, con motivo:

- **Potencialidad.** Ya tiene una barra lateral fija equivalente -`.pt-sidebar`, con su recuento, su navegación por áreas y su botón de guardar-, construida con CSS en línea propio, fuera del `<x-app-layout>` estándar (`.pt-root`/`.pt-layout` en vez de `max-w-Nxl`). Sustituirla por el componente nuevo significaría reescribir un formulario de 156 criterios que ya funciona, por una ganancia -consistencia visual- que no está en el diseño. Se deja intacta.
- **Involucrados y Frecuentación**: ver el punto 6.

### 6. Qué hace en Involucrados y Frecuentación

**Nada. Se dejan fuera de la barra lateral y del ensanchado, con motivo, no por omisión:**

- No tienen bloques que indexar: son un CRUD de filas (actores/sitios), no un formulario de criterios de la zona. "Índice de bloques" no significa nada sobre una tabla.
- Su `index.blade.php` -el "formulario" real de estas dos matrices- ya tiene su propia orientación: la tabla con las insignias "Completo"/"A medias" por fila, y en Frecuentación una sección aparte para la Superficie Territorial con su propio botón de guardar. El problema que la barra lateral resuelve -perder el sitio en un formulario largo de un criterio por fila- no existe aquí.
- Sus formularios de alta/edición de una fila (`form.blade.php`) son deliberadamente pequeños -`max-w-4xl` en Involucrados, `max-w-2xl` en Frecuentación, con 11 y 2 campos respectivamente-. Ensancharlos a `max-w-7xl` los dejaría con más aire vacío, no con más orientación: es precisamente el camino D ("solo apretar lo que ya hay") que el diseño ya descartó, aplicado al revés.

### 7. Pantallas estrechas

El propio código ya tiene un precedente para esto -Potencialidad, en `evaluacion_potencialidad/form.blade.php`-: `@media(max-width:900px){ .pt-sidebar{display:none} }`. Este plan replica la misma idea con utilidades de Tailwind en vez de CSS a mano: el `<aside>` lleva `hidden lg:block` (oculto por debajo de 1024 px, visible desde ahí), y el contenedor que lo envuelve solo se convierte en rejilla de dos columnas desde `lg:` (`lg:grid lg:grid-cols-[1fr_256px]`). Por debajo de 1024 px -tablet y portátiles pequeños con zoom- el formulario vuelve exactamente al layout de una sola columna que tiene hoy, con el botón "Guardar Borrador" de siempre al final del formulario, sin ningún cambio de comportamiento. La barra lateral es una mejora que aparece cuando hay sitio, nunca un requisito para poder guardar.

### 8. El recuento: reutilizar, no reinventar

- **El total de cabecera** reutiliza `EstadoZona::criteriosRespondidos()` -exactamente la función que ya usa `<x-pestanas-matriz>`-, no una cuenta nueva.
- **El desglose por bloque** necesita contar un subconjunto de columnas conocido por cada vista (sus propios campos de bloque), que `criteriosRespondidos()` no ofrece -cuenta TODAS las columnas de criterio de la evaluación, filtrando por nombre con `esColumnaDeCriterio()`-. Se añade un método hermano, `EstadoZona::criteriosRespondidosDe(Model, array $campos): int`, que hace el mismo `!== null` sobre una lista ya conocida, para no repetir ese `array_filter` en las siete vistas.
- **Los contadores en vivo de Concentración y Potencialidad** -Alpine, por bloque, recalculados sin recargar- se quedan como están y **no** se conectan a la barra lateral nueva. Son de ámbito de un bloque (una sección con su propio `x-data`); construir un almacén global de Alpine que los sume entre bloques para alimentar la barra lateral es una pieza de arquitectura de cliente nueva que el diseño no pide y que este plan deja fuera a propósito (ver "Qué queda fuera y por qué"). El recuento de la barra se resuelve en el servidor, en cada carga de página -correcto y mucho más simple-, y se actualiza cuando el evaluador guarda, como el resto de la página.
- **Un 0 respondido es un dato, no un hueco:** cada entrada del índice muestra siempre `X de Y`, sin excepción y sin sustituir por un guion, tanto si `X` es 0 como si es igual a `Y`. Solo cambia el marcador (`✓` si `X === Y`) y el color, nunca si se muestra el número.

---

## Estructura de ficheros — Parte B

**Crear:**
- `resources/views/components/barra-lateral-formulario.blade.php`
- `tests/Feature/BarraLateralFormularioTest.php`

**Modificar:**
- `app/Servicios/EstadoZona.php` — método `criteriosRespondidosDe()` (no colisiona con los métodos de la Parte A: distinto nombre, misma clase).
- `resources/views/operativo/evaluacion_fit/form.blade.php`
- `resources/views/operativo/evaluacion_fet/form.blade.php`
- `resources/views/operativo/evaluacion_paisaje/form.blade.php`
- `resources/views/operativo/evaluacion_percepcion/form.blade.php`
- `resources/views/operativo/evaluacion_irritacion/form.blade.php`
- `resources/views/operativo/evaluacion_concentracion/form.blade.php`
- `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`

**Test:**
- `tests/Unit/EstadoZonaTest.php` (para `criteriosRespondidosDe()`)
- `tests/Feature/FitTest.php`, `FetTest.php`, `PaisajeTest.php`, `PercepcionTest.php`, `IrritacionTest.php`, `ConcentracionTest.php`, `ValoracionTerritorialTest.php` (un test cada uno)

**Trampa transversal a TODAS las tareas de esta parte, léela antes de tocar cualquier vista:**

Los ficheros `EvaluacionesTest.php`, `IrritacionTest.php`, `ConcentracionTest.php`, `PaisajeTest.php`, `ValoracionTerritorialTest.php` y `PermisosAdminTest.php` tienen tests que hacen `assertDontSee('Guardar Borrador')` sobre una matriz **validada** vista por quien no es jefe -equipo o admin-, para comprobar que el formulario queda bloqueado. Si la barra lateral nueva pinta su propio botón "Guardar Borrador" **sin** la misma condición `@unless($bloqueado)` que ya envuelve al botón de siempre, esos tests se rompen: ahora habría un "Guardar Borrador" visible aunque el de abajo esté oculto. La condición tiene que ser la MISMA variable `$bloqueado` que cada vista ya calcula (confirmado idéntica en las siete: `$bloqueado = $estaConfirmado && ! $esJefe`), pasada como prop, no recalculada dentro del componente por un camino distinto. Cada tarea de esta parte incluye, como último paso antes del commit, volver a ejecutar el fichero de test de esa matriz completo -no solo el test nuevo- para que esto quede comprobado en el momento, no al final.

---

### Tarea 4: El helper de recuento y el componente

**Files:**
- Modify: `app/Servicios/EstadoZona.php`
- Create: `resources/views/components/barra-lateral-formulario.blade.php`
- Test: `tests/Unit/EstadoZonaTest.php`, `tests/Feature/BarraLateralFormularioTest.php`

- [ ] **Step 1: Test del helper de recuento, en rojo**

Añadir a `tests/Unit/EstadoZonaTest.php`:

```php
    /**
     * El desglose por bloque que necesita la barra lateral: cuenta solo un
     * subconjunto de campos, no toda la evaluación como criteriosRespondidos().
     */
    public function test_criterios_respondidos_de_cuenta_solo_el_subconjunto_dado(): void
    {
        $evaluacion = $this->fitCompleta();
        $evaluacion->recursos_culturales = null;

        $this->assertSame(
            1,
            EstadoZona::criteriosRespondidosDe($evaluacion, ['recursos_culturales', 'recursos_naturales'])
        );
        // El resto de la evaluación (16 criterios más) no cuenta: el
        // subconjunto es el límite, no un filtro adicional sobre todo.
        $this->assertSame(18, EstadoZona::criteriosRespondidos($evaluacion));
    }
```

- [ ] **Step 2: Ejecutar y comprobar que falla**

```bash
php artisan test --filter=test_criterios_respondidos_de_cuenta_solo_el_subconjunto_dado
```

Esperado: FAIL, `Call to undefined method`.

- [ ] **Step 3: Implementar el helper**

En `app/Servicios/EstadoZona.php`, junto a `criteriosRespondidos()`:

```php
    /**
     * Cuántos de los campos dados están respondidos en esta evaluación.
     *
     * Mismo criterio que criteriosRespondidos(), pero sobre un subconjunto
     * de columnas que YA se conoce de antemano -los campos de un bloque, no
     * toda la matriz-, así que no hace falta inferirlo por nombre con
     * esColumnaDeCriterio(). Existe para no repetir el mismo array_filter en
     * cada una de las vistas que pintan <x-barra-lateral-formulario>.
     *
     * @param  list<string>  $campos
     */
    public static function criteriosRespondidosDe(Model $evaluacion, array $campos): int
    {
        return count(array_filter(
            $campos,
            fn(string $campo) => $evaluacion->$campo !== null
        ));
    }
```

- [ ] **Step 4: Ejecutar y comprobar que pasa**

```bash
php artisan test --filter=EstadoZonaTest
```

Esperado: PASS.

- [ ] **Step 5: Test del componente en aislado, en rojo**

Crear `tests/Feature/BarraLateralFormularioTest.php`. Usa `$this->blade()` -helper de Laravel para renderizar un componente sin pasar por HTTP ni por un controlador- para probar el componente solo, con datos de mentira que no dependen de ninguna matriz real:

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
 * <x-barra-lateral-formulario> no deriva su índice de bloques -cada vista
 * se lo pasa ya resuelto-, así que se prueba en aislado con datos de mentira,
 * sin necesitar ninguna de las siete matrices reales que lo van a usar.
 */
class BarraLateralFormularioTest extends TestCase
{
    use RefreshDatabase;

    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SystemSeeder::class);

        $jefe = User::factory()->create(['role_id' => Role::where('nombre', 'jefe_zona')->value('id')]);
        $this->zona = Zona::create([
            'lugar_id' => DB::table('lugares')->value('id'),
            'jefe_user_id' => $jefe->id,
            'nombre' => 'Zona de prueba',
        ]);
    }

    private function renderizar(array $secciones, bool $bloqueado = false): string
    {
        return (string) $this->blade(
            '<x-barra-lateral-formulario clave="fit" :zona="$zona" :secciones="$secciones" :bloqueado="$bloqueado" formulario="form-fit" />',
            ['zona' => $this->zona, 'secciones' => $secciones, 'bloqueado' => $bloqueado]
        );
    }

    /** Un 0 respondido se muestra como dato, nunca se omite ni se sustituye. */
    public function test_una_seccion_sin_empezar_muestra_cero_de_su_total(): void
    {
        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos Turísticos', 'respondidos' => 0, 'total' => 2],
        ]);

        $this->assertStringContainsString('0/2', $html);
    }

    /** Una sección completa lleva su marcador, y sigue mostrando la fracción. */
    public function test_una_seccion_completa_lleva_marcador_y_fraccion(): void
    {
        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos Turísticos', 'respondidos' => 2, 'total' => 2],
        ]);

        $fragmento = \Illuminate\Support\Str::between($html, 'href="#rtt"', '</a>');
        $this->assertStringContainsString('✓', $fragmento);
        $this->assertStringContainsString('2/2', $fragmento);
    }

    /** Cada sección enlaza a su propia ancla, no a una genérica. */
    public function test_cada_seccion_enlaza_a_su_propia_ancla(): void
    {
        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos', 'respondidos' => 1, 'total' => 2],
            ['ancla' => 'at', 'etiqueta' => 'Atractivos', 'respondidos' => 0, 'total' => 1],
        ]);

        $this->assertStringContainsString('href="#rtt"', $html);
        $this->assertStringContainsString('href="#at"', $html);
    }

    /**
     * Bloqueada, no ofrece guardar. Se comprueba dentro del propio
     * componente para fijar el contrato antes de integrarlo en ninguna
     * vista real -las Tareas 5-11 vuelven a comprobar esto mismo en
     * contexto, contra el $bloqueado real de cada matriz-.
     */
    public function test_bloqueado_no_ofrece_el_boton_de_guardar(): void
    {
        $html = $this->renderizar([['ancla' => 'rtt', 'etiqueta' => 'R', 'respondidos' => 1, 'total' => 1]], bloqueado: true);

        $this->assertStringNotContainsString('Guardar Borrador', $html);
    }

    public function test_sin_bloquear_ofrece_el_boton_de_guardar_ligado_al_formulario_real(): void
    {
        $html = $this->renderizar([['ancla' => 'rtt', 'etiqueta' => 'R', 'respondidos' => 1, 'total' => 1]], bloqueado: false);

        $this->assertStringContainsString('Guardar Borrador', $html);
        $this->assertStringContainsString('form="form-fit"', $html);
    }
}
```

- [ ] **Step 6: Ejecutar y comprobar que fallan**

```bash
php artisan test --filter=BarraLateralFormularioTest
```

Esperado: FAIL -el componente no existe todavía (`Unable to locate a class or view for component`).

- [ ] **Step 7: Escribir el componente**

Crear `resources/views/components/barra-lateral-formulario.blade.php`:

```blade
@props(['clave', 'zona', 'secciones', 'bloqueado', 'formulario'])

{{--
    Barra lateral fija de un formulario de matriz: cuántos criterios lleva
    el evaluador, un índice de sus bloques con los completos marcados, y el
    botón de guardar siempre a la vista -sin subir ni bajar hasta los
    extremos del formulario-.

    No deriva el índice de bloques por su cuenta -a diferencia de
    <x-pestanas-matriz>, que sí deriva TODO de $clave y Registro-: las
    diez matrices no comparten una forma común para sus bloques (con
    'criterios' en FIT/FET/Paisaje, con 'items' en Percepción, planas en
    Irritación, en dos niveles en Concentración, sin envoltorio en
    Valoración Territorial). Cada vista resuelve su propio $secciones y
    este componente solo lo pinta, igual que <x-criterio-pildoras> no sabe
    de dónde viene su criterio.

    El total SÍ se deriva de Registro/EstadoZona -como <x-pestanas-matriz>-
    porque ese número es común a las diez matrices.

    Oculto por debajo de 1024px (lg): el formulario vuelve a su única
    columna de siempre, con el botón de guardar de siempre al final. La
    barra es una mejora cuando hay sitio, nunca un requisito para guardar.
--}}

@php
    $entrada = \App\Matrices\Registro::ENTRADAS[$clave];
    $modelo  = $entrada['modelo'];

    $evaluacion  = $modelo::where('zona_id', $zona->id)->first();
    $total       = $entrada['criterios'];
    $respondidos = $evaluacion
        ? \App\Servicios\EstadoZona::criteriosRespondidos($evaluacion)
        : 0;

    $porcentaje = $total > 0 ? round($respondidos / $total * 100) : 0;
@endphp

<aside class="hidden lg:block lg:sticky lg:top-6 lg:self-start w-64 shrink-0">
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">

        <p class="text-sm font-medium text-gray-900">
            {{ $respondidos }} de {{ $total }} respondidos
        </p>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden mt-2 mb-4">
            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $porcentaje }}%"></div>
        </div>

        <nav class="space-y-1 mb-4">
            @foreach ($secciones as $seccion)
                @php
                    // Un 0 respondido es un dato, no un hueco: las tres
                    // ramas muestran "X/Y" siempre y solo cambian el color
                    // y el marcador, nunca si se ve el número.
                    $completa = $seccion['respondidos'] === $seccion['total'];
                    $empezada = $seccion['respondidos'] > 0;
                    $color    = $completa ? 'text-green-700' : ($empezada ? 'text-gray-900' : 'text-gray-500');
                @endphp
                <a href="#{{ $seccion['ancla'] }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded text-sm hover:bg-gray-50 {{ $color }}">
                    <span class="truncate">
                        @if($completa)<span class="text-green-600">✓</span>@endif
                        {{ $seccion['etiqueta'] }}
                    </span>
                    <span class="text-sm text-gray-400 shrink-0">
                        {{ $seccion['respondidos'] }}/{{ $seccion['total'] }}
                    </span>
                </a>
            @endforeach
        </nav>

        @unless($bloqueado)
            <button type="submit" form="{{ $formulario }}" name="accion_estado" value="borrador"
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">
                Guardar Borrador
            </button>
        @endunless
    </div>
</aside>
```

- [ ] **Step 8: Ejecutar y comprobar que pasan**

```bash
php artisan test --filter=BarraLateralFormularioTest
```

Esperado: PASS.

- [ ] **Step 9: Suite completa y commit**

```bash
php artisan test
git add app/Servicios/EstadoZona.php resources/views/components/barra-lateral-formulario.blade.php tests/Unit/EstadoZonaTest.php tests/Feature/BarraLateralFormularioTest.php
git commit -m "feat(formularios): componente compartido de barra lateral, probado en aislado"
```

---

### Tarea 5: Integración en FIT (plantilla para las seis siguientes)

Esta es la tarea "molde": las Tareas 6-11 repiten la misma forma, adaptada a la forma de datos de cada matriz.

**Files:**
- Modify: `resources/views/operativo/evaluacion_fit/form.blade.php`
- Test: `tests/Feature/FitTest.php`

- [ ] **Step 1: Test, en rojo**

Añadir a `tests/Feature/FitTest.php` (mirar su `setUp()` para el `$this->jefe`/`$this->zona` existentes):

```php
    /**
     * La barra lateral aparece con el ancho nuevo, con el total correcto y
     * con un enlace por bloque -6 bloques en FIT-. No se usa assertSee con
     * el texto suelto "6/18" porque podría coincidir por casualidad con
     * otro número de la página: se cuentan los enlaces de ancla, que solo
     * los pinta la barra lateral.
     */
    public function test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_seis_bloques(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_fit.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('max-w-7xl', $html);

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento, 'No se encontró <aside>: la barra lateral no se está pintando.');

        foreach (array_keys(\App\Matrices\Fit::BLOQUES) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace al bloque '{$clave}'.");
        }
    }

    /**
     * Con 5 de los 18 criterios respondidos, el bloque RTT -2 criterios,
     * ambos respondidos- aparece completo y con marcador; el resto de la
     * barra sigue mostrando su fracción real, no un hueco.
     */
    public function test_la_barra_lateral_desglosa_los_respondidos_por_bloque(): void
    {
        $evaluacion = \App\Models\EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);
        $evaluacion->update(['recursos_culturales' => 2, 'recursos_naturales' => 3]);

        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_fit.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $rtt = \Illuminate\Support\Str::between($fragmento, 'href="#rtt"', '</a>');

        $this->assertStringContainsString('✓', $rtt);
        $this->assertStringContainsString('2/2', $rtt);
    }
```

- [ ] **Step 2: Ejecutar y comprobar que fallan**

```bash
php artisan test --filter=FitTest
```

Esperado: los dos tests nuevos fallan (no hay `<aside>` ni `max-w-7xl` todavía). El resto de `FitTest.php` sigue en verde -esta tarea todavía no ha tocado el fichero-.

- [ ] **Step 3: Cambiar el ancho y envolver el contenido**

En `resources/views/operativo/evaluacion_fit/form.blade.php`, línea 9, cambiar:

```blade
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
```
por:
```blade
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
```

Envolver TODO el contenido existente -desde `<x-pestanas-matriz>` hasta el `</form>` final, sin modificar nada de dentro- en un contenedor de dos columnas, y añadir el índice de bloques justo antes del `<form>`:

```blade
            <x-pestanas-matriz clave="fit" :zona="$zona" activa="formulario" />

            <x-boton-volver :zona="$zona" texto="Regresar"
                class="!inline-flex !items-center !px-4 !py-2 !mb-4 !bg-blue-300 hover:!bg-blue-500 !text-black !font-bold !rounded-lg !shadow-sm" />

            @php
                $esJefe = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                $bloqueado = $estaConfirmado && ! $esJefe;

                // El índice de la barra lateral: un bloque de FIT es
                // 'clave' => ['nombre','peso','criterios' => [campo => ...]],
                // así que basta con array_keys(criterios) y el recuento
                // hermano de criteriosRespondidos() para el subconjunto.
                $indiceBloques = collect($bloques)->map(fn($bloque, $clave) => [
                    'ancla'       => $clave,
                    'etiqueta'    => $bloque['nombre'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($bloque['criterios'])),
                    'total'       => count($bloque['criterios']),
                ])->values()->all();
            @endphp

            <div class="lg:grid lg:grid-cols-[1fr_256px] lg:gap-6 lg:items-start">
            <div class="lg:min-w-0">

            @if($evaluacion?->exists && $evaluacion->user)
                <p class="text-sm text-gray-500 mb-4">
                    Última edición: {{ $evaluacion->user->name }},
                    {{ $evaluacion->updated_at->diffForHumans() }}
                </p>
            @endif

            @if($estaConfirmado)
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                <div class="flex justify-between items-center">
                    <div>
                        <strong class="font-bold text-lg">✓ Evaluación Validada</strong>
                        <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                    </div>
                </div>
            </div>
            @else
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                <strong class="font-bold">Modo Borrador</strong>
                <p>Los datos ingresados son preliminares. El Jefe de Zona debe revisar y confirmar para generar los resultados oficiales.</p>
            </div>
            @endif

            <x-leyenda-escala :niveles="$niveles" />

            <x-flash-exito />

            <form method="POST" action="{{ route('operativo.evaluacion_fit.update', $zona->id) }}" id="form-fit">
                @csrf

                @php
                    $inicial = fn($criterios) => collect($criterios)->mapWithKeys(
                        function ($c, $campo) use ($evaluacion) {
                            $valor = old($campo, $evaluacion->$campo);

                            return [$campo => $valor === null || $valor === '' ? null : (int) $valor];
                        }
                    );
                @endphp

                @foreach($bloques as $clave => $bloque)
                    <section id="{{ $clave }}" class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                             x-data="{
                                valores: @js($inicial($bloque['criterios'])),
                                get promedio() {
                                    const v = Object.values(this.valores);
                                    return v.some(x => x === null)
                                        ? null
                                        : v.reduce((t, x) => t + x, 0) / v.length;
                                },
                                get respondidos() {
                                    return Object.values(this.valores).filter(v => v !== null).length;
                                },
                             }">
                        <div class="flex flex-wrap justify-between items-baseline gap-3 mb-2">
                            <h3 class="text-xl font-bold text-gray-900">
                                {{ $bloque['nombre'] }}
                                <span class="text-base font-normal text-gray-400">({{ strtoupper($clave) }})</span>
                            </h3>
                            <div class="flex items-baseline gap-4">
                                <span class="text-sm text-gray-500">
                                    <span x-text="respondidos" class="font-semibold text-gray-700"></span>
                                    de {{ count($bloque['criterios']) }} respondidos
                                </span>
                                <span class="text-base font-bold text-indigo-700">
                                    Media
                                    <span x-text="promedio === null ? '—' : promedio.toFixed(2)"></span>
                                    <span class="text-gray-400 font-normal">/ 3.00</span>
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mb-5">
                            Pesa un {{ rtrim(rtrim(number_format($bloque['peso'] * 100, 1), '0'), '.') }}% del resultado final.
                        </p>

                        @foreach($bloque['criterios'] as $campo => $criterio)
                            <x-criterio-pildoras :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />
                        @endforeach
                    </section>
                @endforeach

                <div class="flex justify-end mt-8 gap-4 pt-4 border-t">
                    @if(!$bloqueado)
                    @if($estaConfirmado && $esJefe)
                        <x-aviso-reapertura class="w-full mb-1" />
                    @endif
                    <button type="submit" name="accion_estado" value="borrador" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                        Guardar Borrador
                    </button>

                    @if($esJefe)
                    <button type="submit" name="accion_estado" value="confirmado"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg transform hover:scale-105 transition"
                        onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta evaluación.')">
                        Validar y Finalizar
                    </button>
                    @endif
                    @else
                    <span class="text-gray-500 italic self-center"><x-aviso-bloqueo-matriz sustantivo="evaluación" /></span>
                    @if($esJefe)
                    <button type="submit" name="accion_estado" value="confirmado" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                        Actualizar Datos
                    </button>
                    @endif
                    @endif
                </div>
            </form>

            </div>{{-- /lg:min-w-0 --}}

            <x-barra-lateral-formulario clave="fit" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-fit" />

            </div>{{-- /lg:grid --}}
```

Notas de esta edición, para no perderlas al repetirla en las Tareas 6-11:
- Solo cambian: la línea del `max-w`, el `@php` (una variable nueva, `$indiceBloques`), dos `<div>` de envoltura, el `id="form-fit"` en el `<form>`, un `id="{{ $clave }}"` en cada `<section>`, y la línea final con `<x-barra-lateral-formulario>`. **Ninguna línea del contenido original cambia de sitio ni de lógica.**
- El `id` del `<form>` y el `formulario="..."` del componente tienen que coincidir literalmente: es el enlace entre el botón de la barra lateral y el formulario real.

- [ ] **Step 4: Ejecutar el test nuevo**

```bash
php artisan test --filter=FitTest
```

Esperado: PASS, los dos tests nuevos y TODOS los demás de `FitTest.php` -en particular, cualquiera que compruebe `Guardar Borrador` con una matriz bloqueada-.

- [ ] **Step 5: Ejecutar los ficheros de la trampa transversal**

```bash
php artisan test --filter=EvaluacionesTest
php artisan test --filter=ReabrirMatrizTest
php artisan test --filter=PestanasMatrizTest
php artisan test --filter=BotonVolverTest
```

Esperado: PASS en los cuatro. Estos ficheros ejercitan FIT desde ángulos que no son "el formulario de FIT" -mensajes de validación, reapertura, pestañas, volver-: son la prueba de que envolver el contenido en dos `<div>` no le cambió el comportamiento a nada de alrededor.

- [ ] **Step 6: Suite completa y commit**

```bash
php artisan test
git add resources/views/operativo/evaluacion_fit/form.blade.php tests/Feature/FitTest.php
git commit -m "feat(fit): ensancha a max-w-7xl y anade la barra lateral con su indice de bloques"
```

---

### Tarea 6: Integración en FET

FET comparte forma exacta con FIT (`Fet::BLOQUES` con `nombre`/`peso`/`criterios`), así que esta tarea es la Tarea 5 con los nombres cambiados.

**Files:**
- Modify: `resources/views/operativo/evaluacion_fet/form.blade.php`
- Test: `tests/Feature/FetTest.php`

- [ ] **Step 1: Test, en rojo**

Añadir a `tests/Feature/FetTest.php`:

```php
    public function test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_bloques(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_fet.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('max-w-7xl', $html);

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento);

        foreach (array_keys(\App\Matrices\Fet::BLOQUES) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace al bloque '{$clave}'.");
        }
    }
```

- [ ] **Step 2: Ejecutar y comprobar que falla**

```bash
php artisan test --filter=FetTest
```

- [ ] **Step 3: Editar la vista**

En `resources/views/operativo/evaluacion_fet/form.blade.php`: mismo patrón que la Tarea 5 -cambiar `max-w-5xl` (línea 9) a `max-w-7xl`; envolver todo el contenido en `<div class="lg:grid lg:grid-cols-[1fr_256px] lg:gap-6 lg:items-start"><div class="lg:min-w-0">...</div><x-barra-lateral-formulario .../></div>`; añadir `id="form-fet"` al `<form>`; añadir `id="{{ $clave }}"` a cada `<section>` del `@foreach($bloques as $clave => $bloque)`-, con el `@php` del índice:

```php
                $indiceBloques = collect($bloques)->map(fn($bloque, $clave) => [
                    'ancla'       => $clave,
                    'etiqueta'    => $bloque['nombre'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($bloque['criterios'])),
                    'total'       => count($bloque['criterios']),
                ])->values()->all();
```

y la línea de cierre:

```blade
            <x-barra-lateral-formulario clave="fet" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-fet" />
```

- [ ] **Step 4: Ejecutar el test, la trampa transversal, y la suite completa**

```bash
php artisan test --filter=FetTest
php artisan test --filter=EvaluacionesTest
php artisan test
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/operativo/evaluacion_fet/form.blade.php tests/Feature/FetTest.php
git commit -m "feat(fet): ensancha a max-w-7xl y anade la barra lateral con su indice de bloques"
```

---

### Tarea 7: Integración en Paisaje

`Paisaje::CATEGORIAS` tiene la misma forma que `Fit::BLOQUES` (`nombre`/`peso`/`criterios`).

**Files:**
- Modify: `resources/views/operativo/evaluacion_paisaje/form.blade.php`
- Test: `tests/Feature/PaisajeTest.php`

- [ ] **Step 1: Test, en rojo**

```php
    public function test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_categorias(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_paisaje.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('max-w-7xl', $html);

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento);

        foreach (array_keys(\App\Matrices\Paisaje::CATEGORIAS) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace a la categoría '{$clave}'.");
        }
    }
```

- [ ] **Step 2: Ejecutar y comprobar que falla**

```bash
php artisan test --filter=PaisajeTest
```

- [ ] **Step 3: Editar la vista**

Mismo patrón: `max-w-5xl` (línea 9) → `max-w-7xl`; envoltura de dos columnas; `id="form-paisaje"`; `id="{{ $clave }}"` en cada categoría del `@foreach($categorias as $clave => $categoria)`.

```php
                $indiceBloques = collect($categorias)->map(fn($categoria, $clave) => [
                    'ancla'       => $clave,
                    'etiqueta'    => $categoria['nombre'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($categoria['criterios'])),
                    'total'       => count($categoria['criterios']),
                ])->values()->all();
```

```blade
            <x-barra-lateral-formulario clave="paisaje" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-paisaje" />
```

- [ ] **Step 4: Ejecutar el test, `PestanasMatrizTest` (usa Paisaje como su matriz de referencia — máxima atención aquí), y la suite completa**

```bash
php artisan test --filter=PaisajeTest
php artisan test --filter=PestanasMatrizTest
php artisan test
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/operativo/evaluacion_paisaje/form.blade.php tests/Feature/PaisajeTest.php
git commit -m "feat(paisaje): ensancha a max-w-7xl y anade la barra lateral con su indice de categorias"
```

---

### Tarea 8: Integración en Percepción

Percepción **ya está en `max-w-7xl`**: esta tarea solo añade la barra lateral, no toca el ancho. Su variante: el bloque se llama `items`, no `criterios`.

**Files:**
- Modify: `resources/views/operativo/evaluacion_percepcion/form.blade.php`
- Test: `tests/Feature/PercepcionTest.php`

- [ ] **Step 1: Test, en rojo**

```php
    public function test_el_formulario_muestra_la_barra_lateral_con_sus_categorias(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_percepcion.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento);

        foreach (array_keys(\App\Matrices\Percepcion::$categorias) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace a la categoría '{$clave}'.");
        }
    }
```

- [ ] **Step 2: Ejecutar y comprobar que falla**

```bash
php artisan test --filter=PercepcionTest
```

- [ ] **Step 3: Editar la vista**

No se toca el `max-w-7xl` (ya está). Envolver el contenido en la rejilla de dos columnas, añadir `id="form-percepcion"` al `<form>`, `id="{{ $codigo }}"` a cada categoría del `@foreach($categorias as $codigo => $cat)`, y el índice, con `items` en vez de `criterios`:

```php
                $indiceBloques = collect($categorias)->map(fn($cat, $codigo) => [
                    'ancla'       => $codigo,
                    'etiqueta'    => $cat['nombre'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($cat['items'])),
                    'total'       => count($cat['items']),
                ])->values()->all();
```

```blade
            <x-barra-lateral-formulario clave="percepcion" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-percepcion" />
```

- [ ] **Step 4: Ejecutar el test, `BotonVolverTest` (usa Percepción para el caso "sin datos" — ver su docblock), y la suite completa**

```bash
php artisan test --filter=PercepcionTest
php artisan test --filter=BotonVolverTest
php artisan test
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/operativo/evaluacion_percepcion/form.blade.php tests/Feature/PercepcionTest.php
git commit -m "feat(percepcion): anade la barra lateral con su indice de categorias"
```

---

### Tarea 9: Integración en Irritación

Variante: `Irritacion::BLOQUES` no tiene `criterios` ni `peso`; tiene `titulo`/`subtitulo`/`campos`, y `campos` **ya es una lista plana de nombres**, no un mapa campo⇒etiqueta.

**Files:**
- Modify: `resources/views/operativo/evaluacion_irritacion/form.blade.php`
- Test: `tests/Feature/IrritacionTest.php`

- [ ] **Step 1: Test, en rojo**

```php
    public function test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_sus_bloques(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_irritacion.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('max-w-7xl', $html);

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento);

        foreach (array_keys(\App\Matrices\Irritacion::BLOQUES) as $clave) {
            $this->assertStringContainsString("href=\"#{$clave}\"", $fragmento, "Falta el enlace al bloque '{$clave}'.");
        }
    }
```

- [ ] **Step 2: Ejecutar y comprobar que falla**

```bash
php artisan test --filter=IrritacionTest
```

- [ ] **Step 3: Editar la vista**

`max-w-6xl` (línea 9) → `max-w-7xl`; envoltura de dos columnas; `id="form-irritacion"`; `id="{{ $clave }}"` en cada `<section>` del `@foreach($bloques as $clave => $bloque)`. El índice usa `titulo` y `campos` directamente -sin `array_keys()`, porque `campos` ya es la lista de nombres-:

```php
                $indiceBloques = collect($bloques)->map(fn($bloque, $clave) => [
                    'ancla'       => $clave,
                    'etiqueta'    => $bloque['titulo'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $bloque['campos']),
                    'total'       => count($bloque['campos']),
                ])->values()->all();
```

```blade
            <x-barra-lateral-formulario clave="irritacion" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-irritacion" />
```

- [ ] **Step 4: Ejecutar el test y la suite completa**

```bash
php artisan test --filter=IrritacionTest
php artisan test
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/operativo/evaluacion_irritacion/form.blade.php tests/Feature/IrritacionTest.php
git commit -m "feat(irritacion): ensancha a max-w-7xl y anade la barra lateral con su indice de bloques"
```

---

### Tarea 10: Integración en Concentración

Variante mayor: `Concentracion::ATRACTIVOS` anida `categoria => tipo => campo⇒etiqueta` (3 niveles) y `Concentracion::PLANTA` anida `sector => campo⇒etiqueta` (2 niveles), sin ningún envoltorio `nombre`/`peso`. Con 113 campos repartidos en más de una docena de categorías/tipos/sectores, indexar cada uno por separado saturaría una barra de 256 px; el índice se queda en los DOS grupos de primer nivel del instrumento -Atractivos y Planta turística-, aplanando sus campos para contarlos.

**Files:**
- Modify: `resources/views/operativo/evaluacion_concentracion/form.blade.php`
- Test: `tests/Feature/ConcentracionTest.php`

- [ ] **Step 1: Test, en rojo**

```php
    public function test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_atractivos_y_planta(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_concentracion.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('max-w-7xl', $html);

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento);

        $this->assertStringContainsString('href="#atractivos"', $fragmento);
        $this->assertStringContainsString('href="#planta"', $fragmento);
        $this->assertStringContainsString('/77', $fragmento); // 77 campos de atractivos
        $this->assertStringContainsString('/36', $fragmento); // 36 campos de planta
    }
```

- [ ] **Step 2: Ejecutar y comprobar que falla**

```bash
php artisan test --filter=ConcentracionTest
```

- [ ] **Step 3: Editar la vista**

`max-w-6xl` (línea 9) → `max-w-7xl`. El `<form>` **ya tiene** `id="form-concentracion"` (confirmado en el código actual): no hay que añadirlo, solo usarlo. Envoltura de dos columnas; `id="atractivos"` en la sección de atractivos y `id="planta"` en la de planta -no una por categoría/tipo/sector-.

**Ojo con la profundidad:** `$atractivos` anida `categoria => [tipo => [campo => etiqueta]]` -tres niveles- y `$planta` anida `sector => [campo => etiqueta]` -dos-. `Illuminate\Support\Arr::flatten()` no sirve aquí: aplanado a fondo, colapsa hasta los VALORES (las etiquetas) y las claves de campo no sobreviven. Recolectar los nombres de campo con un bucle explícito, uno por cada forma:

```php
                $camposAtractivos = [];
                foreach ($atractivos as $tipos) {
                    foreach ($tipos as $campos) {
                        $camposAtractivos = array_merge($camposAtractivos, array_keys($campos));
                    }
                }

                $camposPlanta = [];
                foreach ($planta as $campos) {
                    $camposPlanta = array_merge($camposPlanta, array_keys($campos));
                }

                $indiceBloques = [
                    [
                        'ancla'       => 'atractivos',
                        'etiqueta'    => 'Atractivos turísticos',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $camposAtractivos),
                        'total'       => count($camposAtractivos),
                    ],
                    [
                        'ancla'       => 'planta',
                        'etiqueta'    => 'Planta turística',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $camposPlanta),
                        'total'       => count($camposPlanta),
                    ],
                ];
```

Comprobar, antes del commit, que `count($camposAtractivos)` y `count($camposPlanta)` dan 77 y 36 -los mismos números que ya fija `tests/Unit/ConcentracionCalculoTest.php`-. Si no coinciden, el bucle tiene un error: ese test existente es la referencia, no el nuevo.

```blade
            <x-barra-lateral-formulario clave="concentracion" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-concentracion" />
```

- [ ] **Step 4: Ejecutar el test y la suite completa**

```bash
php artisan test --filter=ConcentracionTest
php artisan test
```

Prestar atención a `ConcentracionCalculoTest.php`: si los totales 77/36 no coinciden con lo que muestra la barra lateral, el bucle de aplanado tiene un error, no el test.

- [ ] **Step 5: Commit**

```bash
git add resources/views/operativo/evaluacion_concentracion/form.blade.php tests/Feature/ConcentracionTest.php
git commit -m "feat(concentracion): ensancha a max-w-7xl y anade la barra lateral con atractivos y planta"
```

---

### Tarea 11: Integración en Valoración Territorial

Variante: `ValoracionTerritorial::CT` y `::UC` son mapas planos `campo => criterio`, sin ningún agrupador ni título propio -los títulos "Recursos Turísticos Territoriales (RTT)" y "Ubicación y Conectividad (UC)" están escritos a mano en el `.blade.php`, no en la clase-. El índice tiene exactamente dos entradas, una por cada uno.

**Files:**
- Modify: `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`
- Test: `tests/Feature/ValoracionTerritorialTest.php`

- [ ] **Step 1: Test, en rojo**

```php
    public function test_el_formulario_ensancha_y_muestra_la_barra_lateral_con_rtt_y_uc(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_valoracion_territorial.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('max-w-7xl', $html);

        $fragmento = \Illuminate\Support\Str::between($html, '<aside', '</aside>');
        $this->assertNotEmpty($fragmento);

        $this->assertStringContainsString('href="#rtt"', $fragmento);
        $this->assertStringContainsString('href="#uc"', $fragmento);
        $this->assertStringContainsString('/' . count(\App\Matrices\ValoracionTerritorial::CT), $fragmento);
        $this->assertStringContainsString('/' . count(\App\Matrices\ValoracionTerritorial::UC), $fragmento);
    }
```

- [ ] **Step 2: Ejecutar y comprobar que falla**

```bash
php artisan test --filter=ValoracionTerritorialTest
```

- [ ] **Step 3: Editar la vista**

`max-w-5xl` (línea 9) → `max-w-7xl`; envoltura de dos columnas; `id="form-valoracion-territorial"` en el `<form>`; `id="rtt"` en la sección de `$ct` e `id="uc"` en la de `$uc` -las dos secciones ya existen tal cual, solo se les añade el `id`-.

```php
                $indiceBloques = [
                    [
                        'ancla'       => 'rtt',
                        'etiqueta'    => 'Recursos Turísticos (RTT)',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($ct)),
                        'total'       => count($ct),
                    ],
                    [
                        'ancla'       => 'uc',
                        'etiqueta'    => 'Ubicación y Conectividad (UC)',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($uc)),
                        'total'       => count($uc),
                    ],
                ];
```

```blade
            <x-barra-lateral-formulario clave="valoracion_territorial" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-valoracion-territorial" />
```

- [ ] **Step 4: Ejecutar el test y la suite completa**

```bash
php artisan test --filter=ValoracionTerritorialTest
php artisan test
```

- [ ] **Step 5: Commit**

```bash
git add resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php tests/Feature/ValoracionTerritorialTest.php
git commit -m "feat(valoracion-territorial): ensancha a max-w-7xl y anade la barra lateral con RTT y UC"
```

---

## Tarea 12: Revisión final (ambas partes)

- [ ] **Step 1: Suite completa y build**

```bash
php artisan test
npm run build
```

Esperado: PASS y build sin errores. La suite parte de 483 tests; anotar el número final (crece con los tests de las Tareas 1-11).

- [ ] **Step 2: Re-ejecutar, con nombre explícito, los ficheros identificados como riesgo en este plan**

```bash
php artisan test --filter=DashboardTest
php artisan test --filter=EstadoZonaTest
php artisan test --filter=RegistroMatricesTest
php artisan test --filter=PestanasMatrizTest
php artisan test --filter=BotonVolverTest
php artisan test --filter=EvaluacionesTest
php artisan test --filter=ReabrirMatrizTest
php artisan test --filter=PermisosAdminTest
```

Esperado: PASS en los ocho. Si `DashboardTest::test_el_numero_de_consultas_no_crece_con_el_numero_de_zonas` necesitó ampliar su margen de +3, es una señal de que `proximoPaso()` quedó recorriendo zonas en vez de usar consultas de coste fijo: investigar antes de continuar, no ampliar el margen sin más.

- [ ] **Step 3: Recorrido manual en tres anchos, con `jefe@local.test` / `password`**

1. **1366 px (portátil normal):** `/mis-zonas` con una zona sin nada empezado -ver "Empieza por aquí"-; guardar un borrador de FET y volver -ver "Sigue por aquí" (FET) y "Todavía sin empezar" (FIT) como dos tarjetas distintas-. Abrir el formulario de FIT: comprobar que la barra lateral aparece a la derecha, que los enlaces de su índice desplazan a cada bloque, y que "Guardar Borrador" de la barra guarda igual que el de abajo.
2. **1024 px (el límite exacto de `lg`):** la barra lateral debe seguir visible a 1024 px y desaparecer justo por debajo (1023 px), volviendo el formulario a una sola columna con el botón de guardar de siempre.
3. **768 px (tablet):** ningún formulario debe mostrar desplazamiento horizontal ni la barra lateral. El botón de guardar del final del formulario sigue siendo alcanzable y funcional.

- [ ] **Step 4: Confirmar visualmente la regla de las 14 px y el `uppercase`**

Con las herramientas del navegador, comprobar que ningún texto nuevo de este plan -barra lateral, panel del dashboard- usa un tamaño por debajo de `text-sm` (14 px) salvo insignias, y que ninguna clase `uppercase` se añadió. No hay test automático para esto en el proyecto: es una revisión manual, a propósito documentada aquí para que no se salte.

- [ ] **Step 5: Actualizar `docs/ESTADO-PROYECTO.md`**

Añadir una entrada: dashboard con panel de "siguiente paso" (Parte A) y barra lateral fija en siete formularios de matriz -FIT, FET, Paisaje, Valoración Territorial, Percepción, Irritación, Concentración- (Parte B), con la limitación conocida de atribución de usuario en Involucrados/Frecuentación documentada junto al resto de deudas del registro.

---

## Qué queda fuera y por qué

- **Migrar `user_id` a `Involucrado` y `SitioFrecuentacion`** para que "última tocada" sea exacta también en esas dos matrices. Coste estimado en el punto 1 de la Parte A: 2 migraciones + 2 controladores + tests. El diseño no lo pide y este plan no lo amplía a matrices ya cerradas sin que el responsable lo decida.
- **Recuento en vivo, vía Alpine, en la barra lateral.** Los contadores por bloque de Concentración y Potencialidad son de ámbito local a su `x-data`; sumarlos en un almacén global para que la barra lateral se actualice sin recargar es una pieza de arquitectura de cliente nueva, no una reutilización. El recuento en el servidor -recalculado en cada carga- cubre lo que el diseño pide sin ese coste.
- **Reemplazar el `.pt-sidebar` de Potencialidad por `<x-barra-lateral-formulario>`.** Ya resuelve el mismo problema; sustituirlo es una reescritura de un formulario de 156 criterios por consistencia visual, no por una carencia funcional. Se deja para una decisión aparte si algún día se quiere unificar.
- **Barra lateral o ensanchado en Involucrados y Frecuentación.** Justificado en el punto 6: son CRUD de filas, no formularios de criterios, y su ancho actual ya es proporcional a lo que muestran.
- **Índice por categoría/tipo/sector en Concentración**, en vez de los dos grupos de primer nivel (Atractivos/Planta). Defendible si 256 px resultan pocos en la práctica, pero empezar con el nivel más fino multiplica el trabajo de prueba sin certeza de que haga falta.
- **Panel de "siguiente paso" para el admin en `admin.zonas.index`.** Resuelto como "no" en el punto 3 de la Parte A, con motivo, no pendiente.
- **Dos columnas de criterios en ningún formulario.** El diseño ya lo descarta explícitamente y este plan no lo reabre.
