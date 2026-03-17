<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Zonas Asignadas') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">{{ session('success') }}</div>
            @endif

            @if($zonas->isEmpty())
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-700">No tienes zonas asignadas actualmente. Contacta al administrador.</p>
            </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($zonas as $zona)
                <div class="bg-white shadow-sm sm:rounded-lg hover:shadow-md transition duration-300 border border-gray-100">

                    {{-- Imagen de zona --}}
                    @if($zona->imagen_path)
                        <div class="h-40 overflow-hidden rounded-t-lg">
                            <img src="{{ asset('storage/' . $zona->imagen_path) }}"
                                 class="w-full h-full object-cover" alt="{{ $zona->nombre }}">
                        </div>
                    @else
                        <div class="h-40 bg-gradient-to-br from-blue-100 to-indigo-200 flex items-center justify-center rounded-t-lg">
                            <span class="text-5xl font-black text-blue-300 select-none">
                                {{ strtoupper(substr($zona->nombre, 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <div class="p-6">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-xl font-bold text-gray-800">{{ $zona->nombre }}</h3>
                                <p class="text-sm text-gray-500 mt-1">📍 {{ $zona->lugar->nombre }}</p>
                            </div>
                            <span class="bg-blue-100 text-blue-800 text-xs font-semibold px-2.5 py-0.5 rounded">#{{ $zona->id }}</span>
                        </div>

                        <p class="text-gray-600 mt-3 text-sm line-clamp-3">
                            {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
                        </p>

                        <div class="mt-5 flex justify-between items-center border-t pt-4">
                            <div class="text-xs text-gray-400">
                                Recursos: {{ $zona->inventarios()->count() }}
                            </div>

                            <div class="flex items-center gap-2 flex-wrap justify-end">
                                <a href="{{ route('operativo.inventarios.index', $zona->id) }}"
                                   class="inline-flex items-center px-2 py-1.5 bg-green-200 border border-transparent rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest hover:bg-green-300">
                                    Inventario
                                </a>

                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.away="open = false" type="button"
                                            class="inline-flex items-center px-2 py-1.5 bg-indigo-500 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-600">
                                        Evaluación
                                        <svg class="ml-1 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    <div x-show="open" class="absolute right-0 z-50 mt-2 w-52 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 origin-top-right" style="display:none;">
                                        <div class="py-1">
                                            <a href="{{ route('operativo.evaluacion_fit.edit', $zona->id) }}"
                                               class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100">Factores Intrínsecos (FIT)</a>
                                            <a href="{{ route('operativo.evaluacion_fet.edit', $zona->id) }}"
                                               class="block px-4 py-2 text-xs text-gray-700 hover:bg-gray-100">Factores Extrínsecos (FET)</a>
                                            <a href="{{ route('operativo.evaluacion_potencialidad.edit', $zona->id) }}"
                                               class="block px-4 py-2 text-xs text-purple-700 hover:bg-purple-50 font-semibold border-t border-gray-100 mt-1 pt-2">⭐ Potencialidad Turística</a>
                                        </div>
                                    </div>
                                </div>

                                <a href="{{ route('operativo.vtt.final', $zona->id) }}"
                                   class="inline-flex items-center px-2 py-1.5 bg-blue-700 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-800">
                                    Vocación
                                </a>

                                {{-- Botón Potencialidad --}}
                                @php
                                    $eval = $evaluaciones[$zona->id] ?? null;
                                    $evalConfirmada = $eval && $eval->estado === 'confirmado';
                                @endphp
                                <a href="{{ $eval
                                    ? route('operativo.evaluacion_potencialidad.ponderacion', $zona->id)
                                    : route('operativo.evaluacion_potencialidad.edit', $zona->id) }}"
                                   class="inline-flex items-center gap-1 px-2 py-1.5 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest transition
                                       {{ $evalConfirmada ? 'bg-purple-600 hover:bg-purple-700' : ($eval ? 'bg-purple-400 hover:bg-purple-500' : 'bg-gray-400 hover:bg-gray-500') }}"
                                   title="{{ $evalConfirmada ? 'Ver resultados de Potencialidad' : ($eval ? 'Evaluación en borrador — continuar' : 'Evaluación no realizada — comenzar') }}">
                                    ⭐
                                    {{ $evalConfirmada ? 'Potencialidad' : ($eval ? 'En progreso' : 'Sin evaluar') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
