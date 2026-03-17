<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $zona->exists ? 'Editar Zona: ' . $zona->nombre : 'Crear Nueva Zona' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="POST"
                      action="{{ $zona->exists ? route('admin.zonas.update', $zona->id) : route('admin.zonas.store') }}"
                      enctype="multipart/form-data">
                    @csrf
                    @if($zona->exists) @method('PUT') @endif

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        {{-- Columna izquierda --}}
                        <div>
                            <h3 class="text-lg font-bold text-gray-700 mb-4 border-b pb-2">Datos Generales</h3>

                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">Nombre de la Zona</label>
                                <input type="text" name="nombre" class="w-full border-gray-300 rounded-md shadow-sm"
                                       value="{{ old('nombre', $zona->nombre) }}" required>
                                <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">Ubicación (Lugar)</label>
                                <select name="lugar_id" class="w-full border-gray-300 rounded-md shadow-sm" required>
                                    <option value="" disabled selected>Seleccione...</option>
                                    @foreach($lugares as $lugar)
                                        <option value="{{ $lugar->id }}" {{ old('lugar_id', $zona->lugar_id) == $lugar->id ? 'selected' : '' }}>
                                            {{ $lugar->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">Descripción</label>
                                <textarea name="descripcion" rows="4" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('descripcion', $zona->descripcion) }}</textarea>
                            </div>

                            {{-- IMAGEN DE ZONA --}}
                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">Imagen de la Zona</label>

                                @if($zona->exists && $zona->imagen_path)
                                    <div class="mb-3">
                                        <img src="{{ asset('storage/' . $zona->imagen_path) }}"
                                             class="w-full h-48 object-cover rounded-lg border shadow-sm" alt="Imagen actual">
                                        <div class="mt-2 flex items-center gap-3">
                                            <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                                <input type="checkbox" name="quitar_imagen" value="1"
                                                       class="rounded border-gray-300 text-red-600">
                                                Quitar imagen actual
                                            </label>
                                        </div>
                                    </div>
                                @endif

                                <input type="file" name="imagen" accept="image/*"
                                       class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                                <p class="text-xs text-gray-400 mt-1">JPG, PNG, WEBP · máx. 3 MB</p>
                                <x-input-error :messages="$errors->get('imagen')" class="mt-2" />
                            </div>
                        </div>

                        {{-- Columna derecha: Equipo --}}
                        <div>
                            <h3 class="text-lg font-bold text-blue-800 mb-4 border-b pb-2">Equipo de Trabajo</h3>

                            <div class="mb-6 bg-blue-50 p-4 rounded border border-blue-200">
                                <label class="block text-blue-900 font-bold mb-2">Jefe de Zona</label>
                                <select name="jefe_user_id" class="w-full border-blue-300 rounded-md shadow-sm" required>
                                    <option value="" disabled selected>Seleccione responsable...</option>
                                    @foreach($jefes as $jefe)
                                        <option value="{{ $jefe->id }}" {{ old('jefe_user_id', $zona->jefe_user_id) == $jefe->id ? 'selected' : '' }}>
                                            {{ $jefe->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">Estudiantes Asignados</label>
                                <div class="max-h-64 overflow-y-auto border border-gray-200 rounded p-3 bg-gray-50">
                                    @if($estudiantes->isEmpty())
                                        <p class="text-sm text-red-500">No hay estudiantes registrados.</p>
                                    @endif
                                    @foreach($estudiantes as $est)
                                        <div class="flex items-center mb-2 p-2 hover:bg-white rounded transition">
                                            <input type="checkbox" name="equipo[]" value="{{ $est->id }}" id="user_{{ $est->id }}"
                                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:ring-blue-500"
                                                   {{ (is_array(old('equipo')) && in_array($est->id, old('equipo'))) || ($zona->exists && $zona->equipo->contains($est->id)) ? 'checked' : '' }}>
                                            <label for="user_{{ $est->id }}" class="ms-2 text-sm text-gray-700 cursor-pointer w-full">
                                                {{ $est->name }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6">
                        <a href="{{ route('admin.zonas.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded shadow">
                            {{ $zona->exists ? 'Actualizar Zona' : 'Guardar Zona' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
