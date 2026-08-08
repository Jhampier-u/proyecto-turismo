<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class IrritacionTest extends TestCase
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
     * La escala es inversa y el desplegable tiene que decirlo: un 7 suelto no
     * significa nada, «7 — Crítico» sí. Es lo que evita tener que consultar la
     * tabla de rangos del instrumento aparte.
     */
    public function test_el_desplegable_etiqueta_cada_valor_con_su_clasificacion(): void
    {
        $this->withViewErrors([]);

        $html = (string) $this->blade('<x-select-0-10 label="Congestión" name="c" :val="null" />');

        $this->assertStringContainsString('0 — Bajo', $html);
        $this->assertStringContainsString('2 — Bajo', $html);
        $this->assertStringContainsString('3 — Moderado', $html);
        $this->assertStringContainsString('6 — Moderado', $html);
        $this->assertStringContainsString('7 — Crítico', $html);
        $this->assertStringContainsString('10 — Crítico', $html);

        // Un "<=" mal escrito en el bucle del componente pasaría igual las
        // aserciones de arriba (contains, no cuenta); esto lo delata. Once
        // valores más el hueco, ni uno más: el servidor validará 0..10 y una
        // opción de sobra dejaría elegir algo que el backend rechazaría sin
        // explicar por qué.
        $this->assertSame(12, substr_count($html, '<option '), 'La escala no tiene once valores más el hueco.');
    }

    /**
     * Los tres tramos en sus bordes exactos. El instrumento se contradice a sí
     * mismo en una tabla —dice «De 3 a 5» en un lado y «De 3 a 6» en el otro—
     * pero todas sus fórmulas usan >=3, y eso es lo que se implementa.
     */
    public function test_la_clasificacion_respeta_los_umbrales_del_instrumento(): void
    {
        // Pares y no un array asociativo: PHP trunca las claves float a
        // entero, así que 2.9 pisaría a 2.0 y los dos casos con decimales
        // —los que de verdad distinguen >= de >— nunca se llegarían a probar.
        $casos = [
            [0.0, 'Bajo'], [2.0, 'Bajo'], [2.9, 'Bajo'],
            [3.0, 'Moderado'], [6.0, 'Moderado'], [6.9, 'Moderado'],
            [7.0, 'Crítico'], [10.0, 'Crítico'],
        ];

        foreach ($casos as [$valor, $esperada]) {
            $this->assertSame(
                $esperada,
                \App\Models\EvaluacionIrritacion::clasificar($valor),
                "El promedio {$valor} no se clasificó como {$esperada}."
            );
        }
    }

    /** Sin promedio no hay clasificación: la matriz está a medias. */
    public function test_sin_promedio_no_hay_clasificacion(): void
    {
        $this->assertNull(\App\Models\EvaluacionIrritacion::clasificar(null));
    }
}
