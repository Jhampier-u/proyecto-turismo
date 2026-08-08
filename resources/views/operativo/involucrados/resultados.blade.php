<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Involucrados Turísticos — Resultados: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
        // Se deriva del rol, no de una variable que pase el controlador: ver
        // el mismo criterio en el resto de vistas de resultados de este
        // sistema (ponderacion.blade.php de Irritación, por ejemplo).
        $readonly = ! auth()->user()->puedeEditarEvaluaciones();
    @endphp

    @if(! $completa)
        <x-matriz-sin-resultados
            nombre="Involucrados turísticos"
            :zona="$zona"
            ruta-formulario="operativo.involucrados.index" />
    @else
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                    @if($readonly)
                        <a href="{{ route('admin.zonas.index') }}"
                           class="inline-block px-5 py-2 mb-4 bg-gray-200 text-black font-bold rounded-lg hover:bg-gray-400 shadow-md">
                            ← Volver a Zonas
                        </a>
                    @else
                        <a href="{{ route('operativo.involucrados.index', $zona->id) }}"
                           class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                            ← Volver al listado
                        </a>
                    @endif

                    {{--
                        El aviso encabeza la tabla a propósito, no al pie: es
                        el punto que el diseño marca como imprescindible. Sin
                        él, alguien compara el normalizado de un actor entre
                        dos visitas a esta página —una antes y otra después de
                        que se dé de alta o de baja a un tercero— y cree que
                        algo cambió en el territorio, cuando lo único que
                        cambió es el conjunto sobre el que se divide.
                    --}}
                    <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 text-sm rounded">
                        <strong class="font-bold">Los valores normalizados son relativos a este conjunto de actores.</strong>
                        Cada uno se calcula dividiendo el grado del actor entre la suma de los grados de
                        todos los actores registrados: añadir o quitar un actor recalcula los normalizados
                        de TODOS los demás, aunque sus propios criterios no cambien. No compares el
                        normalizado de un actor entre dos momentos distintos de la lista; compáralo solo
                        contra los demás actores de esta misma tabla.
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
                                    —es Involucrados::relevancias() quien ordena, no la
                                    vista—: es el mismo criterio que el resto del sistema,
                                    donde la vista solo pinta lo que el controlador y el
                                    instrumento ya decidieron.
                                --}}
                                @foreach($filas as $fila)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border border-gray-300 p-2 font-bold text-gray-900">
                                            {{ $fila['actor']->nombre }}
                                        </td>
                                        @foreach(array_keys($atributos) as $clave)
                                            <td class="border border-gray-300 p-2 text-center">
                                                {{ $fila['actor']->grado($clave) }}
                                            </td>
                                        @endforeach
                                        @foreach(array_keys($atributos) as $clave)
                                            <td class="border border-gray-300 p-2 text-center">
                                                {{ number_format($fila['normalizado'][$clave], 2) }}
                                            </td>
                                        @endforeach
                                        <td class="border border-gray-300 p-2 text-center font-bold">
                                            {{ number_format($fila['relevancia'], 2) }}
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
