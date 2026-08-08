<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionIrritacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_irritacion';

    protected $fillable = [
        'zona_id', 'user_id', 'estado',

        'vis_congestion', 'vis_calidad_servicios', 'vis_calidad_actividades',
        'vis_calidad_vida', 'vis_apertura', 'vis_seguridad',

        'res_congestion', 'res_impacto_social', 'res_impacto_economico',
        'res_impacto_ambiental', 'res_calidad_vida', 'res_seguridad',

        'visitantes_promedio', 'residentes_promedio',
    ];

    protected function casts(): array
    {
        return [
            'visitantes_promedio' => 'float',
            'residentes_promedio' => 'float',
        ];
    }

    /** Umbrales del instrumento. La escala es inversa: más alto es peor. */
    public const UMBRAL_CRITICO  = 7;
    public const UMBRAL_MODERADO = 3;

    /**
     * Clasifica un valor de la escala, sea un atributo suelto o el promedio de
     * un bloque: el instrumento aplica los mismos umbrales a los dos.
     *
     * Se deriva en vez de almacenarse, como el cuadrante de Valoración
     * Territorial: guardarla sería una segunda fuente de verdad que se
     * desincroniza en cuanto alguien corrija un umbral.
     */
    public static function clasificar(?float $valor): ?string
    {
        return match (true) {
            $valor === null                 => null,
            $valor >= self::UMBRAL_CRITICO  => 'Crítico',
            $valor >= self::UMBRAL_MODERADO => 'Moderado',
            default                         => 'Bajo',
        };
    }

    public function getClasificacionVisitantesAttribute(): ?string
    {
        return self::clasificar($this->visitantes_promedio);
    }

    public function getClasificacionResidentesAttribute(): ?string
    {
        return self::clasificar($this->residentes_promedio);
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
