<?php

namespace Tests\Unit;

use App\Matrices\Involucrados;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * La aritmética del ranking de relevancia (Tarea 4): normalizar(),
 * esAtributoDegenerado() y relevancia() son funciones puras sobre arrays de
 * escalares, sin Eloquent ni el modelo Involucrado —esa dependencia hacia
 * atrás fue un hallazgo de la revisión, que la señaló como la primera
 * dependencia circular instrumento↔modelo del sistema—, así que se prueban
 * aquí, en tests/Unit, sin base de datos, igual que
 * ValoracionTerritorialCriteriosTest prueba los pesos de CT/UC.
 *
 * El ENSAMBLADO —leer los grados de cada actor de una zona y construir estos
 * arrays— vive en InvolucradosController::relevanciasDe() y se prueba en
 * tests/Feature/InvolucradosTest.php, donde sí hace falta la base de datos.
 */
class InvolucradosCalculoTest extends TestCase
{
    public function test_normalizar_con_grados_10_y_5_da_1_333_y_0_667(): void
    {
        // Suma 15, dos posiciones: (10/15)*2 = 1.333..., (5/15)*2 = 0.667...
        $normalizados = Involucrados::normalizar([10, 5]);

        $this->assertEqualsWithDelta(1.333, $normalizados[0], 0.001);
        $this->assertEqualsWithDelta(0.667, $normalizados[1], 0.001);
    }

    /**
     * "Ninguno puntúa nada en este atributo" es un caso real del instrumento
     * (los siete campos de poder en 0 para todos los actores, por ejemplo),
     * no un error de captura. Ver el porqué extenso de 1.0 —y de que NO es
     * "salvar la relevancia de un actor"— en el docblock de normalizar().
     */
    public function test_normalizar_devuelve_1_para_todos_si_la_suma_es_cero(): void
    {
        $this->assertSame([1.0, 1.0, 1.0], Involucrados::normalizar([0, 0, 0]));
    }

    /**
     * DELIBERADO, no un fallo que alguien deba "corregir": normalizar()
     * divide por la suma del conjunto, así que el resultado de CADA posición
     * depende de TODAS las demás. Añadir un valor recalcula los que ya
     * estaban, aunque ellos mismos no cambien un ápice. Decidido así con el
     * responsable del proyecto: comparar el normalizado de un actor entre dos
     * listados distintos no tiene sentido, solo lo tiene dentro del mismo
     * conjunto.
     *
     * Se comprueba con tres distribuciones distintas —no solo una— porque es
     * una propiedad de la fórmula en general, no una coincidencia de un caso
     * concreto: da igual qué tres atributos del instrumento se les ponga por
     * nombre, el comportamiento es el mismo para cualquier array de grados.
     *
     * Ninguna posición de partida es 0: un grado exactamente en 0 normaliza
     * SIEMPRE a 0.0 mientras la suma no sea 0, sea cual sea el resto del
     * conjunto (0/suma×n es 0 para cualquier suma), así que esa posición no
     * sirve para demostrar la propiedad —no es una excepción a ella, es un
     * caso particular donde "cambiar" y "seguir en 0" coinciden—.
     */
    public function test_anadir_un_valor_cambia_el_normalizado_de_los_que_ya_estaban(): void
    {
        $casos = [
            [[2, 1], 3],   // poder: sube uno con grado alto
            [[4, 2], 1],   // legitimidad: sube uno con grado bajo
            [[1, 3], 5],   // urgencia: sube uno con grado más alto aún
        ];

        foreach ($casos as [$grados, $nuevoValor]) {
            $antes   = Involucrados::normalizar($grados);
            $despues = Involucrados::normalizar([...$grados, $nuevoValor]);

            foreach ($antes as $indice => $valorAntes) {
                $this->assertGreaterThan(
                    0.0001,
                    abs($valorAntes - $despues[$indice]),
                    "La posición {$indice} debía cambiar al añadir un valor, con grados iniciales " . json_encode($grados)
                );
            }
        }
    }

    public function test_normalizar_valida_que_no_haya_huecos_aunque_la_suma_de_cero(): void
    {
        // Con TODOS los grados en null la suma también da 0 -"0 + null" no
        // lanza nada en PHP-, así que sin una validación explícita este caso
        // caería en la rama de suma-cero y devolvería 1.0 en silencio para un
        // conjunto que en realidad nunca estuvo completo.
        $this->expectException(InvalidArgumentException::class);

        Involucrados::normalizar([null, null]);
    }

    public function test_normalizar_valida_que_no_haya_huecos_con_suma_distinta_de_cero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Involucrados::normalizar([null, 5]);
    }

    public function test_esAtributoDegenerado_es_cierto_con_la_suma_en_cero(): void
    {
        $this->assertTrue(Involucrados::esAtributoDegenerado([0, 0, 0]));
    }

    /**
     * Con un solo grado, normalizar() siempre da 1.0 pase lo que pase con su
     * valor -grado/grado es 1, y si ese grado es 0 la rama de suma-cero
     * también da 1.0-, así que un único actor hace degenerados los tres
     * atributos sin que ninguno puntúe necesariamente 0.
     */
    public function test_esAtributoDegenerado_es_cierto_con_un_unico_grado_sea_cual_sea_su_valor(): void
    {
        $this->assertTrue(Involucrados::esAtributoDegenerado([0]));
        $this->assertTrue(Involucrados::esAtributoDegenerado([3]));
    }

    public function test_esAtributoDegenerado_es_falso_con_mas_de_un_actor_y_alguno_puntuando_algo(): void
    {
        $this->assertFalse(Involucrados::esAtributoDegenerado([2, 0]));
        $this->assertFalse(Involucrados::esAtributoDegenerado([1, 1]));
    }

    public function test_esAtributoDegenerado_valida_que_no_haya_huecos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Involucrados::esAtributoDegenerado([null, 0]);
    }

    public function test_relevancia_es_el_producto_de_los_normalizados(): void
    {
        $this->assertEqualsWithDelta(
            2.0 * 0.5 * 1.5,
            Involucrados::relevancia(['poder' => 2.0, 'legitimidad' => 0.5, 'urgencia' => 1.5]),
            0.0001
        );
    }

    /**
     * Un atributo degenerado normaliza a 1.0, el neutro del producto: la
     * relevancia queda decidida solo por los atributos que sí diferencian,
     * sin que quien ensambla la fila tenga que excluir nada a mano.
     */
    public function test_relevancia_no_cambia_por_un_atributo_degenerado_en_1(): void
    {
        $this->assertEqualsWithDelta(
            0.6 * 2.0,
            Involucrados::relevancia(['poder' => 0.6, 'legitimidad' => 1.0, 'urgencia' => 2.0]),
            0.0001
        );
    }
}
