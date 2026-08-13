<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Evaluación FIT: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">

            <x-migas :zona="$zona" clave="fit" actual="Formulario" />
            <x-pestanas-matriz clave="fit" :zona="$zona" activa="formulario" />


            @php
                $esJefe = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // Un solo motivo de bloqueo desde que el admin edita: la
                // matriz está validada y tú no eres quien la valida.
                $bloqueado = $estaConfirmado && ! $esJefe;

                // El índice de la barra lateral: un bloque de FIT es
                // 'clave' => ['nombre','peso','criterios' => [campo => ...]],
                // así que basta con array_keys(criterios) y el recuento
                // hermano de criteriosRespondidos() para el subconjunto.
                $indiceBloques = collect($bloques)->map(fn($bloque, $clave) => [
                    'ancla'       => $clave,
                    'etiqueta'    => $bloque['nombre'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($bloque['criterios'])),
                    'total'       => count($bloque['criterios']),
                ])->values()->all();
            @endphp

            <div class="lg:grid lg:grid-cols-[1fr_256px] lg:gap-6 lg:items-start">
            <div class="lg:min-w-0">

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
                        <strong class="font-bold text-lg">✓ Evaluación Validada</strong>
                        <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                    </div>
                </div>
            </div>
            @else
            <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                <strong class="font-bold">Modo Borrador</strong>
                <p>Los datos ingresados son preliminares. El Jefe de Zona debe revisar y confirmar para generar los resultados oficiales.</p>
            </div>
            @endif

            <x-leyenda-escala :niveles="$niveles" />

            <x-flash-exito />

            <form method="POST" action="{{ route('operativo.evaluacion_fit.update', $zona->id) }}" id="form-fit">
                @csrf

                @php
                    // Un campo sin responder llega como null y así tiene que
                    // quedarse: (int) null lo convertiría en 0, que aquí es
                    // una puntuación real -«Nulo»- y no un hueco.
                    //
                    // old() manda sobre lo guardado: si validar se rechaza
                    // por un criterio que falta, el formulario tiene que
                    // devolver los otros diecisiete tal como estaban, no lo
                    // último que se llegó a guardar.
                    $inicial = fn($criterios) => collect($criterios)->mapWithKeys(
                        function ($c, $campo) use ($evaluacion) {
                            $valor = old($campo, $evaluacion->$campo);

                            return [$campo => $valor === null || $valor === '' ? null : (int) $valor];
                        }
                    );
                @endphp

                @foreach($bloques as $clave => $bloque)
                    <x-tarjeta id="{{ $clave }}" class="mb-6"
                             x-data="{
                                // @js() no se compila dentro de un atributo de
                                // <x-componente> -la etiqueta se procesa antes que
                                // las @directivas, así que queda literal-; Js::from()
                                // es lo mismo que usa @js() por debajo.
                                valores: {{ Illuminate\Support\Js::from($inicial($bloque['criterios'])) }},
                                // Con el bloque a medias no hay media que
                                // enseñar: dividir entre el total daba un
                                // número bajo que se lee como una nota mala
                                // cuando lo único que dice es que falta
                                // rellenar. Mismo criterio que en resultados.
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
                    </x-tarjeta>
                @endforeach

                <div class="flex justify-end mt-8 gap-4 pt-4 border-t">
                    @if(!$bloqueado)
                    {{-- El jefe siempre pasa por aquí (nunca está $bloqueado
                         para él), así que es el único sitio donde puede
                         pulsar "Guardar Borrador" sobre una matriz ya
                         validada sin saber que la reabre. --}}
                    @if($estaConfirmado && $esJefe)
                        <x-aviso-reapertura class="w-full mb-1" />
                    @endif
                    <x-boton variante="secundario" tamano="grande" name="accion_estado" value="borrador">
                        Guardar Borrador
                    </x-boton>

                    @if($esJefe)
                    <x-boton tamano="grande" name="accion_estado" value="confirmado"
                        onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta evaluación.')">
                        Validar y Finalizar
                    </x-boton>
                    @endif
                    @else
                    <span class="text-gray-500 italic self-center"><x-aviso-bloqueo-matriz sustantivo="evaluación" /></span>
                    @if($esJefe)
                    <x-boton tamano="grande" name="accion_estado" value="confirmado">
                        Actualizar Datos
                    </x-boton>
                    @endif
                    @endif
                </div>
            </form>

            </div>{{-- /lg:min-w-0 --}}

            <x-barra-lateral-formulario clave="fit" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-fit" />

            </div>{{-- /lg:grid --}}
    </div>
</x-app-layout>
