<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPotencialidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // El admin no usa este dashboard — tiene el suyo propio
        if ($user->role_id === 1) {
            return redirect()->route('admin.dashboard');
        }

        $zonas = collect();

        if ($user->role_id === 2) {
            $zonas = $user->zonasComoJefe()->with('lugar')->get();
        } elseif ($user->role_id === 3) {
            $zonas = $user->zonasComoEquipo()->with('lugar')->get();
        }

        // Cargar estado de evaluación de potencialidad por zona
        $evaluaciones = EvaluacionPotencialidad::whereIn('zona_id', $zonas->pluck('id'))
            ->get()->keyBy('zona_id');

        $percepciones = EvaluacionPercepcion::whereIn('zona_id', $zonas->pluck('id'))
            ->get()->keyBy('zona_id');

        return view('operativo.dashboard', compact('zonas', 'evaluaciones', 'percepciones'));
    }
}