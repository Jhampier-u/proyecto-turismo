<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Inventario: {{ $zona->nombre }}</h2>
                <p class="text-sm text-gray-500">Recursos turísticos registrados</p>
            </div>
            <x-boton :href="route('operativo.inventarios.create', $zona->id)">
                + Agregar Recurso
            </x-boton>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ vista: localStorage.getItem('inventario_vista') || 'lista' }"
         x-init="$watch('vista', v => localStorage.setItem('inventario_vista', v))">

            <div class="mb-4 flex items-center justify-between">
                {{-- El inventario está dentro de una zona: "volver" tiene que
                     bajar al panel de esa zona, no saltarse ese nivel hacia
                     Mis Zonas -el mismo criterio que las matrices. --}}
                <x-boton-volver :zona="$zona" />

                <x-conmutador-vista modelo="vista" />
            </div>

            @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4">{{ session('success') }}</div>
            @endif

            {{-- ═══ VISTA LISTA ══════════════════════════════════════════════════ --}}
            <div x-show="vista === 'lista'" x-transition>
                <x-tarjeta :padding="false" class="overflow-hidden">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Recurso</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase">Estado</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($inventarios as $inv)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @php $img = $inv->imagenes->first(); @endphp
                                        @if($img)
                                            <x-foto :ruta="$img->ruta_archivo" alt=""
                                                    class="w-10 h-10 rounded object-cover border flex-shrink-0" />
                                        @else
                                            <div class="w-10 h-10 rounded bg-gray-100 flex items-center justify-center text-gray-400 flex-shrink-0">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                        @endif
                                        <div>
                                            <div class="font-bold text-gray-900">{{ $inv->nombre_recurso }}</div>
                                            <div class="text-xs text-gray-500">{{ Str::limit($inv->descripcion, 45) }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    {{ $inv->categoria?->nombre ?? 'N/A' }}
                                    <br><span class="text-xs text-gray-400">{{ $inv->categoria?->padre?->nombre ?? '' }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                        {{ $inv->estado_conservacion == 'Bueno' ? 'bg-green-100 text-green-800' : '' }}
                                        {{ $inv->estado_conservacion == 'Regular' ? 'bg-yellow-100 text-yellow-800' : '' }}
                                        {{ $inv->estado_conservacion == 'Malo' ? 'bg-red-100 text-red-800' : '' }}">
                                        {{ $inv->estado_conservacion }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('operativo.inventarios.show', ['zona' => $zona->id, 'inventario' => $inv->id]) }}"
                                           class="text-blue-600 hover:text-blue-900 font-bold text-sm bg-blue-50 px-2 py-1 rounded">Ver</a>
                                        <a href="{{ route('operativo.inventarios.edit', ['zona' => $zona->id, 'inventario' => $inv->id]) }}"
                                           class="text-indigo-600 font-bold text-sm bg-indigo-50 px-2 py-1 rounded">Editar</a>
                                        <form action="{{ route('operativo.inventarios.destroy', ['zona' => $zona->id, 'inventario' => $inv->id]) }}"
                                              method="POST" onsubmit="return confirm('¿Borrar?');">
                                            @csrf @method('DELETE')
                                            <button class="text-red-600 font-bold text-sm bg-red-50 px-2 py-1 rounded">Eliminar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="4" class="px-6 py-10 text-center text-gray-400">No hay recursos registrados.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                    <div class="p-4">{{ $inventarios->links() }}</div>
                </x-tarjeta>
            </div>

            {{-- ═══ VISTA TARJETAS ═══════════════════════════════════════════════ --}}
            <div x-show="vista === 'tarjetas'" x-transition>
                @if($inventarios->isEmpty())
                    <x-tarjeta :padding="false" class="p-10 text-center text-gray-400">No hay recursos registrados.</x-tarjeta>
                @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
                    @foreach ($inventarios as $inv)
                    @php $img = $inv->imagenes->first(); @endphp
                    <x-tarjeta :padding="false" class="hover:shadow-md transition overflow-hidden flex flex-col">

                        {{-- Imagen o placeholder --}}
                        @if($img)
                            <div class="h-44 overflow-hidden">
                                <x-foto :ruta="$img->ruta_archivo" :alt="$inv->nombre_recurso"
                                        class="w-full h-full object-cover" />
                            </div>
                        @else
                            <div class="h-44 bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        @endif

                        <div class="p-4 flex flex-col flex-1">
                            <div class="flex justify-between items-start gap-2 mb-1">
                                <h4 class="font-bold text-gray-800 text-sm leading-tight">{{ $inv->nombre_recurso }}</h4>
                                <span class="px-1.5 py-0.5 text-xs font-semibold rounded-full flex-shrink-0
                                    {{ $inv->estado_conservacion == 'Bueno' ? 'bg-green-100 text-green-700' : '' }}
                                    {{ $inv->estado_conservacion == 'Regular' ? 'bg-yellow-100 text-yellow-700' : '' }}
                                    {{ $inv->estado_conservacion == 'Malo' ? 'bg-red-100 text-red-700' : '' }}">
                                    {{ $inv->estado_conservacion }}
                                </span>
                            </div>

                            <p class="text-xs text-indigo-600 font-medium mb-2">
                                {{ $inv->categoria?->padre?->nombre ?? '' }}
                                @if($inv->categoria?->padre) › @endif
                                {{ $inv->categoria?->nombre ?? 'N/A' }}
                            </p>

                            <p class="text-xs text-gray-500 line-clamp-2 flex-1">{{ $inv->descripcion }}</p>

                            @if($inv->ubicacion_detallada)
                            <p class="text-xs text-gray-400 mt-2 truncate">📍 {{ $inv->ubicacion_detallada }}</p>
                            @endif

                            @if($inv->imagenes->count() > 1)
                            <p class="text-xs text-blue-400 mt-1">🖼 {{ $inv->imagenes->count() }} fotos</p>
                            @endif

                            <div class="mt-3 pt-3 border-t flex gap-2">
                                <a href="{{ route('operativo.inventarios.show', ['zona' => $zona->id, 'inventario' => $inv->id]) }}"
                                   class="flex-1 text-center text-xs font-bold text-blue-700 bg-blue-50 hover:bg-blue-100 py-1.5 rounded transition">
                                    Ver detalle
                                </a>
                                <a href="{{ route('operativo.inventarios.edit', ['zona' => $zona->id, 'inventario' => $inv->id]) }}"
                                   class="flex-1 text-center text-xs font-bold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 py-1.5 rounded transition">
                                    Editar
                                </a>
                                <form action="{{ route('operativo.inventarios.destroy', ['zona' => $zona->id, 'inventario' => $inv->id]) }}"
                                      method="POST" onsubmit="return confirm('¿Borrar?');" class="flex-1">
                                    @csrf @method('DELETE')
                                    <button class="w-full text-xs font-bold text-red-700 bg-red-50 hover:bg-red-100 py-1.5 rounded transition">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </x-tarjeta>
                    @endforeach
                </div>
                <div class="mt-6">{{ $inventarios->links() }}</div>
                @endif
            </div>

    </div>
</x-app-layout>
