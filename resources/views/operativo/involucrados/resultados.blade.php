<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Involucrados Turísticos — Resultados: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @if(! $completa)
        <x-matriz-sin-resultados
            nombre="Involucrados turísticos"
            :zona="$zona"
            ruta-formulario="operativo.involucrados.index" />
    @else
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

                <x-pestanas-matriz clave="involucrados" :zona="$zona" activa="resultados" />

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                    <a href="{{ route('operativo.involucrados.index', $zona->id) }}"
                       class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                        ← Volver al listado
                    </a>

                    {{-- Involucrados no tiene un $evaluacion por actor: el
                         "quién tocó esto por última vez" vive en
                         InvolucradosConfig, el estado del CONJUNTO -no en
                         Involucrado, que no guarda user_id-. Mismo dato que
                         index.blade.php, ahora también en resultados. --}}
                    @if($config?->exists && $config->user)
                        <p class="text-sm text-gray-500 mb-4">
                            Última edición: {{ $config->user->name }},
                            {{ $config->updated_at->diffForHumans() }}
                        </p>
                    @endif

                    {{--
                        El aviso encabeza la tabla a propósito, no al pie: es el
                        punto que el diseño marca como imprescindible. Sin él,
                        alguien compara el normalizado de un actor entre dos
                        visitas a esta página —una antes y otra después de que
                        se dé de alta o de baja a un tercero— y cree que algo
                        cambió en el territorio, cuando lo único que cambió es
                        el conjunto sobre el que se divide.

                        Reescrito tras revisión: la versión anterior afirmaba
                        sin matices que "cada valor se calcula dividiendo...
                        entre la suma", y eso es falso justo en el caso que
                        hace falta explicar —con la suma en 0 no hay nada que
                        dividir, y ese caso es real, ver el docblock de
                        Involucrados::normalizar()—. El texto de abajo separa
                        el caso general del degenerado en vez de generalizar
                        uno como si fuera el otro.
                    --}}
                    <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 text-sm rounded">
                        <p>
                            <strong class="font-bold">Los valores normalizados son relativos a este conjunto de actores.</strong>
                            En el caso general, el normalizado de un actor reparte su grado frente al del resto:
                            es su grado dividido entre la suma de los grados de todos los actores, multiplicado
                            por el número de actores. Añadir o quitar un actor recalcula los normalizados de
                            TODOS los demás, aunque sus propios criterios no cambien.
                        </p>
                        <p class="mt-2">
                            Cuando un atributo no diferencia a ningún actor de este conjunto —porque hay un
                            único actor, o porque ninguno puntúa nada en él— se marca con <strong>—</strong> en
                            vez de un número. La relevancia final es el producto de los atributos que sí
                            diferencian a los actores del conjunto.
                        </p>
                    </div>

                    <h3 class="text-center font-bold text-xl text-gray-700 mb-8">
                        Actores por relevancia
                    </h3>

                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 p-2 text-left">Actor</th>
                                    @foreach($atributos as $atributo)
                                        <th class="border border-gray-300 p-2">{{ $atributo['titulo'] }}</th>
                                    @endforeach
                                    @foreach($atributos as $atributo)
                                        <th class="border border-gray-300 p-2">{{ $atributo['titulo'] }} (norm.)</th>
                                    @endforeach
                                    <th class="border border-gray-300 p-2">Relevancia</th>
                                    <th class="border border-gray-300 p-2">Tipo de Mitchell</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{--
                                    $filas ya llega ordenada por relevancia descendente
                                    —InvolucradosController::relevanciasDe() ordena, no la
                                    vista—: es el mismo criterio que el resto del sistema,
                                    donde la vista solo pinta lo que el controlador y el
                                    instrumento ya decidieron. $fila['grado'] también llega
                                    ya resuelto: son los mismos grados que relevanciasDe()
                                    usó para normalizar, no una segunda llamada a grado()
                                    que podría desalinearse del cálculo real.
                                --}}
                                @foreach($filas as $fila)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border border-gray-300 p-2 font-bold text-gray-900">
                                            {{ $fila['actor']->nombre }}
                                        </td>
                                        @foreach(array_keys($atributos) as $clave)
                                            <td class="border border-gray-300 p-2 text-center">
                                                {{ $fila['grado'][$clave] }}
                                            </td>
                                        @endforeach
                                        @foreach(array_keys($atributos) as $clave)
                                            <td class="border border-gray-300 p-2 text-center">
                                                @if($degenerados[$clave])
                                                    <span class="text-gray-400"
                                                          title="Este atributo no diferencia a ningún actor de este conjunto: hay un único actor, o ninguno puntúa nada en él.">—</span>
                                                @else
                                                    {{ number_format($fila['normalizado'][$clave], 3) }}
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="border border-gray-300 p-2 text-center font-bold">
                                            {{ number_format($fila['relevancia'], 4) }}
                                        </td>
                                        <td class="border border-gray-300 p-2 text-center">
                                            <x-insignia-tipo-mitchell-involucrados :valor="$fila['actor']->tipo_mitchell" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>
        </div>
    @endif
</x-app-layout>
