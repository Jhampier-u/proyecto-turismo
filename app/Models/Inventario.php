<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventario extends Model
{
    protected $fillable = [
        'zona_id', 'categoria_id', 'propietario_id', 'creado_por_user_id',
        'nombre_recurso', 'ubicacion_detallada', 'descripcion',
        'accesibilidad', 'equipamiento_servicios', 'estado_conservacion'
    ];

    public function zona() {
        return $this->belongsTo(Zona::class);
    }

    public function categoria() {
        return $this->belongsTo(CategoriaRecurso::class, 'categoria_id');
    }

    public function propietario() {
        return $this->belongsTo(TipoPropietario::class, 'propietario_id');
    }

    public function creador() {
        return $this->belongsTo(User::class, 'creado_por_user_id');
    }

    public function imagenes() {
        return $this->hasMany(InventarioImagen::class);
    }
}