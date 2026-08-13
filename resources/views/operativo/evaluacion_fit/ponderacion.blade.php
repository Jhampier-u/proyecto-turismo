<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ponderación Variable (FIT) - {{ $zona->nombre }}
        </h2>
    </x-slot>

    {{-- Desde el guardado parcial, una FIT puede existir con criterios sin
         responder: entonces sus medias y su total están en null y la tabla
         entera se llenaría de ceros que nadie ha puntuado. El script del
         gráfico queda dentro del @else por lo mismo: sin datos no hay nada
         que dibujar. --}}
    @if($fit->fit === null)
        <x-matriz-sin-resultados
            nombre="Evaluación FIT"
            :zona="$zona"
            ruta-formulario="operativo.evaluacion_fit.edit" />
    @else

    <div class="py-12">

            <x-migas :zona="$zona" clave="fit" actual="Resultados" />
            <x-pestanas-matriz clave="fit" :zona="$zona" activa="resultados" />

            <x-tarjeta :padding="false" class="overflow-hidden p-8">

                @if($fit?->exists && $fit->user)
                    <p class="text-sm text-gray-500 mb-4">
                        Última edición: {{ $fit->user->name }},
                        {{ $fit->updated_at->diffForHumans() }}
                    </p>
                @endif

                <h3 class="text-center font-bold text-lg text-gray-700 mb-6 uppercase">
                    Tabla de Valoración "Factores Intrínsecos Territoriales"
                </h3>

                <div class="overflow-x-auto border border-gray-400">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-gray-200 text-gray-900 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-400 p-3">Grupo</th>
                                <th class="border border-gray-400 p-3">Componente</th>
                                <th class="border border-gray-400 p-3 bg-yellow-100">Valoración</th>
                            </tr>
                        </thead>

                        <tbody class="text-gray-800">
                            <tr>
                                <td rowspan="2" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Recursos Turísticos Territoriales
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Recursos Turísticos Territoriales Culturales</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->recursos_culturales }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Recursos Turísticos Territoriales Naturales</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->recursos_naturales }}</td>
                            </tr>

                            <tr>
                                <td rowspan="2" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Atractivos Turísticos
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Manifestaciones Culturales</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->atractivos_manifestaciones }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Sitios Naturales</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->atractivos_sitios }}</td>
                            </tr>

                            <tr>
                                <td rowspan="3" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Prestadores de Servicios
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Alojamiento</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->prestadores_alojamiento }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Restauración</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->prestadores_restauracion }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Guianza e Interpretación</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->prestadores_guianza }}</td>
                            </tr>

                            <tr>
                                <td class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Producto Turístico
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Producto Turístico Territorial</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->productos_territoriales }}</td>
                            </tr>

                            <tr>
                                <td rowspan="2" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Infraestructura
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Básica</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->infraestructura_basica }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Apoyo</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->infraestructura_apoyo }}</td>
                            </tr>

                            <tr>
                                <td rowspan="8" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Facilidades Turísticas
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Señalética</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_senaletica }}</td>
                            </tr>
                            {{-- Estas seis faltaban. El rowspan="8" de arriba ya
                                 las contaba, así que la tabla prometía ocho
                                 filas y pintaba dos: entraban en media_ft y en
                                 la nota, pero no había forma de cuadrar el
                                 resultado con lo respondido. Van en el orden en
                                 que las declara App\Matrices\Fit. --}}
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Recepción Visitantes</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_recepcion }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Centros Interpretación</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_interpretacion }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Senderos</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_senderos }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Estacionamientos</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_estacionamientos }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Campamentos</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_campamentos }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Miradores</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_miradores }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Baterías Sanitarias</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fit->facilidades_sanitarios }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <h3 class="text-center font-bold text-lg text-gray-700 mt-10 mb-4 uppercase">
                    Valoración Total "Factores Intrínsecos Territoriales"
                </h3>

                <div class="overflow-x-auto border border-gray-400 mb-8">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-gray-200 text-gray-900 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-400 p-3">Variable</th>
                                <th class="border border-gray-400 p-3">Valor %</th>
                                <th class="border border-gray-400 p-3">Calificación (Media)</th>
                                <th class="border border-gray-400 p-3">FIT (Ponderado)</th>
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Recursos Turísticos Territoriales (RTt)</td>
                                <td class="border border-gray-400 p-2">30%</td>
                                <td class="border border-gray-400 p-2">{{ number_format($fit->media_rtt, 2) }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ number_format($fit->fit_rtt, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Atractivos Turísticos (At)</td>
                                <td class="border border-gray-400 p-2">5%</td>
                                <td class="border border-gray-400 p-2">{{ number_format($fit->media_at, 2) }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ number_format($fit->fit_at, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Prestadores de Servicios (PSt)</td>
                                <td class="border border-gray-400 p-2">20%</td>
                                <td class="border border-gray-400 p-2">{{ number_format($fit->media_pst, 2) }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ number_format($fit->fit_pst, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Producto Turístico (PTt)</td>
                                <td class="border border-gray-400 p-2">5%</td>
                                <td class="border border-gray-400 p-2">{{ number_format($fit->media_ptt, 2) }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ number_format($fit->fit_ptt, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Infraestructura (I)</td>
                                <td class="border border-gray-400 p-2">20%</td>
                                <td class="border border-gray-400 p-2">{{ number_format($fit->media_i, 2) }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ number_format($fit->fit_i, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Facilidades Turísticas (Ft)</td>
                                <td class="border border-gray-400 p-2">20%</td>
                                <td class="border border-gray-400 p-2">{{ number_format($fit->media_ft, 2) }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ number_format($fit->fit_ft, 2) }}</td>
                            </tr>
                        </tbody>

                        <tfoot class="bg-blue-600 text-white font-bold">
                            <tr>
                                <td colspan="3" class="border border-gray-400 p-3 text-right text-lg">
                                    CALIFICACIÓN TOTAL FIT:
                                </td>
                                <td class="border border-gray-400 p-3 text-2xl">
                                    {{ number_format($fit->fit, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Radar Chart FIT --}}
                <div class="flex justify-center mb-10">
                    <div style="width:450px;height:450px;">
                        <canvas id="radarFIT"></canvas>
                    </div>
                </div>

                {{-- La fila de botones que iba aquí se quitó entera:
                     "Ver el Formulario" era exactamente lo que ya hacen las
                     pestañas de arriba; "Volver a Vocación Turística"
                     enlazaba a operativo.vtt.final, que da error si FIT y FET
                     no están las dos confirmadas -un enlace que falla es peor
                     que ninguno, el panel de la zona ya muestra Vocación con
                     su candado y el motivo-; y "Mis Zonas" lo dicen ahora
                     las migas de arriba, que son la única forma de subir de
                     nivel desde la Fase 1. --}}

            </x-tarjeta>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    @php
        $mediasFit = [
            $fit->media_rtt, $fit->media_at,
            $fit->media_pst, $fit->media_ptt,
            $fit->media_i, $fit->media_ft,
        ];
    @endphp
    <script>
        new Chart(document.getElementById('radarFIT').getContext('2d'), {
            type: 'radar',
            data: {
                labels: [
                    'Recursos Turísticos (RTt)',
                    'Atractivos (At)',
                    'Prestadores de Servicios (PSt)',
                    'Producto Turístico (PTt)',
                    'Infraestructura (I)',
                    'Facilidades Turísticas (Ft)'
                ],
                datasets: [{
                    label: 'Calificación Media (0-3)',
                    data: @json($mediasFit),
                    backgroundColor: 'rgba(37, 99, 235, 0.15)',
                    borderColor: 'rgba(37, 99, 235, 0.8)',
                    borderWidth: 2,
                    pointBackgroundColor: 'rgba(37, 99, 235, 1)',
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
                        text: 'Perfil de Factores Intrínsecos Territoriales (FIT)',
                        font: { size: 14, weight: 'bold' },
                        color: '#374151'
                    }
                },
                scales: {
                    r: {
                        min: 0, max: 3,
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