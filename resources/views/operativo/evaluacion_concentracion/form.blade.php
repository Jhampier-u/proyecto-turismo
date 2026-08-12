<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Índice de Concentración Turística — {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">

            <x-pestanas-matriz clave="concentracion" :zona="$zona" activa="formulario" />

            <x-boton-volver :zona="$zona" texto="Regresar"
                class="!inline-flex !items-center !px-4 !py-2 !mb-4 !bg-blue-300 hover:!bg-blue-500 !text-black !font-bold !rounded-lg !shadow-sm" />

            @php
                $esJefe         = auth()->user()->esJefe();
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                // Un solo motivo de bloqueo desde que el admin edita: la
                // matriz está validada y tú no eres quien la valida.
                $bloqueado      = $estaConfirmado && ! $esJefe;

                // Total de campos por sección, derivado de las mismas
                // estructuras que ya recorre el formulario más abajo -no un
                // 77 y un 36 sueltos que alguien tendría que corregir a mano
                // si el instrumento ganara o perdiera un subtipo-.
                $totalAtractivos = array_sum(array_map(
                    fn(array $tipos) => array_sum(array_map('count', $tipos)),
                    $atractivos
                ));
                $totalPlanta = array_sum(array_map('count', $planta));

                // El índice de la barra lateral se queda en los DOS grupos
                // de primer nivel -Atractivos y Planta-, no en cada
                // categoría/tipo/sector: con 113 campos en más de una
                // docena de subgrupos, indexarlos todos saturaría una barra
                // de 256px. Arr::flatten() no sirve aquí -aplanado a fondo
                // colapsa hasta las etiquetas y las claves de campo no
                // sobreviven-, así que los nombres de campo se recogen con
                // un bucle explícito, uno por cada forma de anidado.
                //
                // El TOTAL reutiliza $totalAtractivos/$totalPlanta -ya
                // calculados arriba para el contador en vivo de cada
                // sección-, en vez de recalcularlo con count(): un solo
                // sitio fija ese número, no dos que pudieran discreparse el
                // día que el instrumento cambie de forma.
                $camposAtractivos = [];
                foreach ($atractivos as $tipos) {
                    foreach ($tipos as $campos) {
                        $camposAtractivos = array_merge($camposAtractivos, array_keys($campos));
                    }
                }

                $camposPlanta = [];
                foreach ($planta as $campos) {
                    $camposPlanta = array_merge($camposPlanta, array_keys($campos));
                }

                $indiceBloques = [
                    [
                        'ancla'       => 'atractivos',
                        'etiqueta'    => 'Atractivos turísticos',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $camposAtractivos),
                        'total'       => $totalAtractivos,
                    ],
                    [
                        'ancla'       => 'planta',
                        'etiqueta'    => 'Planta turística',
                        'respondidos' => \App\Servicios\EstadoZona::criteriosRespondidosDe($evaluacion, $camposPlanta),
                        'total'       => $totalPlanta,
                    ],
                ];
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
                            <strong class="font-bold text-lg">✓ Índice de Concentración Validado</strong>
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

            {{-- Cada frase en una sola línea a propósito: Blade respeta los
                 saltos de línea del propio fichero, así que una frase que
                 aquí ocupara dos líneas no la encontraría un assertSee() que
                 buscara la frase entera. --}}
            <div class="mb-6 bg-indigo-50 border-l-4 border-indigo-500 text-indigo-800 p-4 text-sm rounded">
                <p class="font-bold mb-1">Esto no puntúa: cuenta</p>
                <p>Cada campo es una cantidad de atractivos o de establecimientos, sin tope ni desplegable: el 0 es un dato -"no hay ninguno"-, la casilla vacía es "todavía no lo he contado".</p>
            </div>

            @php
                // Color de borde por sección, no por clasificación: es
                // decorativo, solo para orientarse entre las dos partes del
                // instrumento -77 campos de atractivos y 36 de planta-. Un
                // color completo por clave, igual que $bordesPorBloque en
                // evaluacion_irritacion/form.blade.php, no una clase
                // construida por interpolación.
                $colorSeccion = [
                    'atractivos' => 'border-emerald-500',
                    'planta'     => 'border-blue-500',
                ];
            @endphp

            <form method="POST" action="{{ route('operativo.evaluacion_concentracion.update', $zona->id) }}" id="form-concentracion">
                @csrf

                {{-- ═══════════════════ ATRACTIVOS TURÍSTICOS (77) ═══════════════════ --}}
                <x-tarjeta id="atractivos" class="mb-6 border-l-4 {{ $colorSeccion['atractivos'] }}">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-1">
                        <h3 class="text-xl font-bold text-gray-900">Atractivos Turísticos</h3>
                        {{-- "Respondido" incluye el 0 -significa "no hay
                             ninguno", es un dato-, distinto de la casilla
                             vacía: el JavaScript de más abajo cuenta así, no
                             como "valor mayor que cero". --}}
                        <span class="text-sm font-semibold text-emerald-700">
                            Respondidos: <span id="contador-atractivos">0</span> / {{ $totalAtractivos }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mb-5">Manifestaciones culturales y atractivos naturales, dos tablas paralelas del instrumento.</p>

                    @foreach($atractivos as $categoria => $tipos)
                        <h4 class="text-base font-bold text-gray-800 mt-6 mb-3 border-b border-gray-200 pb-1">{{ $categoria }}</h4>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                            @foreach($tipos as $tipo => $campos)
                                {{-- El id del grupo sale del primer campo del
                                     tipo, no de un slug del nombre: un campo
                                     ya es único en las 113 columnas, así que
                                     no hace falta derivar ni comprobar otra
                                     cosa. --}}
                                <x-tarjeta-grupo-concentracion
                                    :titulo="$tipo"
                                    :campos="$campos"
                                    :grupo-id="'gr-at-' . array_key_first($campos)"
                                    seccion="atractivos"
                                    :evaluacion="$evaluacion"
                                    :disabled="$bloqueado"
                                    color-texto="text-emerald-700" />
                            @endforeach
                        </div>
                    @endforeach
                </x-tarjeta>

                {{-- ═══════════════════ PLANTA TURÍSTICA (36) ═══════════════════ --}}
                <x-tarjeta id="planta" class="mb-6 border-l-4 {{ $colorSeccion['planta'] }}">
                    <div class="flex flex-wrap justify-between items-center gap-2 mb-1">
                        <h3 class="text-xl font-bold text-gray-900">Planta Turística</h3>
                        <span class="text-sm font-semibold text-blue-700">
                            Respondidos: <span id="contador-planta">0</span> / {{ $totalPlanta }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mb-5">Diez sectores de establecimientos, cada uno con su propio subtotal.</p>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        @foreach($planta as $sector => $campos)
                            <x-tarjeta-grupo-concentracion
                                :titulo="$sector"
                                :campos="$campos"
                                :grupo-id="'gr-pt-' . array_key_first($campos)"
                                seccion="planta"
                                :evaluacion="$evaluacion"
                                :disabled="$bloqueado"
                                color-texto="text-blue-700" />
                        @endforeach
                    </div>
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
                @else
                    <p class="text-gray-500 italic text-right">
                        <x-aviso-bloqueo-matriz />
                    </p>
                @endunless
            </form>

            </div>{{-- /lg:min-w-0 --}}

            <x-barra-lateral-formulario clave="concentracion" :zona="$zona" :secciones="$indiceBloques" :bloqueado="$bloqueado" formulario="form-concentracion" />

            </div>{{-- /lg:grid --}}
    </div>

    <script>
        // Subtotales por tipo/sector y contador de respondidos por sección,
        // en vivo y sin dependencias -como el resto del proyecto-: con 113
        // campos, el evaluador necesita saber si va bien sin enviar el
        // formulario para averiguarlo.
        (function () {
            var form = document.getElementById('form-concentracion');
            if (!form) {
                return;
            }

            var campos = Array.prototype.slice.call(form.querySelectorAll('input[data-grupo]'));

            function recalcular() {
                var subtotales  = {};
                var respondidos = {};

                campos.forEach(function (campo) {
                    var grupo   = campo.dataset.grupo;
                    var seccion = campo.dataset.seccion;
                    var valor   = campo.value;

                    // Un hueco vacío suma 0 al subtotal -no hay nada que
                    // contar todavía-, pero NO cuenta como respondido: eso
                    // es justo lo que lo distingue de un 0 tecleado.
                    subtotales[grupo] = (subtotales[grupo] || 0) + (valor === '' ? 0 : (parseInt(valor, 10) || 0));

                    if (valor !== '') {
                        respondidos[seccion] = (respondidos[seccion] || 0) + 1;
                    }
                });

                Object.keys(subtotales).forEach(function (grupo) {
                    var etiqueta = document.getElementById('subtotal-' + grupo);
                    if (etiqueta) {
                        etiqueta.textContent = subtotales[grupo];
                    }
                });

                ['atractivos', 'planta'].forEach(function (seccion) {
                    var contador = document.getElementById('contador-' + seccion);
                    if (contador) {
                        contador.textContent = respondidos[seccion] || 0;
                    }
                });
            }

            form.addEventListener('input', recalcular);

            // Recalcula también al cargar la página: un borrador ya
            // guardado tiene que mostrar sus subtotales y su contador
            // reales desde el primer pintado, no un 0 hasta la primera
            // tecla.
            recalcular();
        })();
    </script>
</x-app-layout>
