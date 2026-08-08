<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Catálogo de Lugares Geográficos') }}
            </h2>
            <a href="{{ route('admin.lugares.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                + Nuevo Lugar
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
            @endif
            @if(session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                {{ session('error') }}
            </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">

                <form method="GET" action="{{ route('admin.lugares.index') }}"
                      class="flex flex-wrap gap-3 mb-6">
                    <input type="search" name="buscar" value="{{ $buscar }}"
                           placeholder="Buscar por nombre"
                           class="flex-1 min-w-64 text-base border-gray-300 rounded-lg shadow-sm
                                  focus:ring-indigo-500 focus:border-indigo-500">
                    <button type="submit"
                            class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-base font-medium hover:bg-indigo-700">
                        Buscar
                    </button>
                    @if($buscar !== '')
                        <a href="{{ route('admin.lugares.index') }}"
                           class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-base text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    @endif
                </form>

                @if($lugares->isEmpty())
                    <p class="text-base text-gray-600 py-8 text-center">
                        No hay lugares que coincidan con la búsqueda.
                    </p>
                @else
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Nombre del Lugar</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Provincia</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Descripción</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-600">Zonas</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($lugares as $lugar)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-base font-semibold text-gray-900">{{ $lugar->nombre }}</td>
                            <td class="px-6 py-4 text-base text-gray-600">{{ $lugar->provincia->nombre }}</td>
                            <td class="px-6 py-4 text-base text-gray-600">{{ Str::limit($lugar->descripcion, 50) }}</td>

                            <td class="px-6 py-4 text-center">
                                @if($lugar->zonas_count === 0)
                                    <span class="text-sm text-gray-400">Sin uso</span>
                                @else
                                    <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                        {{ $lugar->zonas_count }} {{ $lugar->zonas_count === 1 ? 'zona' : 'zonas' }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.lugares.edit', $lugar) }}"
                                       class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 py-1 px-3 rounded text-sm font-semibold transition">
                                        Editar
                                    </a>
                                    {{-- Borrar un lugar en uso lo rechaza el controlador, pero el
                                         aviso lo dice antes en vez de después. Js::from() escapa el
                                         texto: dentro de un atributo el navegador decodifica las
                                         entidades antes de que lo vea el parser de JavaScript, así
                                         que concatenar a mano rompe la página con una comilla. --}}
                                    <form action="{{ route('admin.lugares.destroy', $lugar) }}" method="POST"
                                          onsubmit="return confirm({{ $lugar->zonas_count > 0
                                              ? Js::from('Este lugar lo usan ' . $lugar->zonas_count . ' zonas. No se puede borrar mientras las tengan asignadas.')
                                              : Js::from('¿Borrar este lugar?') }});">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bg-red-100 text-red-700 hover:bg-red-200 py-1 px-3 rounded text-sm font-semibold transition">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @endif

                <div class="mt-4">{{ $lugares->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>