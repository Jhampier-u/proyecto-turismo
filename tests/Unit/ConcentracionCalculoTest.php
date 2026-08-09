<?php

namespace Tests\Unit;

use App\Matrices\Concentracion;
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
}
