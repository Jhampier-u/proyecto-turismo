<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ __('Panel de administración') }}
        </h2>
    </x-slot>

    <div class="py-10">

            {{-- Lo único de este panel que pide actuar. Va arriba y solo aparece
                 cuando hay algo que hacer: un aviso permanente deja de leerse. --}}
            @if($resumen['zonasSinJefe'] > 0)
                <div class="mb-6 bg-amber-50 border-l-4 border-amber-500 text-amber-900 p-4 rounded">
                    <p class="text-base">
                        Hay <strong>{{ $resumen['zonasSinJefe'] }} sin jefe asignado</strong>.
                        Sin jefe de zona nadie puede validar sus matrices, así que se quedan
                        en borrador indefinidamente.
                    </p>
                    <a href="{{ route('admin.zonas.index') }}"
                       class="inline-block mt-2 text-base font-medium text-amber-900 underline">
                        Ver las zonas
                    </a>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['usuarios'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Usuarios</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        {{ $resumen['jefes'] }} jefes de zona · {{ $resumen['equipo'] }} en equipos
                    </p>
                    <a href="{{ route('admin.users.index') }}"
                       class="inline-block mt-4 text-base font-medium text-indigo-700 hover:underline">
                        Gestionar →
                    </a>
                </x-tarjeta>

                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['lugares'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Lugares</h3>
                    <p class="text-sm text-gray-600 mt-2">{{ $resumen['lugares'] }} lugares en el catálogo</p>
                    <a href="{{ route('admin.lugares.index') }}"
                       class="inline-block mt-4 text-base font-medium text-indigo-700 hover:underline">
                        Gestionar →
                    </a>
                </x-tarjeta>

                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['zonas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Zonas</h3>
                    <p class="text-sm text-gray-600 mt-2">
                        @if($resumen['zonasSinJefe'] > 0)
                            {{ $resumen['zonasSinJefe'] }} sin jefe asignado
                        @else
                            Todas con jefe asignado
                        @endif
                    </p>
                    <a href="{{ route('admin.zonas.index') }}"
                       class="inline-block mt-4 text-base font-medium text-indigo-700 hover:underline">
                        Gestionar →
                    </a>
                </x-tarjeta>
            </div>

    </div>
</x-app-layout>
