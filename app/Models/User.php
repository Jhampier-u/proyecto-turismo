<?php

namespace App\Models;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // role_id queda deliberadamente fuera: es un campo de privilegio y no debe
    // poder asignarse en masa. Lo establece UserController de forma explícita.
    protected $fillable = [
        'name', 'email', 'password', 'telefono'
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            // Sin este cast, un driver que devuelva la columna como string
            // haría fallar las comparaciones estrictas.
            'role_id' => 'integer',
        ];
    }

    public const ROL_ADMIN  = 1;
    public const ROL_JEFE   = 2;
    public const ROL_EQUIPO = 3;

    public function esAdmin(): bool
    {
        return $this->role_id === self::ROL_ADMIN;
    }

    public function esJefe(): bool
    {
        return $this->role_id === self::ROL_JEFE;
    }

    public function esEquipo(): bool
    {
        return $this->role_id === self::ROL_EQUIPO;
    }

    public function tieneRolOperativo(): bool
    {
        return in_array($this->role_id, [self::ROL_ADMIN, self::ROL_JEFE, self::ROL_EQUIPO], true);
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