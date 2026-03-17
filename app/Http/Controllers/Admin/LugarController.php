<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lugar;
use App\Models\Provincia;
use Illuminate\Http\Request;

class LugarController extends Controller
{
    public function index()
    {
        $lugares = Lugar::with('provincia')->paginate(10);
        return view('admin.lugares.index', compact('lugares'));
    }

    public function create()
    {
        $lugar = new Lugar();
        $provincias = Provincia::orderBy('nombre')->get();
        return view('admin.lugares.form', compact('lugar', 'provincias'));
    }

    public function store(Request $request)
    {
        $validado = $request->validate([
            'nombre' => 'required|string|max:150',
            'provincia_id' => 'required|exists:provincias,id',
            'descripcion' => 'nullable|string'
        ]);

        Lugar::create($validado);
        
        return redirect()->route('admin.lugares.index')
            ->with('success', 'Lugar geográfico creado correctamente.');
    }

    public function edit($id)
    {
        $lugar = Lugar::findOrFail($id);
        $provincias = Provincia::orderBy('nombre')->get();
        return view('admin.lugares.form', compact('lugar', 'provincias'));
    }

    public function update(Request $request, $id)
    {
        $lugar = Lugar::findOrFail($id);
        
        $validado = $request->validate([
            'nombre' => 'required|string|max:150',
            'provincia_id' => 'required|exists:provincias,id',
            'descripcion' => 'nullable|string'
        ]);

        $lugar->update($validado);
        
        return redirect()->route('admin.lugares.index')
            ->with('success', 'Lugar actualizado correctamente');
    }

    public function destroy($id)
    {
        $lugar = Lugar::findOrFail($id);
        
        if ($lugar->zonas()->count() > 0) {
            return back()->with('error', 'No se puede borrar este lugar porque tiene Zonas asociadas.');
        }

        $lugar->delete();
        
        return redirect()->route('admin.lugares.index')
            ->with('success', 'Lugar eliminado correctamente.');
    }
}