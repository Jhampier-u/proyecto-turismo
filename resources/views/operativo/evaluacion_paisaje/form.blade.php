<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Análisis y Valoración del Paisaje: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">

            <x-migas :zona="$zona" clave="paisaje" actual="Formulario" />
            <x-pestanas-matriz clave="paisaje" :zona="$zona" activa="formulario" />


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

                // El índice de la barra lateral: una categoría de Paisaje es
                // 'clave' => ['nombre','peso','criterios' => [campo => ...]],
                // igual forma que FIT/FET.
                $indiceBloques = collect($categorias)->map(fn($categoria, $clave) => [
                    'ancla'       => $clave,
                    'etiqueta'    => $categoria['nombre'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($categoria['criterios'])),
                    'total'       => count($categoria['criterios']),
                ])->values()->all();
            @endphp

            <div class="lg:grid lg:grid-cols-[1fr_256px] lg:gap-6 lg:items-start">
            <div class="lg:min-w-0">

            <x-franja-matriz :evaluacion="$evaluacion"
                             :niveles="[0 => 'Desfavorable', 3 => 'Intermedio', 5 => 'Favorable']" />

            <x-tarjeta class="mb-6">
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
            </x-tarjeta>

            <x-flash-exito />


            <form method="POST" action="{{ route('operativo.evaluacion_paisaje.update', $zona->id) }}" id="form-paisaje">
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

                    <x-tarjeta id="{{ $clave }}" class="mb-6"
                             x-data="{
                                // @js() no se compila dentro de un atributo de
                                // <x-componente> -la etiqueta se procesa antes que
                                // las @directivas, así que queda literal-; Js::from()
                                // es lo mismo que usa @js() por debajo.
                                valores: {{ Illuminate\Support\Js::from($inicial) }},
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
                    </x-tarjeta>
                @endforeach

                @unless($bloqueado)
                    {{-- El jefe siempre pasa por aquí (nunca está $bloqueado
                         para él), así que es el único sitio donde puede
                         pulsar "Guardar Borrador" sobre una matriz ya
                         validada sin saber que la reabre. --}}
                    @if($estaConfirmado && $esJefe)
                        <x-aviso-reapertura class="mb-3" />
                    @endif
                    <div class="flex justify-end gap-3">
                        <x-boton variante="secundario">
                            Guardar Borrador
                        </x-boton>

                        @if($esJefe)
                            <x-boton name="accion_estado" value="confirmado"
                                     onclick="return confirm('Al validar, la evaluación queda cerrada para el equipo. ¿Continuar?');">
                                Validar y Finalizar
                            </x-boton>
                        @endif
                    </div>
                @endunless
            </form>

            </div>{{-- /lg:min-w-0 --}}

            <x-barra-lateral-formulario clave="paisaje" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-paisaje" />

            </div>{{-- /lg:grid --}}
    </div>
</x-app-layout>
