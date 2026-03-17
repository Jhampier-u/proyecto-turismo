<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionValor extends Model
{
    protected $table = 'evaluacion_valores';
    public $timestamps = false;
    protected $fillable = ['evaluacion_id', 'criterio_id', 'valor_asignado', 'observacion_justificacion'];

    public function evaluacion() {
        return $this->belongsTo(Evaluacion::class);
    }

    public function criterio() {
        return $this->belongsTo(MatrizCriterio::class, 'criterio_id');
    }
}