<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * El contrato de <x-migas>, fijado sobre el componente y no sobre las vistas
 * que lo usan.
 *
 * Hereda la lógica de raíz que era de <x-boton-volver>: la jerarquía es lista
 * de zonas → zona → matriz, y quién es la lista de arriba depende del rol. Ese
 * ternario vive en UN sitio a propósito: replicado es exactamente la forma que
 * tomó el fallo que dejó al admin viendo enlaces de edición durante toda una
 * rama.
 */
class MigasTest extends TestCase
{
    use RefreshDatabase;

    private User $jefe;
    private User $admin;
    private User $equipo;
    private Zona $zona;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(SystemSeeder::class);

        $this->jefe  = User::factory()->create(['role_id' => Role::where('nombre', 'jefe_zona')->value('id')]);
        $this->admin  = User::factory()->create(['role_id' => Role::where('nombre', 'admin')->value('id')]);
        $this->equipo = User::factory()->create(['role_id' => Role::where('nombre', 'equipo')->value('id')]);

        $this->zona = Zona::create([
            'lugar_id'     => DB::table('lugares')->value('id'),
            'jefe_user_id' => $this->jefe->id,
            'nombre'       => 'Zona de prueba',
        ]);
        $this->zona->equipo()->attach($this->equipo->id);
    }

    /**
     * Los tres roles suben al mismo sitio desde una matriz: el panel de ESA
     * zona.
     *
     * Esta cobertura viene de `BotonVolverTest`, que se borró al borrarse el
     * componente. No se da por hecha porque el equipo caiga en la misma rama
     * que el jefe: el fallo que motivó aquel test era precisamente de rol —el
     * admin viendo enlaces que no le tocaban durante toda una rama— y un
     * ternario de rol es lo que hay debajo.
     */
    public function test_los_tres_roles_suben_al_panel_de_la_misma_zona(): void
    {
        foreach ([$this->jefe, $this->equipo, $this->admin] as $usuario) {
            $html = $this->migas($usuario, '<x-migas :zona="$zona" clave="fit" actual="Formulario" />');

            $this->assertStringContainsString(
                route('operativo.zona.panel', $this->zona->id),
                $html,
                'Un rol no sube al panel de la zona desde una matriz.'
            );
        }
    }

    private function migas(User $usuario, string $etiqueta): string
    {
        return (string) $this->actingAs($usuario)->blade($etiqueta, ['zona' => $this->zona]);
    }

    public function test_la_raiz_del_operativo_es_mis_zonas(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" />');

        $this->assertStringContainsString('Mis Zonas', $html);
        $this->assertStringContainsString(route('operativo.dashboard'), $html);
    }

    public function test_la_raiz_del_admin_es_su_listado_de_zonas(): void
    {
        $html = $this->migas($this->admin, '<x-migas :zona="$zona" />');

        $this->assertStringContainsString('Zonas', $html);
        $this->assertStringContainsString(route('admin.zonas.index'), $html);
        $this->assertStringNotContainsString(route('operativo.dashboard'), $html);
    }

    /**
     * El último tramo no es enlace, lo pida quien lo pida. Sin esto, la miga de
     * la página actual llevaría a la página actual: un enlace que no hace nada
     * y que además invita a pulsarlo.
     */
    public function test_el_ultimo_tramo_no_es_enlace(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" />');

        // La zona es el último tramo aquí, así que su destino NO puede estar.
        $this->assertStringContainsString('Zona de prueba', $html);
        $this->assertStringNotContainsString(route('operativo.zona.panel', $this->zona->id), $html);
    }

    /**
     * Con una hoja, la zona SÍ pasa a ser enlace: deja de ser el último tramo.
     * Es la contraparte del test anterior; sin ella, un componente que no
     * pintara nunca el enlace de la zona pasaría los dos.
     */
    public function test_con_hoja_la_zona_pasa_a_ser_enlace(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" actual="Inventario" />');

        $this->assertStringContainsString(route('operativo.zona.panel', $this->zona->id), $html);
        $this->assertStringContainsString('Inventario', $html);
    }

    /**
     * El nombre de la matriz sale del Registro y no de la vista. Si se
     * escribiera a mano, la miga y la pestaña podrían acabar diciendo cosas
     * distintas del mismo criterio —que es lo que costó dos ramas cerrar en las
     * etiquetas de FIT y FET—.
     */
    public function test_el_nombre_de_la_matriz_sale_del_registro(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" clave="fit" actual="Resultados" />');

        $this->assertStringContainsString(
            \App\Matrices\Registro::ENTRADAS['fit']['nombre'],
            $html
        );
    }

    /**
     * La miga de un formulario de matriz nombra la zona y la matriz.
     *
     * Se comprueba sobre la página servida y no sobre el componente: lo que
     * hay que garantizar no es que <x-migas> sepa recibir una clave, sino que
     * esta vista se la pase —y que se la pase igual que a sus pestañas—.
     */
    public function test_el_formulario_de_una_matriz_trae_sus_migas(): void
    {
        $html = $this->actingAs($this->jefe)
            ->get(route('operativo.evaluacion_fit.edit', $this->zona->id))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Mis Zonas', $html);
        $this->assertStringContainsString('Zona de prueba', $html);
        $this->assertStringContainsString(\App\Matrices\Registro::ENTRADAS['fit']['nombre'], $html);
        $this->assertStringContainsString(route('operativo.dashboard'), $html);
    }

    /**
     * Una matriz derivada no enlaza a un formulario que no tiene.
     *
     * `vtt` es de tipo 'resultado': se calcula a partir de FIT y FET y no
     * tiene pantalla de edición, así que su entrada del Registro trae `ver` y
     * NO trae `editar`. Pedirle `editar` reventaba con un aviso de índice
     * indefinido, que es un error feo por una situación legítima.
     *
     * El tramo se pinta igual —el nombre hace falta para saber dónde estás—
     * pero sin enlace.
     */
    public function test_una_matriz_sin_formulario_nombra_pero_no_enlaza(): void
    {
        $html = $this->migas($this->jefe, '<x-migas :zona="$zona" clave="vtt" actual="Resultados" />');

        $this->assertStringContainsString(
            \App\Matrices\Registro::ENTRADAS['vtt']['nombre'],
            $html
        );

        // No hay ruta 'editar' que ofrecer, así que tampoco hay <a> para ese
        // tramo: el único enlace que queda por encima es el de la zona.
        $this->assertSame(
            2,
            substr_count($html, '<a href'),
            'La miga de una matriz derivada enlaza a algo, y no debería: solo la raíz y la zona son enlaces.'
        );
    }

    /**
     * Ningún tramo enlaza a la página en la que ya estás, no solo la hoja.
     *
     * La ruta `editar` de una matriz ES la página del formulario, así que en
     * los nueve formularios el tramo de la matriz apuntaba a la pantalla que se
     * estaba viendo: «FIT» recargaba lo que ya tenías delante. La regla ya
     * estaba escrita en el componente —un enlace a donde ya estás no hace nada
     * y se pulsa igual— pero solo se aplicaba al último tramo.
     *
     * Se mira solo dentro del rastro: las pestañas de debajo son otro
     * componente y otra conversación.
     */
    public function test_ningun_tramo_enlaza_a_la_pagina_en_la_que_ya_estas(): void
    {
        $aqui = route('operativo.evaluacion_fit.edit', $this->zona->id);

        $rastro = $this->rastro(
            $this->actingAs($this->jefe)->get($aqui)->assertOk()->getContent()
        );

        $this->assertStringContainsString(
            \App\Matrices\Registro::ENTRADAS['fit']['nombre'],
            $rastro,
            'El tramo de la matriz tiene que seguir pintándose: el nombre es lo que dice dónde estás.'
        );

        $this->assertStringNotContainsString(
            'href="' . $aqui . '"',
            $rastro,
            'Un tramo de la miga enlaza a la página que ya se está viendo.'
        );
    }

    /**
     * Contraparte del anterior: cuando el tramo lleva a OTRA página, sigue
     * siendo enlace. Sin esto, un componente que no pintara nunca el enlace de
     * la matriz pasaría los dos.
     *
     * Se usa «nuevo recurso» del inventario porque su tramo padre —el índice—
     * es una página distinta de la que se está viendo, y porque no hace falta
     * sembrar una evaluación para llegar a ella.
     */
    public function test_un_tramo_que_lleva_a_otra_pagina_si_es_enlace(): void
    {
        $rastro = $this->rastro(
            $this->actingAs($this->jefe)
                ->get(route('operativo.inventarios.create', $this->zona->id))
                ->assertOk()
                ->getContent()
        );

        $this->assertStringContainsString(
            'href="' . route('operativo.inventarios.index', $this->zona->id) . '"',
            $rastro,
            'El tramo del inventario debería llevar a su índice, que es otra página.'
        );
    }

    /**
     * El rastro y nada más. Una página entera trae enlaces de las pestañas, de
     * la barra y de las tarjetas; afirmar sobre todos ellos mediría cualquier
     * cosa menos la miga.
     */
    private function rastro(string $html): string
    {
        $encontrado = preg_match('/<nav aria-label="Migas de pan".*?<\/nav>/s', $html, $coincidencias);

        $this->assertSame(1, $encontrado, 'La página no trae ningún rastro de migas que revisar.');

        return $coincidencias[0];
    }

    public function test_una_clave_desconocida_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage('clave «no-existe» desconocida');

        $this->migas($this->jefe, '<x-migas :zona="$zona" clave="no-existe" />');
    }

    /**
     * Una matriz sin zona no tiene destino posible —su ruta necesita el id—,
     * así que revienta aquí en vez de construir una URL rota. Mismo patrón de
     * guardia que <x-barra-lateral-formulario> y <x-resumen-lista>.
     */
    public function test_una_matriz_sin_zona_revienta(): void
    {
        $this->expectException(\Illuminate\View\ViewException::class);
        $this->expectExceptionMessage(':zona es obligatoria cuando se da una clave');

        $this->actingAs($this->jefe)->blade('<x-migas clave="fit" />');
    }
}
