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
     * Clasifica el promedio de cada bloque llamando directamente a
     * App\Matrices\Irritacion: la definición del instrumento —criterios,
     * etiquetas y umbrales— vive ahí.
     */
    public function getClasificacionVisitantesAttribute(): ?string
    {
        return Irritacion::clasificar($this->visitantes_promedio);
    }

    public function getClasificacionResidentesAttribute(): ?string
    {
        return Irritacion::clasificar($this->residentes_promedio);
    }

    /**
     * Clasifica un atributo suelto, no el promedio de un bloque: la vista de
     * resultados la usa fila a fila en la tabla de detalle. Sí hace falta esta
     * puerta pública, a diferencia de los dos accesorios de arriba, porque la
     * vista solo conoce el nombre del campo en tiempo de bucle
     * ($bloque['campos']): sin este método tendría que llamar a
     * App\Matrices\Irritacion::clasificar($evaluacion->$campo) por su cuenta,
     * que es justo la referencia directa que el resto de la vista ya dejó de
     * hacer.
     */
    public function clasificacionDe(string $campo): ?string
    {
        return Irritacion::clasificar($this->$campo);
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
