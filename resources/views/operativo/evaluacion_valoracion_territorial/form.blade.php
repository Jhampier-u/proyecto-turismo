<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Valoración Territorial: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">

            <x-migas :zona="$zona" clave="valoracion_territorial" actual="Formulario" />
            <x-pestanas-matriz clave="valoracion_territorial" :zona="$zona" activa="formulario" />


            @php
                $esJefe         = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // Un solo motivo de bloqueo desde que el admin edita: la
                // matriz está validada y tú no eres quien la valida.
                $bloqueado      = $estaConfirmado && ! $esJefe;

                // El índice de la barra lateral: CT y UC son mapas planos
                // campo => criterio, sin ningún agrupador -los títulos
                // "RTT"/"UC" están escritos a mano aquí, no en la clase-,
                // así que el índice tiene exactamente dos entradas fijas.
                $indiceBloques = [
                    [
                        'ancla'       => 'rtt',
                        'etiqueta'    => 'Recursos Turísticos (RTT)',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($ct)),
                        'total'       => count($ct),
                    ],
                    [
                        'ancla'       => 'uc',
                        'etiqueta'    => 'Ubicación y Conectividad (UC)',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($uc)),
                        'total'       => count($uc),
                    ],
                ];
            @endphp

            <div class="lg:grid lg:grid-cols-[1fr_256px] lg:gap-6 lg:items-start">
            <div class="lg:min-w-0">

            <x-franja-matriz :evaluacion="$evaluacion"
                             :niveles="[0 => 'Desfavorable', 1 => 'Parcial', 2 => 'Favorable']" />

            <x-flash-exito />


            <form method="POST" action="{{ route('operativo.evaluacion_valoracion_territorial.update', $zona->id) }}" id="form-valoracion-territorial">
                @csrf

                @php
                    // Nada preseleccionado: con todo en 0 se podía enviar el
                    // formulario sin leer un solo criterio y quedaba como una
                    // valoración válida de puros ceros. Un campo sin responder
                    // llega como null y así se queda: (int) null lo volvería 0,
                    // que aquí es una puntuación real y no un hueco.
                    // old() manda sobre lo guardado: si validar se rechaza por
                    // un criterio que falta, el formulario tiene que devolver
                    // los otros veinte tal como estaban, no lo último que se
                    // llegó a guardar.
                    $inicial = fn($grupo) => collect($grupo)->mapWithKeys(
                        function ($c, $campo) use ($evaluacion) {
                            $valor = old($campo, $evaluacion->$campo);

                            return [$campo => $valor === null || $valor === '' ? null : (int) $valor];
                        }
                    );
                    $pesos = fn($grupo) => collect($grupo)->map(fn($c) => $c['peso']);
                @endphp

                <x-tarjeta id="rtt" class="mb-6"
                         x-data="{
                            // @js() no se compila dentro de un atributo de
                            // <x-componente> -la etiqueta se procesa antes que
                            // las @directivas, así que queda literal-; Js::from()
                            // es lo mismo que usa @js() por debajo.
                            valores: {{ Illuminate\Support\Js::from($inicial($ct)) }},
                            pesos: {{ Illuminate\Support\Js::from($pesos($ct)) }},
                            // Con la dimensión a medias no hay subtotal que
                            // enseñar: contar los huecos como 0 daba una cifra
                            // baja que se lee como el resultado y solo dice que
                            // falta rellenar. Mismo criterio que en resultados.
                            get subtotal() {
                                const entradas = Object.entries(this.valores);
                                return entradas.some(([, v]) => v === null)
                                    ? null
                                    : entradas.reduce((t, [k, v]) => t + v * this.pesos[k], 0);
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
                                <span x-text="subtotal === null ? '—' : subtotal.toFixed(3)"></span>
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
                </x-tarjeta>

                <x-tarjeta id="uc" class="mb-6"
                         x-data="{
                            // @js() no se compila dentro de un atributo de
                            // <x-componente> -la etiqueta se procesa antes que
                            // las @directivas, así que queda literal-; Js::from()
                            // es lo mismo que usa @js() por debajo.
                            valores: {{ Illuminate\Support\Js::from($inicial($uc)) }},
                            pesos: {{ Illuminate\Support\Js::from($pesos($uc)) }},
                            // Con la dimensión a medias no hay subtotal que
                            // enseñar: contar los huecos como 0 daba una cifra
                            // baja que se lee como el resultado y solo dice que
                            // falta rellenar. Mismo criterio que en resultados.
                            get subtotal() {
                                const entradas = Object.entries(this.valores);
                                return entradas.some(([, v]) => v === null)
                                    ? null
                                    : entradas.reduce((t, [k, v]) => t + v * this.pesos[k], 0);
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
                                <span x-text="subtotal === null ? '—' : subtotal.toFixed(3)"></span>
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
                </x-tarjeta>

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

            <x-barra-lateral-formulario clave="valoracion_territorial" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-valoracion-territorial" />

            </div>{{-- /lg:grid --}}
    </div>
</x-app-layout>
