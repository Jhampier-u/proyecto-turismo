<?php

namespace App\Models;

use App\Matrices\ValoracionTerritorial;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EvaluacionValoracionTerritorial extends Model
{
    use HasFactory;

    protected $table = 'evaluaciones_valoracion_territorial';

    protected $fillable = [];

    protected function casts(): array
    {
        return [
            'ct_total' => 'float',
            'uc_total' => 'float',
        ];
    }

    public function __construct(array $attributes = [])
    {
        // Los 21 criterios se declaran una sola vez, en la definición del
        // instrumento; repetirlos aquí sería una segunda fuente de verdad.
        $this->fillable = array_merge(
            ['zona_id', 'user_id', 'estado', 'ct_total', 'uc_total'],
            array_keys(ValoracionTerritorial::todos())
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
     * Cuadrante según los cuatro definidos en el instrumento original.
     *
     * Se deriva en vez de almacenarse: el VTT sí guardaba su resultado y por eso
     * podía quedar desfasado respecto a los datos que lo originaban.
     */
    public function getCuadranteAttribute(): string
    {
        $ct = $this->ct_total >= ValoracionTerritorial::UMBRAL;
        $uc = $this->uc_total >= ValoracionTerritorial::UMBRAL;

        return match (true) {
            $ct && $uc   => 'Territorio a Priorizar para el Turismo IV',
            !$ct && $uc  => 'Territorio con Limitación II',
            $ct && !$uc  => 'Territorio con Limitación III',
            default      => 'Territorio No Apto para el Turismo',
        };
    }
}
