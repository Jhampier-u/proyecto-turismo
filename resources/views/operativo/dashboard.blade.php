<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Zonas Asignadas') }}
        </h2>
    </x-slot>

    {{-- La preferencia vive en 'zonas_vista', clave propia y separada de la
         de Inventario ('inventario_vista'), para que cambiar una no afecte
         a la otra. --}}
    <div class="py-12" x-data="{ vista: localStorage.getItem('zonas_vista') || 'tarjetas' }"
         x-init="$watch('vista', v => localStorage.setItem('zonas_vista', v))">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">{{ session('success') }}</div>
            @endif

            @if($zonas->isEmpty())
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-700">No tienes zonas asignadas actualmente. Contacta al administrador.</p>
            </div>
            @endif

            <div class="flex justify-end mb-4">
                <x-conmutador-vista modelo="vista" />
            </div>

            {{-- ═══ VISTA LISTA ══════════════════════════════════════════════════ --}}
            <div x-show="vista === 'lista'" x-transition
                 class="bg-white shadow-sm rounded-xl border border-gray-200 divide-y divide-gray-200">
                @foreach($zonas as $zona)
                    @php $p = $progreso[$zona->id]; @endphp
                    <div class="flex items-center gap-4 p-4">
                        <div class="flex-1 min-w-0">
                            <p class="text-base text-gray-900">{{ $zona->nombre }}</p>
                            <p class="text-sm text-gray-600">📍 {{ $zona->lugar->nombre }}</p>
                            {{-- Misma descripción que la tarjeta, para que cambiar de
                                 formato no le esconda este dato al usuario. --}}
                            <p class="text-sm text-gray-600 mt-1 line-clamp-1">
                                {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
                            </p>
                        </div>

                        <div class="w-40 shrink-0">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                            </div>
                            <p class="text-sm text-gray-600 mt-1">{{ $p['hechas'] }} / {{ $p['total'] }}</p>
                        </div>

                        <div class="flex gap-2 shrink-0">
                            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
                               class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-medium hover:bg-indigo-700">
                                Abrir zona
                            </a>
                            <a href="{{ route('operativo.inventarios.index', $zona->id) }}"
                               class="px-4 py-2 rounded-lg border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Inventario
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ═══ VISTA TARJETAS ═══════════════════════════════════════════════ --}}
            <div x-show="vista === 'tarjetas'" x-transition
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($zonas as $zona)
                <div class="bg-white shadow-sm sm:rounded-lg hover:shadow-md transition duration-300 border border-gray-100">

                    {{-- Imagen de zona --}}
                    @if($zona->imagen_path)
                        <div class="h-40 overflow-hidden rounded-t-lg">
                            <x-foto :ruta="$zona->imagen_path" :alt="$zona->nombre"
                                    class="w-full h-full object-cover" />
                        </div>
                    @else
                        <div class="h-40 bg-gradient-to-br from-blue-100 to-indigo-200 flex items-center justify-center rounded-t-lg">
                            <span class="text-5xl font-black text-blue-300 select-none">
                                {{ strtoupper(substr($zona->nombre, 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $zona->nombre }}</h3>
                        <p class="text-sm text-gray-600 mt-1">📍 {{ $zona->lugar->nombre }}</p>

                        <p class="text-sm text-gray-600 mt-3 line-clamp-2">
                            {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
                        </p>

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

                        {{-- Dos botones, no siete. El resto vive dentro de la zona.
                             Inventario se queda por ser lo que más se usa a diario. --}}
                        <div class="flex gap-2 mt-5">
                            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
                               class="flex-1 text-center px-4 py-2 rounded-lg bg-indigo-600 text-white
                                      text-sm font-medium hover:bg-indigo-700 shadow-sm">
                                Abrir zona
                            </a>
                            <a href="{{ route('operativo.inventarios.index', $zona->id) }}"
                               class="px-4 py-2 rounded-lg border border-gray-300 bg-white
                                      text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
                                Inventario
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
