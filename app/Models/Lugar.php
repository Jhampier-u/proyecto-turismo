<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lugar extends Model
{
    protected $table = 'lugares';
    public $timestamps = false;
    protected $fillable = ['provincia_id', 'nombre', 'descripcion'];

    public function provincia() {
        return $this->belongsTo(Provincia::class);
    }

    public function zonas() {
        return $this->hasMany(Zona::class);
    }
}