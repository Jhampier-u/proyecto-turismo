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
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Zona</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Ubicación</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Jefe</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-600">Equipo</th>
                            <th class="px-6 py-3 text-center text-sm font-medium text-gray-600">Progreso</th>
                            <th class="px-6 py-3 text-right text-sm font-medium text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($zonas as $zona)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if($zona->imagen_path)
                                        <x-foto :ruta="$zona->imagen_path" alt=""
                                                class="w-10 h-10 rounded-full object-cover border shadow-sm" />
                                    @else
                                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-sm">
                                            {{ strtoupper(substr($zona->nombre, 0, 2)) }}
                                        </div>
                                    @endif
                                    <div class="text-base font-bold text-gray-900">{{ $zona->nombre }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">{{ $zona->lugar->nombre ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $zona->jefe->name ?? 'Sin Asignar' }}</td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-purple-100 text-purple-800 text-sm font-semibold px-2.5 py-0.5 rounded">
                                    {{ $zona->equipo_count }} miembros
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600">
                                @php $p = $progreso[$zona->id]; @endphp
                                {{ $p['hechas'] }} / {{ $p['total'] }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('operativo.zona.panel', $zona->id) }}"
                                       class="px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                                        Abrir zona
                                    </a>
                                    <a href="{{ route('admin.zonas.edit', $zona->id) }}"
                                       class="px-3 py-1.5 rounded-lg bg-gray-100 text-gray-700 text-sm font-medium hover:bg-gray-200">
                                        Editar
                                    </a>
                                    <form action="{{ route('admin.zonas.destroy', $zona->id) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar esta zona?');">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="px-3 py-1.5 rounded-lg bg-red-100 text-red-700 text-sm font-medium hover:bg-red-200">
                                            Eliminar
                                        </button>
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
