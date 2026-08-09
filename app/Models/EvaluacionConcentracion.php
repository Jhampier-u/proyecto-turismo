<?php

namespace App\Models;

use App\Matrices\Concentracion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Índice de Concentración Turística.
 *
 * Sin accesorios derivados, a diferencia de EvaluacionIrritacion o
 * EvaluacionPaisaje: aquí no hay una clasificación ni un escenario que leer
 * del propio modelo. Porcentajes, Sia, Pi e ICT se calculan siempre a partir
 * de los 113 conteos guardados -nunca se guardan ellos mismos-, con
 * App\Matrices\ConcentracionCalculo, que a propósito no recibe modelos de
 * Eloquent (ver su docblock de clase).
 */
class EvaluacionConcentracion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_concentracion';

    /**
     * Concentracion::campos() es un método, no una constante -a diferencia de
     * Irritacion::VISITANTES o Irritacion::RESIDENTES-, y un spread de método
     * no es una expresión constante: no puede ser el valor por defecto de una
     * propiedad de clase. Se resuelve en el constructor, como ya hace
     * EvaluacionPaisaje con Paisaje::todos().
     */
    public function __construct(array $attributes = [])
    {
        $this->fillable = array_merge(
            ['zona_id', 'user_id', 'estado'],
            Concentracion::campos(),
        );

        parent::__construct($attributes);
    }

    protected function casts(): array
    {
        // Los 113 conteos son unsignedInteger en la base; sin este cast un
        // motor que los devuelva como cadena (Postgres ya ha dado más de un
        // susto así, a diferencia de SQLite) rompería en silencio cualquier
        // suma o comparación que haga ConcentracionCalculo sobre ellos.
        return array_fill_keys(Concentracion::campos(), 'integer');
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
