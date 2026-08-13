<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Mis Zonas Asignadas') }}
        </h2>
    </x-slot>

    {{-- La preferencia vive en 'zonas_vista', clave propia y separada de la
         de Inventario ('inventario_vista'), para que cambiar una no afecte
         a la otra. --}}
    <div class="py-12" x-data="{ vista: localStorage.getItem('zonas_vista') || 'tarjetas' }"
         x-init="$watch('vista', v => localStorage.setItem('zonas_vista', v))">

            @if(session('success'))
            <div class="mb-4 bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded">{{ session('success') }}</div>
            @endif

            @if($zonas->isEmpty())
            {{-- Y nada más: sin zonas no hay cifras que sumar, ni orden que
                 elegir, ni maquetación que conmutar. Un conmutador que no
                 conmuta nada es una pregunta sin respuesta posible. --}}
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <p class="text-sm text-yellow-700">No tienes zonas asignadas actualmente. Contacta al administrador.</p>
            </div>
            @else

            {{-- ═══ SIGUIENTE PASO ═══════════════════════════════════════════════
                 El dashboard como punto de partida, no como índice: arriba, lo
                 siguiente que toca hacer. Con cero actividad todavía, solo se
                 pinta "siguiente sin terminar" -no hay nada que "seguir"-; con
                 todo validado, no se pinta ninguna de las dos. Nunca un panel
                 vacío: si no hay nada que decir, no se dice nada. --}}
            @if($proximoPaso['ultima'] || $proximoPaso['siguiente'])
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                @if($proximoPaso['ultima'])
                <x-tarjeta :padding="false" class="px-6 pt-5 pb-2">
                    <h3 class="text-base font-semibold text-gray-800 mb-1">
                        {{ $proximoPaso['fusionado'] ? 'Sigue por aquí — es donde lo dejaste' : 'Sigue por aquí' }}
                    </h3>
                    <x-fila-matriz :fila="$proximoPaso['ultima']['fila']" :zona="$proximoPaso['ultima']['zona']" />
                </x-tarjeta>
                @endif

                @if($proximoPaso['siguiente'])
                <x-tarjeta :padding="false" class="px-6 pt-5 pb-2">
                    <h3 class="text-base font-semibold text-gray-800 mb-1">
                        {{ $proximoPaso['ultima'] ? 'Todavía sin empezar' : 'Empieza por aquí' }}
                    </h3>
                    <x-fila-matriz :fila="$proximoPaso['siguiente']['fila']" :zona="$proximoPaso['siguiente']['zona']" />
                </x-tarjeta>
                @endif
            </div>
            @endif

            {{-- ═══ CIFRAS DE CONJUNTO ═══════════════════════════════════════════
                 Debajo de lo accionable, no encima: el siguiente paso sigue
                 siendo lo primero que se lee. Y solo con dos o más zonas —con
                 una, la franja repetiría lo que su propia tarjeta ya dice—.

                 Misma rejilla que el panel de administración (grid
                 md:grid-cols-3 sobre <x-tarjeta>): las dos portadas del
                 sistema quedan con la misma forma sin inventar un primitivo
                 nuevo. --}}
            @if($resumen['zonas'] >= 2)
            <div id="zonas-kpis" class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['zonas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Zonas asignadas</h3>
                </x-tarjeta>

                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['validadas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Matrices validadas</h3>
                    <p class="text-sm text-gray-600 mt-2">de {{ $resumen['matrices'] }} en total</p>
                </x-tarjeta>

                <x-tarjeta>
                    <p class="text-3xl font-semibold text-gray-900">{{ $resumen['terminadas'] }}</p>
                    <h3 class="text-lg font-medium text-gray-800 mt-1">Zonas terminadas</h3>
                    <p class="text-sm text-gray-600 mt-2">de {{ $resumen['zonas'] }} asignadas</p>
                </x-tarjeta>
            </div>
            @endif

            <div class="flex justify-end mb-4">
                <x-conmutador-vista modelo="vista" />
            </div>

            {{-- ═══ VISTA LISTA ══════════════════════════════════════════════════
                 Una tabla de verdad, porque es una tabla: cinco columnas de la
                 misma naturaleza para varias zonas. En un teléfono no caben, y
                 por eso el contenedor lleva scroll horizontal en vez de
                 esconderse: elegir maquetación es del usuario, su preferencia
                 se guarda, y la de tarjetas -que es la que viene por defecto-
                 sigue siendo la que mejor funciona ahí. --}}
            <x-tarjeta :padding="false" id="zonas-lista" x-show="vista === 'lista'" x-transition
                 class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <x-cabecera-ordenable columna="nombre" :orden="$orden" :dir="$dir">Zona</x-cabecera-ordenable>
                            <x-cabecera-ordenable columna="lugar" :orden="$orden" :dir="$dir">Lugar</x-cabecera-ordenable>
                            <th scope="col" class="px-6 py-3 text-left text-sm font-medium text-gray-600">Estado</th>
                            <x-cabecera-ordenable columna="progreso" :orden="$orden" :dir="$dir">Progreso</x-cabecera-ordenable>
                            <th scope="col" class="px-6 py-3 text-right text-sm font-medium text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($zonas as $zona)
                            @php $p = $progreso[$zona->id]; @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <p class="text-base text-gray-900">{{ $zona->nombre }}</p>
                                    {{-- Misma descripción que la tarjeta, para que cambiar de
                                         formato no le esconda este dato al usuario. --}}
                                    <p class="text-sm text-gray-600 mt-1 line-clamp-1">
                                        {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
                                    </p>
                                </td>

                                {{-- Sin el 📍 de la tarjeta: aquí el lugar es una
                                     columna con su cabecera, y el emoji repetiría
                                     en cada fila lo que el encabezado ya dice. --}}
                                <td class="px-6 py-4 text-sm text-gray-600">{{ $zona->lugar->nombre }}</td>

                                <td class="px-6 py-4">
                                    <x-desglose-estados :progreso="$p" />
                                </td>

                                <td class="px-6 py-4">
                                    <div class="w-32 h-2 bg-gray-200 rounded-full overflow-hidden">
                                        <div class="h-full bg-green-500 rounded-full"
                                             style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                                    </div>
                                </td>

                                <td class="px-6 py-4">
                                    <div class="flex justify-end gap-2">
                                        <x-boton :href="route('operativo.zona.panel', $zona->id)">
                                            Abrir zona
                                        </x-boton>
                                        <x-boton :href="route('operativo.inventarios.index', $zona->id)" variante="secundario">
                                            Inventario
                                        </x-boton>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </x-tarjeta>

            {{-- ═══ VISTA TARJETAS ═══════════════════════════════════════════════ --}}
            <div id="zonas-tarjetas" x-show="vista === 'tarjetas'" x-transition
                 class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($zonas as $zona)
                <x-tarjeta :padding="false" class="hover:shadow-md transition duration-300">

                    {{-- Imagen de zona --}}
                    @if($zona->imagen_path)
                        <div class="h-40 overflow-hidden rounded-t-xl">
                            <x-foto :ruta="$zona->imagen_path" :alt="$zona->nombre"
                                    class="w-full h-full object-cover" />
                        </div>
                    @else
                        <div class="h-40 bg-gradient-to-br from-blue-100 to-indigo-200 flex items-center justify-center rounded-t-xl">
                            <span class="text-5xl font-black text-blue-300 select-none">
                                {{ strtoupper(substr($zona->nombre, 0, 2)) }}
                            </span>
                        </div>
                    @endif

                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900">{{ $zona->nombre }}</h3>
                        <p class="text-sm text-gray-600 mt-1">📍 {{ $zona->lugar->nombre }}</p>

                        <p class="text-sm text-gray-600 mt-3 line-clamp-2">
                            {{ $zona->descripcion ?? 'Sin descripción disponible.' }}
                        </p>

                        @php $p = $progreso[$zona->id]; @endphp
                        <div class="mt-5">
                            <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                                <div class="h-full bg-green-500 rounded-full"
                                     style="width: {{ $p['total'] > 0 ? round($p['hechas'] / $p['total'] * 100) : 0 }}%"></div>
                            </div>
                            <x-desglose-estados :progreso="$p" class="mt-3" />
                        </div>

                        {{-- Dos botones, no siete. El resto vive dentro de la zona.
                             Inventario se queda por ser lo que más se usa a diario. --}}
                        <div class="flex gap-2 mt-5">
                            <x-boton :href="route('operativo.zona.panel', $zona->id)" class="flex-1 text-center">
                                Abrir zona
                            </x-boton>
                            <x-boton :href="route('operativo.inventarios.index', $zona->id)" variante="secundario">
                                Inventario
                            </x-boton>
                        </div>
                    </div>
                </x-tarjeta>
                @endforeach
            </div>
            @endif

    </div>
</x-app-layout>
