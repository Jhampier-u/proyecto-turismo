<?php

namespace Tests\Unit;

use App\Matrices\Frecuentacion;
use InvalidArgumentException;
use Tests\TestCase;

class FrecuentacionCalculoTest extends TestCase
{
    public function test_ietp_es_det_entre_st(): void
    {
        $this->assertEqualsWithDelta(2.5, Frecuentacion::ietp(5.0, 2.0), 0.0001);
    }

    /**
     * ST nula o cero -> null, NUNCA una excepción ni INF: en PHP 8, $a / 0
     * lanza DivisionByZeroError, y la plantilla real del instrumento llega
     * vacía (I6:I14 y J6 sin valor), así que este no es un caso de esquina.
     */
    public function test_ietp_es_null_si_st_falta_o_es_cero(): void
    {
        $this->assertNull(Frecuentacion::ietp(5.0, null));
        $this->assertNull(Frecuentacion::ietp(5.0, 0.0));
    }

    public function test_ietp_con_det_cero_es_cero_no_null(): void
    {
        // Un sitio sin visitas es un dato real, 0.0, no "sin responder": esa
        // distinción la hace el controlador antes de llamar aquí (un DET
        // null no debe llegar a ietp()), no esta función.
        $this->assertSame(0.0, Frecuentacion::ietp(0.0, 10.0));
    }

    public function test_ietp_rechaza_det_o_st_negativos(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Frecuentacion::ietp(-1.0, 10.0);
    }

    public function test_ieft_suma_los_ietp_de_todos_los_sitios(): void
    {
        $this->assertEqualsWithDelta(6.0, Frecuentacion::ieft([1.0, 2.0, 3.0]), 0.0001);
    }

    /**
     * Un ÍETP que falta (null) no entra en ningún total, ni con un cero
     * disfrazado: mismo principio que ConcentracionCalculo::validarConteosCompletos()
     * e Involucrados::validarGradosCompletos(). Quien llama a ieft() ya
     * decidió mostrar resultados solo con la lista completa; si esta función
     * recibe un hueco es que esa comprobación no se hizo.
     */
    public function test_ieft_exige_el_conjunto_completo(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Frecuentacion::ieft([1.0, null, 3.0]);
    }

    public function test_ieft_exige_al_menos_un_sitio(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Frecuentacion::ieft([]);
    }
}
