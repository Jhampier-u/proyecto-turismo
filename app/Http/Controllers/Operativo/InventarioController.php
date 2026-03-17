<?php

namespace App\Http\Controllers\Operativo;

use App\Http\Controllers\Controller;
use App\Models\Zona;
use App\Models\Inventario;
use App\Models\InventarioImagen;
use App\Models\CategoriaRecurso;
use App\Models\TipoPropietario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InventarioController extends Controller
{
    public function index($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);


        $inventarios = $zona->inventarios()->with(['categoria', 'categoria.padre', 'imagenes'])->paginate(12);
        return view('operativo.inventarios.index', compact('zona', 'inventarios'));
    }

    public function create($zonaId)
    {
        $zona = Zona::findOrFail($zonaId);

        $categoriasPadre = CategoriaRecurso::whereNull('parent_id')->get();
        $propietarios = TipoPropietario::all();

        return view('operativo.inventarios.create', compact('zona', 'categoriasPadre', 'propietarios'));
    }


    public function subcategorias($id)
    {
        return CategoriaRecurso::where('parent_id', $id)->get();
    }

    public function store(Request $request, $zonaId)
    {
        $request->validate([
            'nombre_recurso' => 'required|string|max:200',
            'categoria_id' => 'required|exists:categorias_recurso,id', // Debe ser la categoría final (hijo)
            'propietario_id' => 'required|exists:tipos_propietario,id',
            'descripcion' => 'required|string',
            'accesibilidad' => 'nullable|string',
            'equipamiento_servicios' => 'nullable|string',
            'estado_conservacion' => 'required|in:Bueno,Regular,Malo',
            'fotos.*' => 'image|mimes:jpeg,png,jpg|max:2048' // Validación de imágenes
        ]);

        //Si falla la subida de fotos, no se crea el inventario
        DB::transaction(function () use ($request, $zonaId) {

            // Crear la Ficha
            $inventario = Inventario::create([
                'zona_id' => $zonaId,
                'categoria_id' => $request->categoria_id,
                'propietario_id' => $request->propietario_id,
                'creado_por_user_id' => auth()->id(),
                'nombre_recurso' => $request->nombre_recurso,
                'ubicacion_detallada' => $request->ubicacion_detallada,
                'descripcion' => $request->descripcion,
                'accesibilidad' => $request->accesibilidad,
                'equipamiento_servicios' => $request->equipamiento_servicios,
                'estado_conservacion' => $request->estado_conservacion,
            ]);

            // Subir y Guardar Fotos
            if ($request->hasFile('fotos')) {
                foreach ($request->file('fotos') as $foto) {
                    $path = $foto->store('inventarios', 'public');

                    // Guardar referencia en BD
                    InventarioImagen::create([
                        'inventario_id' => $inventario->id,
                        'ruta_archivo' => $path,
                        'descripcion' => 'Foto subida por ' . auth()->user()->name
                    ]);
                }
            }
        });

        return redirect()->route('operativo.inventarios.index', $zonaId)
            ->with('success', 'Recurso registrado correctamente.');
    }

    public function destroy($zonaId, $inventarioId)
    {
        $inventario = Inventario::findOrFail($inventarioId);

        foreach ($inventario->imagenes as $img) {
            Storage::disk('public')->delete($img->ruta_archivo);
        }

        $inventario->delete();

        return back()->with('success', 'Recurso eliminado.');
    }

    public function show($zonaId, $inventarioId)
    {
        $zona = Zona::findOrFail($zonaId);
        // Inventario con sus fotos, categoría y propietario (galeria de fotos)
        $inventario = Inventario::with(['imagenes', 'categoria', 'categoria.padre', 'propietario'])
            ->findOrFail($inventarioId);

        return view('operativo.inventarios.show', compact('zona', 'inventario'));
    }

    public function edit($zonaId, $inventarioId)
    {
        $zona = Zona::findOrFail($zonaId);
        $inventario = Inventario::findOrFail($inventarioId);

        $categoriasPadre = CategoriaRecurso::whereNull('parent_id')->get();

        $subcategoriasActuales = CategoriaRecurso::where('parent_id', $inventario->categoria->parent_id)->get();

        $propietarios = TipoPropietario::all();

        return view('operativo.inventarios.edit', compact('zona', 'inventario', 'categoriasPadre', 'subcategoriasActuales', 'propietarios'));
    }

    public function update(Request $request, $zonaId, $inventarioId)
    {
        $inventario = Inventario::findOrFail($inventarioId);

        $request->validate([
            'nombre_recurso' => 'required|string|max:200',
            'categoria_id' => 'required|exists:categorias_recurso,id',
            'propietario_id' => 'required|exists:tipos_propietario,id',
            'descripcion' => 'required|string',
            'estado_conservacion' => 'required|in:Bueno,Regular,Malo',
            'nuevas_fotos.*' => 'nullable|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        DB::transaction(function () use ($request, $inventario) {
            $inventario->update([
                'categoria_id' => $request->categoria_id,
                'propietario_id' => $request->propietario_id,
                'nombre_recurso' => $request->nombre_recurso,
                'ubicacion_detallada' => $request->ubicacion_detallada,
                'descripcion' => $request->descripcion,
                'accesibilidad' => $request->accesibilidad,
                'equipamiento_servicios' => $request->equipamiento_servicios,
                'estado_conservacion' => $request->estado_conservacion,
            ]);

            // Subir nuevas fotos
            if ($request->hasFile('nuevas_fotos')) {
                foreach ($request->file('nuevas_fotos') as $foto) {
                    $path = $foto->store('inventarios', 'public');
                    InventarioImagen::create([
                        'inventario_id' => $inventario->id,
                        'ruta_archivo' => $path,
                        'descripcion' => 'Agregada en edición'
                    ]);
                }
            }
        });

        return redirect()->route('operativo.inventarios.show', ['zona' => $zonaId, 'inventario' => $inventarioId])
            ->with('success', 'Recurso actualizado correctamente.');
    }
}
