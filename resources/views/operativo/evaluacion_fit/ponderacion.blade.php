<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ponderación Variable (FIT) - {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

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

                <div class="mt-8 text-center">
                    <a href="{{ route('operativo.evaluacion_fit.edit', $zona->id) }}"
                        class="inline-block px-5 py-2 bg-blue-600 text-white font-bold text-lg rounded-lg
          hover:bg-blue-700 hover:scale-105 transition-transform duration-200 shadow-md">
                        Ver el Formulario
                    </a>

                    <a href="{{ route('operativo.vtt.final', $zona->id) }}"
                        class="inline-block px-5 py-2 bg-gray-200 text-black font-bold text-lg rounded-lg
          hover:bg-gray-400 hover:scale-105 transition-transform duration-200 shadow-md">
                        Volver a Vocaci&oacute;n Tur&iacute;stica
                    </a>

                </div>

            </div>
        </div>
    </div>
</x-app-layout>