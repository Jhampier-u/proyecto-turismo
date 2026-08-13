<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Involucrados Turísticos: {{ $zona->nombre }}
                </h2>
                <p class="text-sm text-gray-500">Actores con algo que decir sobre el turismo en el territorio</p>
            </div>
            @if($puedeEditar)
            <x-boton :href="route('operativo.involucrados.create', $zona->id)">
                + Nuevo actor
            </x-boton>
            @endif
        </div>
    </x-slot>

    <div class="py-12">

            <x-migas :zona="$zona" clave="involucrados" actual="Formulario" />
            <x-pestanas-matriz clave="involucrados" :zona="$zona" activa="formulario" />

            <x-flash-exito />

            @if($confirmada)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex justify-between items-center flex-wrap gap-3">
                        <div>
                            <strong class="font-bold text-lg">Lista de actores validada</strong>
                            <p>El Jefe de Zona confirmó esta lista. Los normalizados y el tipo de Mitchell ya se pueden consultar.</p>
                            {{--
                                El jefe no debería enterarse de la reapertura recién
                                DESPUÉS de provocarla: "+ Nuevo actor", "Editar" y
                                "Eliminar" siguen activos justo debajo de este banner
                                mientras la lista sigue "validada", y el aviso de que
                                tocarla la reabre tiene que estar aquí, antes de que
                                pulse cualquiera de los tres, no solo en el flash que
                                sale después de guardar.

                                Y condicionado a $puedeEditar, que con la lista
                                validada solo es cierto para el jefe: a los demás
                                bloqueoSiCerrada() les cierra el paso, así que
                                anunciarles la consecuencia de una acción que no
                                pueden ejecutar solo confunde. Los tres botones que
                                este párrafo advierte tampoco se les pintan.
                            --}}
                            @if($puedeEditar)
                            <p class="text-sm mt-1">
                                Si la modificas —añades, editas o borras un actor—, vuelve a borrador: hay que validarla de nuevo.
                            </p>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    <strong class="font-bold">Modo Borrador</strong>
                    <p>La lista sigue abierta: se pueden añadir, editar y borrar actores.</p>
                </div>
            @endif

            {{--
                La franja de resumen sube el recuento de a medias y la acción
                de validar arriba del todo, junto a la tabla: antes vivían
                separados -"— sin responder —" fila por fila sin sumar en
                ningún sitio, y el botón/aviso solo al final de la página-.
                puedeValidar/avisoValidacion son el mismo mensaje para roles
                distintos (jefe / equipo), así que viajan juntos aquí y ya no
                se repiten al final.
            --}}
            <x-resumen-lista sustantivo="actor" plural="actores"
                             :total="$actores->count()"
                             :incompletos="$incompletos"
                             :puede-validar="$puedeValidar"
                             :ruta-validar="route('operativo.involucrados.validar', $zona->id)"
                             :aviso-validacion="$avisoValidacion"
                             :jefe="$zona->jefe?->name" />

            <x-tarjeta :padding="false" class="overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">Actor</th>
                            @foreach($atributos as $atributo)
                                <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">
                                    {{ $atributo['titulo'] }}
                                    <span class="font-normal text-gray-400">(0-{{ count($atributo['campos']) * $escalaMax }})</span>
                                </th>
                            @endforeach
                            <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">Estado</th>
                            @if($puedeEditar)
                                <th class="px-6 py-3 text-right text-sm font-bold text-gray-500">Acciones</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($actores as $actor)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 font-bold text-gray-900">{{ $actor->nombre }}</td>
                                @foreach($atributos as $clave => $atributo)
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        {{ $actor->grado($clave) ?? '—' }}
                                    </td>
                                @endforeach
                                <td class="px-6 py-4">
                                    @if($actor->estaCompleto())
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Completo</span>
                                    @else
                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-amber-100 text-amber-800">A medias</span>
                                    @endif
                                </td>
                                @if($puedeEditar)
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('operativo.involucrados.edit', ['zona' => $zona->id, 'actor' => $actor->id]) }}"
                                           class="text-indigo-600 font-bold text-sm bg-indigo-50 px-2 py-1 rounded">Editar</a>
                                        {{--
                                            El mismo aviso que el banner verde, y por la
                                            misma razón: el diálogo nativo es lo último que
                                            el jefe ve ANTES de reabrir la lista, no
                                            después. Condicionado a $confirmada para no
                                            anunciar una reapertura que no va a ocurrir
                                            cuando la lista todavía está en borrador.
                                        --}}
                                        <form action="{{ route('operativo.involucrados.destroy', ['zona' => $zona->id, 'actor' => $actor->id]) }}"
                                              method="POST"
                                              onsubmit="return confirm('¿Borrar este actor?{{ $confirmada ? ' Esta lista está validada: al borrarlo, vuelve a borrador y hay que validarla de nuevo.' : '' }}');">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 font-bold text-sm bg-red-50 px-2 py-1 rounded">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                                @endif
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ 2 + count($atributos) + ($puedeEditar ? 1 : 0) }}"
                                    class="px-6 py-10 text-center text-gray-400">
                                    Todavía sin actores registrados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </x-tarjeta>

    </div>
</x-app-layout>
