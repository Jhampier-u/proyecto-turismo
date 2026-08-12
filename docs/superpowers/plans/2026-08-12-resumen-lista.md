# Franja de resumen para Involucrados y Frecuentación — Plan de implementación

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Que las dos listas digan de un vistazo cuánto falta —«5 sitios · 2 sin DET»— y ofrezcan validar arriba, en vez de al final de la página.

**Architecture:** Un componente Blade que **pinta lo que le dan y no deriva nada**, igual que `<x-barra-lateral-formulario>`. Cada vista resuelve sus propios números; los controladores ya los calculan y solo hace falta pasarlos. La parte que varía entre las dos —la Superficie Territorial— va en una ranura, no en un prop genérico.

**Tech Stack:** Laravel 12, PHP 8.2, Blade, Tailwind CSS 3, PHPUnit 11.

**Spec:** `docs/superpowers/specs/2026-08-12-resumen-lista-design.md`

## Global Constraints

- Nada de texto por debajo de 14 px salvo insignias. Sin `uppercase` ni `tracking-widest`.
- Clases de Tailwind **completas**, nunca construidas por concatenación. El purgado elimina las dinámicas.
- **Sin botones desactivados**: donde una acción no corresponde, va el texto que dice quién o qué la habilita.
- **Solo el jefe de zona valida.**
- El componente **no deriva** sus datos: los recibe. Las dos listas cuentan cosas distintas y forzar una forma común entre ellas es lo que produjo el tipo `actores` con `zona->involucrados()` cableado dentro.
- Comentarios en castellano, explicando el *por qué*.
- Los tests van con `php artisan test`. **PHP 8.2.33 nativo.** No uses Docker para nada y **no toques ningún contenedor de Docker**.
- **No modifiques `package-lock.json`** — sale modificado desde antes; fuera de todos los commits.
- Suite en la base: **525 tests**.

## Estructura de ficheros

**Crear:**
- `resources/views/components/resumen-lista.blade.php` — la franja. Sin lógica de negocio.
- `tests/Feature/ResumenListaTest.php` — el componente en aislado.

**Modificar:**
- `app/Http/Controllers/Operativo/InvolucradosController.php` — pasar `$incompletos`, que ya calcula.
- `app/Http/Controllers/Operativo/FrecuentacionController.php` — pasar `$incompletos` y `$stDefinida`, que ya calcula.
- `resources/views/operativo/involucrados/index.blade.php` — poner la franja, quitar el bloque del final.
- `resources/views/operativo/frecuentacion/index.blade.php` — ídem, con la ranura de ST.
- `tests/Feature/InvolucradosTest.php` y `tests/Feature/FrecuentacionTest.php` — los tests que buscan el botón donde ya no está.

---

### Task 1: El componente

**Files:**
- Create: `resources/views/components/resumen-lista.blade.php`
- Test: `tests/Feature/ResumenListaTest.php`

**Interfaces:**
- Consumes: nada. Es una hoja: recibe todo por props.
- Produces:
  ```blade
  <x-resumen-lista sustantivo="sitio" plural="sitios" faltante="sin DET"
                   :total="5" :incompletos="2"
                   :puede-validar="false" ruta-validar="/…"
                   :aviso-validacion="false" jefe="Ana Pérez">
      ST: 1.200
  </x-resumen-lista>
  ```
  `plural` vale por defecto `$sustantivo . 's'`. `faltante` vale por defecto `sin completar`.

- [ ] **Step 1: Escribir el test**

Crear `tests/Feature/ResumenListaTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * <x-resumen-lista> no deriva nada —cada vista le pasa sus números ya
 * resueltos—, así que se prueba en aislado con datos de mentira, sin necesitar
 * ni Involucrados ni Frecuentación.
 *
 * Mismo patrón que BarraLateralFormularioTest, y por el mismo motivo: un
 * componente que solo pinta se prueba pintándolo.
 */
class ResumenListaTest extends TestCase
{
    private function renderizar(array $props = [], string $ranura = ''): string
    {
        $props = array_merge([
            'sustantivo'      => 'sitio',
            'plural'          => 'sitios',
            'faltante'        => 'sin DET',
            'total'           => 5,
            'incompletos'     => 2,
            'puedeValidar'    => false,
            'rutaValidar'     => '/validar',
            'avisoValidacion' => false,
            'jefe'            => 'Ana Pérez',
        ], $props);

        return (string) $this->blade(
            '<x-resumen-lista :sustantivo="$sustantivo" :plural="$plural" :faltante="$faltante"
                              :total="$total" :incompletos="$incompletos"
                              :puede-validar="$puedeValidar" :ruta-validar="$rutaValidar"
                              :aviso-validacion="$avisoValidacion" :jefe="$jefe">'
            . $ranura .
            '</x-resumen-lista>',
            $props
        );
    }

    public function test_cuenta_el_total_y_lo_que_falta(): void
    {
        $html = $this->renderizar();

        $this->assertStringContainsString('5 sitios', $html);
        $this->assertStringContainsString('2 sin DET', $html);
    }

    public function test_con_todo_completo_lo_dice_en_vez_de_callar(): void
    {
        $html = $this->renderizar(['incompletos' => 0]);

        $this->assertStringContainsString('5 sitios', $html);
        $this->assertStringNotContainsString('sin DET', $html);
        $this->assertStringContainsString('todos completos', $html);
    }

    /**
     * Con la lista vacía no se dice «todos completos»: no hay nada completo, y
     * afirmarlo invitaría a validar algo que no se puede.
     */
    public function test_con_la_lista_vacia_no_dice_que_este_todo_completo(): void
    {
        $html = $this->renderizar(['total' => 0, 'incompletos' => 0]);

        $this->assertStringContainsString('0 sitios', $html);
        $this->assertStringNotContainsString('todos completos', $html);
    }

    public function test_el_singular_no_dice_1_sitios(): void
    {
        $html = $this->renderizar(['total' => 1, 'incompletos' => 0]);

        $this->assertStringContainsString('1 sitio', $html);
        $this->assertStringNotContainsString('1 sitios', $html);
    }

    /** El plural del castellano no es añadir una s: actor da actores. */
    public function test_el_plural_se_puede_dar_a_mano(): void
    {
        $html = $this->renderizar([
            'sustantivo' => 'actor',
            'plural'     => 'actores',
            'total'      => 3,
        ]);

        $this->assertStringContainsString('3 actores', $html);
        $this->assertStringNotContainsString('3 actors', $html);
    }

    public function test_faltante_vale_sin_completar_por_defecto(): void
    {
        $html = (string) $this->blade(
            '<x-resumen-lista sustantivo="actor" plural="actores" :total="3" :incompletos="1" />'
        );

        $this->assertStringContainsString('1 sin completar', $html);
    }

    public function test_el_jefe_ve_el_boton_de_validar(): void
    {
        $html = $this->renderizar(['puedeValidar' => true, 'incompletos' => 0]);

        $this->assertStringContainsString('Validar y Cerrar la Lista', $html);
        $this->assertStringContainsString('/validar', $html);
    }

    public function test_sin_permiso_no_hay_boton(): void
    {
        $html = $this->renderizar(['puedeValidar' => false]);

        $this->assertStringNotContainsString('Validar y Cerrar la Lista', $html);
    }

    /**
     * El equipo no recibe un botón gris: recibe el texto que dice quién valida.
     * Es la regla global de «sin botones desactivados».
     */
    public function test_el_equipo_ve_a_quien_avisar_en_vez_de_un_boton(): void
    {
        $html = $this->renderizar([
            'puedeValidar'    => false,
            'avisoValidacion' => true,
            'incompletos'     => 0,
        ]);

        $this->assertStringNotContainsString('Validar y Cerrar la Lista', $html);
        $this->assertStringContainsString('avísale a Ana Pérez', $html);
    }

    public function test_la_ranura_se_pinta_cuando_se_le_da_algo(): void
    {
        $this->assertStringContainsString('ST: 1.200', $this->renderizar([], 'ST: 1.200'));
    }

    /** Sin ranura no queda un separador suelto ni un hueco. */
    public function test_sin_ranura_no_queda_rastro(): void
    {
        $html = $this->renderizar();

        $this->assertStringNotContainsString('ST:', $html);
    }
}
```

- [ ] **Step 2: Ejecutar y verificar que falla**

```bash
php artisan test --filter=ResumenListaTest
```

Esperado: FAIL — `Unable to locate a class or view for component [resumen-lista]`.

- [ ] **Step 3: Escribir el componente**

Crear `resources/views/components/resumen-lista.blade.php`:

```blade
@props([
    'sustantivo',
    'total',
    'incompletos',
    'plural'          => null,
    'faltante'        => 'sin completar',
    'puedeValidar'    => false,
    'rutaValidar'     => null,
    'avisoValidacion' => false,
    'jefe'            => null,
])

{{--
    Resumen de una lista de filas —actores, sitios— sobre su tabla: cuántas
    hay, cuántas están a medias, y la acción de validar junto al dato que dice
    si ya se puede.

    No deriva nada: cada vista le pasa sus números ya resueltos, igual que
    <x-barra-lateral-formulario>. Las dos listas que lo usan cuentan cosas
    distintas —un actor incompleto puede serlo por varios campos, un sitio solo
    por su DET—, y forzar una forma común entre ellas es lo que produjo el tipo
    `actores` con zona->involucrados() cableado dentro.

    NO es la barra lateral de los formularios de matriz ni se le parece por
    dentro: aquella indexa bloques de criterios y esta cuenta filas. Se
    parecerían en la pantalla y significarían cosas distintas, que es
    exactamente el error que este proyecto ya pagó dos veces.

    El plural se recibe porque en castellano no es añadir una s: «actor» da
    «actores». Por defecto se compone, que sirve para «sitio».
--}}

@php
    $plural ??= $sustantivo . 's';
@endphp

<div class="bg-white shadow-sm sm:rounded-lg p-4 mb-6 flex flex-wrap items-center justify-between gap-4">

    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-base text-gray-700">
        <span class="font-medium">{{ $total }} {{ $total === 1 ? $sustantivo : $plural }}</span>

        @if($incompletos > 0)
            <span class="text-amber-700">{{ $incompletos }} {{ $faltante }}</span>
        @elseif($total > 0)
            {{-- Con la lista vacía no se dice «todos completos»: no hay nada
                 completo, y afirmarlo invita a validar lo que no se puede. --}}
            <span class="text-green-700">todos completos</span>
        @endif

        @if(trim($slot) !== '')
            <span class="text-gray-600">{{ $slot }}</span>
        @endif
    </div>

    <div>
        @if($puedeValidar)
            <form action="{{ $rutaValidar }}" method="POST"
                  onsubmit="return confirm('Al validar, la lista queda cerrada para el equipo. ¿Continuar?');">
                @csrf
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow">
                    Validar y Cerrar la Lista
                </button>
            </form>
        @elseif($avisoValidacion)
            {{-- El equipo no recibe un botón gris sino el texto que dice quién
                 valida: regla global de «sin botones desactivados». --}}
            <p class="text-sm text-amber-700">
                Lista para validar — avísale a {{ $jefe ?? 'tu Jefe de Zona' }}
            </p>
        @endif
    </div>

</div>
```

- [ ] **Step 4: Verde**

```bash
php artisan test --filter=ResumenListaTest
```

Esperado: PASS, 11 tests.

- [ ] **Step 5: Suite completa y commit**

```bash
php artisan test
git add resources/views/components/resumen-lista.blade.php tests/Feature/ResumenListaTest.php
git commit -m "feat(listas): componente de franja de resumen, probado en aislado"
```

Esperado: 536 tests (525 + 11).

---

### Task 2: Involucrados

**Files:**
- Modify: `app/Http/Controllers/Operativo/InvolucradosController.php`
- Modify: `resources/views/operativo/involucrados/index.blade.php`
- Modify: `tests/Feature/InvolucradosTest.php`

**Interfaces:**
- Consumes: `<x-resumen-lista>` de la Task 1.
- Produces: la vista recibe `incompletos` (int) además de lo que ya recibía.

- [ ] **Step 1: Escribir los tests**

Añadir a `tests/Feature/InvolucradosTest.php`. El fichero ya tiene
`todosEn(int $valor)`, que devuelve los once criterios rellenos, y `urlIndex(Zona)`:

```php
    /** Dos actores, uno de ellos a medias. */
    private function dosActoresUnoIncompleto(): void
    {
        Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $medias = Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor a medias',
        ]);

        // El null se asigna DESPUÉS de crear, no dentro del array: la unión
        // con «+» conserva el operando izquierdo cuando la clave se repite,
        // así que un null a la derecha de todosEn(2) nunca pisaría el 2.
        $medias->leg_sociedad = null;
        $medias->save();
    }

    public function test_la_franja_resume_cuantos_actores_faltan(): void
    {
        $this->dosActoresUnoIncompleto();

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('2 actores')
            ->assertSee('1 sin completar');
    }

    public function test_el_boton_de_validar_esta_arriba_y_no_al_final(): void
    {
        Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $html = $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->getContent();

        // Con la lista completa el botón existe una sola vez. Dos botones que
        // hacen lo mismo es la duplicación que se está quitando.
        $this->assertSame(1, substr_count($html, 'Validar y Cerrar la Lista'));
    }

    /**
     * El admin escribe listas pero no las valida, y tampoco es el equipo: no
     * recibe ni el botón ni el aviso de «avísale a tu jefe».
     */
    public function test_el_admin_ve_el_recuento_y_ninguna_accion_de_validar(): void
    {
        Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('1 actor')
            ->assertDontSee('Validar y Cerrar la Lista')
            ->assertDontSee('avísale a');
    }
```

Comprueba que `leg_sociedad` es un campo real de `Involucrados::campos()` antes
de usarlo; si el instrumento cambió de nombres, usa el primero que devuelva.

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=InvolucradosTest
```

Esperado: FAIL en los dos nuevos.

- [ ] **Step 3: El controlador pasa el recuento**

En `app/Http/Controllers/Operativo/InvolucradosController.php`, dentro del array
de `index()`, añadir la clave que falta. **`$incompletos` ya está calculado unas
líneas más arriba**, no lo recalcules:

```php
            'incompletos' => $incompletos,
```

- [ ] **Step 4: Poner la franja y quitar el bloque del final**

En `resources/views/operativo/involucrados/index.blade.php`, **justo debajo del
bloque `@if($confirmada)` y encima de todo lo demás**:

```blade
            <x-resumen-lista sustantivo="actor" plural="actores"
                             :total="$actores->count()"
                             :incompletos="$incompletos"
                             :puede-validar="$puedeValidar"
                             :ruta-validar="route('operativo.involucrados.validar', $zona->id)"
                             :aviso-validacion="$avisoValidacion"
                             :jefe="$zona->jefe?->name" />
```

Y **borrar entero** el bloque del final que hoy pinta el botón y el aviso —el
`@if($puedeValidar) … @elseif($avisoValidacion) … @endif` de las últimas líneas,
con su `<div class="mt-6 flex justify-end">`—. Los dos se han mudado a la franja:
son el mismo mensaje para roles distintos y partirlos en dos sitios lo rompería.

- [ ] **Step 5: Verde y suite completa**

```bash
php artisan test --filter=InvolucradosTest
php artisan test
```

Si falla algún test que buscaba el botón al final, es lo previsto: **reapúntalo,
no lo borres**. Lo que debe seguir comprobando es que el botón existe cuando toca
y para quien toca, no dónde está.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Operativo/InvolucradosController.php \
        resources/views/operativo/involucrados/index.blade.php \
        tests/Feature/InvolucradosTest.php
git commit -m "feat(involucrados): franja de resumen con el validar arriba"
```

---

### Task 3: Frecuentación

**Files:**
- Modify: `app/Http/Controllers/Operativo/FrecuentacionController.php`
- Modify: `resources/views/operativo/frecuentacion/index.blade.php`
- Modify: `tests/Feature/FrecuentacionTest.php`

**Interfaces:**
- Consumes: `<x-resumen-lista>` de la Task 1.
- Produces: la vista recibe `incompletos` (int) y `stDefinida` (bool) además de lo que ya recibía.

- [ ] **Step 1: Escribir los tests**

Añadir a `tests/Feature/FrecuentacionTest.php`. El fichero ya tiene
`urlIndex(Zona)`, `urlSuperficie()` y crea sitios con `SitioFrecuentacion::create()`;
un sitio sin `det` es el incompleto:

```php
    /** Dos sitios, uno sin DET. */
    private function dosSitiosUnoSinDet(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Sitio a medias',
        ]);
    }

    public function test_la_franja_resume_cuantos_sitios_faltan(): void
    {
        $this->dosSitiosUnoSinDet();

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('2 sitios')
            ->assertSee('1 sin DET');
    }

    /**
     * El admin escribe listas pero no las valida, y tampoco es el equipo: no
     * recibe ni el botón ni el aviso de «avísale a tu jefe».
     */
    public function test_el_admin_ve_el_recuento_y_ninguna_accion_de_validar(): void
    {
        SitioFrecuentacion::create([
            'zona_id' => $this->zona->id,
            'nombre'  => 'Malecón 2000',
            'det'     => 1500,
        ]);

        $this->actingAs($this->jefe)->post($this->urlSuperficie(), ['st' => 1200]);

        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);

        $this->actingAs($admin)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('1 sitio')
            ->assertDontSee('Validar y Cerrar la Lista')
            ->assertDontSee('avísale a');
    }

    /**
     * La ST aparece como dato en la franja, pero se sigue editando en su
     * sección: sin ella ningún sitio tiene ÍETP aunque todos tengan DET.
     */
    public function test_la_franja_avisa_si_falta_la_superficie_territorial(): void
    {
        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('ST sin responder');
    }

    public function test_con_superficie_definida_la_franja_la_muestra(): void
    {
        // Fija la ST por el camino real -la ruta de superficie-, no escribiendo
        // en la base a mano: así el test también cubre que ese camino funciona.
        $this->actingAs($this->jefe)->post(
            $this->urlSuperficie(),
            ['st' => 1200]
        );

        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('ST: 1200')
            ->assertDontSee('ST sin responder');
    }

    public function test_el_campo_de_superficie_sigue_siendo_editable(): void
    {
        $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->assertSee('name="st"', false)
            ->assertSee($this->urlSuperficie(), false);
    }

    public function test_el_boton_de_validar_esta_arriba_y_no_al_final(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get($this->urlIndex($this->zona))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'Validar y Cerrar la Lista'));
    }
```

- [ ] **Step 2: Ejecutar y verificar que fallan**

```bash
php artisan test --filter=FrecuentacionTest
```

- [ ] **Step 3: El controlador pasa recuento y estado de la ST**

En `app/Http/Controllers/Operativo/FrecuentacionController.php`, en el array de
`index()`. **Los dos ya están calculados** unas líneas más arriba:

```php
            'incompletos' => $incompletos,
            'stDefinida'  => $stDefinida,
```

- [ ] **Step 4: Poner la franja con su ranura**

En `resources/views/operativo/frecuentacion/index.blade.php`, **justo debajo del
bloque `@if($confirmada)` y por encima de la sección de la Superficie
Territorial**:

```blade
            <x-resumen-lista sustantivo="sitio" faltante="sin DET"
                             :total="$sitios->count()"
                             :incompletos="$incompletos"
                             :puede-validar="$puedeValidar"
                             :ruta-validar="route('operativo.frecuentacion.validar', $zona->id)"
                             :aviso-validacion="$avisoValidacion"
                             :jefe="$zona->jefe?->name">
                {{ $stDefinida ? 'ST: ' . $config->st : 'ST sin responder' }}
            </x-resumen-lista>
```

Va **encima** de la sección de ST y no debajo: si van pegadas, el mismo dato
aparece dos veces seguidas y la franja parece un duplicado en vez de un resumen.

Y **borrar entero** el bloque del final con el botón y el aviso, igual que en
Involucrados.

**No toques la sección de la Superficie Territorial**: sigue con su título, su
formulario y su botón de guardar. La franja la nombra como dato, no la sustituye.

- [ ] **Step 5: Verde, suite completa y build**

```bash
php artisan test --filter=FrecuentacionTest
php artisan test
npm run build
```

Los tests que buscaran el botón al final se reapuntan, no se borran.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Operativo/FrecuentacionController.php \
        resources/views/operativo/frecuentacion/index.blade.php \
        tests/Feature/FrecuentacionTest.php
git commit -m "feat(frecuentacion): franja de resumen con la ST como dato y el validar arriba"
```

---

### Task 4: Revisión final

- [ ] **Step 1: Suite y build**

```bash
php artisan test && npm run build
```

- [ ] **Step 2: Comprobar que no quedó un botón duplicado**

```bash
grep -rn "Validar y Cerrar la Lista" resources/views/
```

Esperado: **una sola aparición**, en `resources/views/components/resumen-lista.blade.php`.
Si sale en alguna de las dos vistas, quedó el bloque del final sin borrar.

- [ ] **Step 3: Comprobar que el purgado de Tailwind conserva las clases nuevas**

```bash
grep -c "text-amber-700\|text-green-700\|bg-green-600" public/build/assets/*.css
```

Esperado: al menos 1.

- [ ] **Step 4: Recorrido manual**

Con `php artisan serve`, en una zona con lista a medias:

1. **Jefe**: la franja dice cuántos faltan y no ofrece validar. Al completar la
   lista —y en Frecuentación, además, con ST > 0— aparece el botón arriba.
2. **Equipo**: con la lista completa lee «Lista para validar — avísale a …», sin
   botón.
3. **Frecuentación**: la ST aparece en la franja como dato **y sigue editándose**
   en su sección, con su propio botón de guardar.
4. **Lista validada**: el banner de arriba sigue diciendo que está cerrada, la
   franja muestra el recuento y ningún botón.

- [ ] **Step 5: Commit final si algo cambió**

```bash
git status --short
```

---

## Fuera de este plan

- **Reutilizar `<x-barra-lateral-formulario>`.** Son dos componentes distintos
  porque significan cosas distintas: aquel indexa bloques de criterios, este
  cuenta filas.
- **Tocar la maquetación de las tablas**, el ancho de las vistas o la sección de
  la Superficie Territorial.
- **Una barra lateral para estas dos.** Descartada en el diseño, con el motivo
  escrito por si alguien la vuelve a proponer.
