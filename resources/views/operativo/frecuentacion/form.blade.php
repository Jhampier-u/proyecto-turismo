<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $sitio->exists ? 'Editar sitio' : 'Nuevo sitio' }}: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <x-contenedor ancho="estrecho">

            <a href="{{ route('operativo.frecuentacion.index', $zona->id) }}"
               class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                ← Volver al listado
            </a>

            <x-flash-exito />

            <form method="POST"
                  action="{{ $sitio->exists
                        ? route('operativo.frecuentacion.update', ['zona' => $zona->id, 'sitio' => $sitio->id])
                        : route('operativo.frecuentacion.store', $zona->id) }}">
                @csrf
                @if($sitio->exists)
                    @method('PUT')
                @endif

                {{--
                    Sin candado propio en este formulario, igual que el de
                    Involucrados: el cierre de la lista validada ya lo
                    guarda FrecuentacionController::bloqueoSiCerrada(), que
                    redirige antes de llegar aquí -al equipo y al admin en
                    cuanto se confirma, nunca al jefe-, así que no hace
                    falta una segunda comprobación en la vista.
                --}}

                <x-tarjeta class="mb-6">
                    <div class="mb-4">
                        <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                            Nombre del sitio
                        </label>
                        <input type="text" name="nombre" id="nombre" maxlength="200" required
                               value="{{ old('nombre', $sitio->nombre) }}"
                               placeholder="Un atractivo, un mirador, una playa..."
                               class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('nombre')
                            <span class="text-sm text-red-500 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>

                    {{--
                        DET puede quedar realmente sin responder, no en 0: un
                        campo de tipo number sin `value` (o con value="")
                        aparece vacío, y old('det', $sitio->det) devuelve una
                        cadena vacía cuando $sitio->det es null -Blade la
                        interpola tal cual, sin convertirla en "0"-. Mismo
                        tratamiento vacío-vs-cero que los campos numéricos de
                        Concentración.
                    --}}
                    <div>
                        <label for="det" class="block text-sm font-medium text-gray-700 mb-1">
                            DET (Densidad Espacial Turística)
                        </label>
                        <input type="number" step="any" min="0" name="det" id="det"
                               value="{{ old('det', $sitio->det) }}"
                               placeholder="— sin responder —"
                               class="w-48 border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        @error('det')
                            <span class="text-sm text-red-500 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                </x-tarjeta>

                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow">
                        Guardar
                    </button>
                </div>
            </form>

        </x-contenedor>
    </div>
</x-app-layout>
