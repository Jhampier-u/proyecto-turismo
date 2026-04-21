<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvaluacionPercepcion extends Model
{
    protected $table = 'evaluaciones_percepcion';

    protected $fillable = [
        'zona_id', 'user_id', 'estado',
        'ds1_posicion_turistica', 'ds2_interes_participar', 'ds3_contribucion_social',
        'pl1_conoc_recursos', 'pl2_conoc_atractivos', 'pl3_conoc_motivo_visita',
        'pl4_conoc_flujo_visitantes', 'pl5_sentimiento_visitantes', 'pl6_necesidad_visitantes',
        'pe1_incidencia_ingresos', 'pe2_beneficios_esperados', 'pe3_disposicion_inversion',
        'no1_organizacion_colectiva', 'no2_lideres_sociales', 'no3_opinion_lideres', 'no4_conflictos_sociales',
        'media_ds', 'pond_ds', 'media_pl', 'pond_pl',
        'media_pe', 'pond_pe', 'media_no', 'pond_no',
        'percepcion_total', 'acciones_mejora',
    ];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
