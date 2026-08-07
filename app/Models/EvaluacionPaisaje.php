<?php

namespace App\Models;

use App\Matrices\Paisaje;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionPaisaje extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_paisaje';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'ep_promedio'   => 'float',
            'pn_promedio'   => 'float',
            'pc_promedio'   => 'float',
            'iv_promedio'   => 'float',
            'cp_promedio'   => 'float',
            'paisaje_total' => 'float',
        ];
    }

    public function __construct(array $attributes = [])
    {
        // Los 34 criterios se declaran una sola vez, en la definición del
        // instrumento; repetirlos aquí sería una segunda fuente de verdad.
        $this->fillable = array_merge(
            ['zona_id', 'user_id', 'estado', 'paisaje_total'],
            array_keys(Paisaje::todos()),
            array_map(fn($c) => "{$c}_promedio", array_keys(Paisaje::CATEGORIAS)),
        );

        parent::__construct($attributes);
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Escenario según el resultado final, tal como lo define el instrumento.
     *
     * Se deriva en vez de almacenarse, por la misma razón que el cuadrante de
     * Valoración Territorial: un resultado guardado puede quedar desfasado
     * respecto a los datos que lo originan, y entonces miente.
     *
     * @return array{rango: string, nombre: string, texto: string}
     */
    public function getEscenarioAttribute(): array
    {
        $total = $this->paisaje_total;

        // ESCENARIOS va de mayor a menor: el primero cuyo umbral se alcanza gana.
        // Los umbrales son 4, 3, 2 y 1; por debajo de 1 queda el último.
        foreach (Paisaje::ESCENARIOS as $i => $escenario) {
            $umbral = count(Paisaje::ESCENARIOS) - 1 - $i;

            if ($total >= $umbral) {
                return $escenario;
            }
        }

        return Paisaje::ESCENARIOS[array_key_last(Paisaje::ESCENARIOS)];
    }

    /**
     * Lectura cualitativa de una categoría, por tramos de porcentaje sobre el
     * máximo: 80% Alto, 60% Medianamente Alto, 40% Medio, 20% Medianamente
     * Bajo, y por debajo Bajo.
     */
    public function lecturaDe(string $categoria): string
    {
        $proporcion = $this->{"{$categoria}_promedio"} / Paisaje::MAXIMO;

        return match (true) {
            $proporcion >= 0.80 => 'Alto',
            $proporcion >= 0.60 => 'Medianamente Alto',
            $proporcion >= 0.40 => 'Medio',
            $proporcion >= 0.20 => 'Medianamente Bajo',
            default             => 'Bajo',
        };
    }
}
