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
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nombre del Lugar</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provincia</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Descripción</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($lugares as $lugar)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-bold text-gray-900">{{ $lugar->nombre }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $lugar->provincia->nombre }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($lugar->descripcion, 50) }}</td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.lugares.edit', $lugar) }}" class="bg-indigo-100 px-3 rounded text-xs  py-1 text-indigo-600 hover:text-indigo-900">Editar</a>
                                    <form action="{{ route('admin.lugares.destroy', $lugar) }}" method="POST" onsubmit="return confirm('¿Borrar este lugar?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bg-red-100 text-red-700 hover:bg-red-200 py-1 px-3 rounded text-xs font-bold transition">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $lugares->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>