# La franja de estado de los formularios — plan de implementación

> **Para quien lo ejecute:** SUB-SKILL OBLIGATORIA: usa
> `superpowers:subagent-driven-development` (recomendado) o
> `superpowers:executing-plans` para ejecutarlo tarea a tarea. Los pasos usan
> casillas (`- [ ]`) para poder marcarlos.

**Objetivo:** que las tres cajas que hoy hay encima del primer criterio de cada
formulario de matriz —«Última edición», el banner de estado y la tarjeta de la
escala— pasen a ser una sola franja, en los ocho.

**Arquitectura:** un componente nuevo, `<x-franja-matriz>`, que **deriva su
estado** de `$evaluacion` y de `auth()->user()->esJefe()` en vez de recibirlo
por prop. Se retiran dos componentes que quedan absorbidos:
`<x-leyenda-escala>` y `<x-aviso-bloqueo-matriz>`. El neto de componentes baja
en uno.

**Tecnología:** Laravel 12, Blade con componentes anónimos, Tailwind, PHPUnit.
Sin Alpine nuevo: la franja no conmuta nada.

**Spec:** `docs/superpowers/specs/2026-08-15-formularios-design.md` — el plan
no reargumenta sus decisiones, solo las ejecuta; quien ejecute lee las dos.

## Restricciones globales

- **Suite base de la rama: 646 tests en verde**, confirmados con
  `php artisan test` (PHP 8.2.33 nativo, ~20 s). La rama sale de `2938e23`;
  los tres commits que hay entre la última corrida y ese punto son de
  documentación —el `.gitignore` de las maquetas, la spec y este plan—, así
  que la cifra sigue valiendo sin volver a correr nada.
- **Ninguna pantalla fuera de los ocho formularios de matriz se toca.** Ni el
  dashboard, ni el detalle de zona, ni admin, ni las listas de Involucrados y
  Frecuentación —que no son formularios de criterios—.
- **`<x-barra-lateral-formulario>` solo recibe el aviso de reapertura** (T6).
  Ni su índice, ni su progreso, ni su botón se tocan.
- **`<x-criterio-pildoras>`, `<x-pestanas-matriz>`, `<x-migas>` y
  `<x-tarjeta>` se usan tal cual.** Ninguno se modifica.
- **El CSS a medida de Potencialidad no se convierte.** Su bloque `<style>` y
  sus 16 `style=` se quedan salvo el trozo del banner que la franja sustituye.
- **Clases de Tailwind completas y literales, nunca por concatenación**: el
  purgado se lleva las que no aparecen tal cual en el fuente. Los arrays de
  estilos de la franja llevan la clase entera en cada rama, igual que hace
  `EstadoZona::ESTILOS_ESTADO`.
- **Nada de texto por debajo de 14 px salvo insignias, sin `uppercase`.**
- **`package-lock.json` no entra en ningún commit.** Si aparece modificado:
  `git checkout -- package-lock.json`.
- **No se toca ningún contenedor de Docker.**
- Mensajes de commit en español, terminados en
  `Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>`.

## Ficheros

| Fichero | Responsabilidad | Tarea |
|---|---|---|
| `resources/views/components/franja-matriz.blade.php` | la franja: tres estados, escala opcional | T1 |
| `tests/Feature/FranjaMatrizTest.php` | el contrato del componente, sin pasar por las vistas | T1 |
| `.../evaluacion_fit/form.blade.php`, `.../evaluacion_fet/form.blade.php` | las dos de cuatro niveles | T2 |
| `tests/Feature/EvaluacionesTest.php`, `tests/Feature/ReabrirMatrizTest.php` | migrar 4 aserciones (FIT y FET) | T2 |
| `.../evaluacion_paisaje/form.blade.php`, `.../evaluacion_valoracion_territorial/form.blade.php` | escala literal y escala por defecto | T3 |
| `.../evaluacion_percepcion/form.blade.php`, `.../evaluacion_concentracion/form.blade.php`, `.../evaluacion_irritacion/form.blade.php` | las tres restantes convertidas | T4 |
| `tests/Feature/EvaluacionesTest.php`, `ConcentracionTest.php`, `IrritacionTest.php` | migrar 3 aserciones | T4 |
| `.../evaluacion_potencialidad/form.blade.php` | la que tiene CSS propio | T5 |
| `resources/views/components/barra-lateral-formulario.blade.php` | el aviso de reapertura que le falta | T6 |
| `resources/views/components/leyenda-escala.blade.php`, `aviso-bloqueo-matriz.blade.php` | se borran | T6 |
| `docs/ESTADO-PROYECTO.md`, `.superpowers/sdd/progress.md` | traspaso al día | T8 |

## Recuento de tests esperado

| Tarea | Añade | Total |
|---|---|---|
| base | — | 646 |
| T1 | **8** | **654** |
| T2 | 0 (migra 4 aserciones dentro de tests que ya existen) | 654 |
| T3 | 0 | 654 |
| T4 | 0 (migra 3 aserciones) | 654 |
| T5 | 0 | 654 |
| T6 | 5 (3 del recorrido por el registro, 2 de la barra lateral) | **659** |
| T7–T8 | 0 | **659** |

> **T1 acabó con 8 tests, no con los 7 que decía este plan.** La revisión de
> esa tarea señaló que `$paletas[count($niveles)] ?? $paletas[3]` degradaba en
> silencio para una escala de un tamaño sin paleta: caía en la de 3 y se salía
> del array, con un aviso de PHP y un punto sin color en vez de un error. Se
> cambió por una guarda que revienta, como la de `:total`/`:respondidos` en
> `<x-barra-lateral-formulario>`, y el test que la fija es el octavo. De ahí
> que todos los totales de abajo suban en uno respecto de lo planeado.

Si el número no cuadra al terminar una tarea, para y mira por qué antes de
seguir: en este repositorio un test que desaparece sin que nadie lo note ya ha
pasado.

---

### Tarea 1: el componente `<x-franja-matriz>`

Se construye y se prueba **antes de tocar ninguna vista**. Al terminar esta
tarea la suite sigue verde y ningún formulario ha cambiado todavía.

**Ficheros:**
- Crear: `resources/views/components/franja-matriz.blade.php`
- Crear: `tests/Feature/FranjaMatrizTest.php`
- Sobrescribir: `.superpowers/sdd/progress.md`

**Interfaces:**
- Consume: `$evaluacion` (modelo de evaluación, obligatorio) y `$niveles`
  (array `nivel => etiqueta`, opcional, `null` = sin escala).
- Produce: la franja. La consumen T2 a T5.

- [ ] **Paso 1: sobrescribir la bitácora de rama**

`.superpowers/sdd/progress.md` lleva todavía la de la Fase 3, ya volcada en el
traspaso. La regla 3 de `CLAUDE.md` manda sobrescribirla al empezar cada rama:

```markdown
> **Este fichero se sobrescribe al empezar cada rama.** Guarda la bitácora de
> **una sola**, la que esté en curso. Antes de arrancar la siguiente, lo que
> merezca sobrevivir tiene que estar volcado en `docs/ESTADO-PROYECTO.md`, que
> es el documento que sí acumula.
>
> Los `*-report.md` de cada tarea sí se quedan, y son el detalle largo. Los
> `*.diff` y los `*-brief.md` no viajan: se derivan de `git diff` y de los
> planes de `docs/superpowers/plans/`, que ya están versionados.

# Progreso — La franja de estado de los formularios (Fase 4 del rediseño)

Spec: `docs/superpowers/specs/2026-08-15-formularios-design.md`
Plan: `docs/superpowers/plans/2026-08-15-formularios.md`
Rama: fase-4-formularios
Base de la rama: 2938e23
Suite en la base: 646 tests

Objetivo: una sola franja donde hoy hay tres cajas, en los ocho formularios
de matriz.

## Decisiones que vienen del diseño y no se replantean

- **La franja deriva su estado**, no lo recibe por prop: siete vistas lo
  llaman $estaConfirmado/$bloqueado y Potencialidad $isConfirmado/$soloLectura.
- **`:niveles` es null por defecto y null significa «sin escala»**, a
  diferencia de <x-leyenda-escala>, cuyo defecto 0/1/2 hacía que «sin escala»
  y «escala corriente» se escribieran igual.
- **Tres estados, no dos**: el verde se reserva para «validada y puedes
  editarla»; cerrada se pinta neutro. CLAUDE.md recuerda que una fase anterior
  pintó de verde un estado bloqueado.
- **«Validada» a secas**, sin el nombre de la matriz: ya sale tres veces antes.
- **La franja describe; la advertencia de reapertura acompaña al botón.**

## Tareas

T1 el componente · T2 FIT y FET · T3 Paisaje y VT · T4 Percepción,
Concentración e Irritación · T5 Potencialidad · T6 retirar los dos
componentes y el aviso de la barra lateral · T7 verificación de navegador ·
T8 revisión final y traspaso.
```

- [ ] **Paso 2: escribir los tests que fallan**

Crea `tests/Feature/FranjaMatrizTest.php`:

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
 * El contrato de <x-franja-matriz>, fijado sobre el componente y no sobre los
 * ocho formularios que lo usan.
 *
 * Necesita base de datos, a diferencia de <x-desglose-estados>: la franja
 * deriva su estado de una evaluación real y del rol de quien mira, que es
 * justamente lo que la libra de recibir un booleano distinto en cada vista.
 */
class FranjaMatrizTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    private function render(EvaluacionFit $evaluacion, ?array $niveles = null): string
    {
        return (string) $this->blade(
            '<x-franja-matriz :evaluacion="$evaluacion" :niveles="$niveles" />',
            ['evaluacion' => $evaluacion, 'niveles' => $niveles]
        );
    }

    public function test_una_evaluacion_en_borrador_pinta_la_franja_ambar(): void
    {
        $this->actingAs($this->jefe);

        $html = $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador'])
        );

        $this->assertStringContainsString('Borrador', $html);
        $this->assertStringContainsString('border-l-amber-500', $html);
        $this->assertStringNotContainsString('border-l-green-500', $html);
    }

    public function test_el_jefe_ve_verde_una_validada_porque_todavia_puede_editarla(): void
    {
        $this->actingAs($this->jefe);

        $html = $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado'])
        );

        $this->assertStringContainsString('Validada', $html);
        $this->assertStringContainsString('border-l-green-500', $html);
        $this->assertStringNotContainsString('solo lectura', $html);
    }

    /**
     * El defecto que CLAUDE.md recuerda de una fase anterior -«una franja que
     * pintaba en verde un estado bloqueado»- fijado para que no vuelva. No
     * basta con afirmar que aparece «solo lectura»: hay que negar el verde,
     * que es la parte que se rompería sin ruido.
     */
    public function test_quien_no_es_jefe_ve_una_validada_en_neutro_y_nunca_en_verde(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);
        $this->actingAs($equipo);

        $html = $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado'])
        );

        $this->assertStringContainsString('Validada · solo lectura', $html);
        $this->assertStringNotContainsString('border-l-green-500', $html);
    }

    /**
     * El admin tampoco edita una matriz validada -solo el jefe la reabre-, así
     * que le toca la misma franja neutra que al equipo.
     */
    public function test_el_admin_ve_una_validada_en_neutro(): void
    {
        $admin = User::factory()->create([
            'role_id' => Role::where('nombre', 'admin')->value('id'),
        ]);
        $this->actingAs($admin);

        $html = $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado'])
        );

        $this->assertStringContainsString('Validada · solo lectura', $html);
        $this->assertStringNotContainsString('border-l-green-500', $html);
    }

    public function test_sin_niveles_no_pinta_escala_ni_la_frase_de_metodo(): void
    {
        $this->actingAs($this->jefe);

        $html = $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador'])
        );

        $this->assertStringNotContainsString('Elige la descripción', $html);
        $this->assertStringNotContainsString('Desfavorable', $html);
    }

    public function test_con_niveles_pinta_la_escala_y_la_frase_de_metodo(): void
    {
        $this->actingAs($this->jefe);

        $html = $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']),
            [0 => 'Desfavorable', 1 => 'Parcial', 2 => 'Favorable']
        );

        $this->assertStringContainsString('Desfavorable', $html);
        $this->assertStringContainsString('Favorable', $html);
        $this->assertStringContainsString('Elige la descripción que coincide con el territorio, no el número.', $html);
    }

    /**
     * FIT y FET tienen CUATRO niveles (0 Nulo · 1 Bajo · 2 Medio · 3 Alto), no
     * tres. Con la paleta de tres, el cuarto nivel se quedaría sin color y el
     * índice se saldría del array.
     */
    public function test_una_escala_de_cuatro_niveles_pinta_los_cuatro(): void
    {
        $this->actingAs($this->jefe);

        $html = $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']),
            [0 => 'Nulo', 1 => 'Bajo', 2 => 'Medio', 3 => 'Alto']
        );

        foreach (['Nulo', 'Bajo', 'Medio', 'Alto'] as $etiqueta) {
            $this->assertStringContainsString($etiqueta, $html);
        }

        $this->assertStringContainsString('bg-orange-500', $html);
    }
}
```

- [ ] **Paso 3: correrlos y verlos fallar**

```bash
php artisan test --filter=FranjaMatrizTest
```

Esperado: FALLAN los siete, con «Unable to locate a class or view for component [franja-matriz]».

- [ ] **Paso 4: escribir el componente**

Crea `resources/views/components/franja-matriz.blade.php`:

```blade
@props(['evaluacion', 'niveles' => null])

{{--
    Una sola franja donde antes había tres cajas: la línea de «Última
    edición», el banner de estado y la tarjeta de la escala.

    DERIVA el estado en vez de recibirlo. Las ocho vistas que la usan no se
    ponen de acuerdo en cómo llamarlo -siete dicen $estaConfirmado/$bloqueado
    y Potencialidad dice $isConfirmado/$soloLectura-, así que un prop booleano
    serían ocho oportunidades de pasarle el contrario. Con $evaluacion y el
    rol de quien mira, la respuesta es una sola y sale de aquí.

    TRES estados, no dos. Antes, una matriz validada pintaba el mismo verde
    para todos: quien no puede editarla leía «todo correcto» y descubría el
    bloqueo al final de la página. El verde queda para «validada y todavía
    puedes editarla»; cerrada se pinta neutro. CLAUDE.md recuerda que una fase
    anterior de este mismo rediseño pintó de verde un estado bloqueado.

    $niveles a null significa SIN ESCALA, y por eso el defecto no es 0/1/2
    como en la <x-leyenda-escala> que esto sustituye: con aquel defecto,
    «no tengo escala» y «tengo la escala corriente» se escribían igual, y
    Concentración e Irritación -que no tienen escala- habrían recibido una
    inventada. Valoración Territorial, que se apoyaba en ese defecto, ahora
    pasa la suya explícitamente.
--}}

@php
    $confirmada = $evaluacion?->exists && $evaluacion->estado === 'confirmado';
    $esJefe     = auth()->user()->esJefe();

    $estado = match (true) {
        $confirmada && $esJefe => 'validada',
        $confirmada            => 'cerrada',
        default                => 'borrador',
    };

    // Clases enteras en cada rama: Tailwind purga las construidas por
    // concatenación. Mismo criterio que EstadoZona::ESTILOS_ESTADO.
    $estilos = [
        'borrador' => ['marco' => 'border-l-amber-500', 'texto' => 'text-amber-700', 'etiqueta' => 'Borrador'],
        'validada' => ['marco' => 'border-l-green-500', 'texto' => 'text-green-700', 'etiqueta' => 'Validada'],
        'cerrada'  => ['marco' => 'border-l-gray-400',  'texto' => 'text-gray-700',  'etiqueta' => 'Validada · solo lectura'],
    ][$estado];

    // Indexadas por POSICIÓN, no por el valor del nivel: Valoración
    // Territorial usa 0/1/2, Paisaje usa 0/3/5 y FIT/FET usan cuatro niveles
    // (0-3). La de 4 evita repetir el verde a media escala.
    $paletas = [
        3 => ['bg-red-500', 'bg-amber-500', 'bg-green-500'],
        4 => ['bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-green-500'],
    ];

    if ($niveles !== null) {
        ksort($niveles);
        $colores = $paletas[count($niveles)] ?? $paletas[3];
    }
@endphp

<div class="bg-white border border-gray-200/80 border-l-4 {{ $estilos['marco'] }} rounded-xl shadow-sm px-4 py-3 mb-6">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <span class="font-semibold {{ $estilos['texto'] }}">{{ $estilos['etiqueta'] }}</span>

        @if($niveles !== null)
            <span class="text-gray-300" aria-hidden="true">|</span>

            @foreach($niveles as $nivel => $etiqueta)
                <span class="flex items-center gap-1.5 text-gray-700">
                    <span class="w-5 h-1.5 rounded-full {{ $colores[$loop->index] }}"></span>
                    <span class="font-semibold">{{ $nivel }}</span>
                    <span>{{ $etiqueta }}</span>
                </span>
            @endforeach
        @endif

        @if($evaluacion?->exists && $evaluacion->user)
            <span class="ml-auto text-gray-500">
                {{ $evaluacion->user->name }}, {{ $evaluacion->updated_at->diffForHumans() }}
            </span>
        @endif
    </div>

    {{-- La frase que sobrevive del párrafo de la escala: no explica cómo
         funciona el sistema -eso se aprende una vez- sino cómo se puntúa
         bien, que es lo que cambia el dato. Sin escala no tiene sentido. --}}
    @if($niveles !== null)
        <p class="text-sm text-gray-500 mt-2">
            Elige la descripción que coincide con el territorio, no el número.
        </p>
    @endif
</div>
```

- [ ] **Paso 5: correr los tests y verlos pasar**

```bash
php artisan test --filter=FranjaMatrizTest
```

Esperado: PASAN los siete.

- [ ] **Paso 6: construir los assets y correr la suite entera**

`border-l-amber-500`, `border-l-green-500`, `border-l-gray-400` y
`bg-orange-500` son clases que no estaban en el fuente hasta este commit.

```bash
npm run build
php artisan test
```

Esperado: **653 tests en verde** (646 + 7). Ninguna vista ha cambiado
todavía, así que nada más debería moverse.

> **Acabó en 654, no en 653**, y el motivo está en la nota del recuento de
> arriba: la revisión de esta tarea cambió el `?? $paletas[3]` por una guarda
> que revienta, y eso trajo un octavo test. De la Tarea 2 en adelante, la
> cifra de referencia es **654**.

- [ ] **Paso 7: commit**

```bash
git add resources/views/components/franja-matriz.blade.php tests/Feature/FranjaMatrizTest.php .superpowers/sdd/progress.md
git commit -m "feat(formularios): x-franja-matriz, con sus tres estados

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 2: FIT y FET, las dos de cuatro niveles

**Ficheros:**
- Modificar: `resources/views/operativo/evaluacion_fit/form.blade.php`
- Modificar: `resources/views/operativo/evaluacion_fet/form.blade.php`
- Modificar: `tests/Feature/EvaluacionesTest.php` (líneas 265 y 330)
- Modificar: `tests/Feature/ReabrirMatrizTest.php` (líneas 271 y 283)

**Interfaces:** consume `<x-franja-matriz>` (T1). No produce nada nuevo.

**Por qué estas dos primero:** son las de cuatro niveles, el caso ancho, y las
dos únicas que pasan `sustantivo="evaluación"` a `<x-aviso-bloqueo-matriz>`.
Si algo del componente está mal dimensionado, se ve aquí antes que en ningún
otro sitio.

- [ ] **Paso 1: migrar las cuatro aserciones**

Las cuatro comprueban la frase de `<x-aviso-bloqueo-matriz>`, que esta tarea
retira de estas dos vistas. Lo que comprueban —que quien no es jefe ve que
está cerrada, y que **deja de verlo** tras reabrir— es lo que el tercer estado
de la franja tiene que seguir garantizando, así que se migran, no se borran.

En `tests/Feature/EvaluacionesTest.php`, línea 265, sustituye:

```php
            ->assertSee('Solo el Jefe de Zona puede reabrir o editar una evaluación validada.');
```

por:

```php
            ->assertSee('Validada · solo lectura');
```

Línea 330, la misma sustitución, palabra por palabra.

En `tests/Feature/ReabrirMatrizTest.php`, línea 271:

```php
            ->assertSee('Validada · solo lectura');
```

y línea 283, la del `assertDontSee`:

```php
            ->assertDontSee('Validada · solo lectura')
```

- [ ] **Paso 2: correrlos y verlos fallar**

```bash
php artisan test --filter="EvaluacionesTest|ReabrirMatrizTest"
```

Esperado: FALLAN los tests de esas cuatro aserciones —la franja todavía no
está en FIT ni en FET, así que «Validada · solo lectura» no aparece—. El
`assertDontSee` de la 283 **pasa**, porque el texto tampoco está; es correcto y
se vuelve significativo en el paso 4.

- [ ] **Paso 3: convertir FIT**

En `resources/views/operativo/evaluacion_fit/form.blade.php`, **borra** el
bloque de las líneas 36 a 59 —desde `@if($evaluacion?->exists && $evaluacion->user)`
hasta `<x-leyenda-escala :niveles="$niveles" />` incluidos—, que hoy es:

```blade
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
```

y pon en su lugar:

```blade
            <x-franja-matriz :evaluacion="$evaluacion" :niveles="$niveles" />
```

Después, en el pie del formulario, sustituye la línea del aviso de bloqueo
(hoy la 154):

```blade
                    <span class="text-gray-500 italic self-center"><x-aviso-bloqueo-matriz sustantivo="evaluación" /></span>
```

por nada: **bórrala entera**. La franja ya lo dice arriba. El `<x-boton>` de
«Actualizar Datos» que la acompaña en esa rama `@else` **se queda**.

- [ ] **Paso 4: convertir FET**

En `resources/views/operativo/evaluacion_fet/form.blade.php`, el mismo bloque
está en las líneas 35 a 58 y su banner de validada dice «✓ Evaluación FET
Validada» en vez de «✓ Evaluación Validada». Bórralo entero —de
`@if($evaluacion?->exists && $evaluacion->user)` hasta
`<x-leyenda-escala :niveles="$niveles" />` incluidos— y pon:

```blade
            <x-franja-matriz :evaluacion="$evaluacion" :niveles="$niveles" />
```

Y borra su línea de aviso de bloqueo, hoy la 143:

```blade
                    <span class="text-gray-500 italic self-center"><x-aviso-bloqueo-matriz sustantivo="evaluación" /></span>
```

- [ ] **Paso 5: correr los tests tocados y verlos pasar**

```bash
php artisan test --filter="EvaluacionesTest|ReabrirMatrizTest|FitTest|FetTest"
```

Esperado: PASAN todos.

- [ ] **Paso 6: correr la suite entera**

```bash
php artisan test
```

Esperado: **654 tests en verde**. El número no sube: se migraron aserciones
dentro de tests que ya existían.

- [ ] **Paso 7: commit**

```bash
git add resources/views/operativo/evaluacion_fit/form.blade.php resources/views/operativo/evaluacion_fet/form.blade.php tests/Feature/EvaluacionesTest.php tests/Feature/ReabrirMatrizTest.php
git commit -m "feat(formularios): la franja entra en FIT y FET

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 3: Paisaje y Valoración Territorial

**Ficheros:**
- Modificar: `resources/views/operativo/evaluacion_paisaje/form.blade.php`
- Modificar: `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`

**Interfaces:** consume `<x-franja-matriz>` (T1).

**Por qué van juntas:** son las dos que no reciben `$niveles` del controlador.
Paisaje lo escribe literal en la vista y Valoración Territorial **no lo
escribe en absoluto** y se apoya en el valor por defecto de
`<x-leyenda-escala>`. Las dos pasan a decirlo explícitamente, que es la
decisión 3 de la spec.

Ninguna de las dos usa `<x-aviso-bloqueo-matriz>`, así que aquí no hay
aserciones que migrar.

- [ ] **Paso 1: convertir Paisaje**

En `resources/views/operativo/evaluacion_paisaje/form.blade.php`, borra el
bloque de «Última edición» (líneas 40-45), el banner de estado —cuyo texto de
validada es «✓ Matriz de Paisaje Validada»— y la línea 84:

```blade
            <x-leyenda-escala :niveles="[0 => 'Desfavorable', 3 => 'Intermedio', 5 => 'Favorable']" />
```

En el sitio del bloque de «Última edición», pon:

```blade
            <x-franja-matriz :evaluacion="$evaluacion"
                             :niveles="[0 => 'Desfavorable', 3 => 'Intermedio', 5 => 'Favorable']" />
```

La escala de Paisaje es 0/3/5, no 0/1/2: los valores no son correlativos y por
eso el componente colorea por posición y no por el número.

- [ ] **Paso 2: convertir Valoración Territorial**

En `resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php`,
borra el bloque de «Última edición» (líneas 44-49), el banner de estado —«✓
Valoración Territorial Validada»— y la línea 73:

```blade
                <x-leyenda-escala />
```

En el sitio del bloque de «Última edición», pon la escala **explícita**, que
es la que hasta hoy venía del valor por defecto del componente retirado:

```blade
            <x-franja-matriz :evaluacion="$evaluacion"
                             :niveles="[0 => 'Desfavorable', 1 => 'Parcial', 2 => 'Favorable']" />
```

- [ ] **Paso 3: correr los tests de las dos**

```bash
php artisan test --filter="PaisajeTest|ValoracionTerritorial"
```

Esperado: PASAN.

- [ ] **Paso 4: correr la suite entera**

```bash
php artisan test
```

Esperado: **654 tests en verde**.

- [ ] **Paso 5: commit**

```bash
git add resources/views/operativo/evaluacion_paisaje/form.blade.php resources/views/operativo/evaluacion_valoracion_territorial/form.blade.php
git commit -m "feat(formularios): la franja entra en Paisaje y Valoracion Territorial

Valoracion Territorial deja de apoyarse en el valor por defecto de
x-leyenda-escala y declara su escala 0/1/2 explicitamente.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 4: Percepción, Concentración e Irritación

**Ficheros:**
- Modificar: `resources/views/operativo/evaluacion_percepcion/form.blade.php`
- Modificar: `resources/views/operativo/evaluacion_concentracion/form.blade.php`
- Modificar: `resources/views/operativo/evaluacion_irritacion/form.blade.php`
- Modificar: `tests/Feature/EvaluacionesTest.php` (línea 413)
- Modificar: `tests/Feature/ConcentracionTest.php` (línea 235)
- Modificar: `tests/Feature/IrritacionTest.php` (línea 426)

**Interfaces:** consume `<x-franja-matriz>` (T1).

**Las tres tienen algo en común y algo distinto:** las tres usan
`<x-aviso-bloqueo-matriz>` sin `sustantivo` —así que su frase dice «matriz»—,
y Concentración e Irritación **no tienen escala**, así que su franja no lleva
`:niveles`.

**Percepción tiene además otra estructura:** su banner y su escala viven
**dentro del `<form>`**, no antes. Como la franja deriva su propio estado y no
necesita las variables `$esJefe`/`$estaConfirmado` que se declaran en el `@php`
de dentro del formulario, puede subir por encima del `<form>`. Ahí es donde va.

- [ ] **Paso 1: migrar las tres aserciones**

En `tests/Feature/EvaluacionesTest.php`, línea 413, sustituye:

```php
            ->assertSee('Solo el Jefe de Zona puede reabrir o editar una matriz validada.');
```

por:

```php
            ->assertSee('Validada · solo lectura');
```

La misma sustitución, palabra por palabra, en `tests/Feature/ConcentracionTest.php`
línea 235 y en `tests/Feature/IrritacionTest.php` línea 426.

- [ ] **Paso 2: correrlos y verlos fallar**

```bash
php artisan test --filter="EvaluacionesTest|ConcentracionTest|IrritacionTest"
```

Esperado: FALLAN los tres tests migrados.

- [ ] **Paso 3: convertir Percepción**

En `resources/views/operativo/evaluacion_percepcion/form.blade.php`, borra su
bloque de «Última edición» (líneas 72-77), su banner de estado —«✓ Matriz
Validada»— y su línea 95:

```blade
                    <x-leyenda-escala :niveles="$niveles" />
```

Los tres están **dentro** del `<form>`. La franja no va donde estaban: va
**antes** del `<form>`, justo después de `<x-flash-exito />` (línea 23):

```blade
            <x-flash-exito />

            <x-franja-matriz :evaluacion="$evaluacion" :niveles="$niveles" />

```

Y borra su línea de aviso de bloqueo, hoy la 179:

```blade
                            <span class="text-gray-500 italic self-center"><x-aviso-bloqueo-matriz /></span>
```

- [ ] **Paso 4: convertir Concentración**

En `resources/views/operativo/evaluacion_concentracion/form.blade.php`, borra
el bloque de «Última edición» (líneas 76-81) y el banner de estado —«✓ Índice
de Concentración Validado»—. **No hay `<x-leyenda-escala>` que borrar:
Concentración no tiene escala.**

En su lugar, **sin `:niveles`**:

```blade
            <x-franja-matriz :evaluacion="$evaluacion" />
```

Y borra su línea de aviso de bloqueo, hoy la 209:

```blade
                        <x-aviso-bloqueo-matriz />
```

- [ ] **Paso 5: convertir Irritación**

En `resources/views/operativo/evaluacion_irritacion/form.blade.php`, borra el
bloque de «Última edición» (líneas 35-40) y el banner —«✓ Índice de Irritación
Validado»—. Tampoco tiene escala. En su lugar:

```blade
            <x-franja-matriz :evaluacion="$evaluacion" />
```

Y borra su línea de aviso de bloqueo, hoy la 142:

```blade
                        <x-aviso-bloqueo-matriz />
```

- [ ] **Paso 6: correr los tests tocados y verlos pasar**

```bash
php artisan test --filter="PercepcionTest|ConcentracionTest|IrritacionTest|EvaluacionesTest"
```

Esperado: PASAN todos.

- [ ] **Paso 7: correr la suite entera**

```bash
php artisan test
```

Esperado: **654 tests en verde**.

- [ ] **Paso 8: commit**

```bash
git add resources/views/operativo/evaluacion_percepcion/form.blade.php resources/views/operativo/evaluacion_concentracion/form.blade.php resources/views/operativo/evaluacion_irritacion/form.blade.php tests/Feature/EvaluacionesTest.php tests/Feature/ConcentracionTest.php tests/Feature/IrritacionTest.php
git commit -m "feat(formularios): la franja entra en Percepcion, Concentracion e Irritacion

Concentracion e Irritacion no pasan :niveles, que es como se dice «sin
escala» desde que el defecto del componente es null.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 5: Potencialidad

**Ficheros:**
- Modificar: `resources/views/operativo/evaluacion_potencialidad/form.blade.php`

**Interfaces:** consume `<x-franja-matriz>` (T1). Es la última de las ocho.

**Va sola, y al final, por tres motivos** que ninguna de las siete anteriores
tiene: es la única con un bloque `<style>` propio y 16 atributos `style=`; es
la única donde el jefe activa y desactiva campos, así que arrastra avisos
extra; y **su rejilla de dos columnas abre en la línea 277, después del
banner**, no antes como en las otras siete.

**Esa última diferencia es una desviación consciente respecto a la spec.** La
spec dice que la franja va dentro de la columna izquierda de la rejilla; aquí
va **encima** de la rejilla, porque es donde está hoy el banner y bajarla
dentro exigiría mover más de cien líneas de marcado propio, que es la
conversión que quedó explícitamente fuera. No es una regresión: la barra
lateral de Potencialidad ya empieza hoy por debajo de esos bloques.

- [ ] **Paso 1: borrar los bloques que la franja sustituye**

En `resources/views/operativo/evaluacion_potencialidad/form.blade.php`, borra
**el bloque de «Última edición» (líneas 178-183)**:

```blade
        @if($evaluacion?->exists && $evaluacion->user)
            <p class="text-sm text-gray-500 mb-4">
                Última edición: {{ $evaluacion->user->name }},
                {{ $evaluacion->updated_at->diffForHumans() }}
            </p>
        @endif
```

**el banner de estado (líneas 197-214)**:

```blade
        {{-- Banner estado ─────────────────────────────────────────────────── --}}
        @if($isConfirmado)
        <div class="pt-banner-ok">
            <div style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:22px;">✅</span>
                <div>
                    <div style="font-weight:700;color:#15803d;font-size:.93rem;">Evaluación confirmada y validada</div>
                    @if($user->esEquipo())
                    <div style="font-size:.8rem;color:#166534;margin-top:2px;">Esta evaluación fue cerrada por el Jefe de Zona. Solo puedes consultar los valores.</div>
                    @endif
                </div>
            </div>
        </div>
        @elseif($evaluacion->exists)
        <div class="pt-banner-warn">
            ✏️ <strong>Modo Borrador</strong> — Los datos ingresados son preliminares. El Jefe de Zona debe validar para generar el resultado oficial.
        </div>
        @endif
```

y **la escala (líneas 222-227)**, comentario incluido:

```blade
        {{-- Escala de calificación: vivía en una tarjeta de la .pt-sidebar
             de siempre; se traslada al cuerpo con el mismo componente que
             ya usan FIT, FET, Paisaje y Valoración Territorial -Potencialidad
             recibe $niveles desde el controlador desde siempre, pero nunca
             lo había usado-. --}}
        <x-leyenda-escala :niveles="$niveles" />
```

**No borres** el aviso de las líneas 216-220 —«🔒 Solo el Jefe de Zona puede
activar o desactivar campos»— ni el bloque `@if($puedeConfigurar)` que viene
después: no son el estado de la matriz, son permisos sobre su configuración, y
la spec los deja donde están.

**Tampoco borres** los dos bloques de `session('success')` y `session('error')`
de las líneas 185-195.

- [ ] **Paso 2: poner la franja**

Donde estaba el bloque de «Última edición», justo después de
`<x-pestanas-matriz ... />` (línea 176):

```blade
        <x-franja-matriz :evaluacion="$evaluacion" :niveles="$niveles" />
```

**Un cambio de comportamiento que conviene saber:** el banner viejo tenía
`@elseif($evaluacion->exists)`, así que una evaluación **que todavía no
existe** no pintaba banner ninguno. La franja sí pinta «Borrador» en ese caso,
igual que en los otros siete. Es la normalización que la fase persigue, no un
descuido.

- [ ] **Paso 3: comprobar que las clases retiradas ya no las usa nadie**

```bash
grep -n "pt-banner-ok\|pt-banner-warn" resources/views/operativo/evaluacion_potencialidad/form.blade.php
```

Esperado: solo sus dos definiciones dentro del `<style>`, **las líneas 150 y
151**, sin ningún uso —los usos de las líneas 199 y 211 se fueron con el
banner—. Si es así, borra esas dos líneas del `<style>`:

```css
        .pt-banner-ok { background:linear-gradient(135deg,#f0fdf4,#dcfce7); border:1.5px solid #86efac; border-radius:13px; padding:14px 18px; display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap; margin-bottom:18px; }
        .pt-banner-warn { background:#fffbeb; border:1.5px solid #fde68a; border-radius:13px; padding:12px 16px; margin-bottom:18px; font-size:.83rem; color:#92400e; }
```

Son CSS muerto en cuanto el banner se va. **El resto del `<style>` se queda**:
esta tarea no convierte el CSS de Potencialidad, solo se lleva lo que deja de
tener uso.

- [ ] **Paso 4: correr los tests de Potencialidad**

```bash
php artisan test --filter="PotencialidadTest|PotencialidadCalculoTest"
```

Esperado: PASAN.

- [ ] **Paso 5: correr la suite entera**

```bash
php artisan test
```

Esperado: **654 tests en verde**. Los ocho formularios ya llevan franja.

- [ ] **Paso 6: commit**

```bash
git add resources/views/operativo/evaluacion_potencialidad/form.blade.php
git commit -m "feat(formularios): la franja entra en Potencialidad, la octava

Va encima de su rejilla y no dentro de la columna izquierda como en las otras
siete: es donde esta hoy su banner, y bajarla dentro exigiria mover mas de
cien lineas de marcado propio, que es la conversion que quedo fuera.

De paso, una evaluacion que todavia no existe pasa a pintar «Borrador» como
en los otros siete; el banner viejo no pintaba nada en ese caso.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 6: retirar los dos componentes y cerrar el hueco de la barra lateral

**Ficheros:**
- Modificar: `resources/views/components/barra-lateral-formulario.blade.php`
- Borrar: `resources/views/components/leyenda-escala.blade.php`
- Borrar: `resources/views/components/aviso-bloqueo-matriz.blade.php`
- Modificar: `tests/Feature/BarraLateralFormularioTest.php`
- Crear: `tests/Feature/FranjaEnLosOchoTest.php`

**Interfaces:** ninguna nueva. Es la limpieza y el cierre del hueco 1 de la
spec.

- [ ] **Paso 1: escribir los cinco tests que fallan**

Crea `tests/Feature/FranjaEnLosOchoTest.php`:

```php
<?php

namespace Tests\Feature;

use App\Matrices\Registro;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Que la franja esté en LOS OCHO, recorriendo el registro en vez de
 * enumerarlos a mano.
 *
 * Hermano de PaginaZonaTest::test_la_pagina_muestra_todas_las_entradas_del_registro:
 * el valor está en que no depende de que nadie se acuerde de la octava vista
 * -ni de la novena, si algún día se añade una matriz-.
 *
 * Recorre solo las entradas de tipo 'matriz', que son exactamente ocho.
 * Involucrados y Frecuentación son 'actores' y 'sitios': listas, no
 * formularios de criterios, y no llevan franja.
 */
class FranjaEnLosOchoTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe = User::factory()->create([
            'role_id' => Role::where('nombre', 'jefe_zona')->value('id'),
        ]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
    }

    /** @return array<string, array{0: string}> */
    private function formularios(): array
    {
        return collect(Registro::ENTRADAS)
            ->filter(fn(array $entrada) => $entrada['tipo'] === 'matriz')
            ->mapWithKeys(fn(array $entrada, string $clave) => [
                $clave => route($entrada['rutas']['editar'], $this->zona->id),
            ])
            ->all();
    }

    public function test_los_ocho_formularios_pintan_la_franja_en_borrador(): void
    {
        $urls = $this->formularios();

        $this->assertCount(8, $urls, 'El registro debería declarar ocho matrices con formulario.');

        foreach ($urls as $clave => $url) {
            $html = $this->actingAs($this->jefe)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'border-l-amber-500',
                $html,
                "{$clave}: el formulario no pinta la franja de borrador."
            );
        }
    }

    /**
     * Los textos retirados no vuelven por la puerta de atrás. Un formulario
     * que se reescriba copiando de una versión vieja los reintroduciría sin
     * que ningún otro test lo viera.
     */
    public function test_ningun_formulario_conserva_los_textos_retirados(): void
    {
        foreach ($this->formularios() as $clave => $url) {
            $html = $this->actingAs($this->jefe)->get($url)->assertOk()->getContent();

            foreach (['Modo Borrador', 'Escala de valoración', 'Última edición'] as $retirado) {
                $this->assertStringNotContainsString(
                    $retirado,
                    $html,
                    "{$clave}: conserva el texto retirado «{$retirado}»."
                );
            }
        }
    }

    /**
     * Seis llevan escala y dos no, y eso es cableado de cada vista: el test
     * del componente no puede verlo. Sin esto, pasarle :niveles a
     * Concentración -o olvidárselo a Paisaje- no rompería nada visible.
     *
     * La frase de método es el testigo: el componente solo la pinta cuando
     * hay escala, así que su presencia y su ausencia dicen exactamente cuál
     * de los dos casos se cableó.
     */
    public function test_las_seis_con_escala_la_pintan_y_las_dos_sin_escala_no(): void
    {
        $sinEscala = ['concentracion', 'irritacion'];
        $frase     = 'Elige la descripción que coincide con el territorio, no el número.';

        foreach ($this->formularios() as $clave => $url) {
            $html = $this->actingAs($this->jefe)->get($url)->assertOk()->getContent();

            if (in_array($clave, $sinEscala, true)) {
                $this->assertStringNotContainsString(
                    $frase,
                    $html,
                    "{$clave} no tiene escala 0-3, así que no debería pintarla."
                );

                continue;
            }

            $this->assertStringContainsString(
                $frase,
                $html,
                "{$clave} tiene escala y no la está pintando."
            );
        }
    }
}
```

Y añade estos dos a `tests/Feature/BarraLateralFormularioTest.php`, dentro de
la clase.

**Ese fichero renderiza el componente en aislado con su helper
`renderizar()`, no por HTTP**, y su `setUp()` guarda el jefe en una variable
**local**, no en `$this->jefe` —solo expone `$this->zona`—. Los dos tests de
abajo siguen su patrón; no uses `route()` ni `actingAs()` aquí.

Añade primero `use App\Models\EvaluacionFit;` a los `use` del fichero, que hoy
no lo importa.

```php
    /**
     * El hueco que encontró el diseño de la Fase 4: la barra lateral tiene su
     * propio «Guardar Borrador» -el que está siempre a la vista- y no avisaba
     * de que guardar una matriz validada la devuelve a borrador. El aviso
     * estaba solo junto a los botones del pie del formulario.
     */
    public function test_la_barra_lateral_avisa_de_que_guardar_reabre_una_validada(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'confirmado']);

        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos Turísticos', 'respondidos' => 2, 'total' => 2],
        ]);

        $this->assertStringContainsString(
            'Guardarla la devolverá a borrador y habrá que validarla de nuevo.',
            $html
        );
    }

    /**
     * Y no lo dice sobre un borrador: no habría nada que reabrir. Sin esta
     * mitad, el test de arriba pasaría igual con un aviso pintado siempre.
     */
    public function test_la_barra_lateral_no_avisa_de_reapertura_en_un_borrador(): void
    {
        EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']);

        $html = $this->renderizar([
            ['ancla' => 'rtt', 'etiqueta' => 'Recursos Turísticos', 'respondidos' => 1, 'total' => 2],
        ]);

        $this->assertStringNotContainsString('devolverá a borrador', $html);
    }
```

- [ ] **Paso 2: correrlos y verlos fallar**

```bash
php artisan test --filter="FranjaEnLosOchoTest|BarraLateralFormularioTest"
```

Esperado, con precisión —de los cinco, **solo falla uno**—:

- `test_la_barra_lateral_avisa_de_que_guardar_reabre_una_validada`: **FALLA**.
  Es el único que prueba lo que esta tarea añade.
- `test_la_barra_lateral_no_avisa_de_reapertura_en_un_borrador`: **pasa ya**,
  porque hoy no hay aviso en ninguno de los dos casos. Se vuelve significativo
  en el paso 3, cuando el aviso exista y haya que comprobar que no se pinta de
  más.
- Los **tres** de `FranjaEnLosOchoTest`: **pasan ya**, porque T2 a T5 dejaron
  los ocho formularios convertidos. Son una red de seguridad, no una
  funcionalidad nueva. **Si alguno falla, hay una vista mal convertida** y hay
  que arreglarla antes de seguir —el mensaje del test dice cuál—.

- [ ] **Paso 3: añadir el aviso a la barra lateral**

En `resources/views/components/barra-lateral-formulario.blade.php`, sustituye
el bloque del botón de guardar (hoy las líneas 93-98):

```blade
        @unless($bloqueado)
            <button type="submit" form="{{ $formulario }}" name="accion_estado" value="borrador"
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">
                Guardar Borrador
            </button>
        @endunless
```

por:

```blade
        @unless($bloqueado)
            {{-- El aviso acompaña al botón, no a la franja de arriba: no dice
                 QUÉ ES la matriz sino QUÉ VA A HACER este clic. Hasta la Fase
                 4 solo estaba junto a los botones del final del formulario, y
                 este de aquí -el que está siempre a la vista- reabría una
                 matriz validada sin advertirlo. --}}
            @if($evaluacionValidada)
                <x-aviso-reapertura class="mb-2" />
            @endif

            <button type="submit" form="{{ $formulario }}" name="accion_estado" value="borrador"
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">
                Guardar Borrador
            </button>
        @endunless
```

`$evaluacionValidada` no existe todavía. El componente ya carga la evaluación
en su `@php` cuando no le pasan `:total`/`:respondidos`, pero **no siempre**
—Potencialidad se los pasa—, así que no se puede depender de esa variable.
Añade el cálculo al final del `@php` de arriba (después de la línea del
`$porcentaje`), de forma que valga en los dos caminos:

```php
    // Deriva de la base, no de la rama de arriba: cuando el llamante pasa
    // :total y :respondidos -Potencialidad-, $evaluacion no se llega a cargar.
    $entradaFranja       = \App\Matrices\Registro::ENTRADAS[$clave];
    $modeloFranja        = $entradaFranja['modelo'];
    $filaFranja          = $modeloFranja::where('zona_id', $zona->id)->first(['estado']);
    $evaluacionValidada  = $filaFranja?->estado === 'confirmado';
```

- [ ] **Paso 4: borrar los dos componentes retirados**

Antes de borrar, comprueba que no los usa nadie:

```bash
grep -rn "x-leyenda-escala\|x-aviso-bloqueo-matriz" resources/ tests/
```

Esperado: **sin resultados**, salvo la mención de `<x-aviso-bloqueo-matriz>`
dentro del docblock de `aviso-reapertura.blade.php`, que es prosa y no un uso.
Si aparece un consumidor de verdad, **para**: significa que una de las tareas
T2-T5 se dejó una vista sin convertir.

```bash
git rm resources/views/components/leyenda-escala.blade.php resources/views/components/aviso-bloqueo-matriz.blade.php
```

Y quita del docblock de `resources/views/components/aviso-reapertura.blade.php`
la frase que nombra a su hermano ya inexistente:

```blade
    El aviso que ve el Jefe de Zona cuando guardar sobre una matriz o
    evaluación ya validada la va a devolver a borrador. Hermano de
    <x-aviso-bloqueo-matriz>: aquel avisa a quien NO puede tocar el
```

pasa a:

```blade
    El aviso que ve el Jefe de Zona cuando guardar sobre una matriz o
    evaluación ya validada la va a devolver a borrador. Su hermano
    <x-aviso-bloqueo-matriz> -que avisaba a quien NO puede tocar el
    formulario- lo absorbió la franja de estado en la Fase 4. Este se queda
    porque avisa a quien SÍ puede y no sabe la consecuencia, y desde esa fase
    acompaña a los DOS botones de guardar: el del pie y el de la barra
    lateral.
```

- [ ] **Paso 5: correr los tests tocados y verlos pasar**

```bash
php artisan test --filter="FranjaEnLosOchoTest|BarraLateralFormularioTest"
```

Esperado: PASAN los cinco.

- [ ] **Paso 6: construir los assets y correr la suite entera**

```bash
npm run build
php artisan test
```

Esperado: **659 tests en verde** (654 + 5).

- [ ] **Paso 7: commit**

```bash
git add -A resources/views/components tests/Feature/FranjaEnLosOchoTest.php tests/Feature/BarraLateralFormularioTest.php
git commit -m "refactor(formularios): retirados x-leyenda-escala y x-aviso-bloqueo-matriz

Los dos quedan absorbidos por la franja. Y la barra lateral gana el aviso de
reapertura que le faltaba: su boton «Guardar Borrador» -el que esta siempre a
la vista- reabria una matriz validada sin advertirlo, porque el aviso vivia
solo junto a los botones del pie.

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

---

### Tarea 7: verificación de navegador

**Ficheros:** ninguno se modifica —a menos que esta tarea encuentre algo, en
cuyo caso el arreglo vuelve a la tarea que lo provocó, con su propio test y su
propio commit, antes de continuar aquí.

**Por qué es su propia tarea:** en las cuatro fases anteriores de este mismo
rediseño, mirar la página de verdad encontró lo que ningún test veía. En la
Fase 3 no hubo hallazgos, y aun así la revisión posterior encontró dos
defectos; saltarse este paso por eso sería sacar la conclusión contraria.

**No hace falta Playwright.** La Fase 3 cerró su verificación con el navegador
de la propia sesión, midiendo sobre el DOM con `getBoundingClientRect()`,
`getComputedStyle()` y `scrollWidth`. Mismo método aquí.

- [ ] **Paso 1: levantar el servidor y sembrar datos**

```bash
npm run build
```

Levanta el servidor con la configuración `turismo` de `.claude/launch.json`.
Hace falta una zona con **FIT en borrador** (cuatro niveles, el caso ancho),
**FET validada**, y un usuario de equipo asignado a la zona para ver el tercer
estado.

- [ ] **Paso 2: las siete comprobaciones**

1. **A 1280 px, FIT en borrador:** la franja es una sola caja donde antes
   había tres, con el borde izquierdo ámbar; la barra lateral no se ha movido
   hacia abajo respecto de antes.
2. **A 375 px, FIT y FET:** son las de **cuatro** niveles, el caso ancho. Las
   píldoras de escala envuelven sin desbordar la franja y sin partir palabras;
   ni `<body>` ni `<html>` tienen scroll horizontal —compruébalo con
   `document.documentElement.scrollWidth` contra `clientWidth`—.
3. **Los tres estados, uno al lado del otro:** borrador (ámbar), validada
   mirándola el jefe (verde) y validada mirándola el equipo (neutro). **Que el
   verde y el neutro se distingan de verdad al mirarlos**, no solo en el
   nombre de la clase.
4. **La línea de «quién y cuándo»** no se pega a la escala cuando la escala es
   ancha: a 1280 px va a la derecha, y al envolver no se solapa.
5. **Potencialidad:** su CSS a medida no pelea con la franja. Es la única con
   `<style>` propio y la única donde la franja va encima de la rejilla.
6. **Los dos sin escala** —Concentración e Irritación—: su franja lleva solo
   el estado, sin un hueco raro donde iba la escala.
7. **Tipografía:** ningún texto de la franja por debajo de `text-sm` (14 px), y
   ninguna clase `uppercase`.

- [ ] **Paso 3: si algo falla, arreglarlo donde corresponde**

Un defecto del componente vuelve a la Tarea 1. Un defecto de una vista vuelve
a la tarea que la convirtió. Cada arreglo lleva su propio test: el defecto que
un test no puede ver hoy es exactamente el que este paso existe para cazar.

- [ ] **Paso 4: anotar lo encontrado**

Si no hay hallazgos, una línea en `.superpowers/sdd/progress.md` diciéndolo
—«verificación de navegador: sin hallazgos»— es preferible a un silencio que
alguien tenga que interpretar.

---

### Tarea 8: revisión final de la rama y traspaso

**Ficheros:**
- Modificar: `docs/ESTADO-PROYECTO.md`
- Modificar: `.superpowers/sdd/progress.md`
- Crear: `.superpowers/sdd/2026-08-15-formularios/task-8-report.md`

**Ojo con la ruta del informe:** va en **carpeta propia con fecha**, no en la
raíz de `.superpowers/sdd/`. Ahí ya viven los `task-N-report.md` de ramas
anteriores y escribir encima destruiría el rastro que la regla 3 de
`CLAUDE.md` manda conservar. La Fase 3 se tropezó con esto exactamente.

- [ ] **Paso 1: revisión de la rama entera**

```bash
git diff 2938e23..HEAD > .superpowers/sdd/review-fase4.diff
```

Usa `superpowers:requesting-code-review` sobre ese diff. Lee lo que devuelva
con `superpowers:receiving-code-review`: **verifica cada hallazgo antes de
implementarlo**. En la Fase 3, de diez puntos devueltos, dos eran ciertos y
se comprobaron con un test de usar y tirar antes de tocar nada.

Hay tres decisiones que un revisor podría señalar y que ya están respondidas
en la spec: el CSS de Potencialidad que no se convierte, la franja de
Potencialidad que va encima de la rejilla y no dentro, y el `null` por defecto
de `:niveles`.

- [ ] **Paso 2: correr la suite entera sobre el resultado final**

```bash
php artisan test
```

Esperado: **659 tests en verde**.

- [ ] **Paso 3: el traspaso al día**

En `docs/ESTADO-PROYECTO.md`:
- Entrada de la rama `fase-4-formularios`: qué se hizo, el recuento nuevo
  (659), y lo que las tareas 7 y 8 encontraron —o que no encontraron nada—.
- **Tacha la Fase 4 de §6, punto 14, y con ella el punto entero: es la última
  de las cuatro.** El rediseño de interfaz queda cerrado.
- Anota que `<x-leyenda-escala>` y `<x-aviso-bloqueo-matriz>` **ya no
  existen**, por si algún documento viejo los nombra.
- Anota la deuda que queda con nombre propio: **el CSS a medida de
  Potencialidad**, que sigue con su `<style>` y sus `style=` en línea.
- Anota que el punto 8 de §6 —la verificación del conmutador lista/tarjetas—
  **se puede cerrar sin Playwright**, con el navegador de la sesión, como se
  hizo en las fases 3 y 4.

En `.superpowers/sdd/progress.md`, cierra la bitácora con una línea por tarea
y lo que cada una encontró que el plan no decía.

- [ ] **Paso 4: commit**

```bash
git add docs/ESTADO-PROYECTO.md .superpowers/sdd/progress.md .superpowers/sdd/2026-08-15-formularios/task-8-report.md
git commit -m "docs(traspaso): la Fase 4 al dia, y el redisenio cerrado

Co-Authored-By: Claude Opus 5 <noreply@anthropic.com>"
```

**Fusionar se pregunta.** Una vez fusionado, se sube —junto con el contexto—,
y la suite se corre **sobre el resultado fusionado**, no solo sobre la rama
(regla 3 de `CLAUDE.md`).
