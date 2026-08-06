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
                $bloqueado = $evaluacion->estado === 'confirmado' && auth()->user()->esEquipo();
            @endphp

            @if($evaluacion->estado === 'confirmado')
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

                @php
                    // Estado inicial de Alpine: campo => calificación guardada.
                    $inicialCt = collect($ct)->mapWithKeys(fn($c, $campo) => [$campo => (int) ($evaluacion->$campo ?? 0)]);
                    $pesosCt   = collect($ct)->map(fn($c) => $c['peso']);
                @endphp

                <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                         x-data="{ valores: @js($inicialCt), pesos: @js($pesosCt) }">
                    <div class="flex justify-between items-baseline mb-1">
                        <h3 class="text-lg font-bold text-gray-800">Contenido Territorial (CT)</h3>
                        <span class="text-sm font-bold text-indigo-700">
                            Subtotal:
                            <span x-text="Object.entries(valores)
                                .reduce((t, [k, v]) => t + v * pesos[k], 0).toFixed(3)"></span>
                            / 2.000
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">
                        Fuente sugerida: PDOT, PDT, fuentes secundarias y fuentes oficiales
                        públicas. Para elementos culturales y espacios naturales, visitas in
                        situ y documentos públicos.
                    </p>

                    @foreach($ct as $campo => $criterio)
                        <x-criterio-escala :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />
                    @endforeach
                </section>

                @php
                    $inicialUc = collect($uc)->mapWithKeys(fn($c, $campo) => [$campo => (int) ($evaluacion->$campo ?? 0)]);
                    $pesosUc   = collect($uc)->map(fn($c) => $c['peso']);
                @endphp

                <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                         x-data="{ valores: @js($inicialUc), pesos: @js($pesosUc) }">
                    <div class="flex justify-between items-baseline mb-1">
                        <h3 class="text-lg font-bold text-gray-800">Ubicación y Conectividad (UC)</h3>
                        <span class="text-sm font-bold text-indigo-700">
                            Subtotal:
                            <span x-text="Object.entries(valores)
                                .reduce((t, [k, v]) => t + v * pesos[k], 0).toFixed(3)"></span>
                            / 2.000
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mb-4">
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

                        @if(auth()->user()->esJefe())
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
