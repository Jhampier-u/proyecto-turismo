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

    // PostgreSQL devuelve las columnas numeric como string; sin estos casts
    // el tipo difiere entre local (SQLite) y producción.
    protected function casts(): array
    {
        return [
            'media_rtt' => 'float',
            'fit_rtt' => 'float',
            'media_at' => 'float',
            'fit_at' => 'float',
            'media_pst' => 'float',
            'fit_pst' => 'float',
            'media_ptt' => 'float',
            'fit_ptt' => 'float',
            'media_i' => 'float',
            'fit_i' => 'float',
            'media_ft' => 'float',
            'fit_ft' => 'float',
            'fit' => 'float',
        ];
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
