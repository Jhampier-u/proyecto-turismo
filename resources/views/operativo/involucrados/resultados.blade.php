<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Involucrados Turísticos — Resultados: {{ $zona->nombre }}
        </h2>
    </x-slot>

    {{--
        Vista mínima: solo lo necesario para que la ruta 'ver' del registro
        exista y no rompa. La normalización, el producto de relevancia y el
        ranking completo por relevancia son la Tarea 4 — el diseño del
        instrumento (docs/superpowers/specs/2026-08-07-matriz-involucrados-design.md)
        los deja para esa entrega.
    --}}

    @if(! $completa)
        <x-matriz-sin-resultados
            nombre="Involucrados turísticos"
            :zona="$zona"
            ruta-formulario="operativo.involucrados.index" />
    @else
        <div class="py-12">
            <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

                <a href="{{ route('operativo.involucrados.index', $zona->id) }}"
                   class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                    ← Volver al listado
                </a>

                <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 text-sm rounded">
                    Los grados normalizados, el producto de relevancia y el ranking completo llegan en una
                    próxima entrega. Por ahora, la lista de actores y su tipo de Mitchell.
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">Actor</th>
                                <th class="px-6 py-3 text-left text-sm font-bold text-gray-500">Tipo de Mitchell</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($actores as $actor)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 font-bold text-gray-900">{{ $actor->nombre }}</td>
                                    <td class="px-6 py-4 text-sm text-gray-600">{{ $actor->tipo_mitchell }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    @endif
</x-app-layout>
