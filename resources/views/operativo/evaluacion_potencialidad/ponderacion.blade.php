<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Potencialidad Turística: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @php
        // Desde el guardado parcial, un criterio sin responder no puntúa, así
        // que los promedios y los totales pueden llegar en null. Pintar ese
        // null con number_format() da un «0,00» indistinguible de un territorio
        // valorado con ceros de verdad, que es justo lo que hay que evitar.
        $cifra = fn($valor, int $decimales) => $valor === null
            ? '—'
            : number_format($valor, $decimales);

        // El aporte ponderado de un factor: sin valor no hay aporte, y
        // null * 0.40 daría 0 en silencio.
        $ponderado = fn($valor, float $peso, int $decimales = 4) => $valor === null
            ? '—'
            : number_format($valor * $peso, $decimales);

        // Sin ninguno de los dos totales no hay nada que enseñar. Con uno solo
        // sí: puede que la zona haya activado únicamente bloques de FN o de FX,
        // y ese lado está bien calculado.
        $sinResultados = $eval->fn_total === null && $eval->fx_total === null;
    @endphp

    @if($sinResultados)
        <x-matriz-sin-resultados
            nombre="Potencialidad turística"
            :zona="$zona"
            ruta-formulario="operativo.evaluacion_potencialidad.edit" />
    @else

    <div class="py-12">

            <x-migas :zona="$zona" clave="potencialidad" actual="Resultados" />
            <x-pestanas-matriz clave="potencialidad" :zona="$zona" activa="resultados" />

            <x-tarjeta :padding="false" class="overflow-hidden p-8">

                <h3 class="text-center font-bold text-xl text-gray-700 mb-8 uppercase tracking-wide">
                    Análisis de Potencialidad Turística del Territorio
                </h3>

                @if($eval?->exists && $eval->user)
                    <p class="text-sm text-gray-500 mb-4 text-center">
                        Última edición: {{ $eval->user->name }},
                        {{ $eval->updated_at->diffForHumans() }}
                    </p>
                @endif

                {{-- ══════════════════════════════════════════
                     RESULTADO FINAL - CUADRANTE
                ══════════════════════════════════════════ --}}
                @php
                    $fn = $eval->fn_total;
                    $fx = $eval->fx_total;
                    // Cuadrante según FN y FX (escala 0-2)
                    $umbral = 1.0;

                    // El cuadrante cruza los dos ejes: sin uno de ellos no hay
                    // punto que situar. Sin esta rama, un null se comparaba como
                    // 0 y la zona salía siempre en «Bajo Potencial», que es un
                    // veredicto, no un hueco.
                    if ($fn === null || $fx === null) {
                        $cuadrante = 'Cuadrante sin determinar';
                        $descripcion = 'Falta uno de los dos ejes: el cuadrante necesita '
                            . 'los factores endógenos y los exógenos para situar al territorio.';
                        $colorBorder = 'border-gray-400';
                        $colorBg = 'bg-gray-50';
                        $colorText = 'text-gray-700';
                        $emoji = '⚪';
                    } elseif ($fn >= $umbral && $fx >= $umbral) {
                        $cuadrante = 'Alto Potencial Turístico';
                        $descripcion = 'El territorio presenta condiciones endógenas y exógenas favorables para el desarrollo turístico competitivo.';
                        $colorBorder = 'border-green-500';
                        $colorBg = 'bg-green-50';
                        $colorText = 'text-green-700';
                        $emoji = '🟢';
                    } elseif ($fn >= $umbral && $fx < $umbral) {
                        $cuadrante = 'Potencial Endógeno — Demanda Limitada';
                        $descripcion = 'El territorio tiene buena oferta pero necesita desarrollar su demanda, marketing y superestructura.';
                        $colorBorder = 'border-yellow-500';
                        $colorBg = 'bg-yellow-50';
                        $colorText = 'text-yellow-700';
                        $emoji = '🟡';
                    } elseif ($fn < $umbral && $fx >= $umbral) {
                        $cuadrante = 'Potencial Exógeno — Oferta Limitada';
                        $descripcion = 'Existe interés y demanda, pero la oferta turística del territorio necesita ser desarrollada y fortalecida.';
                        $colorBorder = 'border-blue-500';
                        $colorBg = 'bg-blue-50';
                        $colorText = 'text-blue-700';
                        $emoji = '🔵';
                    } else {
                        $cuadrante = 'Bajo Potencial Turístico';
                        $descripcion = 'El territorio requiere inversión y desarrollo tanto en su oferta como en su demanda turística.';
                        $colorBorder = 'border-red-500';
                        $colorBg = 'bg-red-50';
                        $colorText = 'text-red-700';
                        $emoji = '🔴';
                    }
                @endphp

                <div class="w-full mx-auto p-8 rounded-xl border-4 text-center mb-10 {{ $colorBorder }} {{ $colorBg }}">
                    <p class="text-5xl mb-3">{{ $emoji }}</p>
                    <p class="text-2xl font-extrabold uppercase {{ $colorText }}">{{ $cuadrante }}</p>
                    <p class="text-gray-600 mt-2 text-sm max-w-xl mx-auto">{{ $descripcion }}</p>
                    <div class="grid grid-cols-2 gap-6 mt-6 max-w-sm mx-auto">
                        <x-tarjeta :padding="false" class="p-4">
                            <p class="text-xs text-gray-500 uppercase font-bold">Factores Endógenos (FN)</p>
                            <p class="text-4xl font-black text-indigo-600">{{ $cifra($fn, 2) }}</p>
                            <p class="text-xs text-gray-400">Oferta + Infraestructura</p>
                        </x-tarjeta>
                        <x-tarjeta :padding="false" class="p-4">
                            <p class="text-xs text-gray-500 uppercase font-bold">Factores Exógenos (FX)</p>
                            <p class="text-4xl font-black text-teal-600">{{ $cifra($fx, 2) }}</p>
                            <p class="text-xs text-gray-400">Demanda + Superestructura</p>
                        </x-tarjeta>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════
                     GRÁFICO DE CUADRANTES
                ══════════════════════════════════════════ --}}
                <h4 class="text-lg font-bold text-gray-700 mb-4 text-center">Posición en el Plano de Potencialidad</h4>
                <div class="flex justify-center mb-10">
                    <canvas id="cuadranteChart" width="500" height="500"></canvas>
                </div>

                {{-- ══════════════════════════════════════════
                     TABLA FACTORES ENDÓGENOS
                ══════════════════════════════════════════ --}}
                <h4 class="text-lg font-bold text-gray-700 mb-4 mt-8 uppercase tracking-wide border-b pb-2">
                    📊 Análisis de Factores Endógenos (FN)
                </h4>

                <div class="overflow-x-auto border border-gray-300 mb-8">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-indigo-100 text-indigo-900 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-300 p-3 text-left">Factor</th>
                                <th class="border border-gray-300 p-3">Subfactor</th>
                                <th class="border border-gray-300 p-3 bg-yellow-100">Valor</th>
                                <th class="border border-gray-300 p-3">Pond. %</th>
                                <th class="border border-gray-300 p-3 bg-indigo-50">Calificación</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            {{-- Recursos Turísticos --}}
                            <tr class="bg-gray-50">
                                <td rowspan="7" class="border border-gray-300 p-2 font-bold align-middle text-left">
                                    Recursos Turísticos (RT) — 40%
                                </td>
                                <td class="border border-gray-300 p-2 text-left">RN — Zonas de Litoral</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_rn_litoral, 3) }}</td>
                                <td rowspan="4" class="border border-gray-300 p-2">RN 50%</td>
                                <td rowspan="4" class="border border-gray-300 p-2 font-bold bg-indigo-50">{{ $cifra($eval->val_recursos_naturales, 3) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2 text-left">RN — Zonas de Montaña</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_rn_montana, 3) }}</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 p-2 text-left">RN — Áreas Naturales Protegidas</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_rn_anp, 3) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2 text-left">RN — Cuerpos de Agua</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_rn_agua, 3) }}</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 p-2 text-left">RC — Artístico-Monumental</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_rc_am, 3) }}</td>
                                <td rowspan="3" class="border border-gray-300 p-2">RC 50%</td>
                                <td rowspan="3" class="border border-gray-300 p-2 font-bold bg-indigo-50">{{ $cifra($eval->val_recursos_culturales, 3) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2 text-left">RC — Nacionalidades y Pueblos</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_rc_np, 3) }}</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 p-2 text-left">RC — Expresiones Contemporáneas</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_rc_ec, 3) }}</td>
                            </tr>
                            {{-- Planta Turística --}}
                            <tr>
                                <td rowspan="5" class="border border-gray-300 p-2 font-bold align-middle text-left bg-gray-50">
                                    Planta Turística (PT) — 20%
                                </td>
                                <td class="border border-gray-300 p-2 text-left">Alojamiento</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_pt_alojamiento, 3) }}</td>
                                <td rowspan="5" class="border border-gray-300 p-2">20%</td>
                                <td rowspan="5" class="border border-gray-300 p-2 font-bold bg-indigo-50">{{ $cifra($eval->val_planta_turistica, 3) }}</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 p-2 text-left">Restauración</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_pt_restauracion, 3) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2 text-left">Intermediación</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_pt_intermediacion, 3) }}</td>
                            </tr>
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 p-2 text-left">Transportación</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_pt_transportacion, 3) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-300 p-2 text-left">Interpretación / Guianza</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_pt_interpretacion, 3) }}</td>
                            </tr>
                            {{-- Tipologías --}}
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 p-2 font-bold text-left align-middle">
                                    Tipologías de Turismo (TT) — 20%
                                </td>
                                <td class="border border-gray-300 p-2 text-left">10 tipologías turísticas</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_tipologias, 3) }}</td>
                                <td class="border border-gray-300 p-2">20%</td>
                                <td class="border border-gray-300 p-2 font-bold bg-indigo-50">{{ $cifra($eval->val_tipologias, 3) }}</td>
                            </tr>
                            {{-- Infraestructura --}}
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold text-left align-middle">
                                    Infraestructura (I) — 20%
                                </td>
                                <td class="border border-gray-300 p-2 text-left">6 elementos de infraestructura</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_infraestructura, 3) }}</td>
                                <td class="border border-gray-300 p-2">20%</td>
                                <td class="border border-gray-300 p-2 font-bold bg-indigo-50">{{ $cifra($eval->val_infraestructura, 3) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-indigo-600 text-white font-bold">
                            <tr>
                                <td colspan="4" class="border border-gray-300 p-3 text-right text-lg">
                                    CALIFICACIÓN TOTAL FACTORES ENDÓGENOS (FN):
                                </td>
                                <td class="border border-gray-300 p-3 text-2xl">
                                    {{ $cifra($eval->fn_total, 4) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ══════════════════════════════════════════
                     RADAR CHARTS FACTORES ENDÓGENOS
                ══════════════════════════════════════════ --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-10">
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 flex justify-center">
                        <div style="width:100%;max-width:380px;">
                            <canvas id="radarRT"></canvas>
                        </div>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50 flex justify-center">
                        <div style="width:100%;max-width:380px;">
                            <canvas id="radarPT"></canvas>
                        </div>
                    </div>
                </div>
                <div class="flex justify-center mb-10">
                    <div class="border border-gray-200 rounded-lg p-4 bg-gray-50" style="width:100%;max-width:440px;">
                        <canvas id="radarFN"></canvas>
                    </div>
                </div>

                {{-- ══════════════════════════════════════════
                     TABLA FACTORES EXÓGENOS
                ══════════════════════════════════════════ --}}
                <h4 class="text-lg font-bold text-gray-700 mb-4 mt-8 uppercase tracking-wide border-b pb-2">
                    📊 Análisis de Factores Exógenos (FX)
                </h4>

                <div class="overflow-x-auto border border-gray-300 mb-8">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-teal-100 text-teal-900 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-300 p-3 text-left">Factor</th>
                                <th class="border border-gray-300 p-3">Subfactor</th>
                                <th class="border border-gray-300 p-3 bg-yellow-100">Valor</th>
                                <th class="border border-gray-300 p-3">Pond. %</th>
                                <th class="border border-gray-300 p-3 bg-teal-50">Calificación</th>
                            </tr>
                        </thead>
                        <tbody class="text-gray-800">
                            {{-- Afluencia --}}
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold text-left align-middle">
                                    Afluencia Turística (AT) — 40%
                                </td>
                                <td class="border border-gray-300 p-2 text-left">Flujos Local, Regional, Nacional, Internacional</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_afluencia, 3) }}</td>
                                <td class="border border-gray-300 p-2">40%</td>
                                <td class="border border-gray-300 p-2 font-bold bg-teal-50">{{ $ponderado($eval->val_afluencia, 0.40) }}</td>
                            </tr>
                            {{-- Marketing --}}
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 p-2 font-bold text-left align-middle">
                                    Marketing Turístico (MK) — 30%
                                </td>
                                <td class="border border-gray-300 p-2 text-left">Promotor, Plan, Tendencias, Investigación, Digital</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_marketing, 3) }}</td>
                                <td class="border border-gray-300 p-2">30%</td>
                                <td class="border border-gray-300 p-2 font-bold bg-teal-50">{{ $ponderado($eval->val_marketing, 0.30) }}</td>
                            </tr>
                            {{-- Superestructura --}}
                            <tr>
                                <td class="border border-gray-300 p-2 font-bold text-left align-middle">
                                    Superestructura del Turismo (ST) — 30%
                                </td>
                                <td class="border border-gray-300 p-2 text-left">Política, Gestión, Actores, Normativa, Planificación</td>
                                <td class="border border-gray-300 p-2 bg-yellow-50 font-bold">{{ $cifra($eval->val_superestructura, 3) }}</td>
                                <td class="border border-gray-300 p-2">30%</td>
                                <td class="border border-gray-300 p-2 font-bold bg-teal-50">{{ $ponderado($eval->val_superestructura, 0.30) }}</td>
                            </tr>
                        </tbody>
                        <tfoot class="bg-teal-600 text-white font-bold">
                            <tr>
                                <td colspan="4" class="border border-gray-300 p-3 text-right text-lg">
                                    CALIFICACIÓN TOTAL FACTORES EXÓGENOS (FX):
                                </td>
                                <td class="border border-gray-300 p-3 text-2xl">
                                    {{ $cifra($eval->fx_total, 4) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- ══════════════════════════════════════════
                     RADAR CHART FACTORES EXÓGENOS
                ══════════════════════════════════════════ --}}
                <div class="flex justify-center mb-10">
                    <div style="width:420px;height:420px;">
                        <canvas id="radarFX"></canvas>
                    </div>
                </div>

                {{-- Tabla de rangos --}}
                <div class="overflow-x-auto border border-gray-300 mb-8">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-gray-200 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-300 p-3">Rango FN / FX</th>
                                <th class="border border-gray-300 p-3">Categoría</th>
                                <th class="border border-gray-300 p-3">Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="bg-red-50">
                                <td class="border border-gray-300 p-2 font-bold">0.00 – 0.99</td>
                                <td class="border border-gray-300 p-2">🔴 Bajo</td>
                                <td class="border border-gray-300 p-2 text-left">Ausencia o graves deficiencias. Requiere desarrollo desde cero.</td>
                            </tr>
                            <tr class="bg-yellow-50">
                                <td class="border border-gray-300 p-2 font-bold">1.00 – 1.49</td>
                                <td class="border border-gray-300 p-2">🟡 Medio-Bajo</td>
                                <td class="border border-gray-300 p-2 text-left">Elementos frágiles. Necesita fortalecimiento y gestión.</td>
                            </tr>
                            <tr class="bg-lime-50">
                                <td class="border border-gray-300 p-2 font-bold">1.50 – 1.99</td>
                                <td class="border border-gray-300 p-2">🟢 Medio-Alto</td>
                                <td class="border border-gray-300 p-2 text-left">Condiciones favorables pero con brechas por cubrir.</td>
                            </tr>
                            <tr class="bg-green-50">
                                <td class="border border-gray-300 p-2 font-bold">2.00</td>
                                <td class="border border-gray-300 p-2">⭐ Alto</td>
                                <td class="border border-gray-300 p-2 text-left">Máximo potencial. Condiciones óptimas para el turismo.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- Botones --}}
                <div class="mt-8 flex gap-4 justify-center flex-wrap">
                    <x-boton :href="route('operativo.evaluacion_potencialidad.edit', $zona->id)">
                        ← Volver al Formulario
                    </x-boton>
                </div>

            </x-tarjeta>
    </div>

    {{-- Gráfico de cuadrantes con Chart.js --}}
    @php
        $datosRecursos = [
            $eval->val_rn_litoral, $eval->val_rn_montana,
            $eval->val_rn_anp, $eval->val_rn_agua,
            $eval->val_rc_am, $eval->val_rc_np, $eval->val_rc_ec,
        ];
        $datosPlanta = [
            $eval->val_pt_alojamiento, $eval->val_pt_restauracion,
            $eval->val_pt_intermediacion, $eval->val_pt_transportacion,
            $eval->val_pt_interpretacion,
        ];
        $datosEndogenos = [
            $eval->val_recursos_turisticos, $eval->val_planta_turistica,
            $eval->val_tipologias, $eval->val_infraestructura,
        ];
        $datosExogenos = [
            $eval->val_afluencia, $eval->val_marketing, $eval->val_superestructura,
        ];
    @endphp
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        const fn = @json($eval->fn_total);
        const fx = @json($eval->fx_total);

        const ctx = document.getElementById('cuadranteChart').getContext('2d');
        new Chart(ctx, {
            type: 'scatter',
            data: {
                datasets: [
                    {
                        label: @json($zona->nombre),
                        data: [{ x: fn, y: fx }],
                        backgroundColor: 'rgba(79, 70, 229, 0.9)',
                        pointRadius: 12,
                        pointHoverRadius: 16,
                    }
                ]
            },
            options: {
                responsive: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: ctx => `FN: ${ctx.parsed.x.toFixed(3)} | FX: ${ctx.parsed.y.toFixed(3)}`
                        }
                    },
                    annotation: {
                        annotations: {}
                    }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 2,
                        title: { display: true, text: 'Factores Endógenos (FN) — Oferta + Infraestructura', font: { size: 13, weight: 'bold' } },
                        ticks: { stepSize: 0.5 },
                        grid: { color: ctx => ctx.tick.value === 1 ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.1)' }
                    },
                    y: {
                        min: 0,
                        max: 2,
                        title: { display: true, text: 'Factores Exógenos (FX) — Demanda + Superestructura', font: { size: 13, weight: 'bold' } },
                        ticks: { stepSize: 0.5 },
                        grid: { color: ctx => ctx.tick.value === 1 ? 'rgba(0,0,0,0.4)' : 'rgba(0,0,0,0.1)' }
                    }
                }
            },
            plugins: [{
                id: 'cuadrantLabels',
                afterDraw(chart) {
                    const { ctx, chartArea: { left, right, top, bottom }, scales } = chart;
                    const midX = scales.x.getPixelForValue(1);
                    const midY = scales.y.getPixelForValue(1);
                    ctx.save();
                    ctx.font = '12px Arial';
                    ctx.fillStyle = 'rgba(100,100,100,0.7)';
                    ctx.textAlign = 'center';
                    // Q1: Alto FN + Alto FX
                    ctx.fillStyle = 'rgba(22,163,74,0.25)';
                    ctx.fillRect(midX, top, right - midX, midY - top);
                    // Q2: Bajo FN + Alto FX
                    ctx.fillStyle = 'rgba(59,130,246,0.15)';
                    ctx.fillRect(left, top, midX - left, midY - top);
                    // Q3: Alto FN + Bajo FX
                    ctx.fillStyle = 'rgba(234,179,8,0.15)';
                    ctx.fillRect(midX, midY, right - midX, bottom - midY);
                    // Q4: Bajo FN + Bajo FX
                    ctx.fillStyle = 'rgba(239,68,68,0.15)';
                    ctx.fillRect(left, midY, midX - left, bottom - midY);

                    ctx.fillStyle = 'rgba(70,70,70,0.8)';
                    ctx.font = 'bold 11px Arial';
                    ctx.textAlign = 'center';
                    ctx.fillText('🟢 Alto Potencial', (midX + right) / 2, top + 20);
                    ctx.fillText('🔵 Potencial Exógeno', (left + midX) / 2, top + 20);
                    ctx.fillText('🟡 Potencial Endógeno', (midX + right) / 2, (midY + bottom) / 2);
                    ctx.fillText('🔴 Bajo Potencial', (left + midX) / 2, (midY + bottom) / 2);
                    ctx.restore();
                }
            }]
        });
    </script>

    {{-- Radar Charts --}}
    <script>
        // ── Opciones base compartidas por los radars de FN ─────────────────
        const radarBaseFN = (titulo, color) => ({
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                title: {
                    display: true,
                    text: titulo,
                    font: { size: 13, weight: 'bold' },
                    color: '#374151'
                },
                tooltip: {
                    callbacks: { label: c => `${c.label}: ${c.parsed.r.toFixed(3)}` }
                }
            },
            scales: {
                r: {
                    min: 0, max: 2,
                    ticks: { stepSize: 0.5, font: { size: 10 }, backdropColor: 'transparent' },
                    pointLabels: { font: { size: 10, weight: '600' }, color: '#4b5563' },
                    grid: { color: 'rgba(0,0,0,0.08)' },
                    angleLines: { color: 'rgba(0,0,0,0.08)' }
                }
            }
        });

        // ── Radar Recursos Turísticos (RT) — subfactores ───────────────────
        new Chart(document.getElementById('radarRT').getContext('2d'), {
            type: 'radar',
            data: {
                labels: [
                    'RN Litoral', 'RN Montaña', 'RN Áreas Protegidas', 'RN Cuerpos de Agua',
                    'RC Artístico-Monum.', 'RC Nacionalidades', 'RC Expr. Contemp.'
                ],
                datasets: [{
                    label: 'Valor (0-2)',
                    data: @json($datosRecursos),
                    backgroundColor: 'rgba(22, 163, 74, 0.15)',
                    borderColor: 'rgba(22, 163, 74, 0.85)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(22, 163, 74, 1)',
                    pointRadius: 4, pointHoverRadius: 6,
                }]
            },
            options: radarBaseFN('Recursos Turísticos (RT) — Subfactores')
        });

        // ── Radar Planta Turística (PT) — subfactores ──────────────────────
        new Chart(document.getElementById('radarPT').getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['Alojamiento', 'Restauración', 'Intermediación', 'Transportación', 'Guianza'],
                datasets: [{
                    label: 'Valor (0-2)',
                    data: @json($datosPlanta),
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    borderColor: 'rgba(37, 99, 235, 0.85)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(37, 99, 235, 1)',
                    pointRadius: 4, pointHoverRadius: 6,
                }]
            },
            options: radarBaseFN('Planta Turística (PT) — Subfactores')
        });

        // ── Radar General FN — Factores principales ────────────────────────
        new Chart(document.getElementById('radarFN').getContext('2d'), {
            type: 'radar',
            data: {
                labels: [
                    'Recursos Turísticos (RT)',
                    'Planta Turística (PT)',
                    'Tipologías de Turismo (TT)',
                    'Infraestructura (I)'
                ],
                datasets: [{
                    label: 'Valor (0-2)',
                    data: @json($datosEndogenos),
                    backgroundColor: 'rgba(79, 70, 229, 0.15)',
                    borderColor: 'rgba(79, 70, 229, 0.85)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(79, 70, 229, 1)',
                    pointRadius: 5, pointHoverRadius: 7,
                }]
            },
            options: radarBaseFN('Factores Endógenos (FN) — Vista General')
        });

        // ── Radar FX: Factores Exógenos por subfactor ───────────────────────
        new Chart(document.getElementById('radarFX').getContext('2d'), {
            type: 'radar',
            data: {
                labels: ['Afluencia Turística', 'Marketing Turístico', 'Superestructura'],
                datasets: [{
                    label: 'Valor (0-2)',
                    data: @json($datosExogenos),
                    backgroundColor: 'rgba(13, 148, 136, 0.15)',
                    borderColor: 'rgba(13, 148, 136, 0.8)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(13, 148, 136, 1)',
                    pointRadius: 5,
                    pointHoverRadius: 7,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    title: {
                        display: true,
                        text: 'Perfil de Factores Exógenos (FX)',
                        font: { size: 14, weight: 'bold' },
                        color: '#374151'
                    }
                },
                scales: {
                    r: {
                        min: 0, max: 2,
                        ticks: { stepSize: 0.5, font: { size: 10 }, backdropColor: 'transparent' },
                        pointLabels: { font: { size: 11, weight: '600' }, color: '#4b5563' },
                        grid: { color: 'rgba(0,0,0,0.08)' },
                        angleLines: { color: 'rgba(0,0,0,0.08)' }
                    }
                }
            }
        });
    </script>

    @endif
</x-app-layout>
