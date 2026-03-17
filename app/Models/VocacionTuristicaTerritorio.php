<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VocacionTuristicaTerritorio extends Model
{
    use HasFactory;

    protected $table = 'vocacion_turistica_territorio';

    protected $fillable = [
        'zona_id',
        'user_id',
        'fit',
        'fet',
        'vtt',
        'vocacion_texto',
    ];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
}