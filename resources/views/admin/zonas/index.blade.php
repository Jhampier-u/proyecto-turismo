<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Zonas Turísticas</h2>
            <a href="{{ route('admin.zonas.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow transition">
                + Nueva Zona
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Zona</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ubicación</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Jefe</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Equipo</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($zonas as $zona)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($zona->imagen_path)
                                        <img src="{{ asset('storage/' . $zona->imagen_path) }}"
                                             class="w-10 h-10 rounded-full object-cover border shadow-sm" alt="">
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                                            {{ strtoupper(substr($zona->nombre, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="text-sm font-bold text-gray-900">{{ $zona->nombre }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $zona->lugar->nombre ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $zona->jefe->name ?? 'Sin Asignar' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-purple-100 text-purple-800 text-xs font-semibold px-2.5 py-0.5 rounded">
                                    {{ $zona->equipo_count }} miembros
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right text-sm font-medium">
                                <div class="flex justify-end gap-2 flex-wrap">
                                    <a href="{{ route('admin.vtt.final.admin', $zona->id) }}"
                                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-bold shadow transition">
                                        Resultados VTT
                                    </a>
                                    <a href="{{ route('admin.zonas.potencialidad', $zona->id) }}"
                                       class="bg-purple-600 hover:bg-purple-700 text-white px-3 py-1 rounded text-xs font-bold shadow transition">
                                        ⭐ Potencialidad
                                    </a>
                                    <a href="{{ route('admin.zonas.edit', $zona->id) }}"
                                       class="bg-indigo-100 text-indigo-700 px-3 py-1 rounded text-xs font-bold">Editar</a>
                                    <form action="{{ route('admin.zonas.destroy', $zona->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta zona?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="bg-red-100 text-red-700 px-3 py-1 rounded text-xs font-bold">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="mt-4">{{ $zonas->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
