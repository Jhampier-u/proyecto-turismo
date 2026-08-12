<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Frecuentación Turística — Resultados: {{ $zona->nombre }}
        </h2>
    </x-slot>

    {{--
        La condición de la ST es del territorio ENTERO, no de un sitio (ver el
        diseño): se resuelve una sola vez, aquí, antes de pintar ninguna
        tabla. No hay un "no aplica" repetido fila por fila -a diferencia de
        Concentración, donde cada sector tiene su propio Sia y puede estar
        vacío mientras otros no lo están-.
    --}}
    @if(! $completa)
        <x-matriz-sin-resultados
            nombre="Frecuentación turística"
            :zona="$zona"
            ruta-formulario="operativo.frecuentacion.index">
            {{ $motivoSinResultados }}
        </x-matriz-sin-resultados>
    @else
        <div class="py-12">

                <x-pestanas-matriz clave="frecuentacion" :zona="$zona" activa="resultados" />

                <x-tarjeta :padding="false" class="overflow-hidden p-8">

                    <a href="{{ route('operativo.frecuentacion.index', $zona->id) }}"
                       class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                        ← Volver al listado
                    </a>

                    <x-flash-exito />

                    {{-- Igual que index.blade.php: quién tocó esto por última
                         vez vive en FrecuentacionConfig -el estado del
                         CONJUNTO-, no en cada sitio. --}}
                    @if($config?->exists && $config->user)
                        <p class="text-sm text-gray-500 mb-4">
                            Última edición: {{ $config->user->name }},
                            {{ $config->updated_at->diffForHumans() }}
                        </p>
                    @endif

                    <h3 class="text-center font-bold text-xl text-gray-700 mb-2">
                        Índice Espacial de Frecuentación Turística
                    </h3>
                    <p class="text-sm text-gray-500 text-center mb-8">
                        Superficie Territorial (ST): {{ number_format($config->st, 4) }}
                    </p>

                    <div class="overflow-x-auto mb-8">
                        <table class="min-w-full text-sm border-collapse border border-gray-300">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="border border-gray-300 p-2 text-left">Sitio</th>
                                    <th class="border border-gray-300 p-2">DET</th>
                                    <th class="border border-gray-300 p-2">ÍETP</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{--
                                    $filas ya llega con el ÍETP calculado por
                                    FrecuentacionController::resultados(): la
                                    vista no vuelve a llamar a
                                    Frecuentacion::ietp() por su cuenta, mismo
                                    criterio que Involucrados con sus
                                    normalizados ya resueltos.
                                --}}
                                @foreach($filas as $fila)
                                    <tr class="hover:bg-gray-50">
                                        <td class="border border-gray-300 p-2 font-bold text-gray-900">
                                            {{ $fila['sitio']->nombre }}
                                        </td>
                                        <td class="border border-gray-300 p-2 text-center">
                                            {{ number_format($fila['sitio']->det, 4) }}
                                        </td>
                                        <td class="border border-gray-300 p-2 text-center font-bold">
                                            {{ number_format($fila['ietp'], 4) }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{--
                        El ÍEFT del territorio, destacado como un total que se
                        lee de un vistazo -no como una fila más de la tabla de
                        arriba-: es la suma de todos los ÍETP, un número del
                        TERRITORIO, no de un sitio.
                    --}}
                    <div class="bg-indigo-50 border-l-4 border-indigo-500 rounded p-6 flex items-center justify-between flex-wrap gap-2">
                        <span class="text-base font-bold text-indigo-900">
                            ÍEFT — Índice Espacial de Frecuentación Turística del territorio
                        </span>
                        <span class="text-2xl font-bold text-indigo-900">{{ number_format($ieft, 4) }}</span>
                    </div>

                </x-tarjeta>
        </div>
    @endif
</x-app-layout>
