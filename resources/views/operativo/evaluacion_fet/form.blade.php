<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Evaluación FET: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ url()->previous()}}"
                class="inline-flex items-center px-4 py-2 mb-4 bg-blue-300 hover:bg-blue-500 text-black-700 font-bold rounded-lg shadow-sm">
                Regresar
            </a>

            @php
                $esJefe = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // Dos motivos de bloqueo, no uno: el admin nunca edita
                // evaluaciones -esté en borrador o no-, y el equipo deja de
                // poder hacerlo en cuanto se confirma. Separados en dos
                // variables porque cada uno tiene su propia frase en
                // x-aviso-bloqueo-matriz; sin esto, el admin sobre un
                // borrador vería el motivo del equipo, que no es el suyo.
                $bloqueadoPorRol = ! auth()->user()->puedeEditarEvaluaciones();
                $bloqueado = $bloqueadoPorRol || ($estaConfirmado && !$esJefe);
            @endphp

            @if($estaConfirmado)
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                <div class="flex justify-between items-center">
                    <div>
                        <strong class="font-bold text-lg">✓ Evaluación FET Validada</strong>
                        <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                    </div>

                    <a href="{{ route('operativo.evaluacion_fet.ponderacion', $zona->id) }}"
                        class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700">
                        Ver Tabla de Ponderación
                    </a>
                </div>
            </div>
            @else
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                <strong class="font-bold">Modo Borrador</strong>
                <p>Los datos ingresados son preliminares.</p>
            </div>
            @endif

            <x-leyenda-escala :niveles="$niveles" />

            <x-flash-exito />

            <form method="POST" action="{{ route('operativo.evaluacion_fet.update', $zona->id) }}">
                @csrf

                @php
                    // Ver evaluacion_fit/form.blade.php: mismo criterio para
                    // el null de "sin responder" y para que old() mande sobre
                    // lo guardado tras un error de validación.
                    $inicial = fn($criterios) => collect($criterios)->mapWithKeys(
                        function ($c, $campo) use ($evaluacion) {
                            $valor = old($campo, $evaluacion->$campo);

                            return [$campo => $valor === null || $valor === '' ? null : (int) $valor];
                        }
                    );
                @endphp

                @foreach($bloques as $clave => $bloque)
                    <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                             x-data="{
                                valores: @js($inicial($bloque['criterios'])),
                                get promedio() {
                                    const v = Object.values(this.valores);
                                    return v.some(x => x === null)
                                        ? null
                                        : v.reduce((t, x) => t + x, 0) / v.length;
                                },
                                get respondidos() {
                                    return Object.values(this.valores).filter(v => v !== null).length;
                                },
                             }">
                        <div class="flex flex-wrap justify-between items-baseline gap-3 mb-2">
                            <h3 class="text-xl font-bold text-gray-900">
                                {{ $bloque['nombre'] }}
                                <span class="text-base font-normal text-gray-400">({{ strtoupper($clave) }})</span>
                            </h3>
                            <div class="flex items-baseline gap-4">
                                <span class="text-sm text-gray-500">
                                    <span x-text="respondidos" class="font-semibold text-gray-700"></span>
                                    de {{ count($bloque['criterios']) }} respondidos
                                </span>
                                <span class="text-base font-bold text-indigo-700">
                                    Media
                                    <span x-text="promedio === null ? '—' : promedio.toFixed(2)"></span>
                                    <span class="text-gray-400 font-normal">/ 3.00</span>
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mb-5">
                            Pesa un {{ rtrim(rtrim(number_format($bloque['peso'] * 100, 1), '0'), '.') }}% del resultado final.
                        </p>

                        @foreach($bloque['criterios'] as $campo => $criterio)
                            <x-criterio-pildoras :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />
                        @endforeach
                    </section>
                @endforeach

                <div class="flex justify-end mt-8 gap-4 pt-4 border-t">
                    @if(!$bloqueado)
                    <button type="submit" name="accion_estado" value="borrador" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                        Guardar Borrador
                    </button>

                    @if($esJefe)
                    <button type="submit" name="accion_estado" value="confirmado"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded shadow-lg transform hover:scale-105 transition"
                        onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta evaluación.')">
                        Validar y Finalizar FET
                    </button>
                    @endif
                    @else
                    <span class="text-gray-500 italic self-center"><x-aviso-bloqueo-matriz :por-rol="$bloqueadoPorRol" sustantivo="evaluación" /></span>
                    @endif
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
