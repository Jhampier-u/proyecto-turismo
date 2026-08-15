<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-2xl text-gray-800 leading-tight">
            {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-10">

            <x-migas :zona="$zona" />

            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-800 p-4 rounded text-base">
                    {{ session('success') }}
                </div>
            @endif
            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-500 text-red-800 p-4 rounded text-base">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ═══ DOS COLUMNAS ═════════════════════════════════════════════════
                 El panel lateral va PRIMERO en el DOM -320px, fijo- y la columna
                 principal después -1fr-: mismo mecanismo que ya usan los ocho
                 formularios de matriz (lg:grid lg:grid-cols-[1fr_256px]), con el
                 aside al revés porque aquí es la columna principal la que crece.

                 Por debajo de lg no hay grid: las dos partes se apilan en el
                 mismo orden del DOM, que es el que la página ya tenía en una
                 sola columna -el panel de identidad, primero-. No hace falta
                 esconder nada en móvil: a diferencia de
                 <x-barra-lateral-formulario>, cuyo botón de guardar está
                 duplicado más abajo en el formulario, aquí no hay ningún otro
                 sitio de la pantalla que repita esta información. --}}
            <div class="lg:grid lg:grid-cols-[320px_1fr] lg:gap-8 lg:items-start">

                <aside id="zona-panel-lateral" class="mb-6 lg:mb-0 lg:sticky lg:top-6 lg:self-start">
                    <x-tarjeta>
                        @php
                            // 'equipo' va en teal, no en verde: verde es el
                            // color de <x-badge estado="validada">
                            // -EstadoZona::ESTILOS_ESTADO-, y las dos
                            // píldoras conviven en esta misma tarjeta desde
                            // que el progreso pasó a <x-desglose-estados>.
                            $etiquetaPapel = [
                                'admin'  => ['texto' => 'Administración', 'clase' => 'bg-blue-100 text-blue-800'],
                                'jefe'   => ['texto' => 'Jefe de zona',   'clase' => 'bg-purple-100 text-purple-800'],
                                'equipo' => ['texto' => 'Equipo',         'clase' => 'bg-teal-100 text-teal-800'],
                            ][$estado->papel()];
                        @endphp
                        <span class="inline-flex text-sm font-medium px-3 py-1 rounded-full {{ $etiquetaPapel['clase'] }}">
                            {{ $etiquetaPapel['texto'] }} · {{ auth()->user()->name }}
                        </span>

                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="text-gray-500">Lugar</dt>
                                <dd class="text-gray-800 mt-0.5">📍 {{ $zona->lugar->nombre }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">Jefe de zona</dt>
                                <dd class="text-gray-800 mt-0.5">{{ $zona->jefe->name ?? 'Sin asignar' }}</dd>
                            </div>
                        </dl>

                        {{-- El desglose reemplaza la fracción «X de Y
                             validadas» de antes, igual que ya hizo en el
                             dashboard: sigue llevando la misma barra de
                             porcentaje, y debajo las insignias que reparten
                             validadas/borrador/sin empezar. --}}
                        @php $desglose = $estado->desglose(); @endphp
                        <div class="mt-5 pt-5 border-t border-gray-200">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $desglose['total'] > 0 ? round($desglose['hechas'] / $desglose['total'] * 100) : 0 }}%"></div>
                            </div>
                            <x-desglose-estados :progreso="$desglose" class="mt-3" />
                        </div>
                    </x-tarjeta>
                </aside>

                <div id="zona-panel-matrices" class="lg:min-w-0">
                    @foreach($estado->grupos() as $grupo)
                        <x-tarjeta :padding="false" class="mb-6 px-6 pt-5 pb-2">
                            <h3 class="text-lg font-semibold text-gray-800 mb-1">{{ $grupo['titulo'] }}</h3>

                            @foreach($grupo['filas'] as $fila)
                                <x-fila-matriz :fila="$fila" />
                            @endforeach
                        </x-tarjeta>
                    @endforeach
                </div>

            </div>

    </div>
</x-app-layout>
