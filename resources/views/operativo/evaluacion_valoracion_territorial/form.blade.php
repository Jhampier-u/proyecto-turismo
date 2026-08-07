<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Valoración Territorial: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ url()->previous() }}"
                class="inline-flex items-center px-4 py-2 mb-4 bg-blue-300 hover:bg-blue-500 text-black-700 font-bold rounded-lg shadow-sm">
                Regresar
            </a>

            @php
                $esJefe         = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                $bloqueado      = $estaConfirmado && !$esJefe;
            @endphp

            @if($estaConfirmado)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong class="font-bold text-lg">✓ Valoración Territorial Validada</strong>
                            <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                        </div>

                        <a href="{{ route('operativo.evaluacion_valoracion_territorial.ponderacion', $zona->id) }}"
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

            <form method="POST" action="{{ route('operativo.evaluacion_valoracion_territorial.update', $zona->id) }}">
                @csrf

                <x-leyenda-escala />

                @php
                    // En una evaluación nueva no se pre-selecciona nada: con todo
                    // en 0 se podía enviar el formulario sin leer un solo criterio
                    // y quedaba como una valoración válida de puros ceros. Al
                    // dejarlos vacíos, la regla `required` obliga a responder.
                    $esNueva = ! $evaluacion->exists;

                    $inicial = fn($grupo) => collect($grupo)->mapWithKeys(
                        fn($c, $campo) => [$campo => $esNueva ? null : (int) $evaluacion->$campo]
                    );
                    $pesos = fn($grupo) => collect($grupo)->map(fn($c) => $c['peso']);
                @endphp

                <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                         x-data="{
                            valores: @js($inicial($ct)),
                            pesos: @js($pesos($ct)),
                            get subtotal() {
                                return Object.entries(this.valores)
                                    .reduce((t, [k, v]) => t + (v ?? 0) * this.pesos[k], 0);
                            },
                            get respondidos() {
                                return Object.values(this.valores).filter(v => v !== null).length;
                            },
                         }">
                    <div class="flex flex-wrap justify-between items-baseline gap-3 mb-2">
                        <h3 class="text-xl font-bold text-gray-900">Contenido Territorial (CT)</h3>
                        <div class="flex items-baseline gap-4">
                            <span class="text-sm text-gray-500">
                                <span x-text="respondidos" class="font-semibold text-gray-700"></span>
                                de {{ count($ct) }} respondidos
                            </span>
                            <span class="text-base font-bold text-indigo-700">
                                Subtotal
                                <span x-text="subtotal.toFixed(3)"></span>
                                <span class="text-gray-400 font-normal">/ 2.000</span>
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-5 leading-relaxed">
                        Fuente sugerida: PDOT, PDT, fuentes secundarias y fuentes oficiales
                        públicas. Para elementos culturales y espacios naturales, visitas in
                        situ y documentos públicos.
                    </p>

                    @foreach($ct as $campo => $criterio)
                        <x-criterio-escala :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />
                    @endforeach
                </section>

                <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                         x-data="{
                            valores: @js($inicial($uc)),
                            pesos: @js($pesos($uc)),
                            get subtotal() {
                                return Object.entries(this.valores)
                                    .reduce((t, [k, v]) => t + (v ?? 0) * this.pesos[k], 0);
                            },
                            get respondidos() {
                                return Object.values(this.valores).filter(v => v !== null).length;
                            },
                         }">
                    <div class="flex flex-wrap justify-between items-baseline gap-3 mb-2">
                        <h3 class="text-xl font-bold text-gray-900">Ubicación y Conectividad (UC)</h3>
                        <div class="flex items-baseline gap-4">
                            <span class="text-sm text-gray-500">
                                <span x-text="respondidos" class="font-semibold text-gray-700"></span>
                                de {{ count($uc) }} respondidos
                            </span>
                            <span class="text-base font-bold text-indigo-700">
                                Subtotal
                                <span x-text="subtotal.toFixed(3)"></span>
                                <span class="text-gray-400 font-normal">/ 2.000</span>
                            </span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-500 mb-5 leading-relaxed">
                        Fuente sugerida: PDOT, fuentes de información primaria y secundaria,
                        visitas in situ y documentos oficiales.
                    </p>

                    @foreach($uc as $campo => $criterio)
                        <x-criterio-escala :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />
                    @endforeach
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
                @endunless
            </form>
        </div>
    </div>
</x-app-layout>
