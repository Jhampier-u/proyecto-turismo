<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ponderación Variable (FET) - {{ $zona->nombre }}
        </h2>
    </x-slot>

    {{-- Desde el guardado parcial, una FET puede existir con criterios sin
         responder: entonces sus medias y su total están en null y la tabla
         entera se llenaría de ceros que nadie ha puntuado. El script del
         gráfico queda dentro del @else por lo mismo: sin datos no hay nada
         que dibujar. --}}
    @if($fet->fet === null)
        <x-matriz-sin-resultados
            nombre="Evaluación FET"
            :zona="$zona"
            ruta-formulario="operativo.evaluacion_fet.edit" />
    @else

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-pestanas-matriz clave="fet" :zona="$zona" activa="resultados" />

            {{-- "Volver a la Zona" sube aquí, junto a las pestañas -mismo
                 sitio que ocupa "Regresar" en el formulario-: es la misma
                 acción de navegación, y antes vivía duplicada al pie junto a
                 dos enlaces que ya no existen (ver el comentario de más
                 abajo, donde estaban). --}}
            <x-boton-volver :zona="$zona" class="mb-4" />

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

                @if($fet?->exists && $fet->user)
                    <p class="text-sm text-gray-500 mb-4">
                        Última edición: {{ $fet->user->name }},
                        {{ $fet->updated_at->diffForHumans() }}
                    </p>
                @endif

                <h3 class="text-center font-bold text-lg text-gray-700 mb-6 uppercase">
                    Tabla de Valoración "Factores Extrínsecos Territoriales"
                </h3>

                <div class="overflow-x-auto border border-gray-400">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-gray-200 text-gray-900 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-400 p-3">Variable</th>
                                <th class="border border-gray-400 p-3">Componente</th>
                                <th class="border border-gray-400 p-3 bg-yellow-100">Valoración</th>
                            </tr>
                        </thead>

                        <tbody class="text-gray-800">

                            <tr>
                                <td rowspan="2" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Demanda Turística
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Flujos Turísticos</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->demanda_flujos }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Estadía Promedio</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->demanda_estadia }}</td>
                            </tr>


                            <tr>
                                <td rowspan="3" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Superestructura
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Institucionalidad Turística</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->super_institucionalidad }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Organización Comunitaria en Turismo</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->super_organizacion }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Planificación Turística</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->super_planificacion }}</td>
                            </tr>


                            <tr>
                                <td rowspan="4" class="border border-gray-400 p-2 font-bold bg-gray-50 align-middle">
                                    Imagen del Sitio
                                </td>
                                <td class="border border-gray-400 p-2 text-left">Grado de Apertura de la Comunidad Local</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->imagen_apertura }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Seguridad del Destino o Sitio de Visita</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->imagen_seguridad }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Imagen Percibida del Visitante</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->imagen_percibida }}</td>
                            </tr>
                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Marketing Turístico</td>
                                <td class="border border-gray-400 p-2 bg-yellow-50 font-bold">{{ $fet->imagen_marketing }}</td>
                            </tr>

                        </tbody>
                    </table>
                </div>

                <h3 class="text-center font-bold text-lg text-gray-700 mt-10 mb-4 uppercase">
                    Valoración Total "Factores Extrínsecos Territoriales"
                </h3>

                <div class="overflow-x-auto border border-gray-400 mb-8">
                    <table class="min-w-full text-sm text-center border-collapse">
                        <thead class="bg-gray-200 text-gray-900 font-bold uppercase">
                            <tr>
                                <th class="border border-gray-400 p-3">Variable</th>
                                <th class="border border-gray-400 p-3">Valor %</th>
                                <th class="border border-gray-400 p-3">Calificación (Media)</th>
                                <th class="border border-gray-400 p-3">FET (Ponderado)</th>
                            </tr>
                        </thead>

                        <tbody>

                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Demanda Turística</td>
                                <td class="border border-gray-400 p-2">20%</td>
                                <td class="border border-gray-400 p-2">{{ $fet->media_demanda }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ $fet->fet_demanda }}</td>
                            </tr>

                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Superestructura</td>
                                <td class="border border-gray-400 p-2">40%</td>
                                <td class="border border-gray-400 p-2">{{ $fet->media_super }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ $fet->fet_super }}</td>
                            </tr>

                            <tr>
                                <td class="border border-gray-400 p-2 text-left">Imagen del Sitio</td>
                                <td class="border border-gray-400 p-2">40%</td>
                                <td class="border border-gray-400 p-2">{{ $fet->media_imagen }}</td>
                                <td class="border border-gray-400 p-2 font-bold">{{ $fet->fet_imagen }}</td>
                            </tr>

                        </tbody>

                        <tfoot class="bg-blue-600 text-white font-bold">
                            <tr>
                                <td colspan="3" class="border border-gray-400 p-3 text-right text-lg">
                                    CALIFICACIÓN TOTAL FET:
                                </td>
                                <td class="border border-gray-400 p-3 text-2xl">
                                    {{ number_format($fet->fet, 2) }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                {{-- Radar Chart FET --}}
                <div class="flex justify-center mb-10">
                    <div style="width:450px;height:450px;">
                        <canvas id="radarFET"></canvas>
                    </div>
                </div>

                {{-- La fila de botones que iba aquí se quitó entera:
                     "Ver el Formulario" era exactamente lo que ya hacen las
                     pestañas de arriba; "Volver a Vocación Turística"
                     enlazaba a operativo.vtt.final, que da error si FIT y FET
                     no están las dos confirmadas -un enlace que falla es peor
                     que ninguno, el panel de la zona ya muestra Vocación con
                     su candado y el motivo-; y "Mis Zonas" subió junto a las
                     pestañas, convertido en el botón de volver a la zona. --}}
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    @php $mediasFet = [$fet->media_demanda, $fet->media_super, $fet->media_imagen]; @endphp
    <script>
        new Chart(document.getElementById('radarFET').getContext('2d'), {
            type: 'radar',
            data: {
                labels: [
                    'Demanda Turística',
                    'Superestructura',
                    'Imagen del Sitio'
                ],
                datasets: [{
                    label: 'Calificación Media (0-3)',
                    data: @json($mediasFet),
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
                        text: 'Perfil de Factores Extrínsecos Territoriales (FET)',
                        font: { size: 14, weight: 'bold' },
                        color: '#374151'
                    }
                },
                scales: {
                    r: {
                        min: 0, max: 3,
                        ticks: { stepSize: 0.5, font: { size: 10 }, backdropColor: 'transparent' },
                        pointLabels: { font: { size: 12, weight: '600' }, color: '#4b5563' },
                        grid: { color: 'rgba(0,0,0,0.08)' },
                        angleLines: { color: 'rgba(0,0,0,0.08)' }
                    }
                }
            }
        });
    </script>

    @endif
</x-app-layout>
