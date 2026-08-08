<?php

namespace App\Models;

use App\Matrices\Irritacion;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionIrritacion extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_irritacion';

    protected $fillable = [
        'zona_id', 'user_id', 'estado',
        ...Irritacion::VISITANTES,
        ...Irritacion::RESIDENTES,
        'visitantes_promedio', 'residentes_promedio',
    ];

    protected function casts(): array
    {
        return [
            // Los doce criterios son tinyInteger en la base; sin este cast un
            // motor que los devuelva como cadena (Postgres ya nos ha dado
            // sustos así, a diferencia de SQLite) rompería en silencio la
            // firma ?float de clasificar().
            ...array_fill_keys(Irritacion::VISITANTES, 'integer'),
            ...array_fill_keys(Irritacion::RESIDENTES, 'integer'),

            'visitantes_promedio' => 'float',
            'residentes_promedio' => 'float',
        ];
    }

    /**
     * Clasifica un valor de la escala, sea un atributo suelto o el promedio de
     * un bloque. Delega en App\Matrices\Irritacion: la definición del
     * instrumento —criterios, etiquetas y umbrales— vive ahí, no en el
     * modelo. El método se conserva aquí porque ya hay código que lo llama.
     */
    public static function clasificar(?float $valor): ?string
    {
        return Irritacion::clasificar($valor);
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
