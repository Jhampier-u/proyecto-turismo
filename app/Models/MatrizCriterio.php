<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatrizCriterio extends Model
{
    protected $table = 'matriz_criterios';
    public $timestamps = false;
    protected $fillable = ['variable_id', 'nombre', 'descripcion_guia', 'peso_relativo'];

    public function variable() {
        return $this->belongsTo(MatrizVariable::class, 'variable_id');
    }
}