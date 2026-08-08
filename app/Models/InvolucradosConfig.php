<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Estado del conjunto de actores de la Matriz de Involucrados de una zona.
 *
 * No lleva los actores en sí —esos están en Involucrado, una fila por
 * actor—: esta tabla solo guarda si la lista ya se confirmó, quién lo hizo y
 * cuándo. Ver el docblock de la migración para el porqué de separarlas.
 */
class InvolucradosConfig extends Model
{
    protected $table = 'involucrados_config';

    protected $fillable = ['zona_id', 'user_id', 'estado'];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
