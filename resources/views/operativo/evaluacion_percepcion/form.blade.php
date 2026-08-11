<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Matriz de Percepción de la Localidad — {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <x-pestanas-matriz clave="percepcion" :zona="$zona" activa="formulario" />

            <a href="{{ url()->previous() }}"
                class="inline-flex items-center px-4 py-2 mb-4 bg-blue-300 hover:bg-blue-500 text-black-700 font-bold rounded-lg shadow-sm">
                Regresar
            </a>

            @if(session('error'))
                <div class="mb-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    {{ session('error') }}
                </div>
            @endif

            <x-flash-exito />


            <form method="POST" action="{{ route('operativo.evaluacion_percepcion.update', $zona->id) }}">
                @csrf

                @php
                    $esJefe         = auth()->user()->esJefe();
                    $estaConfirmado = $evaluacion->estado === 'confirmado';
                    // Un solo motivo de bloqueo desde que el admin edita: la
                    // matriz está validada y tú no eres quien la valida.
                    $bloqueado      = $estaConfirmado && ! $esJefe;

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

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">

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
                                    <strong class="font-bold text-lg">✓ Matriz Validada</strong>
                                    <p>Esta matriz ha sido confirmada por el Jefe de Zona.</p>
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

                    {{-- Categorías --}}
                    @foreach($categorias as $codigo => $cat)
                        @php $c = $colores[$codigo]; @endphp
                        <div class="{{ $c['bg'] }} border-l-4 {{ $c['border'] }} p-5 rounded mb-6"
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
                                <p class="w-full text-sm text-amber-700 mb-1">
                                    <strong>Esta matriz está validada.</strong>
                                    Guardarla la devolverá a borrador y habrá que validarla de nuevo.
                                </p>
                            @endif
                            <button type="submit" name="accion_estado" value="borrador"
                                    class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                                Guardar Borrador
                            </button>

                            @if($esJefe)
                                <button type="submit" name="accion_estado" value="confirmado"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg transform hover:scale-105 transition"
                                        onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta matriz.')">
                                    Validar y Finalizar
                                </button>
                            @endif
                        @else
                            <span class="text-gray-500 italic self-center"><x-aviso-bloqueo-matriz /></span>
                            @if($esJefe)
                                <button type="submit" name="accion_estado" value="confirmado"
                                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                                    Actualizar Datos
                                </button>
                            @endif
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
