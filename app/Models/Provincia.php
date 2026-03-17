<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    public $timestamps = false;
    protected $fillable = ['region_id', 'nombre'];

    public function region() {
        return $this->belongsTo(Region::class);
    }

    public function lugares() {
        return $this->hasMany(Lugar::class);
    }
}