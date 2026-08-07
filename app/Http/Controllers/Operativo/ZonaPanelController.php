<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Illuminate\Support\Facades\Auth;

/**
 * Página de una zona: el índice de su trabajo.
 *
 * La sirven los tres roles por la misma URL. El middleware 'zona' ya limita al
 * admin a métodos seguros, así que no hacen falta rutas de solo lectura
 * aparte —que era justo lo que se desincronizaba.
 */
class ZonaPanelController extends Controller
{
    public function show($zonaId)
    {
        $zona   = Zona::with('lugar', 'jefe')->findOrFail($zonaId);
        $estado = new EstadoZona($zona, Auth::user());

        return view('operativo.zona.panel', compact('zona', 'estado'));
    }
}
