<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use App\Models\Role;
use App\Models\User;
use App\Models\Zona;

class PanelController extends Controller
{
    public function index()
    {
        // Los ids de rol salen del seeder, no son constantes: se leen de una vez
        // en lugar de consultar por cada rol, y sobre todo en lugar de escribir
        // el número a mano, que es lo que hace la redirección de /dashboard.
        $idRol = Role::pluck('id', 'nombre');

        $resumen = [
            'usuarios'     => User::count(),
            'jefes'        => User::where('role_id', $idRol['jefe_zona'] ?? null)->count(),
            'equipo'       => User::where('role_id', $idRol['equipo'] ?? null)->count(),
            'lugares'      => Lugar::count(),
            'zonas'        => Zona::count(),
            // Una zona sin jefe no puede validar ninguna matriz: se queda
            // atascada en borrador y nadie sabe por qué. Es lo único de este
            // panel que pide una acción concreta del admin.
            'zonasSinJefe' => Zona::whereNull('jefe_user_id')->count(),
        ];

        return view('admin.dashboard', compact('resumen'));
    }
}
