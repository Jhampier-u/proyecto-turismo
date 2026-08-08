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

        $this->assertStringContainsString('<option value="" selected>', $html);
        $this->assertStringContainsString('0 — Bajo', $html);
        $this->assertStringContainsString('2 — Bajo', $html);
        $this->assertStringContainsString('3 — Moderado', $html);
        $this->assertStringContainsString('6 — Moderado', $html);
        $this->assertStringContainsString('7 — Crítico', $html);
        $this->assertStringContainsString('10 — Crítico', $html);
    }

    /** Mismo contrato que los demás desplegables: el hueco no es un cero. */
    public function test_el_desplegable_distingue_el_hueco_del_cero(): void
    {
        $this->withViewErrors([]);

        $conCero = (string) $this->blade('<x-select-0-10 label="C" name="c" :val="0" />');

        $this->assertStringContainsString('<option value="0" selected>', $conCero);
        $this->assertStringNotContainsString('<option value="" selected>', $conCero);
    }
}
