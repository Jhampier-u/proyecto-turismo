<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Paisaje: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
        // Se deriva del rol, no de una variable que pase el controlador: la versión
        // anterior dependía de que cada controlador acordara pasar readonly=true, y
        // al desaparecer los métodos del admin se quedó en falso para siempre sin
        // que nada lo detectara. El rol es la única fuente de verdad que no se
        // puede desincronizar.
        $readonly = ! auth()->user()->puedeEditarEvaluaciones();
    @endphp

    {{-- Dos casos, un mismo aviso: la matriz no existe (el admin puede abrir
         una zona sin empezar) o existe a medias, y entonces paisaje_total está
         en null. En ambos, seguir adelante rompería: $evaluacion->escenario
         necesita un total real. --}}
    @if(! $evaluacion || $evaluacion->paisaje_total === null)
        <x-matriz-sin-resultados
            nombre="Matriz de Paisaje"
            :zona="$zona"
            ruta-formulario="operativo.evaluacion_paisaje.edit" />
    @else

    @php
        $escenario = $evaluacion->escenario;

        // Clases completas: Tailwind purga las construidas por concatenación.
        $estilos = [
            'Eficiente'   => ['caja' => 'bg-green-50 border-green-500',   'texto' => 'text-green-700',  'emoji' => '🟢'],
            'Proactivo'   => ['caja' => 'bg-teal-50 border-teal-500',     'texto' => 'text-teal-700',   'emoji' => '🔵'],
            'Neutro'      => ['caja' => 'bg-yellow-50 border-yellow-500', 'texto' => 'text-yellow-700', 'emoji' => '🟡'],
            'Reactivo'    => ['caja' => 'bg-orange-50 border-orange-500', 'texto' => 'text-orange-700', 'emoji' => '🟠'],
            'Inexistente' => ['caja' => 'bg-red-50 border-red-500',       'texto' => 'text-red-700',    'emoji' => '🔴'],
        ][$escenario['nombre']];

        $etiquetas = collect($categorias)->map(fn($c) => $c['nombre'])->values()->all();
        $valores   = collect($categorias)->keys()
            ->map(fn($clave) => round($evaluacion->{"{$clave}_promedio"}, 3))->all();
    @endphp

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <div class="w-full p-8 rounded-xl border-4 text-center mb-8 {{ $estilos['caja'] }}">
                <p class="text-5xl mb-3">{{ $estilos['emoji'] }}</p>
                <p class="text-sm uppercase tracking-wide text-gray-500 font-semibold">Escenario</p>
                <p class="text-3xl font-extrabold uppercase {{ $estilos['texto'] }}">{{ $escenario['nombre'] }}</p>
                <p class="text-lg font-bold text-gray-700 mt-2">
                    {{ number_format($evaluacion->paisaje_total, 2) }} / 5.00
                    <span class="text-sm font-normal text-gray-500">({{ $escenario['rango'] }})</span>
                </p>
                <p class="text-gray-600 mt-4 max-w-3xl mx-auto text-sm leading-relaxed">{{ $escenario['texto'] }}</p>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Perfil por categoría</h3>
                <div class="max-w-lg mx-auto">
                    <canvas id="radarPaisaje" height="360"></canvas>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Desglose y ponderación</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm border-collapse border border-gray-300">
                        <thead class="bg-gray-100 text-xs uppercase text-gray-700">
                            <tr>
                                <th class="border border-gray-300 p-3 text-left">Categoría</th>
                                <th class="border border-gray-300 p-3">Promedio</th>
                                <th class="border border-gray-300 p-3">Lectura</th>
                                <th class="border border-gray-300 p-3">Peso</th>
                                <th class="border border-gray-300 p-3">Aporte</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            @foreach($categorias as $clave => $categoria)
                                @php $promedio = $evaluacion->{"{$clave}_promedio"}; @endphp
                                <tr>
                                    <td class="border border-gray-300 p-2">
                                        {{ $categoria['nombre'] }}
                                        <span class="text-gray-400">({{ strtoupper($clave) }})</span>
                                    </td>
                                    <td class="border border-gray-300 p-2 text-center font-bold">{{ number_format($promedio, 2) }}</td>
                                    <td class="border border-gray-300 p-2 text-center">{{ $evaluacion->lecturaDe($clave) }}</td>
                                    <td class="border border-gray-300 p-2 text-center">{{ rtrim(rtrim(number_format($categoria['peso'] * 100, 1), '0'), '.') }}%</td>
                                    <td class="border border-gray-300 p-2 text-center">{{ number_format($promedio * $categoria['peso'], 3) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50 font-bold">
                            <tr>
                                <td colspan="4" class="border border-gray-300 p-3 text-right">RESULTADO</td>
                                <td class="border border-gray-300 p-3 text-center">{{ number_format($evaluacion->paisaje_total, 3) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Detalle por criterio</h3>
                @foreach($categorias as $clave => $categoria)
                    <h4 class="font-bold text-gray-700 mt-5 mb-2">{{ $categoria['nombre'] }}</h4>
                    <table class="min-w-full text-sm border-collapse border border-gray-300 mb-3">
                        <tbody>
                            @foreach($categoria['criterios'] as $campo => $criterio)
                                <tr>
                                    <td class="border border-gray-300 p-2">{{ $criterio['nombre'] }}</td>
                                    <td class="border border-gray-300 p-2 text-center font-bold w-16">{{ $evaluacion->$campo }}</td>
                                    <td class="border border-gray-300 p-2 w-48">{{ $criterio['niveles'][$evaluacion->$campo] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach
            </div>

            <div class="flex justify-end gap-3">
                @if($readonly)
                    <a href="{{ route('admin.zonas.index') }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-5 rounded shadow">
                        Volver a Zonas
                    </a>
                @else
                    <a href="{{ route('operativo.evaluacion_paisaje.edit', $zona->id) }}"
                       class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-5 rounded shadow">
                        ← Volver al Formulario
                    </a>
                    <a href="{{ route('operativo.dashboard') }}"
                       class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-5 rounded shadow">
                        Mis Zonas
                    </a>
                @endif
            </div>

        </div>
    </div>

    {{-- El gráfico va dentro del @else: sin evaluación no hay datos que pintar
         y sus variables no existen. --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('radarPaisaje').getContext('2d'), {
            type: 'radar',
            data: {
                labels: @json($etiquetas),
                datasets: [{
                    label: @json($zona->nombre),
                    data: @json($valores),
                    backgroundColor: 'rgba(79, 70, 229, 0.15)',
                    borderColor: 'rgba(79, 70, 229, 0.9)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                    pointRadius: 5,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: { r: { min: 0, max: 5, ticks: { stepSize: 1 } } },
                plugins: { legend: { display: false } },
            },
        });
    </script>

    @endif
</x-app-layout>
