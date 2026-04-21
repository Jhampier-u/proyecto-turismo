<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Matriz de Percepción de la Localidad — {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ url()->previous() }}"
                class="inline-flex items-center px-4 py-2 mb-4 bg-blue-300 hover:bg-blue-500 text-black-700 font-bold rounded-lg shadow-sm">
                Regresar
            </a>

            @if(session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('operativo.evaluacion_percepcion.update', $zona->id) }}">
                @csrf

                @php
                    $esJefe         = auth()->user()->role_id == 2;
                    $estaConfirmado = $evaluacion->estado === 'confirmado';
                    $bloqueado      = $estaConfirmado && !$esJefe;

                    $colores = [
                        'DS' => ['bg' => 'bg-blue-50',    'border' => 'border-blue-400',    'title' => 'text-blue-800'],
                        'PL' => ['bg' => 'bg-amber-50',   'border' => 'border-amber-400',   'title' => 'text-amber-800'],
                        'PE' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-400', 'title' => 'text-emerald-800'],
                        'NO' => ['bg' => 'bg-purple-50',  'border' => 'border-purple-400',  'title' => 'text-purple-800'],
                    ];
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">

                    @if($estaConfirmado)
                        <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                            <div class="flex justify-between items-center">
                                <div>
                                    <strong class="font-bold text-lg">✓ Matriz Validada</strong>
                                    <p>Esta matriz ha sido confirmada por el Jefe de Zona.</p>
                                </div>
                                <a href="{{ route('operativo.evaluacion_percepcion.ponderacion', $zona->id) }}"
                                   class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700">
                                    Ver Resultados
                                </a>
                            </div>
                        </div>
                    @else
                        <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                            <strong class="font-bold">Modo Borrador</strong>
                            <p>Los datos ingresados son preliminares. El Jefe de Zona debe revisar y confirmar para generar los resultados oficiales.</p>
                        </div>
                    @endif

                    <div class="mb-6 bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 text-sm">
                        <p class="font-bold mb-1">Instrucciones — Escala Valorativa</p>
                        <p>Evalúe la percepción de la localidad en cada atributo, según la siguiente escala:</p>
                        <ul class="list-disc ml-6 mt-1">
                            <li><b>1 — Negativo</b></li>
                            <li><b>2 — Neutral</b></li>
                            <li><b>3 — Positivo</b></li>
                        </ul>
                    </div>

                    {{-- Categorías --}}
                    @foreach($categorias as $codigo => $cat)
                        @php $c = $colores[$codigo]; @endphp
                        <div class="{{ $c['bg'] }} border-l-4 {{ $c['border'] }} p-5 rounded mb-6">
                            <div class="flex items-baseline justify-between mb-4 border-b pb-2">
                                <h3 class="font-bold text-lg {{ $c['title'] }}">
                                    {{ $codigo }} — {{ $cat['nombre'] }}
                                </h3>
                                <span class="text-xs font-semibold text-gray-600 uppercase">
                                    Peso: {{ number_format($cat['peso'] * 100, 0) }}%
                                </span>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($cat['items'] as $campo => $etiqueta)
                                    <div class="bg-white p-3 rounded border">
                                        <x-select-percepcion
                                            :label="$etiqueta"
                                            :name="$campo"
                                            :val="$evaluacion->$campo"
                                            :disabled="$bloqueado" />
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach

                    {{-- Acciones de mejora --}}
                    <div class="bg-gray-50 border-l-4 border-gray-400 p-5 rounded mb-6">
                        <label for="acciones_mejora" class="block text-sm font-bold text-gray-700 mb-2">
                            Acciones de Mejora Propuestas
                        </label>
                        <textarea name="acciones_mejora" id="acciones_mejora" rows="4"
                                  class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100"
                                  {{ $bloqueado ? 'disabled' : '' }}
                                  placeholder="Describa las acciones sugeridas a partir de la percepción evaluada...">{{ old('acciones_mejora', $evaluacion->acciones_mejora) }}</textarea>
                    </div>

                    <div class="flex justify-end mt-8 gap-4 pt-4 border-t">
                        @if(!$bloqueado)
                            <button type="submit" name="accion_estado" value="borrador"
                                    class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                                Guardar Borrador
                            </button>

                            @if($esJefe)
                                <button type="submit" name="accion_estado" value="confirmado"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg transform hover:scale-105 transition"
                                        onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta matriz.')">
                                    Validar y Finalizar
                                </button>
                            @endif
                        @else
                            <span class="text-gray-500 italic self-center">Solo el Jefe de Zona puede reabrir o editar una matriz validada.</span>
                            @if($esJefe)
                                <button type="submit" name="accion_estado" value="confirmado"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                                    Actualizar Datos
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
