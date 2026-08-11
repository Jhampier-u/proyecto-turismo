<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Estado del conjunto de sitios de la Matriz de Frecuentación de una zona,
 * y la Superficie Territorial (ST) que comparten todos ellos.
 *
 * A diferencia de InvolucradosConfig, esta configuración SÍ lleva un dato
 * del cálculo -ST-, no solo el estado: ST no es un sitio, no tiene DET, y
 * repetirla en cada fila de sitio la duplicaría sin necesidad.
 */
class FrecuentacionConfig extends Model
{
    protected $table = 'frecuentacion_config';

    protected $fillable = ['zona_id', 'user_id', 'estado', 'st'];

    // PostgreSQL devuelve las columnas numeric como string; sin este cast,
    // Frecuentacion::ietp() recibiría una cadena donde espera un float. Mismo
    // motivo que EvaluacionFit.
    protected function casts(): array
    {
        return ['st' => 'float'];
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
