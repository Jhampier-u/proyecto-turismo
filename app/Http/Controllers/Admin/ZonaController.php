<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Matrices\Paisaje;
use App\Matrices\ValoracionTerritorial;
use App\Models\Zona;
use App\Models\Lugar;
use App\Models\User;
use App\Models\EvaluacionPercepcion;
use App\Models\EvaluacionPaisaje;
use App\Models\EvaluacionPotencialidad;
use App\Models\EvaluacionValoracionTerritorial;
use App\Models\InventarioImagen;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ZonaController extends Controller
{
    /**
     * Reglas comunes de creación y edición.
     *
     * El jefe y el equipo se restringen por rol: antes bastaba con que el id
     * existiera, así que se podía asignar como jefe a un estudiante y la zona
     * quedaba bloqueada en borrador sin explicación (solo el rol jefe_zona
     * puede confirmar evaluaciones).
     */
    private function reglas(): array
    {
        $idRol = fn(string $nombre) => Role::where('nombre', $nombre)->value('id');

        return [
            'nombre'       => 'required|string|max:150',
            'descripcion'  => 'nullable|string',
            'lugar_id'     => 'required|exists:lugares,id',
            'jefe_user_id' => ['required', Rule::exists('users', 'id')->where('role_id', $idRol('jefe_zona'))],
            'equipo'       => 'nullable|array',
            'equipo.*'     => [Rule::exists('users', 'id')->where('role_id', $idRol('equipo'))],
            'imagen'       => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
        ];
    }
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
        $validated = $request->validate($this->reglas());

        $data = [
            'nombre'       => $validated['nombre'],
            // 'descripcion' es nullable: si no viene en el request, validate()
            // no la incluye en el array devuelto.
            'descripcion'  => $validated['descripcion'] ?? null,
            'lugar_id'     => $validated['lugar_id'],
            'jefe_user_id' => $validated['jefe_user_id'],
        ];

        if ($request->hasFile('imagen')) {
            $data['imagen_path'] = $request->file('imagen')->store('zonas');
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

        $validated = $request->validate($this->reglas());

        $data = [
            'nombre'       => $validated['nombre'],
            'descripcion'  => $validated['descripcion'] ?? null,
            'lugar_id'     => $validated['lugar_id'],
            'jefe_user_id' => $validated['jefe_user_id'],
        ];

        // Subir una imagen nueva y marcar "quitar imagen" a la vez son órdenes
        // contradictorias: antes se guardaba el archivo y luego se anulaba la
        // referencia, dejándolo huérfano en disco. Gana la imagen nueva.
        if ($request->hasFile('imagen')) {
            if ($zona->imagen_path) {
                Storage::delete($zona->imagen_path);
            }
            $data['imagen_path'] = $request->file('imagen')->store('zonas');
        } elseif ($request->input('quitar_imagen') == '1' && $zona->imagen_path) {
            Storage::delete($zona->imagen_path);
            $data['imagen_path'] = null;
        }

        $zona->update($data);
        $zona->equipo()->sync($request->input('equipo', []));

        return redirect()->route('admin.zonas.index')->with('success', 'Zona actualizada correctamente.');
    }

    public function destroy($id) {
        $zona = Zona::findOrFail($id);

        // La cascada de la base de datos borra inventarios e inventario_imagenes,
        // pero no los archivos: hay que recogerlos antes de perder las filas.
        $archivos = InventarioImagen::whereIn(
            'inventario_id',
            $zona->inventarios()->select('id')
        )->pluck('ruta_archivo');

        if ($zona->imagen_path) {
            $archivos->push($zona->imagen_path);
        }

        $zona->delete();

        Storage::delete($archivos->all());

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

    // Vista admin de resultados de la Matriz de Paisaje
    public function paisaje($id) {
        $zona = Zona::findOrFail($id);

        return view('operativo.evaluacion_paisaje.ponderacion', [
            'zona'       => $zona,
            'evaluacion' => EvaluacionPaisaje::where('zona_id', $id)->first(),
            'categorias' => Paisaje::CATEGORIAS,
            'readonly'   => true,
        ]);
    }

    // Vista admin de resultados de valoración territorial
    public function valoracionTerritorial($id) {
        $zona = Zona::findOrFail($id);
        $evaluacion = EvaluacionValoracionTerritorial::where('zona_id', $id)->first();
        $readonly = true;

        return view('operativo.evaluacion_valoracion_territorial.ponderacion', [
            'zona'       => $zona,
            'evaluacion' => $evaluacion,
            'ct'         => ValoracionTerritorial::CT,
            'uc'         => ValoracionTerritorial::UC,
            'readonly'   => $readonly,
        ]);
    }
}
