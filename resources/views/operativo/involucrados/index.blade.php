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
            <a href="{{ route('operativo.involucrados.create', $zona->id) }}"
               class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded shadow">
                + Nuevo actor
            </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
               class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                ← Volver a la zona
            </a>

            <x-flash-exito />

            @if($confirmada)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex justify-between items-center flex-wrap gap-3">
                        <div>
                            <strong class="font-bold text-lg">Lista de actores validada</strong>
                            <p>El Jefe de Zona confirmó esta lista. Los normalizados y el tipo de Mitchell ya se pueden consultar.</p>
                        </div>
                        <a href="{{ route('operativo.involucrados.resultados', $zona->id) }}"
                           class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700">
                            Ver Resultados
                        </a>
                    </div>
                </div>
            @else
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    <strong class="font-bold">Modo Borrador</strong>
                    <p>La lista sigue abierta: se pueden añadir, editar y borrar actores.</p>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
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
                                        <form action="{{ route('operativo.involucrados.destroy', ['zona' => $zona->id, 'actor' => $actor->id]) }}"
                                              method="POST" onsubmit="return confirm('¿Borrar este actor?');">
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
            </div>

            @if($puedeValidar)
                <div class="mt-6 flex justify-end">
                    <form action="{{ route('operativo.involucrados.validar', $zona->id) }}" method="POST"
                          onsubmit="return confirm('Al validar, la lista queda cerrada para el equipo. ¿Continuar?');">
                        @csrf
                        <button type="submit"
                                class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow">
                            Validar y Cerrar la Lista
                        </button>
                    </form>
                </div>
            @elseif($avisoValidacion)
                <p class="mt-6 text-sm text-amber-700 text-right">
                    Lista para validar — avísale a {{ $zona->jefe?->name ?? 'tu Jefe de Zona' }}
                </p>
            @endif

        </div>
    </div>
</x-app-layout>
