<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Índice de Concentración Turística — {{ $zona->nombre }}
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
                // sin este predicado, $bloqueado solo miraría la confirmación
                // y el admin vería el formulario abierto de par en par.
                $bloqueado      = ! auth()->user()->puedeEditarEvaluaciones() || ($estaConfirmado && !$esJefe);
            @endphp

            @if($estaConfirmado)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong class="font-bold text-lg">✓ Índice de Concentración Validado</strong>
                            <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                        </div>
                        <a href="{{ route('operativo.evaluacion_concentracion.ponderacion', $zona->id) }}"
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

            {{--
                Esqueleto de la Tarea 3: todavía no pinta los 113 campos de
                conteo -dos secciones, atractivos y planta turística, cada una
                con un campo numérico por subtipo- porque eso es la Tarea 4.
                Esta vista solo tiene que existir y responder: sin ella,
                EvaluacionConcentracionController::edit() no tendría qué
                devolver, y RegistroMatricesTest recorre la ruta 'editar' de
                cada entrada del registro por HTTP.
            --}}
            <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 text-sm rounded">
                <p class="font-bold mb-1">Formulario en construcción</p>
                <p>
                    Los campos de conteo de atractivos y planta turística —{{ count(\App\Matrices\Concentracion::campos()) }} en
                    total— llegan en un paso posterior.
                </p>
            </div>

            <form method="POST" action="{{ route('operativo.evaluacion_concentracion.update', $zona->id) }}">
                @csrf

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
