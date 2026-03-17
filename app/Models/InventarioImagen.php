<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventarioImagen extends Model
{
    protected $table = 'inventario_imagenes';
    public $timestamps = false;
    protected $fillable = ['inventario_id', 'ruta_archivo', 'descripcion'];

    public function inventario() {
        return $this->belongsTo(Inventario::class);
    }
}