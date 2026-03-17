<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model {
    protected $fillable = ['lugar_id', 'jefe_user_id', 'nombre', 'descripcion', 'imagen_path'];

    public function lugar() { return $this->belongsTo(Lugar::class); }
    public function jefe() { return $this->belongsTo(User::class, 'jefe_user_id'); }
    public function equipo() {
        return $this->belongsToMany(User::class, 'zona_equipo', 'zona_id', 'user_id')->withPivot('asignado_at');
    }
    public function inventarios() { return $this->hasMany(Inventario::class); }
    public function evaluaciones() { return $this->hasMany(Evaluacion::class); }
    public function camposActivos() { return $this->hasOne(PotencialidadCamposActivos::class); }
}
