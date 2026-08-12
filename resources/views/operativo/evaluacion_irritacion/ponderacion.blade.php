<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Índice de Irritación Turística: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
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

            <x-pestanas-matriz clave="irritacion" :zona="$zona" activa="resultados" />

            <x-tarjeta :padding="false" class="overflow-hidden p-8">

                <x-flash-exito />

                @if($evaluacion?->exists && $evaluacion->user)
                    <p class="text-sm text-gray-500 mb-4">
                        Última edición: {{ $evaluacion->user->name }},
                        {{ $evaluacion->updated_at->diffForHumans() }}
                    </p>
                @endif

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
                    <x-boton :href="route('operativo.evaluacion_irritacion.edit', $zona->id)">
                        ← Volver al Formulario
                    </x-boton>
                    <x-boton-volver :zona="$zona" />
                </div>

            </x-tarjeta>
    </div>
    @endif
</x-app-layout>
