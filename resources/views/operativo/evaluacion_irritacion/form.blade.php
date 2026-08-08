<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Índice de Irritación Turística — {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ url()->previous() }}"
                class="inline-flex items-center px-4 py-2 mb-4 bg-blue-300 hover:bg-blue-500 text-black-700 font-bold rounded-lg shadow-sm">
                Regresar
            </a>

            @php
                $esJefe         = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // El admin nunca edita evaluaciones, aunque estén en borrador:
                // sin este predicado, $bloqueado solo miraba la confirmación y
                // el admin vería el formulario abierto de par en par.
                $bloqueado      = ! auth()->user()->puedeEditarEvaluaciones() || ($estaConfirmado && !$esJefe);
            @endphp

            @if($estaConfirmado)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong class="font-bold text-lg">✓ Índice de Irritación Validado</strong>
                            <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                        </div>
                        <a href="{{ route('operativo.evaluacion_irritacion.ponderacion', $zona->id) }}"
                            class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700">
                            Ver Resultados
                        </a>
                    </div>
                </div>
            @else
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    <strong class="font-bold">Modo Borrador</strong>
                    <p>Los datos ingresados son preliminares.</p>
                </div>
            @endif

            <x-flash-exito />

            {{-- La escala es inversa: hay que decirlo aquí y no solo en el
                 desplegable, porque quien rellena esto por primera vez espera
                 que 10 sea "lo mejor", como en el resto de matrices. --}}
            <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 text-sm rounded">
                <p class="font-bold mb-1">Escala inversa</p>
                <p>
                    0 es el mejor resultado posible y 10 la irritación crítica. Sin pesos: cada
                    bloque promedia por igual sus seis atributos.
                </p>
            </div>

            <form method="POST" action="{{ route('operativo.evaluacion_irritacion.update', $zona->id) }}">
                @csrf

                <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 border-l-4 border-sky-400">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Encuesta a visitantes</h3>
                    <p class="text-sm text-gray-500 mb-5">Percepción de quien visita el destino.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($visitantes as $campo)
                            <x-select-0-10
                                :label="$etiquetas[$campo]"
                                :name="$campo"
                                :val="$evaluacion->$campo"
                                :disabled="$bloqueado" />
                        @endforeach
                    </div>
                </section>

                <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6 border-l-4 border-amber-400">
                    <h3 class="text-xl font-bold text-gray-900 mb-1">Encuesta a residentes</h3>
                    <p class="text-sm text-gray-500 mb-5">Percepción de la localidad receptora.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($residentes as $campo)
                            <x-select-0-10
                                :label="$etiquetas[$campo]"
                                :name="$campo"
                                :val="$evaluacion->$campo"
                                :disabled="$bloqueado" />
                        @endforeach
                    </div>
                </section>

                @unless($bloqueado)
                    <div class="flex justify-end gap-3">
                        <button type="submit"
                                class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-2 px-5 rounded shadow">
                            Guardar Borrador
                        </button>

                        @if($esJefe)
                            <button type="submit" name="accion_estado" value="confirmado"
                                    class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow"
                                    onclick="return confirm('Al validar, la evaluación queda cerrada para el equipo. ¿Continuar?');">
                                Validar y Finalizar
                            </button>
                        @endif
                    </div>
                @else
                    <p class="text-gray-500 italic text-right">
                        Solo el Jefe de Zona puede reabrir o editar una matriz validada.
                    </p>
                @endunless
            </form>
        </div>
    </div>
</x-app-layout>
