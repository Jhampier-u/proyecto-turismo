<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Índice de Irritación Turística: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
        // Se deriva del rol, no de una variable que pase el controlador: ver el
        // mismo criterio en el resto de vistas de resultados de este sistema.
        $readonly = ! auth()->user()->puedeEditarEvaluaciones();

        // Los dos bloques se responden por separado y pueden completarse en
        // momentos distintos: con cualquiera de los dos promedios en null la
        // matriz sigue a medias, y pintarlo como 0.00 se leería como una
        // irritación mínima real que nadie ha medido todavía.
        $sinDatos = ! $evaluacion
            || $evaluacion->visitantes_promedio === null
            || $evaluacion->residentes_promedio === null;
    @endphp

    @if($sinDatos)
        <x-matriz-sin-resultados
            nombre="Índice de Irritación"
            :zona="$zona"
            ruta-formulario="operativo.evaluacion_irritacion.edit">
            Hacen falta los dos bloques —visitantes y residentes— respondidos por completo.
        </x-matriz-sin-resultados>
    @else

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                <x-flash-exito />

                <h3 class="text-center font-bold text-xl text-gray-700 mb-8">
                    Índice de Irritación Turística
                </h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                    <div class="bg-sky-50 border border-sky-200 rounded-lg p-6 text-center">
                        <p class="text-sm text-sky-700 font-bold">Visitantes</p>
                        <p class="text-4xl font-black text-sky-700 mt-1">
                            {{ number_format($evaluacion->visitantes_promedio, 2) }}
                        </p>
                        <x-insignia-clasificacion-irritacion :valor="$evaluacion->clasificacion_visitantes" class="mt-2" />
                        <p class="text-sm text-gray-600 mt-3">
                            {{ $interpretaciones['visitantes'][$evaluacion->clasificacion_visitantes] }}
                        </p>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
                        <p class="text-sm text-amber-700 font-bold">Residentes</p>
                        <p class="text-4xl font-black text-amber-700 mt-1">
                            {{ number_format($evaluacion->residentes_promedio, 2) }}
                        </p>
                        <x-insignia-clasificacion-irritacion :valor="$evaluacion->clasificacion_residentes" class="mt-2" />
                        <p class="text-sm text-gray-600 mt-3">
                            {{ $interpretaciones['residentes'][$evaluacion->clasificacion_residentes] }}
                        </p>
                    </div>
                </div>

                {{-- Detalle por bloque, cada uno con sus propios seis atributos:
                     cruzarlos en una sola tabla mezclaría dos poblaciones
                     distintas, igual que evita el controlador al no combinarlos
                     en un índice único. Un solo bucle sobre $bloques
                     (Irritacion::BLOQUES, que pasa el controlador) en vez de un
                     array literal de pares: el conjunto de bloques ya vive
                     declarado una sola vez en el instrumento. --}}
                @foreach($bloques as $bloque)
                    <h4 class="font-bold text-gray-700 mt-6 mb-2">{{ $bloque['titulo'] }}</h4>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 p-2 text-left">Atributo</th>
                                    <th class="border border-gray-300 p-2">Valor</th>
                                    <th class="border border-gray-300 p-2">Clasificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- clasificacionDe() vive en el modelo, no aquí: es la
                                     misma razón por la que $etiquetas y $bloques llegan
                                     por el controlador y no por App\Matrices\Irritacion
                                     directo. El único sitio que sigue llamando a
                                     Irritacion::clasificar() por su cuenta es
                                     select-irritacion.blade.php, que no tiene
                                     controlador detrás. --}}
                                @foreach($bloque['campos'] as $campo)
                                    <tr>
                                        <td class="border border-gray-300 p-2">{{ $etiquetas[$campo] }}</td>
                                        <td class="border border-gray-300 p-2 text-center font-bold">{{ $evaluacion->$campo }}</td>
                                        <td class="border border-gray-300 p-2 text-center">
                                            <x-insignia-clasificacion-irritacion :valor="$evaluacion->clasificacionDe($campo)" />
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                <div class="mt-8 flex gap-4 justify-center flex-wrap">
                    @if(!$readonly)
                        <a href="{{ route('operativo.evaluacion_irritacion.edit', $zona->id) }}"
                            class="inline-block px-5 py-2 bg-indigo-600 text-white font-bold text-lg rounded-lg hover:bg-indigo-700 hover:scale-105 transition-transform duration-200 shadow-md">
                            ← Volver al Formulario
                        </a>
                        <a href="{{ route('operativo.dashboard') }}"
                            class="inline-block px-5 py-2 bg-gray-200 text-black font-bold text-lg rounded-lg hover:bg-gray-400 hover:scale-105 transition-transform duration-200 shadow-md">
                            Mis Zonas
                        </a>
                    @else
                        <a href="{{ route('admin.zonas.index') }}"
                            class="inline-block px-5 py-2 bg-gray-200 text-black font-bold text-lg rounded-lg hover:bg-gray-400 hover:scale-105 transition-transform duration-200 shadow-md">
                            Volver a Zonas
                        </a>
                    @endif
                </div>

            </div>
        </div>
    </div>
    @endif
</x-app-layout>
