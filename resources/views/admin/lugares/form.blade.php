<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ $lugar->exists ? 'Editar Lugar: ' . $lugar->nombre : 'Registrar Nuevo Lugar' }}
        </h2>
    </x-slot>

    <div class="py-12">
        {{-- :padding="false" porque este contenedor va dentro del que el
             layout ya pone: el ancho estrecho sí se quiere, el padding
             repetido no. --}}
        <x-contenedor ancho="estrecho" :padding="false">
            <x-tarjeta class="overflow-hidden">

                <form method="POST" action="{{ $lugar->exists ? route('admin.lugares.update', $lugar) : route('admin.lugares.store') }}">
                    @csrf
                    @if($lugar->exists) @method('PUT') @endif

                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 font-bold mb-2">Nombre del Lugar</label>
                        <input type="text" name="nombre" class="w-full border-gray-300 rounded-md shadow-sm text-base" 
                               value="{{ old('nombre', $lugar->nombre) }}" required placeholder="Ej: San Fernando">
                        <x-input-error :messages="$errors->get('nombre')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm text-gray-700 font-bold mb-2">Provincia</label>
                        <select name="provincia_id" class="w-full border-gray-300 rounded-md shadow-sm text-base">
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
                        <label class="block text-sm text-gray-700 font-bold mb-2">Descripción (Opcional)</label>
                        <textarea name="descripcion" rows="3" class="w-full border-gray-300 rounded-md shadow-sm text-base">{{ old('descripcion', $lugar->descripcion) }}</textarea>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('admin.lugares.index') }}" class="text-base text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                        <x-boton>
                            {{ $lugar->exists ? 'Actualizar' : 'Guardar' }}
                        </x-boton>
                    </div>
                </form>

            </x-tarjeta>
        </x-contenedor>
    </div>
</x-app-layout>