<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded text-base">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded text-base">
                    {{ session('error') }}
                </div>
            @endif

            @if($estado->papel() === 'admin')
                <div class="mb-6 bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 rounded-lg text-base">
                    Modo consulta — puedes ver los resultados, no modificarlos.
                </div>
            @endif

            <div class="bg-white shadow-sm rounded-xl border border-gray-200 mb-6 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-base text-gray-600">📍 {{ $zona->lugar->nombre }}</p>
                        <p class="text-sm text-gray-500 mt-1">
                            Jefe de zona: {{ $zona->jefe->name ?? 'sin asignar' }}
                        </p>
                    </div>

                    @php
                        $etiquetaPapel = [
                            'admin'  => ['texto' => 'Administración', 'clase' => 'bg-blue-100 text-blue-800'],
                            'jefe'   => ['texto' => 'Jefe de zona',   'clase' => 'bg-purple-100 text-purple-800'],
                            'equipo' => ['texto' => 'Equipo',         'clase' => 'bg-green-100 text-green-800'],
                        ][$estado->papel()];
                    @endphp
                    <span class="shrink-0 text-sm font-medium px-3 py-1 rounded-full {{ $etiquetaPapel['clase'] }}">
                        {{ $etiquetaPapel['texto'] }} · {{ auth()->user()->name }}
                    </span>
                </div>

                @php
                    $total   = $estado->totalMatrices();
                    $hechas  = $estado->validadas();
                    $porcien = $total > 0 ? round($hechas / $total * 100) : 0;
                @endphp
                <div class="flex items-center gap-4 mt-6">
                    <div class="flex-1 h-2 bg-gray-200 rounded-full overflow-hidden">
                        <div class="h-full bg-green-500 rounded-full" style="width: {{ $porcien }}%"></div>
                    </div>
                    <span class="text-sm text-gray-600 whitespace-nowrap">
                        {{ $hechas }} de {{ $total }} validadas
                    </span>
                </div>
            </div>

            @foreach($estado->grupos() as $grupo)
                <div class="bg-white shadow-sm rounded-xl border border-gray-200 mb-6 px-6 pt-5 pb-2">
                    <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $grupo['titulo'] }}</h3>

                    @foreach($grupo['filas'] as $fila)
                        <x-fila-matriz :fila="$fila" />
                    @endforeach
                </div>
            @endforeach

            <div class="flex justify-end">
                <a href="{{ $estado->papel() === 'admin' ? route('admin.zonas.index') : route('operativo.dashboard') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white
                          text-base text-gray-700 hover:bg-gray-50 shadow-sm">
                    ← Volver
                </a>
            </div>

        </div>
    </div>
</x-app-layout>
