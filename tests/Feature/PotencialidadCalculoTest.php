<?php

namespace Tests\Feature;

use App\Http\Controllers\Operativo\EvaluacionPotencialidadController as Ctrl;
use App\Models\EvaluacionPotencialidad;
use App\Models\PotencialidadCamposActivos;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Caracterización: fija lo que calcular() hace HOY, antes de tocarla.
 *
 * No juzga si el comportamiento es correcto —parte de él no lo es, de ahí el
 * cambio—; solo lo congela para que la modificación posterior no mueva nada
 * que no queramos mover.
 */
class PotencialidadCalculoTest extends TestCase
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

    /** @return list<string> todos los nombres de campo del instrumento */
    private function todosLosCampos(): array
    {
        return collect(Ctrl::$secciones)
            ->flatMap(fn(array $campos) => array_keys($campos))
            ->all();
    }

    /** @return list<string> los campos de una sección concreta */
    private function camposDe(string $seccion): array
    {
        return array_keys(Ctrl::$secciones[$seccion]);
    }

    private function url(): string
    {
        return "/operativo/zona/{$this->zona->id}/evaluacion-potencialidad";
    }

    /**
     * Guarda con los campos indicados en $valores y el resto de campos activos
     * enviados explícitamente al valor $relleno.
     */
    private function guardar(array $valores, array $activos, int $relleno = 0): EvaluacionPotencialidad
    {
        $datos = ['campos' => $activos];

        foreach ($activos as $campo) {
            $datos[$campo] = $valores[$campo] ?? $relleno;
        }

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        return EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();
    }

    public function test_todo_al_maximo_con_todos_los_campos_activos_da_dos(): void
    {
        $eval = $this->guardar([], $this->todosLosCampos(), relleno: 2);

        $this->assertEqualsWithDelta(2.0, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->fx_total, 0.0001);
    }

    public function test_todo_a_cero_da_cero(): void
    {
        $eval = $this->guardar([], $this->todosLosCampos(), relleno: 0);

        $this->assertEqualsWithDelta(0.0, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->fx_total, 0.0001);
    }

    /**
     * FX pondera Afluencia 0.40, Marketing 0.30 y Superestructura 0.30.
     * Solo Afluencia al máximo debe dar 2 * 0.40 = 0.80.
     */
    public function test_los_pesos_de_fx_son_40_30_30(): void
    {
        $valores = array_fill_keys($this->camposDe('Afluencia Turística'), 2);

        $eval = $this->guardar($valores, $this->todosLosCampos(), relleno: 0);

        $this->assertEqualsWithDelta(0.80, $eval->fx_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->val_afluencia, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->val_marketing, 0.0001);
    }

    /**
     * Con solo Afluencia activa, su 0.40 se renormaliza a 1.0: el resultado
     * pasa de 0.80 a 2.0. Es la redistribución de pesos, y es lo más fácil de
     * romper sin darse cuenta.
     */
    public function test_desactivar_grupos_renormaliza_los_pesos_de_fx(): void
    {
        $activos = $this->camposDe('Afluencia Turística');
        $valores = array_fill_keys($activos, 2);

        $eval = $this->guardar($valores, $activos);

        $this->assertEqualsWithDelta(2.0, $eval->fx_total, 0.0001);
    }

    /** FN pondera RT 0.40, PT 0.20, TT 0.20 e Infraestructura 0.20. */
    public function test_los_pesos_de_fn_son_40_20_20_20(): void
    {
        $valores = array_fill_keys($this->camposDe('Infraestructura'), 2);

        $eval = $this->guardar($valores, $this->todosLosCampos(), relleno: 0);

        $this->assertEqualsWithDelta(0.40, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(2.0, $eval->val_infraestructura, 0.0001);
    }

    /**
     * Con solo Infraestructura activa (excluyendo secciones enteras, no solo
     * poniéndolas a cero), su peso 0.20 se renormaliza a 1.0: el resultado
     * pasa de 0.40 (test_los_pesos_de_fn_son_40_20_20_20, arriba) a 2.0.
     *
     * $fn_pesos (líneas ~409-422 de calcular()) es más arriesgado que
     * $fx_pesos porque anida la redistribución de RT entre Recursos
     * Naturales y Culturales; este test es el equivalente de
     * test_desactivar_grupos_renormaliza_los_pesos_de_fx pero para FN.
     */
    public function test_desactivar_grupos_renormaliza_los_pesos_de_fn(): void
    {
        $activos = $this->camposDe('Infraestructura');
        $valores = array_fill_keys($activos, 2);

        $eval = $this->guardar($valores, $activos);

        $this->assertEqualsWithDelta(2.0, $eval->fn_total, 0.0001);
    }

    /**
     * La renormalización de FN debe repartir proporcionalmente a los pesos
     * originales, no dividir a partes iguales entre los grupos que queden
     * activos. Aquí quedan activos RT (peso original 0.40) e Infraestructura
     * (peso original 0.20) — pesos desiguales a propósito—, así que una
     * implementación que repartiera 50/50 entre los dos grupos activos
     * daría un resultado distinto y este test lo detectaría.
     *
     * Derivación:
     * - Único subgrupo de RN activo: Cuerpos de Agua, al máximo (2) →
     *   val_recursos_naturales = 2; sin RC activo → val_recursos_turisticos
     *   = val_recursos_naturales = 2 (rama `elseif ($hasRN)` en calcular()).
     * - val_infraestructura = 0 (activa pero enviada a 0).
     * - $fn_pesos = ['rt' => 0.40, 'i' => 0.20], $fn_sum_pesos = 0.60.
     * - fn_total = 2 * (0.40/0.60) + 0 * (0.20/0.60) = 4/3.
     */
    public function test_renormalizacion_de_fn_respeta_proporciones_desiguales(): void
    {
        $activos = array_merge(
            $this->camposDe('RN — Cuerpos de Agua'),
            $this->camposDe('Infraestructura')
        );
        $valores = array_fill_keys($this->camposDe('RN — Cuerpos de Agua'), 2);

        $eval = $this->guardar($valores, $activos);

        $this->assertEqualsWithDelta(4 / 3, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->val_recursos_culturales, 0.0001);
    }

    /** RT es la media de Recursos Naturales y Culturales al 50 % cada uno. */
    public function test_recursos_turisticos_promedia_naturales_y_culturales(): void
    {
        $valores = array_fill_keys($this->camposDe('RN — Cuerpos de Agua'), 2);

        $eval = $this->guardar($valores, $this->todosLosCampos(), relleno: 0);

        // RN tiene 4 subgrupos; solo uno al máximo → val_rn = 2/4 = 0.5
        $this->assertEqualsWithDelta(0.5, $eval->val_recursos_naturales, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->val_recursos_culturales, 0.0001);
        $this->assertEqualsWithDelta(0.25, $eval->val_recursos_turisticos, 0.0001);
    }

    /** Sin ningún recurso cultural activo, RT es RN a secas, sin promediar con 0. */
    public function test_sin_recursos_culturales_activos_rt_es_solo_rn(): void
    {
        $activos = $this->camposDe('RN — Cuerpos de Agua');
        $valores = array_fill_keys($activos, 2);

        $eval = $this->guardar($valores, $activos);

        $this->assertEqualsWithDelta(2.0, $eval->val_recursos_turisticos, 0.0001);
    }

    /**
     * Ni RN ni RC tienen campos activos (solo Infraestructura) → val_rt cae
     * en la rama `else` de calcular() (línea ~386) y vale 0 explícitamente,
     * no por casualidad de promediar con ceros.
     */
    public function test_val_recursos_turisticos_es_cero_sin_recursos_naturales_ni_culturales_activos(): void
    {
        $activos = $this->camposDe('Infraestructura');
        $valores = array_fill_keys($activos, 2);

        $eval = $this->guardar($valores, $activos);

        $this->assertEqualsWithDelta(0.0, $eval->val_recursos_turisticos, 0.0001);
    }

    /**
     * ESTE es el fallo que el cambio va a corregir, congelado tal cual está.
     *
     * Un campo activo que no se envía se guarda como 0 y cuenta en la media:
     * «no lo he mirado» acaba puntuando igual que «lo he mirado y no hay nada».
     * Cuando la Task 5 lo arregle, este test se reescribe con el comportamiento
     * nuevo — a propósito y de forma visible en el diff.
     */
    public function test_comportamiento_actual_un_campo_no_enviado_cuenta_como_cero(): void
    {
        $activos = $this->camposDe('Afluencia Turística');

        $datos = ['campos' => $activos];
        foreach ($activos as $campo) {
            $datos[$campo] = 2;
        }

        // Se omite uno de los cinco campos de la sección.
        unset($datos['dt_at_estadia']);

        $this->actingAs($this->jefe)->post($this->url(), $datos)
            ->assertSessionHasNoErrors();

        $eval = EvaluacionPotencialidad::where('zona_id', $this->zona->id)->firstOrFail();

        // 4 campos a 2 y uno a 0 → media 1.6, no 2.
        $this->assertEqualsWithDelta(1.6, $eval->val_afluencia, 0.0001);
        $this->assertSame(0, (int) $eval->dt_at_estadia);
    }

    /** Un campo desactivado conserva el valor que tenía guardado. */
    public function test_desactivar_un_campo_conserva_su_valor_anterior(): void
    {
        $todos = $this->todosLosCampos();
        $this->guardar([], $todos, relleno: 2);

        $activos = $this->camposDe('Afluencia Turística');
        $eval = $this->guardar(array_fill_keys($activos, 1), $activos);

        // 'i_transporte' quedó desactivado pero mantiene el 2 anterior.
        $this->assertSame(2, (int) $eval->i_transporte);
    }

    public function test_la_configuracion_de_campos_activos_se_persiste(): void
    {
        $activos = $this->camposDe('Afluencia Turística');
        $this->guardar(array_fill_keys($activos, 2), $activos);

        $config = PotencialidadCamposActivos::where('zona_id', $this->zona->id)->firstOrFail();

        $this->assertSame($activos, $config->campos_activos);
    }

    /**
     * Cero campos activos en absoluto: todos los `$fn_pesos`/`$fx_pesos`
     * quedan vacíos, así que `array_sum($fn_pesos) ?: 1` (línea ~417) y su
     * equivalente de FX (línea ~439) caen al `1` del fallback — evita la
     * división entre cero, pero como ningún `isset($fn_pesos[...])` es
     * cierto, ningún término se suma nunca a `$fn_total`/`$fx_total`, así
     * que el resultado es 0 de todas formas. No debe reventar.
     */
    public function test_cero_campos_activos_no_revienta_y_todo_da_cero(): void
    {
        $eval = $this->guardar([], []);

        $this->assertEqualsWithDelta(0.0, $eval->fn_total, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->fx_total, 0.0001);
        $this->assertEqualsWithDelta(0.0, $eval->val_recursos_turisticos, 0.0001);
    }
}
