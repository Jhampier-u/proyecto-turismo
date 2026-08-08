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
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow">
                    <div class="flex items-center gap-4">
                        <span class="text-4xl">🧭</span>
                        <div>
                            <h3 class="font-bold text-yellow-800 text-lg">Índice de Irritación no disponible</h3>
                            <p class="text-yellow-700 mt-1">
                                La zona <strong>{{ $zona->nombre }}</strong> todavía no tiene los dos bloques
                                —visitantes y residentes— respondidos por completo.
                            </p>
                            @if($readonly)
                                <p class="text-yellow-600 text-sm mt-2">
                                    Como administrador puedes consultar la zona, pero <strong>completar y validar
                                    esta matriz es responsabilidad del Jefe de Zona y su equipo</strong>.
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4">
                        @if($readonly)
                            <a href="{{ route('admin.zonas.index') }}"
                               class="inline-block bg-gray-200 hover:bg-gray-400 text-black font-bold py-2 px-5 rounded shadow transition">
                                Volver a Zonas
                            </a>
                        @else
                            <a href="{{ route('operativo.evaluacion_irritacion.edit', $zona->id) }}"
                               class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-5 rounded shadow transition">
                                Ver Formulario de Evaluación
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @else

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                <x-flash-exito />

                <h3 class="text-center font-bold text-xl text-gray-700 mb-8 uppercase tracking-wide">
                    Índice de Irritación Turística
                </h3>

                @php
                    // Mismos tres tramos que usa el desplegable del formulario:
                    // la definición vive en Irritacion::clasificar(), aquí solo
                    // se traduce la etiqueta a un color.
                    $coloresClasificacion = [
                        'Bajo'     => 'bg-green-100 text-green-800',
                        'Moderado' => 'bg-yellow-100 text-yellow-800',
                        'Crítico'  => 'bg-red-100 text-red-800',
                    ];
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                    <div class="bg-sky-50 border border-sky-200 rounded-lg p-6 text-center">
                        <p class="text-xs text-sky-700 uppercase font-bold">Visitantes</p>
                        <p class="text-4xl font-black text-sky-700 mt-1">
                            {{ number_format($evaluacion->visitantes_promedio, 2) }}
                        </p>
                        <span class="inline-block mt-2 px-3 py-1 rounded text-sm font-semibold {{ $coloresClasificacion[$evaluacion->clasificacion_visitantes] }}">
                            {{ $evaluacion->clasificacion_visitantes }}
                        </span>
                    </div>
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-6 text-center">
                        <p class="text-xs text-amber-700 uppercase font-bold">Residentes</p>
                        <p class="text-4xl font-black text-amber-700 mt-1">
                            {{ number_format($evaluacion->residentes_promedio, 2) }}
                        </p>
                        <span class="inline-block mt-2 px-3 py-1 rounded text-sm font-semibold {{ $coloresClasificacion[$evaluacion->clasificacion_residentes] }}">
                            {{ $evaluacion->clasificacion_residentes }}
                        </span>
                    </div>
                </div>

                {{-- Detalle por bloque, cada uno con sus propios seis atributos:
                     cruzarlos en una sola tabla mezclaría dos poblaciones
                     distintas, igual que evita el controlador al no combinarlos
                     en un índice único. --}}
                @foreach([
                    ['Encuesta a visitantes', \App\Matrices\Irritacion::VISITANTES],
                    ['Encuesta a residentes', \App\Matrices\Irritacion::RESIDENTES],
                ] as [$titulo, $campos])
                    <h4 class="font-bold text-gray-700 mt-6 mb-2">{{ $titulo }}</h4>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border-collapse border border-gray-300">
                            <thead class="bg-gray-100 text-xs uppercase">
                                <tr>
                                    <th class="border border-gray-300 p-2 text-left">Atributo</th>
                                    <th class="border border-gray-300 p-2">Valor</th>
                                    <th class="border border-gray-300 p-2">Clasificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campos as $campo)
                                    @php $clasificacion = \App\Matrices\Irritacion::clasificar((float) $evaluacion->$campo); @endphp
                                    <tr>
                                        <td class="border border-gray-300 p-2">{{ \App\Matrices\Irritacion::ETIQUETAS[$campo] }}</td>
                                        <td class="border border-gray-300 p-2 text-center font-bold">{{ $evaluacion->$campo }}</td>
                                        <td class="border border-gray-300 p-2 text-center">
                                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $coloresClasificacion[$clasificacion] }}">
                                                {{ $clasificacion }}
                                            </span>
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
