<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $actor->exists ? 'Editar actor' : 'Nuevo actor' }}: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ route('operativo.involucrados.index', $zona->id) }}"
               class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                ← Volver al listado
            </a>

            <x-flash-exito />

            <form method="POST"
                  action="{{ $actor->exists
                        ? route('operativo.involucrados.update', ['zona' => $zona->id, 'actor' => $actor->id])
                        : route('operativo.involucrados.store', $zona->id) }}">
                @csrf
                @if($actor->exists)
                    @method('PUT')
                @endif

                <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre del actor
                    </label>
                    <input type="text" name="nombre" id="nombre" maxlength="200" required
                           value="{{ old('nombre', $actor->nombre) }}"
                           placeholder="Un municipio, una comunidad, una operadora..."
                           class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    @error('nombre')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </section>

                {{-- Los once criterios, agrupados en sus tres atributos. Un solo
                     bucle sobre Involucrados::ATRIBUTOS para las tres secciones,
                     igual que el formulario de Irritación recorre BLOQUES: una
                     sección nueva no se olvida de pintarse porque no hay que
                     copiar nada a mano. --}}
                @foreach($atributos as $atributo)
                    <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                        <h3 class="text-lg font-bold text-gray-900 mb-4">{{ $atributo['titulo'] }}</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($atributo['campos'] as $campo => $etiqueta)
                                <x-select-involucrados
                                    :label="$etiqueta"
                                    :name="$campo"
                                    :val="$actor->$campo"
                                    :etiquetas="$etiquetasEscala[$campo]" />
                            @endforeach
                        </div>
                    </section>
                @endforeach

                {{-- Las tres casillas van juntas y con la explicación de qué
                     significan: el instrumento nunca dice cómo se pasa de un
                     grado numérico a "tiene poder" —no hay un umbral en la
                     hoja original—, así que esto no es una cuarta puntuación,
                     es el juicio de quien evalúa. De aquí sale el tipo de
                     Mitchell del actor. --}}
                @php
                    // old('tiene_poder', $actor->tiene_poder) no basta: una
                    // casilla desmarcada no viaja en la petición, así que si
                    // el usuario la destildó y OTRO campo falla la
                    // validación, old() no encuentra la clave y cae al
                    // valor guardado —marcándola nuevamente sin que nadie lo
                    // pidiera—. session()->hasOldInput() sin clave dice si
                    // esto es un repintado tras un error (y entonces manda
                    // exactamente lo enviado, con ausencia = false) o una
                    // carga normal del formulario (y entonces manda el
                    // valor ya guardado del actor).
                    $marcado = fn(string $campo) => session()->hasOldInput()
                        ? old($campo, false)
                        : $actor->$campo;
                @endphp
                <section class="bg-indigo-50 border-l-4 border-indigo-500 shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-1">¿Qué atributos posee este actor?</h3>
                    <p class="text-sm text-indigo-800 mb-4">
                        Los grados de arriba puntúan los criterios, pero el instrumento no fija un umbral
                        para decir a partir de qué grado un actor "tiene" poder, legitimidad o urgencia.
                        Esa decisión la toma quien evalúa, marcando las casillas de abajo. De las tres
                        marcas —no de los grados— sale el tipo de Mitchell del actor.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-6">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="tiene_poder" value="1"
                                   @checked($marcado('tiene_poder'))
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            Tiene poder
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="tiene_legitimidad" value="1"
                                   @checked($marcado('tiene_legitimidad'))
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            Tiene legitimidad
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="tiene_urgencia" value="1"
                                   @checked($marcado('tiene_urgencia'))
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            Tiene urgencia
                        </label>
                    </div>
                </section>

                <div class="flex justify-end">
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow">
                        Guardar
                    </button>
                </div>
            </form>

        </div>
    </div>
</x-app-layout>
