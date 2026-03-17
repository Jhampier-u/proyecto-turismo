<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $lugar->exists ? 'Editar Lugar: ' . $lugar->nombre : 'Registrar Nuevo Lugar' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form method="POST" action="{{ $lugar->exists ? route('admin.lugares.update', $lugar) : route('admin.lugares.store') }}">
                    @csrf
                    @if($lugar->exists) @method('PUT') @endif

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Nombre del Lugar</label>
                        <input type="text" name="nombre" class="w-full border-gray-300 rounded-md shadow-sm" 
                               value="{{ old('nombre', $lugar->nombre) }}" required placeholder="Ej: San Fernando">
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Provincia</label>
                        <select name="provincia_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="" disabled selected>Seleccione provincia...</option>
                            @foreach($provincias as $prov)
                                <option value="{{ $prov->id }}" {{ old('provincia_id', $lugar->provincia_id) == $prov->id ? 'selected' : '' }}>
                                    {{ $prov->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('provincia_id')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 font-bold mb-2">Descripción (Opcional)</label>
                        <textarea name="descripcion" rows="3" class="w-full border-gray-300 rounded-md shadow-sm">{{ old('descripcion', $lugar->descripcion) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('admin.lugares.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                            {{ $lugar->exists ? 'Actualizar' : 'Guardar' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>