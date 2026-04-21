<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados Matriz de Percepción — {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
        $etiquetaValor = fn($v) => match((int)$v) { 1 => 'Negativo', 2 => 'Neutral', 3 => 'Positivo', default => '—' };
        $colorValor    = fn($v) => match((int)$v) { 1 => 'bg-red-100 text-red-800', 2 => 'bg-yellow-100 text-yellow-800', 3 => 'bg-green-100 text-green-800', default => 'bg-gray-100 text-gray-600' };
        $totalPct      = $evaluacion->percepcion_total * 100;
        $interp        = $totalPct >= 70 ? ['Percepción Favorable', 'bg-green-600'] : ($totalPct >= 40 ? ['Percepción Moderada', 'bg-yellow-500'] : ['Percepción Desfavorable', 'bg-red-600']);
    @endphp

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                <h3 class="text-center font-bold text-lg text-gray-700 mb-6 uppercase">
                    Evaluación de la Percepción Local hacia el Turismo
                </h3>

                {{-- Tabla detalle por atributo --}}
                <div class="overflow-x-auto border border-gray-400 mb-8">
                    <table class="min-w-full text-sm border-collapse">
                        <thead class="bg-gray-200 text-gray-900 font-bold uppercase text-center">
                            <tr>
                                <th class="border border-gray-400 p-3">Categoría</th>
                                <th class="border border-gray-400 p-3">Código</th>
                                <th class="border border-gray-400 p-3 text-left">Atributo</th>
                                <th class="border border-gray-400 p-3">Valor</th>
                                <th class="border border-gray-400 p-3">Categoría</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            @foreach($categorias as $codigo => $cat)
                                @php $items = $cat['items']; $count = count($items); $i = 0; @endphp
                                @foreach($items as $campo => $etiqueta)
                                    <tr>
                                        @if($i === 0)
                                            <td rowspan="{{ $count }}" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle text-center">
                                                {{ $cat['nombre'] }}<br>
                                                <span class="text-xs text-gray-500">({{ $codigo }})</span>
                                            </td>
                                        @endif
                                        <td class="border border-gray-400 p-2 text-center font-mono">{{ $codigo }}{{ $i + 1 }}</td>
                                        <td class="border border-gray-400 p-2">{{ $etiqueta }}</td>
                                        <td class="border border-gray-400 p-2 text-center font-bold">{{ $evaluacion->$campo }}</td>
                                        <td class="border border-gray-400 p-2 text-center">
                                            <span class="inline-block px-2 py-1 rounded text-xs font-semibold {{ $colorValor($evaluacion->$campo) }}">
                                                {{ $etiquetaValor($evaluacion->$campo) }}
                                            </span>
                                        </td>
                                    </tr>
                                    @php $i++; @endphp
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Tabla ponderación --}}
                <h3 class="text-center font-bold text-lg text-gray-700 mt-8 mb-4 uppercase">
                    Escala Valorativa y Ponderación por Categoría
                </h3>

                <div class="overflow-x-auto border border-gray-400 mb-8">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-gray-200 text-gray-900 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-400 p-3">Categoría</th>
                                <th class="border border-gray-400 p-3">Peso</th>
                                <th class="border border-gray-400 p-3">Escala por Criterio (Promedio)</th>
                                <th class="border border-gray-400 p-3">Valor Ponderado</th>
                                <th class="border border-gray-400 p-3">% Cumplimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categorias as $codigo => $cat)
                                @php
                                    $mediaAttr = 'media_' . strtolower($codigo);
                                    $pondAttr  = 'pond_'  . strtolower($codigo);
                                    $media     = $evaluacion->$mediaAttr;
                                    $pond      = $evaluacion->$pondAttr;
                                    $cumpl     = ($media / 3) * 100;
                                @endphp
                                <tr>
                                    <td class="border border-gray-400 p-2 text-left font-semibold">{{ $cat['nombre'] }} ({{ $codigo }})</td>
                                    <td class="border border-gray-400 p-2">{{ number_format($cat['peso'] * 100, 0) }}%</td>
                                    <td class="border border-gray-400 p-2">{{ number_format($media, 2) }}</td>
                                    <td class="border border-gray-400 p-2 font-bold">{{ number_format($pond, 3) }}</td>
                                    <td class="border border-gray-400 p-2">{{ number_format($cumpl, 1) }}%</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-blue-600 text-white font-bold">
                            <tr>
                                <td colspan="3" class="border border-gray-400 p-3 text-right text-lg">PERCEPCIÓN TOTAL:</td>
                                <td class="border border-gray-400 p-3 text-xl">{{ number_format($evaluacion->percepcion_total, 3) }}</td>
                                <td class="border border-gray-400 p-3 text-xl">{{ number_format($totalPct, 1) }}%</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Banner interpretación --}}
                <div class="{{ $interp[1] }} text-white text-center rounded-lg p-6 mb-10">
                    <p class="uppercase text-sm font-semibold opacity-90">Interpretación Global</p>
                    <p class="text-3xl font-bold mt-1">{{ $interp[0] }}</p>
                    <p class="mt-1 opacity-90">Nivel de percepción de la localidad hacia el turismo: <b>{{ number_format($totalPct, 1) }}%</b></p>
                </div>

                {{-- Gráfica de Radar --}}
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-10">
                    <div>
                        <h4 class="text-center font-bold text-gray-700 mb-3">Gráfica de Red (Radar)</h4>
                        <div style="position:relative;width:100%;height:360px;">
                            <canvas id="radarPercepcion"></canvas>
                        </div>
                    </div>
                    <div>
                        <h4 class="text-center font-bold text-gray-700 mb-3">Gráfica Lineal — Valor por Ítem</h4>
                        <div style="position:relative;width:100%;height:360px;">
                            <canvas id="lineaPercepcion"></canvas>
                        </div>
                    </div>
                </div>

                @if($evaluacion->acciones_mejora)
                    <div class="border-t pt-6 mb-6">
                        <h4 class="font-bold text-gray-800 mb-2">Acciones de Mejora Propuestas</h4>
                        <p class="bg-gray-50 p-4 rounded border text-gray-700 whitespace-pre-line">{{ $evaluacion->acciones_mejora }}</p>
                    </div>
                @endif

                <div class="mt-8 text-center space-x-3">
                    <a href="{{ route('operativo.evaluacion_percepcion.edit', $zona->id) }}"
                       class="inline-block px-5 py-2 bg-blue-600 text-white font-bold text-lg rounded-lg hover:bg-blue-700 hover:scale-105 transition-transform duration-200 shadow-md">
                        Ver el Formulario
                    </a>
                    <a href="{{ route('operativo.dashboard') }}"
                       class="inline-block px-5 py-2 bg-gray-200 text-black font-bold text-lg rounded-lg hover:bg-gray-400 hover:scale-105 transition-transform duration-200 shadow-md">
                        Volver a Mis Zonas
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
        const categoriasLabels = @json(array_values(array_map(fn($c) => $c['nombre'], $categorias)));
        const mediasData = [
            {{ $evaluacion->media_ds }},
            {{ $evaluacion->media_pl }},
            {{ $evaluacion->media_pe }},
            {{ $evaluacion->media_no }}
        ];

        new Chart(document.getElementById('radarPercepcion').getContext('2d'), {
            type: 'radar',
            data: {
                labels: categoriasLabels,
                datasets: [{
                    label: 'Promedio (1–3)',
                    data: mediasData,
                    backgroundColor: 'rgba(99, 102, 241, 0.18)',
                    borderColor: 'rgba(99, 102, 241, 0.9)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(99, 102, 241, 1)',
                    pointRadius: 5
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    r: { min: 0, max: 3, ticks: { stepSize: 0.5 } }
                }
            }
        });

        const itemLabels = [
            @foreach($categorias as $codigo => $cat)
                @foreach(array_keys($cat['items']) as $idx => $campo)
                    '{{ $codigo }}{{ $idx + 1 }}',
                @endforeach
            @endforeach
        ];
        const itemValues = [
            @foreach($categorias as $codigo => $cat)
                @foreach(array_keys($cat['items']) as $campo)
                    {{ (int) $evaluacion->$campo }},
                @endforeach
            @endforeach
        ];

        new Chart(document.getElementById('lineaPercepcion').getContext('2d'), {
            type: 'line',
            data: {
                labels: itemLabels,
                datasets: [{
                    label: 'Valor por Ítem',
                    data: itemValues,
                    borderColor: 'rgba(234, 88, 12, 0.9)',
                    backgroundColor: 'rgba(234, 88, 12, 0.15)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 4
                }]
            },
            options: {
                maintainAspectRatio: false,
                scales: {
                    y: { min: 0, max: 3, ticks: { stepSize: 1 } }
                }
            }
        });
        });
    </script>
</x-app-layout>
