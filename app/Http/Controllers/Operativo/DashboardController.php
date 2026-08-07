<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
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
        $progreso = $zonas->mapWithKeys(function (Zona $zona) use ($user) {
            $estado = new EstadoZona($zona, $user);

            return [$zona->id => [
                'hechas' => $estado->validadas(),
                'total'  => $estado->totalMatrices(),
            ]];
        });

        return view('operativo.dashboard', compact('zonas', 'progreso'));
    }
}
