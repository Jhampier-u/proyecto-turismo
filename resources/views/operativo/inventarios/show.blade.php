<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Detalle del Recurso: {{ $inventario->nombre_recurso }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            
            <div class="mb-6 flex justify-between">
                <a href="{{ route('operativo.inventarios.index', $zona->id) }}" class="text-blue-600 hover:underline">&larr; Volver al listado</a>
                
                <div class="flex gap-2">
                    <a href="{{ route('operativo.inventarios.edit', ['zona' => $zona->id, 'inventario' => $inventario->id]) }}" class="bg-indigo-100 text-indigo-700 px-4 py-2 rounded font-bold hover:bg-indigo-200">
                        Editar Información
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">{{ session('success') }}</div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Información General</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-500 font-bold">Categoría</p>
                                <p>{{ $inventario->categoria->padre->nombre }} > {{ $inventario->categoria->nombre }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 font-bold">Propietario</p>
                                <p>{{ $inventario->propietario->nombre }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 font-bold">Ubicación</p>
                                <p>{{ $inventario->ubicacion_detallada }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 font-bold">Estado Conservación</p>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $inventario->estado_conservacion == 'Bueno' ? 'bg-green-100 text-green-800' : '' }}
                                    {{ $inventario->estado_conservacion == 'Regular' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                    {{ $inventario->estado_conservacion == 'Malo' ? 'bg-red-100 text-red-800' : '' }}">
                                    {{ $inventario->estado_conservacion }}
                                </span>
                            </div>
                        </div>

                        <div class="mt-6 space-y-4">
                            <div>
                                <p class="text-gray-500 font-bold">Descripción</p>
                                <p class="text-gray-700 whitespace-pre-line">{{ $inventario->descripcion }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 font-bold">Accesibilidad</p>
                                <p class="text-gray-700 whitespace-pre-line">{{ $inventario->accesibilidad ?? 'No especificado' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-500 font-bold">Equipamiento y Servicios</p>
                                <p class="text-gray-700 whitespace-pre-line">{{ $inventario->equipamiento_servicios ?? 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="md:col-span-1">
                    <div class="bg-white p-6 rounded-lg shadow">
                        <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Evidencia Fotográfica</h3>
                        
                        @if($inventario->imagenes->isEmpty())
                            <div class="text-center py-8 bg-gray-50 rounded border border-dashed">
                                <p class="text-gray-400">No hay fotos subidas.</p>
                            </div>
                        @else
                            <div class="space-y-4">
                                @foreach($inventario->imagenes as $img)
                                    <div class="relative group">
                                        <img src="{{ asset('storage/' . $img->ruta_archivo) }}" class="w-full h-48 object-cover rounded-lg shadow-sm border" alt="Evidencia">
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition duration-300 rounded-lg"></div>
                                        <a href="{{ asset('storage/' . $img->ruta_archivo) }}" target="_blank" class="absolute bottom-2 right-2 bg-white text-xs px-2 py-1 rounded shadow opacity-0 group-hover:opacity-100 transition">
                                            Ver Original
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>