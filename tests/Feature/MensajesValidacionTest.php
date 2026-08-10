<?php

namespace Tests\Feature;

use App\Http\Controllers\Operativo\EvaluacionPercepcionController;
use App\Http\Controllers\Operativo\EvaluacionPotencialidadController;
use App\Matrices\Concentracion;
use App\Matrices\Fet;
use App\Matrices\Fit;
use App\Matrices\Involucrados;
use App\Matrices\Irritacion;
use App\Matrices\Paisaje;
use App\Matrices\ValoracionTerritorial;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;
use Database\Seeders\SystemSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Los mensajes de validación en castellano, con el nombre del campo tal como
 * lo ve el evaluador -no "the ep cambios tiempo field is required"-.
 *
 * Ver docs/ESTADO-PROYECTO.md §"Un aviso pendiente, sin arreglar": antes casi
 * no se veía porque los formularios mandaban todos los campos, pero con el
 * guardado parcial y con Concentración (113 campos) "confirmar cuando falta
 * algo" es un camino normal, no la excepción.
 *
 * Cada matriz cubierta aquí representa una FORMA distinta de declarar sus
 * etiquetas -es justo lo que MatrizPonderadaController::etiquetas() y sus
 * sobreescrituras tienen que soportar sin copiar nada-:
 *
 *   - Paisaje / ValoracionTerritorial / Fit / Fet: const con 'nombre' anidado
 *     por criterio. FIT y FET se sumaron a este grupo en la rama
 *     fit-fet-componentes: antes no tenían ninguna clase de la que derivar
 *     su etiqueta -ver más abajo, y docs/ESTADO-PROYECTO.md-.
 *   - Irritacion: const que ya es directamente campo => texto.
 *   - Concentracion: dos const de dos niveles más (categoría/sector > tipo),
 *     aplanadas por Concentracion::etiquetas().
 *   - Percepcion: propiedad estática del controlador (no del instrumento).
 *   - Potencialidad: propiedad estática de un controlador que ni siquiera
 *     hereda de MatrizPonderadaController.
 *   - Involucrados: ni matriz de formulario ni MatrizPonderadaController -es
 *     una lista de actores con su propio reglas()/etiquetas()-.
 *
 * Hasta la rama fit-fet-componentes, FIT y FET eran las únicas dos matrices
 * SIN etiqueta: sus nombres solo existían como literales sueltos en sus
 * vistas Blade, sin ninguna clase PHP de la que derivarlos sin copiarlos.
 * Ahora que App\Matrices\Fit y App\Matrices\Fet existen, se comportan igual
 * que Paisaje y ValoracionTerritorial.
 */
class MensajesValidacionTest extends TestCase
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

    // ── Payloads válidos mínimos de cada matriz, uno por forma de declarar ──

    private const CAMPOS_FIT = [
        'recursos_culturales', 'recursos_naturales',
        'atractivos_manifestaciones', 'atractivos_sitios',
        'prestadores_alojamiento', 'prestadores_restauracion', 'prestadores_guianza',
        'productos_territoriales',
        'infraestructura_basica', 'infraestructura_apoyo',
        'facilidades_senaletica', 'facilidades_recepcion', 'facilidades_interpretacion',
        'facilidades_senderos', 'facilidades_estacionamientos', 'facilidades_campamentos',
        'facilidades_miradores', 'facilidades_sanitarios',
    ];

    private function fitValido(): array
    {
        return array_fill_keys(self::CAMPOS_FIT, 3);
    }

    private const CAMPOS_FET = [
        'demanda_flujos', 'demanda_estadia',
        'super_institucionalidad', 'super_organizacion', 'super_planificacion',
        'imagen_apertura', 'imagen_seguridad', 'imagen_percibida', 'imagen_marketing',
    ];

    private function fetValido(): array
    {
        return array_fill_keys(self::CAMPOS_FET, 3);
    }

    private function paisajeValido(): array
    {
        return array_fill_keys(array_keys(Paisaje::todos()), 3);
    }

    private function valoracionTerritorialValida(): array
    {
        return array_fill_keys(array_keys(ValoracionTerritorial::todos()), 1);
    }

    private function irritacionValida(): array
    {
        return array_fill_keys(array_merge(Irritacion::VISITANTES, Irritacion::RESIDENTES), 5);
    }

    private function concentracionValida(): array
    {
        return array_fill_keys(Concentracion::campos(), 2);
    }

    /** Los 16 campos de Percepción, tomados de la propia definición del controlador. */
    private function itemsPercepcion(): array
    {
        return collect(EvaluacionPercepcionController::$categorias)
            ->flatMap(fn($cat) => array_keys($cat['items']))
            ->all();
    }

    private function percepcionValida(): array
    {
        return array_fill_keys($this->itemsPercepcion(), 3);
    }

    /** Un subconjunto pequeño y legible de Potencialidad, no los 156 campos reales. */
    private function camposPotencialidad(): array
    {
        return array_keys(EvaluacionPotencialidadController::$secciones['Afluencia Turística']);
    }

    // ── 1. El mensaje sale en castellano: una frase concreta ────────────────

    public function test_un_error_de_validacion_sale_en_castellano(): void
    {
        $datos = $this->paisajeValido() + ['accion_estado' => 'confirmado'];
        unset($datos['pn_geologia']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/paisaje", $datos)
            ->assertSessionHasErrors([
                // No "contiene la palabra campo": la frase completa, tal como
                // la lee el evaluador. Antes de esta rama salía en inglés y
                // con la columna cruda: "The pn geologia field is required."
                'pn_geologia' => 'El campo Geología es obligatorio.',
            ]);
    }

    // ── 2. El campo se nombra como lo ve el evaluador, una matriz por forma ──

    /** Paisaje: const con 'nombre' anidado por criterio (App\Matrices\Paisaje::todos()). */
    public function test_paisaje_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        // Se lee la etiqueta vigente del instrumento, no se copia a mano: si
        // alguien la cambia en Paisaje.php mañana, este test sigue
        // construyendo el mensaje esperado con la etiqueta nueva.
        $etiqueta = Paisaje::todos()['pn_geologia']['nombre'];

        $datos = $this->paisajeValido() + ['accion_estado' => 'confirmado'];
        unset($datos['pn_geologia']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/paisaje", $datos)
            ->assertSessionHasErrors([
                'pn_geologia' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    /** Valoración Territorial: misma forma que Paisaje, instrumento distinto. */
    public function test_valoracion_territorial_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = ValoracionTerritorial::todos()['ct_salud']['nombre'];

        $datos = $this->valoracionTerritorialValida() + ['accion_estado' => 'confirmado'];
        unset($datos['ct_salud']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/valoracion-territorial", $datos)
            ->assertSessionHasErrors([
                'ct_salud' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    /** Irritación: const que ya es directamente campo => texto (sin 'nombre' anidado). */
    public function test_irritacion_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = Irritacion::ETIQUETAS['vis_congestion'];

        $datos = $this->irritacionValida() + ['accion_estado' => 'confirmado'];
        unset($datos['vis_congestion']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/irritacion", $datos)
            ->assertSessionHasErrors([
                'vis_congestion' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    /**
     * Concentración: dos const de dos niveles más que el resto (categoría o
     * sector > tipo > campo), aplanadas por Concentracion::etiquetas(). Es
     * el campo que usa el propio reporte de esta rama como ejemplo: sin
     * etiqueta, Laravel pintaría `at_nat_rios_cascada` con guiones bajos.
     */
    public function test_concentracion_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = Concentracion::etiquetas()['at_nat_rios_cascada'];
        $this->assertSame('Cascada', $etiqueta); // documenta qué se está probando

        $datos = $this->concentracionValida() + ['accion_estado' => 'confirmado'];
        unset($datos['at_nat_rios_cascada']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/concentracion", $datos)
            ->assertSessionHasErrors([
                'at_nat_rios_cascada' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    /** Percepción: propiedad estática del CONTROLADOR, no una clase en App\Matrices. */
    public function test_percepcion_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = EvaluacionPercepcionController::$categorias['PL']['items']['pl3_conoc_motivo_visita'];

        $datos = $this->percepcionValida() + ['accion_estado' => 'confirmado'];
        unset($datos['pl3_conoc_motivo_visita']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-percepcion", $datos)
            ->assertSessionHasErrors([
                'pl3_conoc_motivo_visita' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    /**
     * Potencialidad: ni siquiera pasa por MatrizPonderadaController::etiquetas()
     * -EvaluacionPotencialidadController hereda de EvaluacionZonaController
     * directamente, con su propio prepararDatos()-, así que confirma que el
     * tercer argumento de validate() se conectó también en ese camino.
     */
    public function test_potencialidad_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = EvaluacionPotencialidadController::$secciones['Afluencia Turística']['dt_at_estadia'];

        $activos = $this->camposPotencialidad();
        $datos   = ['campos' => $activos, 'accion_estado' => 'confirmado']
            + array_fill_keys($activos, 2);
        unset($datos['dt_at_estadia']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-potencialidad", $datos)
            ->assertSessionHasErrors([
                'dt_at_estadia' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    /**
     * Involucrados: no hereda de MatrizPonderadaController -es una lista de
     * actores, con su propio reglas()/etiquetas() en InvolucradosController-,
     * y sus criterios son siempre 'nullable' (no hay "confirmar" a medio
     * formulario), así que aquí se prueba con un valor fuera de rango en vez
     * de un campo ausente.
     */
    public function test_involucrados_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = Involucrados::ATRIBUTOS['poder']['campos']['pod_poder'];

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/involucrados", [
                'nombre'    => 'Actor de prueba',
                'pod_poder' => 99,
            ])
            ->assertSessionHasErrors([
                'pod_poder' => "El campo {$etiqueta} no debe ser mayor que 3.",
            ]);
    }

    /**
     * Fit: misma forma que Paisaje/ValoracionTerritorial (const con 'nombre'
     * anidado), instrumento distinto. Antes de la rama fit-fet-componentes,
     * FIT no tenía ninguna clase de la que derivar su etiqueta -sus nombres
     * de campo eran literales sueltos en el Blade del formulario- y este
     * mismo test comprobaba justo lo contrario: que el mensaje se quedaba en
     * "recursos culturales" en minúsculas. Ver docs/ESTADO-PROYECTO.md.
     */
    public function test_fit_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = Fit::todos()['recursos_culturales']['nombre'];

        $datos = $this->fitValido() + ['accion_estado' => 'confirmado'];
        unset($datos['recursos_culturales']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fit", $datos)
            ->assertSessionHasErrors([
                'recursos_culturales' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    /** Fet: mismo caso que Fit, mismo motivo. */
    public function test_fet_nombra_el_campo_con_la_etiqueta_del_instrumento(): void
    {
        $etiqueta = Fet::todos()['demanda_flujos']['nombre'];

        $datos = $this->fetValido() + ['accion_estado' => 'confirmado'];
        unset($datos['demanda_flujos']);

        $this->actingAs($this->jefe)
            ->post("/operativo/zona/{$this->zona->id}/evaluacion-fet", $datos)
            ->assertSessionHasErrors([
                'demanda_flujos' => "El campo {$etiqueta} es obligatorio.",
            ]);
    }

    // ── 4. La etiqueta sale del instrumento, no de una copia ────────────────

    /**
     * Prueba de mutación real, no solo lectura dinámica: PHP no permite
     * reasignar en tiempo de ejecución una class constant -Paisaje::CATEGORIAS,
     * Irritacion::ETIQUETAS, Concentracion::ATRACTIVOS/PLANTA e
     * Involucrados::ATRIBUTOS son todas `public const`, inmutables incluso por
     * Reflection-, así que los tests de arriba ya se apoyan en leer el valor
     * VIGENTE de cada const en vez de copiar un literal, que es la única
     * forma de detectar una divergencia futura en esos casos.
     *
     * EvaluacionPercepcionController::$categorias sí es una propiedad estática
     * normal (no una const), así que aquí SÍ se puede mutar de verdad: se
     * cambia una etiqueta del instrumento dentro del test, se comprueba que el
     * mensaje cambia con ella, y se restaura en el finally para no dejar
     * estado mutado para el resto de la suite.
     */
    public function test_la_etiqueta_de_percepcion_sale_del_instrumento_y_no_de_una_copia(): void
    {
        $original = EvaluacionPercepcionController::$categorias['DS']['items']['ds1_posicion_turistica'];

        EvaluacionPercepcionController::$categorias['DS']['items']['ds1_posicion_turistica']
            = 'Etiqueta Mutada Para Esta Prueba';

        try {
            $datos = $this->percepcionValida() + ['accion_estado' => 'confirmado'];
            unset($datos['ds1_posicion_turistica']);

            $this->actingAs($this->jefe)
                ->post("/operativo/zona/{$this->zona->id}/evaluacion-percepcion", $datos)
                ->assertSessionHasErrors([
                    'ds1_posicion_turistica' => 'El campo Etiqueta Mutada Para Esta Prueba es obligatorio.',
                ]);
        } finally {
            EvaluacionPercepcionController::$categorias['DS']['items']['ds1_posicion_turistica'] = $original;
        }

        // Si la restauración fallara, cualquier test posterior que use este
        // campo lo notaría; lo comprobamos aquí también para que el fallo se
        // señale en ESTE test y no en uno inocente que se ejecute después.
        $this->assertSame(
            $original,
            EvaluacionPercepcionController::$categorias['DS']['items']['ds1_posicion_turistica']
        );
    }
}
