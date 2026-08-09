<?php

namespace Tests\Feature;

use App\Matrices\Registro;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class RegistroMatricesTest extends TestCase
{
    /**
     * El bug que este test existe para impedir: la ruta admin.zonas.paisaje
     * existía pero ninguna vista la enlazaba, así que la Matriz de Paisaje era
     * inalcanzable para el admin y nadie se enteró.
     */
    public function test_todas_las_rutas_declaradas_existen(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            foreach ($entrada['rutas'] as $papel => $ruta) {
                $this->assertTrue(
                    Route::has($ruta),
                    "{$clave}: la ruta '{$ruta}' ({$papel}) no está registrada."
                );
            }
        }
    }

    public function test_todos_los_modelos_declarados_existen(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            if ($entrada['modelo'] === null) {
                continue;
            }

            $this->assertTrue(
                class_exists($entrada['modelo']),
                "{$clave}: la clase {$entrada['modelo']} no existe."
            );
        }
    }

    public function test_toda_entrada_pertenece_a_un_grupo_declarado(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            $this->assertArrayHasKey(
                $entrada['grupo'],
                Registro::GRUPOS,
                "{$clave}: el grupo '{$entrada['grupo']}' no está declarado."
            );
        }
    }

    public function test_toda_dependencia_apunta_a_una_entrada_existente(): void
    {
        foreach (Registro::ENTRADAS as $clave => $entrada) {
            foreach ($entrada['depende_de'] as $dependencia) {
                $this->assertArrayHasKey(
                    $dependencia,
                    Registro::ENTRADAS,
                    "{$clave}: depende de '{$dependencia}', que no existe."
                );
            }
        }
    }

    /**
     * El progreso de una zona se cuenta sobre las matrices validables.
     * Inventario no tiene estado y Vocación es un resultado derivado: si
     * entraran en el recuento, el denominador mentiría.
     */
    public function test_solo_las_matrices_validables_cuentan_para_el_progreso(): void
    {
        $this->assertCount(9, Registro::matrices());

        foreach (Registro::matrices() as $clave => $entrada) {
            $this->assertContains($entrada['tipo'], Registro::TIPOS_VALIDABLES, $clave);
            $this->assertNotNull($entrada['modelo'], $clave);
        }

        $this->assertArrayNotHasKey('inventario', Registro::matrices());
        $this->assertArrayNotHasKey('vtt', Registro::matrices());
    }

    public function test_los_grupos_se_declaran_en_orden_metodologico(): void
    {
        $this->assertSame(
            ['base', 'vocacion', 'valoracion', 'social', 'presion'],
            array_keys(Registro::GRUPOS)
        );
    }

    /**
     * El número de criterios alimenta el «21 de 34 respondidos». Un número mal
     * copiado no rompe nada visible, así que se comprueba solo donde se puede:
     * Paisaje y Valoración Territorial exponen todos() y son verificables.
     *
     * FIT, FET, Percepción y Potencialidad declaran sus criterios en métodos
     * protegidos de sus controladores, sin superficie pública que consultar;
     * los suyos se verifican a mano en el Step 5 de esta tarea.
     */
    public function test_los_criterios_declarados_coinciden_con_el_instrumento(): void
    {
        $verificables = [
            'paisaje'                => \App\Matrices\Paisaje::class,
            'valoracion_territorial' => \App\Matrices\ValoracionTerritorial::class,
            'irritacion'             => \App\Matrices\Irritacion::class,
        ];

        foreach ($verificables as $clave => $matriz) {
            $this->assertSame(
                count($matriz::todos()),
                Registro::ENTRADAS[$clave]['criterios'],
                "{$clave}: el registro declara un número de criterios que no cuadra con {$matriz}."
            );
        }

        // Concentración va aparte porque su lista se llama campos(), no todos():
        // no puntúa criterios, cuenta cantidades, y el nombre lo dice. Se
        // comprueba igual, porque su clase se genera desde el Excel y el número
        // del registro está copiado a mano.
        $this->assertSame(
            count(\App\Matrices\Concentracion::campos()),
            Registro::ENTRADAS['concentracion']['criterios'],
            'concentracion: el registro declara un número que no cuadra con la clase generada.'
        );
    }

    /**
     * icono.blade.php degrada en silencio un nombre que no reconoce a un
     * círculo genérico: sin este test, una errata de tipeo en 'icono' no se
     * nota hasta que alguien la ve a ojo en la pantalla, que es justo como se
     * cazó que dos matrices llegaron a compartir icono en esta misma rama.
     * fila-matriz.blade.php reserva el color para el estado, así que la
     * identidad de cada fila depende solo del icono y el nombre: repetido,
     * dos matrices se vuelven indistinguibles.
     */
    public function test_cada_entrada_declara_un_icono_existente_y_sin_repetir(): void
    {
        // El trazo que icono.blade.php pinta cuando el nombre no coincide con
        // ninguno de los suyos. Se deriva renderizando el componente con un
        // nombre inexistente, en vez de copiar aquí el trazo a mano: si
        // alguien cambia el trazo por defecto, una copia a mano seguiría
        // comparando contra el valor viejo y el test pasaría siempre, sin
        // proteger nada.
        $trazoPorDefecto = $this->trazoIconoPorDefecto();

        $vistos = [];

        foreach (Registro::ENTRADAS as $clave => $entrada) {
            $html = (string) $this->blade(
                '<x-icono :nombre="$nombre" />',
                ['nombre' => $entrada['icono']]
            );

            $this->assertStringNotContainsString(
                $trazoPorDefecto,
                $html,
                "{$clave}: el icono '{$entrada['icono']}' no existe en icono.blade.php y degrada al círculo por defecto."
            );

            $anterior = $vistos[$entrada['icono']] ?? '';
            $this->assertArrayNotHasKey(
                $entrada['icono'],
                $vistos,
                "{$clave}: comparte el icono '{$entrada['icono']}' con '{$anterior}'."
            );

            $vistos[$entrada['icono']] = $clave;
        }
    }

    /**
     * Renderiza <x-icono> con un nombre que no existe y extrae el trazo del
     * SVG resultante: es el mismo componente que produce el fallback, así que
     * esta referencia no puede quedarse desactualizada por su cuenta.
     */
    private function trazoIconoPorDefecto(): string
    {
        $html = (string) $this->blade('<x-icono nombre="__inexistente__" />');

        preg_match('/\sd="([^"]+)"/', $html, $coincidencia);

        $this->assertNotEmpty($coincidencia, 'No se pudo extraer el trazo por defecto de icono.blade.php.');

        return $coincidencia[1];
    }
}
