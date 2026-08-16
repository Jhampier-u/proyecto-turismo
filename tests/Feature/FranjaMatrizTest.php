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

    /**
     * Solo hay paleta para 3 y 4 niveles. Los colores se consumen por
     * posición ($colores[$loop->index]), así que una escala de otro tamaño
     * -aquí, dos- caería en la paleta de 3 y se saldría del array: un punto
     * sin color y un aviso de PHP, en vez de un error claro. Mejor que
     * reviente aquí, con un mensaje que dice qué falta, a que adivine mal en
     * silencio.
     */
    public function test_una_escala_sin_paleta_definida_revienta_en_vez_de_adivinar(): void
    {
        $this->actingAs($this->jefe);

        // Blade envuelve cualquier excepción lanzada al renderizar un
        // componente en ViewException -no deja pasar la original tal cual-,
        // igual que en BarraLateralFormularioTest, así que se comprueba el
        // mensaje en vez del tipo original.
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('no hay paleta para una escala de 2 niveles');

        $this->render(
            EvaluacionFit::create(['zona_id' => $this->zona->id, 'estado' => 'borrador']),
            [0 => 'No', 1 => 'Sí']
        );
    }
}
