<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionFit extends Model
{

    protected $table = 'evaluaciones_fit';

    protected $fillable = [
        'zona_id',
        'user_id',
        'estado',
        
        'recursos_culturales',
        'recursos_naturales',
        'atractivos_manifestaciones',
        'atractivos_sitios',
        'prestadores_alojamiento',
        'prestadores_restauracion',
        'prestadores_guianza',
        'productos_territoriales',
        'infraestructura_basica',
        'infraestructura_apoyo',
        'facilidades_senaletica',
        'facilidades_recepcion',
        'facilidades_interpretacion',
        'facilidades_senderos',
        'facilidades_estacionamientos',
        'facilidades_campamentos',
        'facilidades_miradores',
        'facilidades_sanitarios',

        'media_rtt',
        'fit_rtt',
        'media_at',
        'fit_at',
        'media_pst',
        'fit_pst',
        'media_ptt',
        'fit_ptt',
        'media_i',
        'fit_i',
        'media_ft',
        'fit_ft',
        'fit'
    ];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
}
