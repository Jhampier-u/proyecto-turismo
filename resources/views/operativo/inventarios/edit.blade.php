<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar Recurso: {{ $inventario->nombre_recurso }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('operativo.inventarios.update', ['zona' => $zona->id, 'inventario' => $inventario->id]) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">1. Clasificación</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            
                            <div>
                                <label class="block font-bold text-gray-700">Tipo (Categoría Principal)</label>
                                <select id="cat_padre" class="w-full border-gray-300 rounded shadow-sm">
                                    <option value="">Seleccione...</option>
                                    @foreach($categoriasPadre as $cat)
                                        <option value="{{ $cat->id }}" 
                                            {{-- Pre-seleccionamos el PADRE de la categoría actual --}}
                                            {{ $inventario->categoria->parent_id == $cat->id ? 'selected' : '' }}>
                                            {{ $cat->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-gray-700">Subtipo (Específico)</label>
                                <select name="categoria_id" id="cat_hijo" class="w-full border-gray-300 rounded shadow-sm bg-gray-50" required>
                                    @foreach($subcategoriasActuales as $sub)
                                        <option value="{{ $sub->id }}" 
                                            {{ $inventario->categoria_id == $sub->id ? 'selected' : '' }}>
                                            {{ $sub->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">2. Datos de Identificación</h3>
                        
                        <div class="mb-4">
                            <label class="block font-bold text-gray-700">Nombre del Recurso</label>
                            <input type="text" name="nombre_recurso" class="w-full border-gray-300 rounded shadow-sm" 
                                   value="{{ old('nombre_recurso', $inventario->nombre_recurso) }}" required>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-gray-700">Propietario</label>
                                <select name="propietario_id" class="w-full border-gray-300 rounded shadow-sm" required>
                                    @foreach($propietarios as $prop)
                                        <option value="{{ $prop->id }}" {{ $inventario->propietario_id == $prop->id ? 'selected' : '' }}>
                                            {{ $prop->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700">Estado de Conservación</label>
                                <select name="estado_conservacion" class="w-full border-gray-300 rounded shadow-sm" required>
                                    <option value="Bueno" {{ $inventario->estado_conservacion == 'Bueno' ? 'selected' : '' }}>Bueno</option>
                                    <option value="Regular" {{ $inventario->estado_conservacion == 'Regular' ? 'selected' : '' }}>Regular</option>
                                    <option value="Malo" {{ $inventario->estado_conservacion == 'Malo' ? 'selected' : '' }}>Malo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block font-bold text-gray-700">Ubicación Detallada</label>
                            <input type="text" name="ubicacion_detallada" class="w-full border-gray-300 rounded shadow-sm" 
                                   value="{{ old('ubicacion_detallada', $inventario->ubicacion_detallada) }}">
                        </div>
                    </div>

                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">3. Descripción Técnica</h3>
                        
                        <div class="mb-4">
                            <label class="block font-bold text-gray-700">Descripción General</label>
                            <textarea name="descripcion" rows="4" class="w-full border-gray-300 rounded shadow-sm" required>{{ old('descripcion', $inventario->descripcion) }}</textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-gray-700">Accesibilidad</label>
                                <textarea name="accesibilidad" rows="3" class="w-full border-gray-300 rounded shadow-sm">{{ old('accesibilidad', $inventario->accesibilidad) }}</textarea>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700">Equipamiento y Servicios</label>
                                <textarea name="equipamiento_servicios" rows="3" class="w-full border-gray-300 rounded shadow-sm">{{ old('equipamiento_servicios', $inventario->equipamiento_servicios) }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">4. Evidencia Fotográfica</h3>
                        
                        <div class="mb-4 p-3 bg-blue-50 text-blue-800 rounded text-sm">
                            Actualmente hay <strong>{{ $inventario->imagenes->count() }}</strong> fotos guardadas. 
                            Las fotos que subas aquí se <strong>agregarán</strong> a las existentes.
                        </div>

                        <div class="border-2 border-dashed border-gray-300 p-6 rounded-md text-center hover:bg-gray-50 transition">
                            <p class="text-gray-500 mb-2">Subir NUEVAS imágenes (Opcional)</p>
                            <input type="file" name="nuevas_fotos[]" multiple accept="image/*" class="w-full cursor-pointer">
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('operativo.inventarios.show', ['zona' => $zona->id, 'inventario' => $inventario->id]) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700">Actualizar Recurso</button>
                    </div>

                </form>
            </div>
        </div>
    </div>
@vite(['resources/js/inventario-categorias.js'])
</x-app-layout>