<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Ponderación Variable (FET) - {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-8">

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

                <div class="mt-8 text-center">
                    <a href="{{ route('operativo.evaluacion_fet.edit', $zona->id) }}"
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
</x-app-layout>
