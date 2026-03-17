<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable; 

    protected $fillable = [
        'name', 'email', 'password', 'role_id', 'telefono'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relaciones
    public function role() {
        return $this->belongsTo(Role::class);
    }

    public function zonasComoJefe() {
        return $this->hasMany(Zona::class, 'jefe_user_id');
    }

    public function zonasComoEquipo() {
        return $this->belongsToMany(Zona::class, 'zona_equipo', 'user_id', 'zona_id')
                    ->withPivot('asignado_at');
    }
}