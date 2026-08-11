<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Un sitio de la Matriz de Frecuentación: su nombre y su DET. */
class SitioFrecuentacion extends Model
{
    protected $table = 'frecuentacion_sitios';

    protected $fillable = ['zona_id', 'nombre', 'orden', 'det'];

    protected function casts(): array
    {
        // Mismo motivo que FrecuentacionConfig::$st: PostgreSQL devuelve
        // numeric como string.
        return ['det' => 'float'];
    }

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }

    public function estaCompleto(): bool
    {
        return $this->det !== null;
    }

    /**
     * Los sitios sin DET, en SQL y no en PHP: la página de zona y las
     * pestañas lo cuentan en cada carga.
     */
    public function scopeIncompletos(Builder $consulta): Builder
    {
        return $consulta->whereNull('det');
    }
}
