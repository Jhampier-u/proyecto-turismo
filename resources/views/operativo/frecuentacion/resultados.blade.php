<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Frecuentación Turística — Resultados: {{ $zona->nombre }}
        </h2>
    </x-slot>

    @if(! $completa)
        <x-matriz-sin-resultados
            nombre="Frecuentación turística"
            :zona="$zona"
            ruta-formulario="operativo.frecuentacion.index" />
    @else
        <div class="py-12">
            <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

                <x-pestanas-matriz clave="frecuentacion" :zona="$zona" activa="resultados" />

                {{-- La tabla de ÍETP por sitio y el ÍEFT del territorio
                     llegan en la Tarea 5. --}}

            </div>
        </div>
    @endif
</x-app-layout>
