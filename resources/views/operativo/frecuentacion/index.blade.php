<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Frecuentación Turística: {{ $zona->nombre }}
                </h2>
                <p class="text-sm text-gray-500">Cuánto concentra cada sitio la frecuentación del territorio</p>
            </div>
            @if($puedeEditar)
            <a href="{{ route('operativo.frecuentacion.create', $zona->id) }}"
               class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
                + Nuevo sitio
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <x-pestanas-matriz clave="frecuentacion" :zona="$zona" activa="formulario" />

            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
               class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                ← Volver a la zona
            </a>

            <x-flash-exito />

            @if($confirmada)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <strong class="font-bold text-lg">Lista de sitios validada</strong>
                    <p>El Jefe de Zona confirmó esta lista. El ÍETP de cada sitio y el ÍEFT del territorio ya se pueden consultar.</p>
                    {{--
                        Igual que Involucrados: el aviso de que tocar la lista
                        (o la ST) la reabre tiene que estar aquí, antes de que
                        se pulse cualquier acción, no solo en el flash que
                        sale después de guardar.
                    --}}
                    <p class="text-sm mt-1">
                        Si la modificas —añades, editas o borras un sitio, o cambias la Superficie Territorial—, vuelve a borrador: hay que validarla de nuevo.
                    </p>
                </div>
            @else
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    <strong class="font-bold">Modo Borrador</strong>
                    <p>La lista sigue abierta: se pueden añadir, editar y borrar sitios, y actualizar la Superficie Territorial.</p>
                </div>
            @endif

            {{--
                La franja de resumen sube el recuento de a medias y la acción
                de validar arriba del todo, junto a la tabla: antes vivían
                separados -el botón/aviso solo al final de la página-.
                puedeValidar/avisoValidacion son el mismo mensaje para roles
                distintos (jefe / equipo), así que viajan juntos aquí y ya no
                se repiten al final.

                La ST es propia de Frecuentación y no tiene equivalente en
                Involucrados: se pasa por la ranura del componente, como un
                dato más de la franja, no como un prop nuevo -el componente no
                deriva nada, solo pinta lo que le dan-. Va ENCIMA de la
                sección de ST y no debajo: si fueran pegadas, el mismo dato
                aparecería dos veces seguidas y la franja parecería un
                duplicado en vez de un resumen.
            --}}
            <x-resumen-lista sustantivo="sitio" faltante="sin DET"
                             :total="$sitios->count()"
                             :incompletos="$incompletos"
                             :puede-validar="$puedeValidar"
                             :ruta-validar="route('operativo.frecuentacion.validar', $zona->id)"
                             :aviso-validacion="$avisoValidacion"
                             :jefe="$zona->jefe?->name">
                {{ $stDefinida ? 'ST: ' . $config->st : 'ST sin responder' }}
            </x-resumen-lista>

            {{--
                La Superficie Territorial (ST): un escalar de la ZONA, uno
                solo para todos los sitios -no una fila más de la tabla de
                abajo-, así que vive en su propia sección con su propio
                formulario y su propio botón de guardar. La franja de arriba
                solo la NOMBRA como dato -no la sustituye-: sigue siendo un
                campo editable con envío propio, y eso no encaja en un
                resumen.
            --}}
            <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-1">Superficie Territorial (ST)</h3>
                <p class="text-sm text-gray-500 mb-4">
                    Un solo valor para todo el territorio: sin él, ningún sitio tiene ÍETP, aunque todos tengan su DET respondido.
                </p>
                <form method="POST" action="{{ route('operativo.frecuentacion.superficie', $zona->id) }}"
                      class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div>
                        <label for="st" class="block text-sm font-medium text-gray-700 mb-1">Superficie Territorial</label>
                        <input type="number" step="any" min="0" name="st" id="st"
                               value="{{ old('st', $config?->st) }}"
                               placeholder="— sin responder —"
                               @disabled(! $puedeEditar)
                               class="w-48 border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500">
                        @error('st')
                            <span class="text-sm text-red-500 block mt-1">{{ $message }}</span>
                        @enderror
                    </div>
                    @if($puedeEditar)
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow">
                            Guardar
                        </button>
                    @endif
                </form>
            </section>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">Sitio</th>
                            <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">DET</th>
                            <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">Estado</th>
                            @if($puedeEditar)
                                <th class="px-6 py-3 text-right text-sm font-bold text-gray-500">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($sitios as $sitio)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ $sitio->nombre }}</td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $sitio->det !== null ? $sitio->det : '— sin responder —' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($sitio->estaCompleto())
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completo</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">A medias</span>
                                    @endif
                                </td>
                                @if($puedeEditar)
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('operativo.frecuentacion.edit', ['zona' => $zona->id, 'sitio' => $sitio->id]) }}"
                                           class="text-indigo-600 font-bold text-sm bg-indigo-50 px-2 py-1 rounded">Editar</a>
                                        {{--
                                            Mismo aviso que el banner verde, y
                                            por la misma razón: condicionado a
                                            $confirmada para no anunciar una
                                            reapertura que no va a ocurrir
                                            mientras la lista sigue en
                                            borrador.
                                        --}}
                                        <form action="{{ route('operativo.frecuentacion.destroy', ['zona' => $zona->id, 'sitio' => $sitio->id]) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Borrar este sitio?{{ $confirmada ? ' Esta lista está validada: al borrarlo, vuelve a borrador y hay que validarla de nuevo.' : '' }}');">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 font-bold text-sm bg-red-50 px-2 py-1 rounded">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 3 + ($puedeEditar ? 1 : 0) }}"
                                    class="px-6 py-10 text-center text-gray-400">
                                    Todavía sin sitios registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</x-app-layout>
