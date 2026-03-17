<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model
{
    protected $table = 'evaluaciones';
    protected $fillable = [
        'zona_id', 'realizada_por_user_id', 'estado',
        'fecha_finalizacion', 'resultado_fit', 'resultado_fet', 'vocacion_texto'
    ];

    public function zona() {
        return $this->belongsTo(Zona::class);
    }

    public function auditor() {
        return $this->belongsTo(User::class, 'realizada_por_user_id');
    }

    public function valores() {
        return $this->hasMany(EvaluacionValor::class);
    }
}