<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Models\Lugar;
use App\Models\User;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPotencialidad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ZonaController extends Controller
{
    public function index() {
        $zonas = Zona::with(['lugar', 'jefe'])->withCount('equipo')->paginate(10);
        return view('admin.zonas.index', compact('zonas'));
    }

    public function create() {
        $zona = new Zona();
        $lugares = Lugar::orderBy('nombre')->get();
        $jefes = User::where('role_id', 2)->orderBy('name')->get();
        $estudiantes = User::where('role_id', 3)->orderBy('name')->get();
        return view('admin.zonas.form', compact('zona', 'lugares', 'jefes', 'estudiantes'));
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'nombre'        => 'required|string|max:150',
            'descripcion'   => 'nullable|string',
            'lugar_id'      => 'required|exists:lugares,id',
            'jefe_user_id'  => 'required|exists:users,id',
            'equipo'        => 'nullable|array',
            'equipo.*'      => 'exists:users,id',
            'imagen'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $data = [
            'nombre'       => $validated['nombre'],
            'descripcion'  => $validated['descripcion'],
            'lugar_id'     => $validated['lugar_id'],
            'jefe_user_id' => $validated['jefe_user_id'],
        ];

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('zonas', 'public');
        }

        $zona = Zona::create($data);

        if ($request->has('equipo')) {
            $zona->equipo()->sync($request->equipo);
        }

        return redirect()->route('admin.zonas.index')->with('success', 'Zona creada correctamente.');
    }

    public function edit($id) {
        $zona = Zona::with('equipo')->findOrFail($id);
        $lugares = Lugar::orderBy('nombre')->get();
        $jefes = User::where('role_id', 2)->orderBy('name')->get();
        $estudiantes = User::where('role_id', 3)->orderBy('name')->get();
        return view('admin.zonas.form', compact('zona', 'lugares', 'jefes', 'estudiantes'));
    }

    public function update(Request $request, $id) {
        $zona = Zona::findOrFail($id);

        $validated = $request->validate([
            'nombre'        => 'required|string|max:150',
            'descripcion'   => 'nullable|string',
            'lugar_id'      => 'required|exists:lugares,id',
            'jefe_user_id'  => 'required|exists:users,id',
            'equipo'        => 'nullable|array',
            'equipo.*'      => 'exists:users,id',
            'imagen'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ]);

        $data = [
            'nombre'       => $validated['nombre'],
            'descripcion'  => $validated['descripcion'],
            'lugar_id'     => $validated['lugar_id'],
            'jefe_user_id' => $validated['jefe_user_id'],
        ];

        if ($request->hasFile('imagen')) {
            // Borrar imagen anterior
            if ($zona->imagen_path) {
                Storage::disk('public')->delete($zona->imagen_path);
            }
            $data['imagen_path'] = $request->file('imagen')->store('zonas', 'public');
        }

        // Opción de quitar imagen
        if ($request->input('quitar_imagen') == '1' && $zona->imagen_path) {
            Storage::disk('public')->delete($zona->imagen_path);
            $data['imagen_path'] = null;
        }

        $zona->update($data);
        $zona->equipo()->sync($request->input('equipo', []));

        return redirect()->route('admin.zonas.index')->with('success', 'Zona actualizada correctamente.');
    }

    public function destroy($id) {
        $zona = Zona::findOrFail($id);
        if ($zona->imagen_path) {
            Storage::disk('public')->delete($zona->imagen_path);
        }
        $zona->delete();
        return redirect()->route('admin.zonas.index')->with('success', 'Zona eliminada correctamente.');
    }

    // Vista admin de resultados de potencialidad
    public function potencialidad($id) {
        $zona = Zona::findOrFail($id);
        $evaluacion = EvaluacionPotencialidad::where('zona_id', $id)->first();
        return view('admin.zonas.potencialidad', compact('zona', 'evaluacion'));
    }

    // Vista admin de resultados de la Matriz de Percepción
    public function percepcion($id) {
        $zona = Zona::findOrFail($id);
        $evaluacion = EvaluacionPercepcion::where('zona_id', $id)->first();
        $categorias = \App\Http\Controllers\Operativo\EvaluacionPercepcionController::$categorias;
        $readonly = true;
        return view('operativo.evaluacion_percepcion.ponderacion',
            compact('zona', 'evaluacion', 'categorias', 'readonly'));
    }
}
