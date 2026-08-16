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

    /** @return array<string, string> */
    private function formularios(): array
    {
        return collect(Registro::ENTRADAS)
            ->filter(fn(array $entrada) => $entrada['tipo'] === 'matriz')
            ->mapWithKeys(fn(array $entrada, string $clave) => [
                $clave => route($entrada['rutas']['editar'], $this->zona->id),
            ])
            ->all();
    }

    /**
     * Afirma data-franja="borrador", no una clase Tailwind. La primera
     * versión de este test buscaba 'border-l-amber-500' en la página
     * entera, y esa clase no es exclusiva de la franja: <x-criterio-pildoras>
     * pinta el mismo border-l-amber-500 en sus píldoras de nivel medio, y
     * cinco de las ocho matrices usan píldoras. Se comprobó borrando la
     * línea <x-franja-matriz> de FIT y corriendo el test: seguía en verde,
     * porque las píldoras del formulario sostenían la aserción solas. El
     * atributo data-franja lo pinta solo este componente, así que ahora sí
     * hace falta que la franja esté.
     */
    public function test_los_ocho_formularios_pintan_la_franja_en_borrador(): void
    {
        $urls = $this->formularios();

        $this->assertCount(8, $urls, 'El registro debería declarar ocho matrices con formulario.');

        foreach ($urls as $clave => $url) {
            $html = $this->actingAs($this->jefe)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'data-franja="borrador"',
                $html,
                "{$clave}: el formulario no pinta la franja de borrador."
            );
        }
    }

    /**
     * Los textos retirados no vuelven por la puerta de atrás. Un formulario
     * que se reescriba copiando de una versión vieja los reintroduciría sin
     * que ningún otro test lo viera.
     *
     * Dos de los tres textos siguen vivos fuera de las matrices, y a
     * propósito: 'Modo Borrador' sigue en frecuentacion/index.blade.php e
     * involucrados/index.blade.php, y 'Última edición' sigue en las vistas
     * ponderacion.blade.php de cada matriz. Este test es seguro solo
     * porque formularios() filtra a tipo === 'matriz' -esas otras vistas
     * quedan fuera del recorrido-; si algún día se ampliara a otro tipo,
     * esta aserción dejaría de ser válida sin más aviso que este comentario.
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
     * La versión anterior de este test se llamaba
     * test_ningun_formulario_ofrece_actualizar_datos_a_quien_no_es_jefe y no
     * podía fallar: GETeaba las ocho vistas sin confirmar ninguna matriz, así
     * que $estaConfirmado era siempre false, $bloqueado también, y la rama
     * del pie que ofrecía «Actualizar Datos» —solo alcanzable con
     * $bloqueado— nunca llegaba a renderizarse. Encima, el texto «Actualizar
     * Datos» ya no existe en resources/ ni en app/: el test habría pasado
     * igual contra el código muerto completamente restaurado.
     *
     * Ahora confirma cada matriz en el bucle antes de pedir la página, así
     * que $bloqueado sí es true para el equipo y la rama de solo lectura sí
     * se ejecuta. De paso, este es el único sitio donde ese estado de solo
     * lectura se comprueba a través de una página real para paisaje,
     * valoracion_territorial y potencialidad: esas tres nunca tuvieron el
     * viejo aviso de bloqueo, así que «Validada · solo lectura» es
     * comportamiento nuevo justo ahí.
     */
    public function test_los_ocho_llegan_cerrados_a_quien_no_es_jefe(): void
    {
        $equipo = User::factory()->create([
            'role_id' => Role::where('nombre', 'equipo')->value('id'),
        ]);
        $this->zona->equipo()->attach($equipo->id);

        foreach ($this->formularios() as $clave => $url) {
            Registro::ENTRADAS[$clave]['modelo']::updateOrCreate(
                ['zona_id' => $this->zona->id],
                ['estado' => 'confirmado']
            );

            $html = $this->actingAs($equipo)->get($url)->assertOk()->getContent();

            $this->assertStringContainsString(
                'data-franja="cerrada"',
                $html,
                "{$clave}: no pinta la franja de solo lectura a quien no es jefe."
            );
            $this->assertStringNotContainsString(
                'Actualizar Datos',
                $html,
                "{$clave}: ofrece «Actualizar Datos» a quien no puede pulsarlo."
            );
            $this->assertStringNotContainsString(
                'Guardar Borrador',
                $html,
                "{$clave}: ofrece guardar sobre una matriz cerrada para quien mira."
            );
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
        // No se deriva de Registro::ENTRADAS: esa lista no lleva metadato
        // de escala, así que esta lista se mantiene a mano. El supuesto por
        // defecto es "tiene escala" -la rama else de abajo-, así que una
        // matriz nueva que se olvide de añadir aquí falla ruidoso (se le
        // exige la frase y no la tiene) en vez de pasar en silencio.
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
