<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Servicios\EstadoZona;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // El admin no usa este dashboard — tiene el suyo propio
        if ($user->esAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $zonas = match (true) {
            $user->esJefe()   => $user->zonasComoJefe()->with('lugar')->get(),
            $user->esEquipo() => $user->zonasComoEquipo()->with('lugar')->get(),
            default           => collect(),
        };

        // Antes esto eran cuatro consultas manuales que había que ampliar con
        // cada matriz nueva; una de ellas se olvidó y Paisaje quedó fuera.
        //
        // progresoDe() resuelve todas las zonas con un número fijo de
        // consultas (una por matriz). Instanciar un EstadoZona por zona aquí
        // costaba seis consultas por zona — correcto pero no escalaba con la
        // lista de zonas del jefe.
        $progreso = EstadoZona::progresoDe($zonas);

        // proximoPaso() recibe $progreso ya calculado: pedirlo dos veces
        // duplicaría las consultas de progresoDe() sin ganar nada.
        $proximoPaso = EstadoZona::proximoPaso($user, $zonas, $progreso);

        return view('operativo.dashboard', compact('zonas', 'progreso', 'proximoPaso'));
    }
}
