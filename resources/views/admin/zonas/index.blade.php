<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Gestión de Zonas Turísticas</h2>
            <x-boton :href="route('admin.zonas.create')">
                + Nueva Zona
            </x-boton>
        </div>
    </x-slot>

    {{-- Clave propia 'zonas_vista': la misma que usa «Mis Zonas», así que el
         admin y el jefe recuerdan cada uno su formato preferido sin pisarse
         (el admin no ve /mis-zonas y el jefe no ve /admin/zonas, pero
         comparten localStorage si son la misma persona en dos pestañas). --}}
    <div class="py-12" x-data="{ vista: localStorage.getItem('zonas_vista') || 'lista' }"
         x-init="$watch('vista', v => localStorage.setItem('zonas_vista', v))">

            @if(session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="flex justify-end mb-4">
                <x-conmutador-vista modelo="vista" />
            </div>

            {{-- ═══ VISTA LISTA ══════════════════════════════════════════════════ --}}
            <x-tarjeta x-show="vista === 'lista'" x-transition class="overflow-hidden">
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
            </x-tarjeta>

            {{-- ═══ VISTA TARJETAS ═══════════════════════════════════════════════ --}}
            <div x-show="vista === 'tarjetas'" x-transition
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($zonas as $zona)
                <x-tarjeta :padding="false" class="hover:shadow-md transition duration-300">

                    @if($zona->imagen_path)
                        <div class="h-40 overflow-hidden rounded-t-xl">
                            <x-foto :ruta="$zona->imagen_path" :alt="$zona->nombre"
                                    class="w-full h-full object-cover" />
                        </div>
                    @else
                        <div class="h-40 bg-gradient-to-br from-blue-100 to-indigo-200 flex items-center justify-center rounded-t-xl">
                            <span class="text-5xl font-black text-blue-300 select-none">
                                {{ strtoupper(substr($zona->nombre, 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $zona->nombre }}</h3>
                        <p class="text-sm text-gray-600 mt-1">📍 {{ $zona->lugar->nombre ?? 'N/A' }}</p>
                        <p class="text-sm text-gray-900 mt-1">{{ $zona->jefe->name ?? 'Sin Asignar' }}</p>

                        <span class="inline-block mt-2 bg-purple-100 text-purple-800 text-sm font-semibold px-2.5 py-0.5 rounded">
                            {{ $zona->equipo_count }} miembros
                        </span>

                        @php $p = $progreso[$zona->id]; @endphp
                        <div class="flex items-center gap-3 mt-5">
                            <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                            </div>
                            <span class="text-sm text-gray-600 whitespace-nowrap">
                                {{ $p['hechas'] }} / {{ $p['total'] }}
                            </span>
                        </div>

                        <div class="flex gap-2 mt-5">
                            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
                               class="flex-1 text-center px-3 py-1.5 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
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
                    </div>
                </x-tarjeta>
                @endforeach
            </div>

            {{-- El paginador queda fuera de los dos x-show: el conmutador cambia
                 la maquetación, no la consulta. La página sigue trayendo diez
                 zonas y el paginador sigue ahí, se mire como se mire. --}}
            <div class="mt-4">{{ $zonas->links() }}</div>
    </div>
</x-app-layout>
