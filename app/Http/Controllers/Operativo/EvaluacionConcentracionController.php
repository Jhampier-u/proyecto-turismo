<?php

namespace App\Http\Controllers\Operativo;

use App\Matrices\Concentracion;
use App\Models\EvaluacionConcentracion;
use App\Models\Zona;

/**
 * Índice de Concentración Turística.
 *
 * A diferencia del resto de matrices del sistema, esta no puntúa criterios en
 * una escala: cuenta atractivos turísticos y establecimientos de planta
 * turística por categoría. Porcentajes, Sia, Pi e ICT se derivan siempre a
 * partir de esos conteos (ver App\Matrices\ConcentracionCalculo) y no se
 * guardan, así que calcular() no tiene nada que devolver.
 */
class EvaluacionConcentracionController extends MatrizPonderadaController
{
    protected function modelo(): string
    {
        return EvaluacionConcentracion::class;
    }

    protected function rutaResultados(): string
    {
        return 'operativo.evaluacion_concentracion.ponderacion';
    }

    /**
     * No se usa: reglaValor() está sobreescrito más abajo, así que escala()
     * es irrelevante para la validación real. Sigue habiendo que
     * implementarlo porque MatrizPonderadaController lo declara abstracto.
     * [0, PHP_INT_MAX] no es un tope de negocio inventado -esta matriz no
     * tiene uno: una zona puede tener tres hoteles o cuatrocientos-, es el
     * techo técnico real de un entero de PHP, así que no miente sobre un
     * límite que no existe.
     */
    protected function escala(): array
    {
        return [0, PHP_INT_MAX];
    }

    /**
     * Sin tope: la regla por defecto de la clase base es integer|min:X|max:Y
     * a partir de escala(), y aquí un conteo de cuatrocientos hoteles es tan
     * válido como uno de tres. El único límite real es no admitir negativos.
     * Paisaje sobreescribe este mismo método por otro motivo (su escala no es
     * contigua); es el mecanismo previsto y no hace falta tocar la clase base.
     */
    protected function reglaValor(): string
    {
        return 'integer|min:0';
    }

    /**
     * La clase base espera ['clave' => [campos...]], una lista plana de
     * campos por clave. Concentracion::ATRACTIVOS trae un nivel de más
     * (categoría -> tipo -> campos), así que aquí se aplana a categoría ->
     * campos, fusionando todos los tipos de esa categoría. Concentracion::PLANTA
     * ya tiene la forma exacta que pide el contrato (sector -> campos).
     */
    protected function criterios(): array
    {
        $atractivos = array_map(
            fn(array $tipos) => array_merge(...array_values(array_map('array_keys', $tipos))),
            Concentracion::ATRACTIVOS
        );

        $planta = array_map('array_keys', Concentracion::PLANTA);

        return array_merge($atractivos, $planta);
    }

    /** Las 113 etiquetas del instrumento, sin copiarlas: ver Concentracion::etiquetas(). */
    protected function etiquetas(): array
    {
        return Concentracion::etiquetas();
    }

    /**
     * Nada que calcular: porcentajes, Sia, Pi e ICT se derivan siempre a
     * partir de los conteos guardados (ver App\Matrices\ConcentracionCalculo);
     * guardarlos sería una segunda fuente de verdad. Comprobado que
     * MatrizPonderadaController::columnasCalculadasVacias() da [] a partir de
     * un calcular() vacío y que su unión con los valores es inocua: esta es
     * la primera matriz del sistema sin ninguna columna calculada.
     */
    protected function calcular(array $valores): array
    {
        return [];
    }

    /**
     * No hay un Sia, un Pi ni un total ponderado que citar aquí -no se
     * calculan ni se guardan-, pero sí hay algo honesto: cuántos atractivos y
     * cuántos establecimientos de planta lleva contados la zona, una simple
     * suma de lo que el usuario ya tecleó, no una fórmula del instrumento.
     */
    protected function mensajeExito(string $estado, array $datos): string
    {
        $criterios = $this->criterios();

        $camposAtractivos = array_merge(...array_map(
            fn(string $categoria) => $criterios[$categoria],
            array_keys(Concentracion::ATRACTIVOS)
        ));
        $camposPlanta = array_merge(...array_map(
            fn(string $sector) => $criterios[$sector],
            array_keys(Concentracion::PLANTA)
        ));

        $totalAtractivos = array_sum(array_map(fn(string $c) => $datos[$c], $camposAtractivos));
        $totalPlanta     = array_sum(array_map(fn(string $c) => $datos[$c], $camposPlanta));

        return $estado === 'confirmado'
            ? "Índice de Concentración VALIDADO. {$totalAtractivos} atractivos y {$totalPlanta} establecimientos de planta turística contados."
            : "Borrador guardado. {$totalAtractivos} atractivos y {$totalPlanta} establecimientos de planta turística contados.";
    }

    protected function mensajeCerrada(): string
    {
        return 'Este Índice de Concentración ya fue validado por el Jefe de Zona. No puedes editarlo.';
    }

    public function edit($zonaId)
    {
        $zona       = Zona::findOrFail($zonaId);
        $evaluacion = EvaluacionConcentracion::firstOrNew(['zona_id' => $zonaId]);

        return view('operativo.evaluacion_concentracion.form', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'atractivos' => Concentracion::ATRACTIVOS,
            'planta'     => Concentracion::PLANTA,
        ]);
    }

    public function ponderacion($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        // ->first() y no ->firstOrFail(): se puede llegar a esta URL antes de
        // completar la matriz, y la vista ya contempla ese caso con un aviso.
        $evaluacion = EvaluacionConcentracion::where('zona_id', $zonaId)->first();

        return view('operativo.evaluacion_concentracion.ponderacion', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'atractivos' => Concentracion::ATRACTIVOS,
            'planta'     => Concentracion::PLANTA,
        ]);
    }
}
