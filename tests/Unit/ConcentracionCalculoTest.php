<?php

namespace Tests\Unit;

use App\Matrices\Concentracion;
use App\Matrices\ConcentracionCalculo;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Fija el recuento de la definición generada (Task 1 del plan de esta
 * matriz). El riesgo de esta matriz no es la lógica sino los 113 nombres de
 * campo: uno mal copiado no rompe ninguna otra prueba, solo desvía un conteo
 * en silencio. Este test es lo que convierte ese error silencioso en un
 * fallo ruidoso si alguien vuelve a ejecutar el generador contra un
 * instrumento distinto, o edita Concentracion.php a mano.
 *
 * Los números de abajo se verificaron contra
 * Documentación/Índice de Concentración Turística.xlsx con un recuento
 * independiente del generador (contar filas de datos entre el encabezado y
 * la fila de total de cada tabla), no copiados del PHP generado:
 * manifestaciones culturales 22 subtipos en 4 tipos, atractivos naturales 55
 * subtipos en 11 tipos (77 subtipos en total), planta turística 36
 * subcategorías en 10 sectores. El plan aproximaba «unos 22», «unas 55» y
 * «unas 40»: el real de planta es 36, no ~40; el total real es 113 campos,
 * no los ~117 que mencionaba de pasada la introducción del plan.
 */
class ConcentracionCalculoTest extends TestCase
{
    /** Suma las hojas de un árbol categoría -> tipo -> {campo: etiqueta}. */
    private function contarSubtipos(array $categoria): int
    {
        return array_sum(array_map('count', $categoria));
    }

    public function test_manifestaciones_culturales_tiene_cuatro_tipos(): void
    {
        $this->assertCount(4, Concentracion::ATRACTIVOS['Manifestaciones Culturales']);
    }

    public function test_manifestaciones_culturales_tiene_22_subtipos(): void
    {
        $this->assertSame(22, $this->contarSubtipos(Concentracion::ATRACTIVOS['Manifestaciones Culturales']));
    }

    public function test_atractivos_naturales_tiene_once_tipos(): void
    {
        $this->assertCount(11, Concentracion::ATRACTIVOS['Atractivos Naturales']);
    }

    public function test_atractivos_naturales_tiene_55_subtipos(): void
    {
        $this->assertSame(55, $this->contarSubtipos(Concentracion::ATRACTIVOS['Atractivos Naturales']));
    }

    public function test_atractivos_tiene_77_campos_en_total(): void
    {
        $total = array_sum(array_map(fn ($categoria) => $this->contarSubtipos($categoria), Concentracion::ATRACTIVOS));

        $this->assertSame(77, $total);
    }

    public function test_planta_tiene_diez_sectores(): void
    {
        $this->assertCount(10, Concentracion::PLANTA);
    }

    public function test_planta_tiene_36_subcategorias_en_total(): void
    {
        $total = array_sum(array_map('count', Concentracion::PLANTA));

        $this->assertSame(36, $total);
    }

    public function test_campos_devuelve_los_113_nombres_del_instrumento(): void
    {
        $this->assertCount(113, Concentracion::campos());
    }

    /**
     * El fallo más probable de esta taxonomía: un subtipo que se repite bajo
     * dos tipos distintos -"Cueva o Caverna" aparece tanto en Fenómenos
     * Espeleológicos como en Ambientes Marinos- produciría el mismo nombre de
     * campo si el generador no incluyera el tipo, y dos filas escribiendo en
     * la misma columna es exactamente el desvío silencioso que esta matriz no
     * se puede permitir. El generador ya aborta si eso ocurre (no llegaría a
     * escribir Concentracion.php), pero este test deja fijada la garantía
     * también del lado de la clase generada, no solo del generador.
     */
    public function test_no_hay_ningun_campo_repetido(): void
    {
        $campos = Concentracion::campos();

        $this->assertSame(array_unique($campos), $campos);
    }

    /**
     * "En minúsculas y sin acentos", como pide el diseño: si el generador
     * dejara pasar una tilde o una mayúscula residual de alguna celda del
     * Excel, el nombre de columna real (la migración lo deriva de
     * Concentracion::campos()) dejaría de coincidir con lo que un evaluador
     * esperaría poder escribir a mano en un `where` o una URL.
     */
    public function test_todos_los_campos_son_snake_case_ascii(): void
    {
        foreach (Concentracion::campos() as $campo) {
            $this->assertMatchesRegularExpression('/^[a-z0-9_]+$/', $campo, $campo);
        }
    }

    /**
     * PostgreSQL trunca en silencio cualquier identificador de más de 63
     * bytes (NAMEDATALEN); SQLite no tiene ese límite, así que sin este test
     * un nombre demasiado largo pasaría toda la suite y solo se notaría en
     * producción. El generador ya aborta si un campo se pasa (ver
     * ABREVIATURAS en generar_concentracion.py), esto fija la garantía del
     * lado de la clase generada.
     */
    public function test_ningun_campo_pasa_del_limite_de_identificador_de_postgres(): void
    {
        foreach (Concentracion::campos() as $campo) {
            $this->assertLessThanOrEqual(63, strlen($campo), $campo);
        }
    }

    /** ATRACTIVOS y PLANTA deben ser exactamente las dos fuentes de campos(): ninguno huérfano, ninguno inventado. */
    public function test_campos_es_la_union_exacta_de_atractivos_y_planta(): void
    {
        $esperados = [];
        foreach (Concentracion::ATRACTIVOS as $tipos) {
            foreach ($tipos as $mapa) {
                $esperados = array_merge($esperados, array_keys($mapa));
            }
        }
        foreach (Concentracion::PLANTA as $mapa) {
            $esperados = array_merge($esperados, array_keys($mapa));
        }

        $this->assertSame($esperados, Concentracion::campos());
    }

    /**
     * La etiqueta es solo el subtipo, sin repetir el tipo que ya es la clave
     * del nivel que la contiene -si volviera a colarse el prefijo "Tipo · "
     * que tuvo una versión anterior de este generador, este test lo nota-.
     */
    public function test_la_etiqueta_de_un_subtipo_no_repite_su_tipo(): void
    {
        $museos = Concentracion::ATRACTIVOS['Manifestaciones Culturales']['Arquitectura']['at_mc_arquitectura_museos'];

        $this->assertSame('Museos', $museos);
    }

    // ------------------------------------------------------------------
    // Task 2: el cálculo (ConcentracionCalculo). Aritmética pura sobre
    // arrays de escalares -sin Eloquent, sin modelos-, ver el docblock de
    // clase de ConcentracionCalculo para el porqué de que viva en un
    // fichero aparte de Concentracion.php.
    // ------------------------------------------------------------------

    public function test_porcentajes_de_tres_subtipos_con_2_3_y_5_dan_20_30_y_50_por_ciento(): void
    {
        $porcentajes = ConcentracionCalculo::porcentajesAtractivos(['a' => 2, 'b' => 3, 'c' => 5]);

        $this->assertEqualsWithDelta(20.0, $porcentajes['a'], 0.0001);
        $this->assertEqualsWithDelta(30.0, $porcentajes['b'], 0.0001);
        $this->assertEqualsWithDelta(50.0, $porcentajes['c'], 0.0001);
    }

    /**
     * Con la tabla entera a 0 el total también da 0: 0/0 no tiene una
     * lectura matemática, pero SÍ tiene una lectura de negocio, y no es la
     * misma que la de Pi (ver ConcentracionCalculo::porcentajesAtractivos()
     * para el porqué completo). Se fija aquí para que quede escrito y nadie
     * lo cambie a null pensando que "debería" comportarse como Pi.
     */
    public function test_porcentajes_de_una_tabla_entera_a_cero_no_revientan_ni_inventan_un_numero(): void
    {
        $porcentajes = ConcentracionCalculo::porcentajesAtractivos(['a' => 0, 'b' => 0, 'c' => 0]);

        $this->assertSame(['a' => 0.0, 'b' => 0.0, 'c' => 0.0], $porcentajes);
    }

    public function test_porcentajesAtractivos_exige_el_bloque_completo_sin_huecos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ConcentracionCalculo::porcentajesAtractivos(['a' => 2, 'b' => null]);
    }

    public function test_sia_suma_los_conteos_de_un_sector(): void
    {
        $this->assertSame(9, ConcentracionCalculo::sia(['pt_x' => 2, 'pt_y' => 7]));
    }

    public function test_sia_exige_el_bloque_completo_sin_huecos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ConcentracionCalculo::sia(['pt_x' => 2, 'pt_y' => null]);
    }

    public function test_pi_de_un_sector_con_sia_4_es_50(): void
    {
        $this->assertEqualsWithDelta(50.0, ConcentracionCalculo::pi(4), 0.0001);
    }

    /**
     * Un sector sin ningún establecimiento no tiene "un Pi bajo": no tiene
     * Pi. 100/√0 es una división por cero, no un 0.0 disfrazado -ver el
     * docblock de ConcentracionCalculo::pi()-.
     */
    public function test_pi_de_un_sector_vacio_es_null_no_cero(): void
    {
        $this->assertNull(ConcentracionCalculo::pi(0));
    }

    /**
     * Pi NO es lineal y baja cuando Sia sube: un sector con más
     * establecimientos tiene un Pi menor. No es un error de esta prueba ni
     * de la implementación, es la fórmula del instrumento (100/√Sia).
     */
    public function test_pi_baja_cuando_sia_sube(): void
    {
        $this->assertGreaterThan(ConcentracionCalculo::pi(16), ConcentracionCalculo::pi(4));
    }

    public function test_ict_de_un_sector_es_su_sia_sobre_el_total_general(): void
    {
        $this->assertEqualsWithDelta(0.25, ConcentracionCalculo::ict(5, 20), 0.0001);
    }

    /**
     * Con la planta turística entera a cero (los 36 campos en 0, en los diez
     * sectores) el total general también da 0 y el ICT de cada sector
     * dividiría por él. 0.0, no una división por cero ni un reparto
     * inventado: ver el docblock de ConcentracionCalculo::ict().
     */
    public function test_ict_es_cero_si_el_total_general_es_cero(): void
    {
        $this->assertSame(0.0, ConcentracionCalculo::ict(0, 0));
    }

    public function test_planta_calcula_sia_pi_e_ict_por_sector(): void
    {
        $resultado = ConcentracionCalculo::planta([
            'Alojamiento' => ['pt_al_hotel' => 1, 'pt_al_hostal' => 3], // Sia 4
            'Restauración' => ['pt_rs_restaurante' => 6],               // Sia 6
        ]);

        $this->assertSame(4, $resultado['Alojamiento']['sia']);
        $this->assertEqualsWithDelta(50.0, $resultado['Alojamiento']['pi'], 0.0001);
        $this->assertEqualsWithDelta(0.4, $resultado['Alojamiento']['ict'], 0.0001);

        $this->assertSame(6, $resultado['Restauración']['sia']);
        $this->assertEqualsWithDelta(0.6, $resultado['Restauración']['ict'], 0.0001);
    }

    /** Un sector vacío en medio de otros con establecimientos: Sia 0, Pi null -no cero-, ICT 0. */
    public function test_planta_con_un_sector_vacio_entre_otros_con_establecimientos(): void
    {
        $resultado = ConcentracionCalculo::planta([
            'Vacío' => ['pt_x_uno' => 0, 'pt_x_dos' => 0],
            'Con establecimientos' => ['pt_y_uno' => 5],
        ]);

        $this->assertSame(0, $resultado['Vacío']['sia']);
        $this->assertNull($resultado['Vacío']['pi']);
        $this->assertSame(0.0, $resultado['Vacío']['ict']);
    }

    public function test_ict_suma_1_entre_todos_los_sectores_cuando_alguno_tiene_establecimientos(): void
    {
        $resultado = ConcentracionCalculo::planta([
            'A' => ['x' => 4],
            'B' => ['x' => 6],
            'C' => ['x' => 10],
        ]);

        $sumaIct = array_sum(array_column($resultado, 'ict'));

        $this->assertEqualsWithDelta(1.0, $sumaIct, 0.0001);
    }

    /**
     * Caso no listado explícitamente en la tarea pero que hay que resolver
     * igual: la planta turística ENTERA a cero (los 36 campos, en los diez
     * sectores). El total general da 0 y el ICT de cada sector dividiría por
     * él; todos deben salir en 0.0, ninguno debe reventar.
     */
    public function test_planta_entera_a_cero_da_ict_cero_en_todos_los_sectores_sin_reventar(): void
    {
        $resultado = ConcentracionCalculo::planta([
            'A' => ['x' => 0],
            'B' => ['x' => 0, 'y' => 0],
        ]);

        foreach ($resultado as $sector => $datos) {
            $this->assertSame(0, $datos['sia'], $sector);
            $this->assertNull($datos['pi'], $sector);
            $this->assertSame(0.0, $datos['ict'], $sector);
        }
    }

    public function test_planta_exige_el_bloque_completo_sin_huecos_en_cualquier_sector(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ConcentracionCalculo::planta(['A' => ['x' => 2, 'y' => null]]);
    }
}
