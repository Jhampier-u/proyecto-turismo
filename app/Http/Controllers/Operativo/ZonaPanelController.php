<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Servicios\EstadoZona;
use Illuminate\Support\Facades\Auth;

/**
 * Página de una zona: el índice de su trabajo.
 *
 * La sirven los tres roles por la misma URL, sin rutas de solo lectura
 * aparte para el admin: PerteneceAZona ya no lo limita a métodos seguros, y
 * EstadoZona::fila() le da las mismas acciones que al equipo salvo validar.
 */
class ZonaPanelController extends Controller
{
    public function show($zonaId)
    {
        $zona   = Zona::with('lugar', 'jefe', 'equipo')->findOrFail($zonaId);
        $estado = new EstadoZona($zona, Auth::user());

        return view('operativo.zona.panel', compact('zona', 'estado'));
    }
}
