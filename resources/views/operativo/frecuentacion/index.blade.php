<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Frecuentación Turística: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <x-pestanas-matriz clave="frecuentacion" :zona="$zona" activa="formulario" />

            <a href="{{ route('operativo.zona.panel', $zona->id) }}"
               class="inline-block px-5 py-2 mb-4 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 shadow-md">
                ← Volver a la zona
            </a>

            <x-flash-exito />

            {{-- El formulario de la Superficie Territorial y la lista de
                 sitios llegan en la Tarea 4. --}}

        </div>
    </div>
</x-app-layout>
