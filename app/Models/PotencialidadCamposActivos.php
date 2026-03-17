<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PotencialidadCamposActivos extends Model {
    protected $table = 'potencialidad_campos_activos';
    protected $fillable = ['zona_id', 'campos_activos'];
    protected $casts = ['campos_activos' => 'array'];

    public function zona() {
        return $this->belongsTo(Zona::class);
    }
}
