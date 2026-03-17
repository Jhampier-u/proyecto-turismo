<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Registrar Nuevo Recurso en: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST" action="{{ route('operativo.inventarios.store', $zona->id) }}" enctype="multipart/form-data">
                    @csrf

                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">1. Clasificación</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-gray-700">Tipo (Categoría Principal)</label>
                                <select id="cat_padre" class="w-full border-gray-300 rounded shadow-sm">
                                    <option value="">Seleccione...</option>
                                    @foreach($categoriasPadre as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700">Subtipo (Específico)</label>
                                <select name="categoria_id" id="cat_hijo" class="w-full border-gray-300 rounded shadow-sm bg-gray-50" disabled required>
                                    <option value="">Primero seleccione el tipo...</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">2. Datos de Identificación</h3>
                        
                        <div class="mb-4">
                            <label class="block font-bold text-gray-700">Nombre del Recurso</label>
                            <input type="text" name="nombre_recurso" class="w-full border-gray-300 rounded shadow-sm" required placeholder="Ej: Cascada Velo de Novia">
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-gray-700">Propietario</label>
                                <select name="propietario_id" class="w-full border-gray-300 rounded shadow-sm" required>
                                    @foreach($propietarios as $prop)
                                        <option value="{{ $prop->id }}">{{ $prop->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700">Estado de Conservación</label>
                                <select name="estado_conservacion" class="w-full border-gray-300 rounded shadow-sm" required>
                                    <option value="Bueno">Bueno</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Malo">Malo</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block font-bold text-gray-700">Ubicación Detallada</label>
                            <input type="text" name="ubicacion_detallada" class="w-full border-gray-300 rounded shadow-sm" placeholder="Ej: A 5km de la entrada principal, sector norte">
                        </div>
                    </div>

                    <div class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">3. Descripción Técnica</h3>
                        
                        <div class="mb-4">
                            <label class="block font-bold text-gray-700">Descripción General</label>
                            <textarea name="descripcion" rows="3" class="w-full border-gray-300 rounded shadow-sm" required></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block font-bold text-gray-700">Accesibilidad</label>
                                <textarea name="accesibilidad" rows="3" class="w-full border-gray-300 rounded shadow-sm" placeholder="Estado de vías, transporte..."></textarea>
                            </div>
                            <div>
                                <label class="block font-bold text-gray-700">Equipamiento y Servicios</label>
                                <textarea name="equipamiento_servicios" rows="3" class="w-full border-gray-300 rounded shadow-sm" placeholder="Baños, señalética, parqueo..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-700 mb-4">4. Evidencia Fotográfica</h3>
                        <div class="border-2 border-dashed border-gray-300 p-6 rounded-md text-center hover:bg-gray-50 transition">
                            <p class="text-gray-500 mb-2">Haga clic o arrastre imágenes aquí</p>
                            <input type="file" name="fotos[]" multiple accept="image/*" class="w-full cursor-pointer">
                            <p class="text-xs text-gray-400 mt-2">Formatos: JPG, PNG. Máx 2MB por foto.</p>
                        </div>
                    </div>

                    <div class="flex justify-end gap-4">
                        <a href="{{ route('operativo.inventarios.index', $zona->id) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">Cancelar</a>
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-bold rounded hover:bg-blue-700">Guardar Recurso</button>
                    </div>

                </form>
            </div>
        </div>
    </div>

@vite(['resources/js/inventario-categorias.js'])
</x-app-layout>