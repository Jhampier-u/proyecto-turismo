<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $actor->exists ? 'Editar actor' : 'Nuevo actor' }}: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">

            <x-boton :href="route('operativo.involucrados.index', $zona->id)" variante="secundario" class="mb-4">
                ← Volver al listado
            </x-boton>

            <x-flash-exito />

            <form method="POST"
                  action="{{ $actor->exists
                        ? route('operativo.involucrados.update', ['zona' => $zona->id, 'actor' => $actor->id])
                        : route('operativo.involucrados.store', $zona->id) }}">
                @csrf
                @if($actor->exists)
                    @method('PUT')
                @endif

                {{--
                    Único formulario del sistema que ya no lleva ningún
                    candado: el admin gestiona actores como uno más -las siete
                    matrices de formulario sí bloquean, con
                    `$estaConfirmado && ! $esJefe`-. El cierre de la lista
                    validada se guarda en
                    InvolucradosController::bloqueoSiCerrada(), que ya
                    redirige antes de llegar a esta vista -al equipo en
                    cuanto se confirma, nunca al jefe ni al admin-, así que no
                    hace falta una segunda comprobación aquí.
                --}}

                <x-tarjeta class="mb-6">
                    <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">
                        Nombre del actor
                    </label>
                    <input type="text" name="nombre" id="nombre" maxlength="200" required
                           value="{{ old('nombre', $actor->nombre) }}"
                           placeholder="Un municipio, una comunidad, una operadora..."
                           class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500">
                    @error('nombre')
                        <span class="text-sm text-red-500">{{ $message }}</span>
                    @enderror
                </x-tarjeta>

                {{-- Los once criterios, agrupados en sus tres atributos. Un solo
                     bucle sobre Involucrados::ATRIBUTOS para las tres secciones,
                     igual que el formulario de Irritación recorre BLOQUES: una
                     sección nueva no se olvida de pintarse porque no hay que
                     copiar nada a mano. --}}
                @foreach($atributos as $atributo)
                    <x-tarjeta class="mb-6">
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
                    </x-tarjeta>
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
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:bg-gray-100">
                            Tiene poder
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="tiene_legitimidad" value="1"
                                   @checked($marcado('tiene_legitimidad'))
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:bg-gray-100">
                            Tiene legitimidad
                        </label>
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="tiene_urgencia" value="1"
                                   @checked($marcado('tiene_urgencia'))
                                   class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 disabled:bg-gray-100">
                            Tiene urgencia
                        </label>
                    </div>
                </section>

                <div class="flex justify-end">
                    <x-boton>
                        Guardar
                    </x-boton>
                </div>
            </form>

    </div>
</x-app-layout>
