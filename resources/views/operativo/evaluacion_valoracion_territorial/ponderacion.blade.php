<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Valoración Territorial: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
        // No basta con que la evaluación exista: desde el guardado parcial
        // puede existir a medias, y entonces sus totales están en null. Pintar
        // ese null da un 0,00 que parece un territorio valorado con ceros.
        $sinDatos = ! $evaluacion || $evaluacion->ct_total === null;
    @endphp

    @if($sinDatos)
        <div class="py-12">
            <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow">
                    <div class="flex items-center gap-4">
                        <span class="text-4xl">🗺️</span>
                        <div>
                            <h3 class="font-bold text-yellow-800 text-lg">Evaluación aún no realizada</h3>
                            <p class="text-yellow-700 mt-1">
                                El equipo de la zona <strong>{{ $zona->nombre }}</strong> todavía no ha completado la
                                Matriz de Valoración Territorial.
                            </p>
                        </div>
                    </div>
                    <div class="mt-4 flex gap-3">
                        {{-- Ahora todos pueden ir al formulario, admin
                             incluido: el enlace ya no depende del rol. --}}
                        <a href="{{ route('operativo.evaluacion_valoracion_territorial.edit', $zona->id) }}"
                           class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-5 rounded shadow transition">
                            Ver Formulario de Evaluación
                        </a>
                        <x-boton-volver :zona="$zona" />
                    </div>
                </div>
            </div>
        </div>
    @else

    <div class="py-12">

            <x-pestanas-matriz clave="valoracion_territorial" :zona="$zona" activa="resultados" />

            <x-tarjeta :padding="false" class="overflow-hidden p-8">

                <h3 class="text-center font-bold text-xl text-gray-700 mb-8 uppercase tracking-wide">
                    Matriz de Valoración Territorial
                </h3>

                @if($evaluacion?->exists && $evaluacion->user)
                    <p class="text-sm text-gray-500 mb-4">
                        Última edición: {{ $evaluacion->user->name }},
                        {{ $evaluacion->updated_at->diffForHumans() }}
                    </p>
                @endif

                {{-- ══════════════════════════════════════════
                     RESULTADO FINAL - CUADRANTE
                ══════════════════════════════════════════ --}}
                @php
                    $estilos = [
                        'Territorio a Priorizar para el Turismo IV' => ['caja' => 'bg-green-50 border-green-500',  'texto' => 'text-green-700',  'emoji' => '🟢', 'lectura' => 'Apto en ambas dimensiones: contenido territorial y conectividad.'],
                        'Territorio con Limitación II'              => ['caja' => 'bg-yellow-50 border-yellow-500','texto' => 'text-yellow-700', 'emoji' => '🟡', 'lectura' => 'Bien conectado, pero sin base territorial suficiente.'],
                        'Territorio con Limitación III'             => ['caja' => 'bg-blue-50 border-blue-500',    'texto' => 'text-blue-700',   'emoji' => '🔵', 'lectura' => 'Con base territorial, pero aislado.'],
                        'Territorio No Apto para el Turismo'        => ['caja' => 'bg-red-50 border-red-500',      'texto' => 'text-red-700',    'emoji' => '🔴', 'lectura' => 'Ni contenido territorial ni conectividad.'],
                    ][$evaluacion->cuadrante];
                @endphp

                <div class="w-full mx-auto p-8 rounded-xl border-4 text-center mb-10 {{ $estilos['caja'] }}">
                    <p class="text-5xl mb-3">{{ $estilos['emoji'] }}</p>
                    <p class="text-2xl font-extrabold uppercase {{ $estilos['texto'] }}">{{ $evaluacion->cuadrante }}</p>
                    <p class="text-gray-600 mt-2 text-sm max-w-xl mx-auto">{{ $estilos['lectura'] }}</p>
                    <div class="grid grid-cols-2 gap-6 mt-6 max-w-sm mx-auto">
                        <x-tarjeta :padding="false" class="p-4">
                            <p class="text-xs text-gray-500 uppercase font-bold">Contenido Territorial (CT)</p>
                            <p class="text-4xl font-black text-indigo-600">{{ number_format($evaluacion->ct_total, 2) }}</p>
                            <p class="text-xs text-gray-400">Escala 0 – 2</p>
                        </x-tarjeta>
                        <x-tarjeta :padding="false" class="p-4">
                            <p class="text-xs text-gray-500 uppercase font-bold">Ubicación y Conectividad (UC)</p>
                            <p class="text-4xl font-black text-teal-600">{{ number_format($evaluacion->uc_total, 2) }}</p>
                            <p class="text-xs text-gray-400">Escala 0 – 2</p>
                        </x-tarjeta>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════
                     GRÁFICO DE CUADRANTES
                ══════════════════════════════════════════ --}}
                <h4 class="text-lg font-bold text-gray-700 mb-4 text-center">Posición en el Plano de Valoración Territorial</h4>

                <div class="max-w-2xl mx-auto mb-10">
                    <div class="grid grid-cols-2 gap-2 text-xs text-center mb-2">
                        <div class="bg-yellow-50 border border-yellow-300 rounded p-2">
                            <strong>Territorio con Limitación II</strong><br>
                            <span class="text-gray-500">UC &gt; 1 · CT &lt; 1</span>
                        </div>
                        <div class="bg-green-50 border border-green-300 rounded p-2">
                            <strong>Territorio a Priorizar para el Turismo IV</strong><br>
                            <span class="text-gray-500">CT &gt; 1 · UC &gt; 1</span>
                        </div>
                    </div>

                    <canvas id="cuadranteVT" height="420"></canvas>

                    <div class="grid grid-cols-2 gap-2 text-xs text-center mt-2">
                        <div class="bg-red-50 border border-red-300 rounded p-2">
                            <strong>Territorio No Apto para el Turismo</strong><br>
                            <span class="text-gray-500">UC &lt; 1 · CT &lt; 1</span>
                        </div>
                        <div class="bg-blue-50 border border-blue-300 rounded p-2">
                            <strong>Territorio con Limitación III</strong><br>
                            <span class="text-gray-500">UC &lt; 1 · CT &gt; 1</span>
                        </div>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════
                     TABLAS DE DESGLOSE POR DIMENSIÓN
                ══════════════════════════════════════════ --}}
                @foreach([['Contenido Territorial (CT)', $ct, $evaluacion->ct_total], ['Ubicación y Conectividad (UC)', $uc, $evaluacion->uc_total]] as [$titulo, $grupo, $total])
                    <h4 class="font-bold text-gray-700 mt-6 mb-2">{{ $titulo }}</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm border-collapse border border-gray-300 mb-4">
                            <thead class="bg-gray-100 text-xs uppercase">
                                <tr>
                                    <th class="border border-gray-300 p-2 text-left">Criterio</th>
                                    <th class="border border-gray-300 p-2">Calificación</th>
                                    <th class="border border-gray-300 p-2">Peso</th>
                                    <th class="border border-gray-300 p-2">Aporte</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($grupo as $campo => $criterio)
                                    <tr>
                                        <td class="border border-gray-300 p-2">{{ $criterio['nombre'] }}</td>
                                        <td class="border border-gray-300 p-2 text-center font-bold">{{ $evaluacion->$campo }}</td>
                                        <td class="border border-gray-300 p-2 text-center">{{ $criterio['peso'] * 100 }}%</td>
                                        <td class="border border-gray-300 p-2 text-center">{{ number_format($evaluacion->$campo * $criterio['peso'], 3) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot class="bg-gray-50 font-bold">
                                <tr>
                                    <td colspan="3" class="border border-gray-300 p-2 text-right">TOTAL</td>
                                    <td class="border border-gray-300 p-2 text-center">{{ number_format($total, 3) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                @endforeach

                {{-- Botones --}}
                <div class="mt-8 flex gap-4 justify-center flex-wrap">
                    <x-boton :href="route('operativo.evaluacion_valoracion_territorial.edit', $zona->id)">
                        ← Volver al Formulario
                    </x-boton>
                    <x-boton-volver :zona="$zona" />
                </div>

            </x-tarjeta>
    </div>
    @endif

    {{-- Gráfico de dispersión con Chart.js --}}
    @unless($sinDatos)
    @php
        $punto = [['x' => (float) $evaluacion->ct_total, 'y' => (float) $evaluacion->uc_total]];
    @endphp
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        new Chart(document.getElementById('cuadranteVT').getContext('2d'), {
            type: 'scatter',
            data: {
                datasets: [{
                    label: @json($zona->nombre),
                    data: @json($punto),
                    backgroundColor: 'rgba(79, 70, 229, 0.9)',
                    pointRadius: 12,
                    pointHoverRadius: 16,
                }],
            },
            options: {
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `CT: ${ctx.parsed.x.toFixed(3)} | UC: ${ctx.parsed.y.toFixed(3)}`
                        }
                    },
                },
                scales: {
                    x: {
                        min: 0,
                        max: 2,
                        title: { display: true, text: 'Contenido Territorial (CT)' },
                        ticks: { stepSize: 0.5 },
                        grid: { color: ctx => ctx.tick.value === 1 ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.1)' },
                    },
                    y: {
                        min: 0,
                        max: 2,
                        title: { display: true, text: 'Ubicación y Conectividad (UC)' },
                        ticks: { stepSize: 0.5 },
                        grid: { color: ctx => ctx.tick.value === 1 ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.1)' },
                    },
                },
            },
        });
    </script>
    @endunless
</x-app-layout>
