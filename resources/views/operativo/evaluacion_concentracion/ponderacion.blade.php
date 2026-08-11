<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Índice de Concentración Turística: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
        // Esta matriz no guarda ningún total -EvaluacionConcentracionController::calcular()
        // devuelve [] porque porcentajes, Sia, Pi e ICT se derivan siempre de
        // los 113 conteos, nunca se persisten-, así que no hay una sola
        // columna que preguntar como en el resto de matrices (paisaje_total,
        // fn_total...). La completitud se comprueba en los propios conteos:
        // si cualquiera de los 113 sigue en null, el bloque al que pertenece
        // está a medias y ningún porcentaje, Sia, Pi ni ICT calculado a
        // partir de él tendría una lectura razonable.
        $camposInstrumento = \App\Matrices\Concentracion::campos();
        $sinDatos = ! $evaluacion || collect($camposInstrumento)->contains(
            fn (string $campo) => $evaluacion->$campo === null
        );
    @endphp

    @if($sinDatos)
        <x-matriz-sin-resultados
            nombre="Índice de Concentración"
            :zona="$zona"
            ruta-formulario="operativo.evaluacion_concentracion.edit">
            Hacen falta los 113 conteos del instrumento —atractivos y planta turística— respondidos por completo.
        </x-matriz-sin-resultados>
    @else

    @php
        // Dos tablas paralelas del instrumento, cada una con su propio total:
        // porcentajesAtractivos() se llama una vez por categoría
        // (Manifestaciones Culturales, Atractivos Naturales), nunca sobre las
        // dos mezcladas, o el porcentaje de cada subtipo saldría sobre un
        // total que el instrumento no usa.
        $porcentajesAtractivos = [];
        foreach ($atractivos as $categoria => $tipos) {
            $conteos = [];
            foreach ($tipos as $camposDelTipo) {
                foreach ($camposDelTipo as $campo => $etiqueta) {
                    $conteos[$campo] = $evaluacion->$campo;
                }
            }
            $porcentajesAtractivos[$categoria] = \App\Matrices\ConcentracionCalculo::porcentajesAtractivos($conteos);
        }

        // Sia, Pi e ICT de los diez sectores a la vez: el ICT de cada uno
        // necesita el total general de TODOS los sectores, así que no se
        // puede resolver sector a sector por separado (ver
        // ConcentracionCalculo::planta()).
        $conteosPorSector = [];
        foreach ($planta as $sector => $camposDelSector) {
            foreach ($camposDelSector as $campo => $etiqueta) {
                $conteosPorSector[$sector][$campo] = $evaluacion->$campo;
            }
        }
        $resultadosPlanta = \App\Matrices\ConcentracionCalculo::planta($conteosPorSector);
    @endphp

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                <x-flash-exito />

                @if($evaluacion?->exists && $evaluacion->user)
                    <p class="text-sm text-gray-500 mb-4">
                        Última edición: {{ $evaluacion->user->name }},
                        {{ $evaluacion->updated_at->diffForHumans() }}
                    </p>
                @endif

                <h3 class="text-center font-bold text-xl text-gray-700 mb-8">
                    Índice de Concentración Turística
                </h3>

                {{-- ═══════════════════ ATRACTIVOS TURÍSTICOS ═══════════════════ --}}
                <h4 class="font-bold text-lg text-gray-800 mb-1">Atractivos turísticos</h4>
                <p class="text-sm text-gray-500 mb-5">Cantidad y porcentaje de cada subtipo sobre el total de su propia tabla: manifestaciones culturales y atractivos naturales no se mezclan.</p>

                @foreach($atractivos as $categoria => $tipos)
                    <h5 class="font-bold text-gray-700 mt-6 mb-2 border-b border-gray-200 pb-1">{{ $categoria }}</h5>
                    <div class="overflow-x-auto mb-6">
                        <table class="min-w-full text-sm border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 p-2 text-left">Subtipo</th>
                                    <th class="border border-gray-300 p-2">Cantidad</th>
                                    <th class="border border-gray-300 p-2">% sobre la tabla</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($tipos as $tipo => $camposDelTipo)
                                    <tr class="bg-gray-50">
                                        <td colspan="3" class="border border-gray-300 p-2 font-semibold text-gray-600">{{ $tipo }}</td>
                                    </tr>
                                    @foreach($camposDelTipo as $campo => $etiqueta)
                                        <tr>
                                            <td class="border border-gray-300 p-2 pl-6">{{ $etiqueta }}</td>
                                            <td class="border border-gray-300 p-2 text-center font-bold">{{ $evaluacion->$campo }}</td>
                                            <td class="border border-gray-300 p-2 text-center">{{ number_format($porcentajesAtractivos[$categoria][$campo], 2) }}%</td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach

                {{-- ═══════════════════ PLANTA TURÍSTICA ═══════════════════ --}}
                <h4 class="font-bold text-lg text-gray-800 mt-10 mb-1">Planta turística</h4>
                <p class="text-sm text-gray-500 mb-2">Sia, Pi e ICT de cada uno de los diez sectores.</p>

                {{-- Frase completa en una sola línea a propósito: Pi es la parte
                     contraintuitiva de este instrumento y hay un test que busca
                     esta nota; un salto de línea la partiría en el HTML y el
                     assertSee() ya no la encontraría entera. --}}
                <p class="text-sm text-gray-600 mb-5">Pi baja cuando el sector tiene más establecimientos: no es un error de esta pantalla, es la fórmula del instrumento, 100 entre la raíz cuadrada de Sia.</p>

                <div class="overflow-x-auto mb-6">
                    <table class="min-w-full text-sm border-collapse border border-gray-300">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border border-gray-300 p-2 text-left">Sector</th>
                                <th class="border border-gray-300 p-2">Sia</th>
                                <th class="border border-gray-300 p-2">Pi</th>
                                <th class="border border-gray-300 p-2">ICT</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($resultadosPlanta as $sector => $resultado)
                                <tr>
                                    <td class="border border-gray-300 p-2">{{ $sector }}</td>
                                    <td class="border border-gray-300 p-2 text-center font-bold">{{ $resultado['sia'] }}</td>
                                    <td class="border border-gray-300 p-2 text-center">
                                        @if($resultado['pi'] === null)
                                            {{-- Ni un cero ni un guion suelto: un sector sin
                                                 establecimientos no tiene "un Pi bajo", tiene un Pi
                                                 que no existe (100/√0 es una división por cero). Un
                                                 guion sin explicar se leería como cero o como error,
                                                 así que la frase lo dice con palabras. --}}
                                            <span class="text-gray-500 italic">No aplica (sin establecimientos)</span>
                                        @else
                                            {{ number_format($resultado['pi'], 2) }}
                                        @endif
                                    </td>
                                    <td class="border border-gray-300 p-2 text-center">
                                        {{-- ICT es Sia sobre el total general (una fracción de
                                             0 a 1): se muestra como porcentaje solo para que se
                                             lea de un vistazo, sin cambiar el cálculo. Un sector
                                             sin establecimientos da 0.00%, que es correcto: no
                                             aporta nada al reparto. --}}
                                        {{ number_format($resultado['ict'] * 100, 2) }}%
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-8 flex gap-4 justify-center flex-wrap">
                    <a href="{{ route('operativo.evaluacion_concentracion.edit', $zona->id) }}"
                        class="inline-block px-5 py-2 bg-indigo-600 text-white font-bold text-lg rounded-lg hover:bg-indigo-700 hover:scale-105 transition-transform duration-200 shadow-md">
                        ← Volver al Formulario
                    </a>
                    <x-boton-volver texto="Mis Zonas" />
                </div>

            </div>
        </div>
    </div>
    @endif
</x-app-layout>
