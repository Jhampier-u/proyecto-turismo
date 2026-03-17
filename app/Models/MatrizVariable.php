<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MatrizVariable extends Model
{
    protected $table = 'matriz_variables';
    public $timestamps = false;
    protected $fillable = ['tipo', 'nombre', 'peso_porcentual'];

    public function criterios() {
        return $this->hasMany(MatrizCriterio::class, 'variable_id');
    }
}