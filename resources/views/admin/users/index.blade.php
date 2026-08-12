<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Gestión de Usuarios') }}
            </h2>
            <x-boton :href="route('admin.users.create')">
                + Nuevo Usuario
            </x-boton>
        </div>
    </x-slot>

    <div class="py-12">

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">¡Éxito!</strong>
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Error:</strong>
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            <x-tarjeta :padding="false" class="overflow-hidden">
                <div class="p-6 text-gray-900">

                    <form method="GET" action="{{ route('admin.users.index') }}"
                          class="flex flex-wrap gap-3 mb-6">
                        <input type="search" name="buscar" value="{{ $buscar }}"
                               placeholder="Buscar por nombre o correo"
                               class="flex-1 min-w-64 text-base border-gray-300 rounded-lg shadow-sm
                                      focus:ring-indigo-500 focus:border-indigo-500">

                        <select name="rol"
                                class="text-base border-gray-300 rounded-lg shadow-sm
                                       focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Todos los roles</option>
                            @foreach($roles as $unRol)
                                <option value="{{ $unRol->id }}" @selected((string) $rol === (string) $unRol->id)>
                                    {{ ucfirst(str_replace('_', ' ', $unRol->nombre)) }}
                                </option>
                            @endforeach
                        </select>

                        <x-boton>
                            Buscar
                        </x-boton>

                        @if($buscar !== '' || $rol)
                            <x-boton :href="route('admin.users.index')" variante="secundario">
                                Limpiar
                            </x-boton>
                        @endif
                    </form>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Nombre</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Email</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Rol</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Teléfono</th>
                                    <th class="px-6 py-3 text-left text-sm font-medium text-gray-600">Zonas</th>
                                    <th class="px-6 py-3 text-right text-sm font-medium text-gray-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach ($users as $user)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-base font-medium text-gray-900">{{ $user->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-base text-gray-600">{{ $user->email }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($user->esAdmin())
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Admin</span>
                                        @elseif($user->esJefe())
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800">Jefe Zona</span>
                                        @elseif($user->esEquipo())
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Equipo</span>
                                        @else
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Sin Rol</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-base text-gray-600">
                                        {{ $user->telefono ?? '—' }}
                                    </td>

                                    <td class="px-6 py-4">
                                        @php
                                            // Un usuario es jefe de unas zonas o miembro del equipo de
                                            // otras, nunca las dos cosas por rol, pero se juntan por si
                                            // alguna vez cambia de rol conservando asignaciones.
                                            $suyas = $user->zonasComoJefe->merge($user->zonasComoEquipo);
                                        @endphp

                                        @if($suyas->isEmpty())
                                            <span class="text-sm text-gray-400">Sin zonas</span>
                                        @else
                                            <div class="flex flex-wrap gap-1">
                                                @foreach($suyas as $zona)
                                                    <span class="text-sm bg-gray-100 text-gray-700 px-2 py-1 rounded">
                                                        {{ $zona->nombre }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex justify-end items-center gap-2">
                                            
                                            <a href="{{ route('admin.users.edit', $user) }}" class="bg-indigo-100 text-indigo-700 hover:bg-indigo-200 py-1 px-3 rounded text-sm font-semibold transition">
                                                Editar
                                            </a>

                                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST"
                                                  class="js-eliminar-usuario" data-nombre="{{ $user->name }}">
                                                @csrf
                                                @method('DELETE')
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
                    </div>

                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>

                </div>
            </x-tarjeta>
    </div>

    {{-- El nombre viaja en un data-* y se lee vía dataset, nunca interpolado
         dentro de JavaScript: así un nombre con comillas no puede ejecutar código. --}}
    <script>
        document.querySelectorAll('form.js-eliminar-usuario').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                var nombre = form.dataset.nombre || 'este usuario';
                if (! confirm('¿Estás seguro de que deseas eliminar a ' + nombre + '? Esta acción no se puede deshacer.')) {
                    e.preventDefault();
                }
            });
        });
    </script>
</x-app-layout>