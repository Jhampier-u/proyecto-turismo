<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoPropietario extends Model
{
    protected $table = 'tipos_propietario';
    public $timestamps = false;
    protected $fillable = ['nombre'];

    public function inventarios() {
        return $this->hasMany(Inventario::class, 'propietario_id');
    }
}