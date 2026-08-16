<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Matriz de Percepción de la Localidad — {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">

            <x-migas :zona="$zona" clave="percepcion" actual="Formulario" />
            <x-pestanas-matriz clave="percepcion" :zona="$zona" activa="formulario" />


            <div class="lg:grid lg:grid-cols-[1fr_256px] lg:gap-6 lg:items-start">
            <div class="lg:min-w-0">

            @if(session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <x-flash-exito />

            <x-franja-matriz :evaluacion="$evaluacion" :niveles="$niveles" />

            <form method="POST" action="{{ route('operativo.evaluacion_percepcion.update', $zona->id) }}" id="form-percepcion">
                @csrf

                @php
                    $esJefe         = auth()->user()->esJefe();
                    $estaConfirmado = $evaluacion->estado === 'confirmado';
                    // Un solo motivo de bloqueo desde que el admin edita: la
                    // matriz está validada y tú no eres quien la valida.
                    $bloqueado      = $estaConfirmado && ! $esJefe;

                    // El índice de la barra lateral: una categoría de
                    // Percepción es 'clave' => ['nombre','peso','items' =>
                    // [campo => etiqueta]] -'items', no 'criterios'-.
                    $indiceBloques = collect($categorias)->map(fn($cat, $codigo) => [
                        'ancla'       => $codigo,
                        'etiqueta'    => $cat['nombre'],
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, array_keys($cat['items'])),
                        'total'       => count($cat['items']),
                    ])->values()->all();

                    $colores = [
                        'DS' => ['bg' => 'bg-blue-50',    'border' => 'border-blue-400',    'title' => 'text-blue-800'],
                        'PL' => ['bg' => 'bg-amber-50',   'border' => 'border-amber-400',   'title' => 'text-amber-800'],
                        'PE' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-400', 'title' => 'text-emerald-800'],
                        'NO' => ['bg' => 'bg-purple-50',  'border' => 'border-purple-400',  'title' => 'text-purple-800'],
                    ];

                    // Un campo sin responder llega como null y así tiene que
                    // quedarse: (int) null lo convertiría en 0, que aquí es
                    // una puntuación real -«Negativo»- y no un hueco.
                    //
                    // old() manda sobre lo guardado: si validar se rechaza
                    // por un criterio que falta, el formulario tiene que
                    // devolver los otros quince tal como estaban, no lo
                    // último que se llegó a guardar.
                    $inicial = fn($items) => collect($items)->mapWithKeys(
                        function ($etiqueta, $campo) use ($evaluacion) {
                            $valor = old($campo, $evaluacion->$campo);

                            return [$campo => $valor === null || $valor === '' ? null : (int) $valor];
                        }
                    );
                @endphp

                <x-tarjeta class="overflow-hidden mb-6">

                    {{-- Categorías --}}
                    @foreach($categorias as $codigo => $cat)
                        @php $c = $colores[$codigo]; @endphp
                        <div id="{{ $codigo }}" class="{{ $c['bg'] }} border-l-4 {{ $c['border'] }} p-5 rounded mb-6"
                             x-data="{
                                valores: @js($inicial($cat['items'])),
                                // Con la categoría a medias no hay media que
                                // enseñar: dividir entre el total daba un
                                // número bajo que se lee como una percepción
                                // negativa cuando lo único que dice es que
                                // falta rellenar. Mismo criterio que FIT.
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
                            <div class="flex flex-wrap justify-between items-baseline gap-3 mb-4 border-b pb-2">
                                <h3 class="font-bold text-lg {{ $c['title'] }}">
                                    {{ $codigo }} — {{ $cat['nombre'] }}
                                </h3>
                                <div class="flex items-baseline gap-4">
                                    <span class="text-sm text-gray-600">
                                        <span x-text="respondidos" class="font-semibold text-gray-700"></span>
                                        de {{ count($cat['items']) }} respondidos
                                    </span>
                                    <span class="text-sm font-bold {{ $c['title'] }}">
                                        Media
                                        <span x-text="promedio === null ? '—' : promedio.toFixed(2)"></span>
                                        <span class="text-gray-400 font-normal">/ 3.00</span>
                                    </span>
                                    <span class="text-sm font-semibold text-gray-600">
                                        Peso: {{ number_format($cat['peso'] * 100, 0) }}%
                                    </span>
                                </div>
                            </div>

                            @foreach($cat['items'] as $campo => $etiqueta)
                                <x-criterio-pildoras
                                    :campo="$campo"
                                    :criterio="['nombre' => $etiqueta, 'niveles' => $niveles]"
                                    :bloqueado="$bloqueado" />
                            @endforeach
                        </div>
                    @endforeach

                    {{-- Acciones de mejora --}}
                    <div class="bg-gray-50 border-l-4 border-gray-400 p-5 rounded mb-6">
                        <label for="acciones_mejora" class="block text-sm font-bold text-gray-700 mb-2">
                            Acciones de Mejora Propuestas
                        </label>
                        <textarea name="acciones_mejora" id="acciones_mejora" rows="4"
                                  class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100"
                                  {{ $bloqueado ? 'disabled' : '' }}
                                  placeholder="Describa las acciones sugeridas a partir de la percepción evaluada...">{{ old('acciones_mejora', $evaluacion->acciones_mejora) }}</textarea>
                    </div>

                    <div class="flex justify-end mt-8 gap-4 pt-4 border-t">
                        @if(!$bloqueado)
                            {{-- El jefe siempre pasa por aquí (nunca está
                                 $bloqueado para él), así que es el único
                                 sitio donde puede pulsar "Guardar Borrador"
                                 sobre una matriz ya validada sin saber que
                                 la reabre. --}}
                            @if($estaConfirmado && $esJefe)
                                <x-aviso-reapertura class="w-full mb-1" />
                            @endif
                            <x-boton variante="secundario" tamano="grande" name="accion_estado" value="borrador">
                                Guardar Borrador
                            </x-boton>

                            @if($esJefe)
                                <x-boton tamano="grande" name="accion_estado" value="confirmado"
                                        onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta matriz.')">
                                    Validar y Finalizar
                                </x-boton>
                            @endif
                        @endif
                    </div>
                </x-tarjeta>
            </form>

            </div>{{-- /lg:min-w-0 --}}

            <x-barra-lateral-formulario clave="percepcion" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-percepcion" />

            </div>{{-- /lg:grid --}}
    </div>
</x-app-layout>
