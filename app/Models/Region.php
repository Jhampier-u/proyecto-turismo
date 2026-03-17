<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    protected $table = 'regiones';
    public $timestamps = false;
    protected $fillable = ['nombre'];

    public function provincias() {
        return $this->hasMany(Provincia::class);
    }
}