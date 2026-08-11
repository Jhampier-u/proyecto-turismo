<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Análisis y Valoración del Paisaje: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">

            <x-pestanas-matriz clave="paisaje" :zona="$zona" activa="formulario" />

            <x-boton-volver texto="Regresar" class="mb-4" />

            @php
                $esJefe         = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // Un solo motivo de bloqueo desde que el admin edita: la
                // matriz está validada y tú no eres quien la valida.
                $bloqueado      = $estaConfirmado && ! $esJefe;

                // Cabecera del instrumento, derivada de la propia zona.
                $lugar     = $zona->lugar;
                $provincia = $lugar?->provincia;
                $region    = $provincia?->region;
            @endphp

            @if($evaluacion?->exists && $evaluacion->user)
                <p class="text-sm text-gray-500 mb-4">
                    Última edición: {{ $evaluacion->user->name }},
                    {{ $evaluacion->updated_at->diffForHumans() }}
                </p>
            @endif

            @if($estaConfirmado)
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                    <div class="flex justify-between items-center">
                        <div>
                            <strong class="font-bold text-lg">✓ Matriz de Paisaje Validada</strong>
                            <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                        </div>
                    </div>
                </div>
            @else
                <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                    <strong class="font-bold">Modo Borrador</strong>
                    <p>Los datos ingresados son preliminares.</p>
                </div>
            @endif

            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <dl class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Región geográfica</dt>
                        <dd class="font-semibold text-gray-900">{{ $region?->nombre ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Provincia</dt>
                        <dd class="font-semibold text-gray-900">{{ $provincia?->nombre ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Lugar</dt>
                        <dd class="font-semibold text-gray-900">{{ $lugar?->nombre ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Sitio / Territorio</dt>
                        <dd class="font-semibold text-gray-900">{{ $zona->nombre }}</dd>
                    </div>
                </dl>
            </div>

            <x-leyenda-escala :niveles="[0 => 'Desfavorable', 3 => 'Intermedio', 5 => 'Favorable']" />

            <x-flash-exito />


            <form method="POST" action="{{ route('operativo.evaluacion_paisaje.update', $zona->id) }}">
                @csrf

                @foreach($categorias as $clave => $categoria)
                    @php
                        // Un campo sin responder llega como null y así tiene que
                        // quedarse: (int) null lo convertiría en 0, que aquí es
                        // una puntuación real —«sin gestión»— y no un hueco. Ya
                        // no hace falta distinguir la evaluación nueva: una
                        // recién creada tiene todos los campos en null igual.
                        //
                        // old() manda sobre lo guardado: si validar se rechaza
                        // por un criterio que falta, el formulario tiene que
                        // devolver los otros treinta y tres tal como estaban, no
                        // lo último que se llegó a guardar.
                        $inicial = collect($categoria['criterios'])->mapWithKeys(
                            function ($c, $campo) use ($evaluacion) {
                                $valor = old($campo, $evaluacion->$campo);

                                return [$campo => $valor === null || $valor === '' ? null : (int) $valor];
                            }
                        );
                    @endphp

                    <section class="bg-white shadow-sm sm:rounded-lg p-6 mb-6"
                             x-data="{
                                valores: @js($inicial),
                                // Con la categoría a medias no hay promedio que
                                // enseñar: dividir entre el total daba un número
                                // bajo que se lee como una nota mala cuando lo
                                // único que dice es que falta rellenar. Es el
                                // mismo criterio que en los resultados.
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
                                {{ $categoria['nombre'] }}
                                <span class="text-base font-normal text-gray-400">({{ strtoupper($clave) }})</span>
                            </h3>
                            <div class="flex items-baseline gap-4">
                                <span class="text-sm text-gray-500">
                                    <span x-text="respondidos" class="font-semibold text-gray-700"></span>
                                    de {{ count($categoria['criterios']) }} respondidos
                                </span>
                                <span class="text-base font-bold text-indigo-700">
                                    Promedio
                                    <span x-text="promedio === null ? '—' : promedio.toFixed(2)"></span>
                                    <span class="text-gray-400 font-normal">/ 5.00</span>
                                </span>
                            </div>
                        </div>
                        <p class="text-sm text-gray-500 mb-5">
                            Pesa un {{ rtrim(rtrim(number_format($categoria['peso'] * 100, 1), '0'), '.') }}% del resultado final.
                        </p>

                        @foreach($categoria['criterios'] as $campo => $criterio)
                            <x-criterio-pildoras :campo="$campo" :criterio="$criterio" :bloqueado="$bloqueado" />
                        @endforeach
                    </section>
                @endforeach

                @unless($bloqueado)
                    {{-- El jefe siempre pasa por aquí (nunca está $bloqueado
                         para él), así que es el único sitio donde puede
                         pulsar "Guardar Borrador" sobre una matriz ya
                         validada sin saber que la reabre. --}}
                    @if($estaConfirmado && $esJefe)
                        <p class="text-sm text-amber-700 mb-3">
                            <strong>Esta matriz está validada.</strong>
                            Guardarla la devolverá a borrador y habrá que validarla de nuevo.
                        </p>
                    @endif
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
