<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoriaRecurso extends Model
{
    protected $table = 'categorias_recurso';
    public $timestamps = false;
    protected $fillable = ['parent_id', 'nombre', 'codigo_ficha'];

    public function padre() {
        return $this->belongsTo(CategoriaRecurso::class, 'parent_id');
    }

    public function hijos() {
        return $this->hasMany(CategoriaRecurso::class, 'parent_id');
    }

    public function inventarios() {
        return $this->hasMany(Inventario::class, 'categoria_id');
    }
}