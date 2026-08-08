<?php

namespace Tests\Feature;

use App\Matrices\Involucrados;
use App\Models\Involucrado;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InvolucradosTest extends TestCase
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

    /**
     * Los siete tipos de Mitchell y el caso sin ninguno.
     *
     * La tabla del instrumento original asocia «Exigentes» a legitimidad y
     * «Discrecionales» a urgencia, que es al revés de como los define Mitchell:
     * demanding es el que solo tiene urgencia y discretionary el que solo tiene
     * legitimidad. Se implementa según la fuente, no según la hoja.
     */
    public function test_los_tipos_de_mitchell_salen_de_los_tres_atributos(): void
    {
        $casos = [
            [false, false, false, 'No es actor relevante'],
            [true,  false, false, 'Adormecido'],
            [false, true,  false, 'Discrecional'],
            [false, false, true,  'Exigente'],
            [true,  false, true,  'Peligroso'],
            [true,  true,  false, 'Dominante'],
            [false, true,  true,  'Dependiente'],
            [true,  true,  true,  'Definitivo'],
        ];

        foreach ($casos as [$poder, $legitimidad, $urgencia, $esperado]) {
            $this->assertSame(
                $esperado,
                Involucrados::tipoDe($poder, $legitimidad, $urgencia),
                sprintf('poder=%d legitimidad=%d urgencia=%d', $poder, $legitimidad, $urgencia)
            );
        }
    }

    /** Los once criterios al mismo valor, para no repetir la lista en cada test. */
    private function todosEn(int $valor): array
    {
        return array_fill_keys(Involucrados::campos(), $valor);
    }

    public function test_un_actor_esta_completo_solo_con_sus_once_criterios(): void
    {
        $actor = Involucrado::create($this->todosEn(2) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor completo',
        ]);

        $this->assertTrue($actor->estaCompleto());

        // El null se asigna DESPUÉS de crear el actor, y no metido en el
        // array de creación con "+": la unión de arrays con "+" conserva el
        // operando izquierdo cuando la clave se repite en los dos lados, así
        // que un ['leg_sociedad' => null] a la derecha de todosEn(2) nunca
        // llegaría a pisar el 2 que ya está puesto ahí, y este test pasaría
        // sin probar nada.
        $actor->leg_sociedad = null;
        $actor->save();

        $this->assertFalse($actor->fresh()->estaCompleto());
    }

    public function test_grado_suma_los_criterios_del_atributo_o_null_si_falta_alguno(): void
    {
        $actor = Involucrado::create($this->todosEn(3) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Actor con todo a 3',
        ]);

        // Siete criterios a 3 dan 21 en poder, dos a 3 dan 6 en legitimidad
        // (y en urgencia, que también tiene dos).
        $this->assertSame(21, $actor->grado('poder'));
        $this->assertSame(6, $actor->grado('legitimidad'));
        $this->assertSame(6, $actor->grado('urgencia'));

        $actor->pod_poder = null;
        $actor->save();
        $actor->refresh();

        $this->assertNull($actor->grado('poder'));
        // Un hueco en poder no debería afectar a los otros dos atributos.
        $this->assertSame(6, $actor->grado('legitimidad'));
    }

    public function test_el_scope_incompletos_encuentra_a_los_que_faltan_y_no_a_los_completos(): void
    {
        $completo = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Completo',
        ]);

        $incompleto = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Incompleto',
        ]);
        $incompleto->urg_criticidad = null;
        $incompleto->save();

        $ids = Involucrado::incompletos()->pluck('id');

        $this->assertTrue($ids->contains($incompleto->id));
        $this->assertFalse($ids->contains($completo->id));
        $this->assertSame(1, Involucrado::incompletos()->count());
    }

    /**
     * Sin el paréntesis que agrupa los orWhereNull en scopeIncompletos(), el
     * primer "or" se saldría del ->where('zona_id', ...) anterior y un actor
     * incompleto de OTRA zona aparecería igual en la consulta filtrada por
     * esta.
     */
    public function test_el_scope_incompletos_no_se_sale_de_un_filtro_previo_por_zona(): void
    {
        $otraZona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Otra zona',
        ]);

        Involucrado::create($this->todosEn(1) + [
            'zona_id' => $this->zona->id,
            'nombre'  => 'Completo de esta zona',
        ]);

        $incompletoDeOtraZona = Involucrado::create($this->todosEn(1) + [
            'zona_id' => $otraZona->id,
            'nombre'  => 'Incompleto de otra zona',
        ]);
        $incompletoDeOtraZona->pod_poder = null;
        $incompletoDeOtraZona->save();

        $incompletosDeEstaZona = Involucrado::where('zona_id', $this->zona->id)->incompletos()->count();

        $this->assertSame(0, $incompletosDeEstaZona);
    }
}
