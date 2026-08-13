<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Índice de Irritación Turística — {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">

            <x-migas :zona="$zona" clave="irritacion" actual="Formulario" />
            <x-pestanas-matriz clave="irritacion" :zona="$zona" activa="formulario" />


            @php
                $esJefe         = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // Un solo motivo de bloqueo desde que el admin edita: la
                // matriz está validada y tú no eres quien la valida.
                $bloqueado      = $estaConfirmado && ! $esJefe;

                // El índice de la barra lateral: un bloque de Irritación es
                // 'clave' => ['titulo','subtitulo','campos' => [nombre, ...]]
                // -'campos' ya es la lista plana, sin array_keys()-.
                $indiceBloques = collect($bloques)->map(fn($bloque, $clave) => [
                    'ancla'       => $clave,
                    'etiqueta'    => $bloque['titulo'],
                    'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $bloque['campos']),
                    'total'       => count($bloque['campos']),
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
                            <strong class="font-bold text-lg">✓ Índice de Irritación Validado</strong>
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

            <x-flash-exito />

            {{-- La escala es inversa: hay que decirlo aquí y no solo en el
                 desplegable, porque quien viene de rellenar Paisaje trae la
                 escala al revés en la cabeza y puntuaría los doce atributos
                 invertidos si solo se fiara de la memoria. --}}
            <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 text-sm rounded">
                <p class="font-bold mb-1">Escala inversa: cuanto más alto, peor</p>
                <p class="mb-3">
                    0 es el mejor resultado posible y 10 la irritación crítica. Sin pesos: cada
                    bloque promedia por igual sus seis atributos.
                </p>
                {{-- Los tres tramos y su rango viven en el instrumento
                     (Irritacion::TRAMOS) y llegan aquí como $tramos, ya
                     resueltos por el controlador: son los mismos que
                     clasifican la tabla de resultados, y duplicarlos dejaría
                     dos sitios que corregir el día que el instrumento cambie
                     uno. El color es aparte, en
                     <x-insignia-clasificacion-irritacion>. --}}
                <div class="flex flex-wrap gap-2">
                    @foreach($tramos as $clasificacion => $tramo)
                        <x-insignia-clasificacion-irritacion :valor="$clasificacion">{{ $tramo['rango'] }}: {{ $clasificacion }}</x-insignia-clasificacion-irritacion>
                    @endforeach
                </div>
            </div>

            @php
                // El color de borde identifica el bloque, no una clasificación:
                // es una decisión de esta vista, así que vive aquí y no en
                // Irritacion::BLOQUES junto al resto de app/Matrices, que ya
                // no tiene ninguna clase de Tailwind.
                $bordesPorBloque = [
                    'visitantes' => 'border-sky-400',
                    'residentes' => 'border-amber-400',
                ];
            @endphp

            <form method="POST" action="{{ route('operativo.evaluacion_irritacion.update', $zona->id) }}" id="form-irritacion">
                @csrf

                {{-- Un solo bucle sobre $bloques (Irritacion::BLOQUES, que pasa
                     el controlador) para las dos secciones: antes cada una se
                     desenrollaba a mano y un bloque nuevo —o un título
                     corregido— había que tocarlo dos veces sin que nada
                     avisara si se olvidaba una. --}}
                @foreach($bloques as $clave => $bloque)
                    <x-tarjeta id="{{ $clave }}" class="mb-6 border-l-4 {{ $bordesPorBloque[$clave] }}">
                        <h3 class="text-xl font-bold text-gray-900 mb-1">{{ $bloque['titulo'] }}</h3>
                        <p class="text-sm text-gray-500 mb-5">{{ $bloque['subtitulo'] }}</p>

                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            @foreach($bloque['campos'] as $campo)
                                <x-select-irritacion
                                    :label="$etiquetas[$campo]"
                                    :name="$campo"
                                    :val="$evaluacion->$campo"
                                    :disabled="$bloqueado" />
                            @endforeach
                        </div>
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
                @else
                    <p class="text-gray-500 italic text-right">
                        <x-aviso-bloqueo-matriz />
                    </p>
                @endunless
            </form>

            </div>{{-- /lg:min-w-0 --}}

            <x-barra-lateral-formulario clave="irritacion" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-irritacion" />

            </div>{{-- /lg:grid --}}
    </div>
</x-app-layout>
