<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionFet extends Model
{

    protected $table = 'evaluaciones_fet';

    protected $fillable = [
        'zona_id',
        'user_id',
        'estado',

        'demanda_flujos',
        'demanda_estadia',
        'super_institucionalidad',
        'super_organizacion',
        'super_planificacion',
        'imagen_apertura',
        'imagen_seguridad',
        'imagen_percibida',
        'imagen_marketing',
        
        'media_demanda',
        'fet_demanda',
        'media_super',
        'fet_super',
        'media_imagen',
        'fet_imagen',
        'fet'
    ];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
}