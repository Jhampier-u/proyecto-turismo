{{--
    Saltar de zona sin subir a «Mis Zonas».

    El árbol tiene tres niveles y el salto que de verdad se repite es el
    primero: estás dentro de una matriz de una zona y quieres la misma matriz de
    otra. Antes había que subir dos niveles y volver a bajar dos.

    Es lo que sustituye al `Cmd+K` que la Fase 0 dejó aplazado. Una paleta de
    comandos resuelve saltos arbitrarios en un árbol ancho, y este no lo es
    -diez matrices por zona y tres niveles-; además esto funciona en móvil,
    donde un atajo de teclado no sirve de nada.

    «Mis zonas» es la UNIÓN de las dos relaciones -las que uno dirige y las que
    tiene asignadas como equipo-, no solo las primeras: mirando solo
    zonasComoJefe, el equipo vería un selector vacío teniendo zonas.

    Reusa <x-dropdown>, el mismo que el menú de usuario. Un segundo desplegable
    escrito a mano sería un segundo sistema para lo mismo.
--}}

@php
    $usuario = auth()->user();

    $zonasDelSelector = $usuario->zonasComoJefe
        ->merge($usuario->zonasComoEquipo)
        ->unique('id')
        ->sortBy('nombre');
@endphp

{{--
    Dos motivos para no pintarse, y los dos son «aquí no sirve de nada»:

    - Sin zonas. Un selector vacío es peor que ausente: promete una navegación
      que no existe.
    - En «Mis Zonas». Esa página ES la lista de zonas, así que el desplegable
      ofrecería en pequeño lo que la pantalla ya enseña entero. El selector
      existe para saltar cuando estás DENTRO de una zona, que es donde la lista
      no está a la vista.
--}}
@if($zonasDelSelector->isNotEmpty() && ! request()->routeIs('operativo.dashboard'))
    <div id="selector-zona" class="hidden sm:flex sm:items-center sm:ms-6">
        <x-dropdown align="left" width="48">
            <x-slot name="trigger">
                <button class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                    <x-icono nombre="lista" class="w-4 h-4" />
                    Cambiar de zona
                </button>
            </x-slot>

            <x-slot name="content">
                @foreach($zonasDelSelector as $zonaDelSelector)
                    <x-dropdown-link :href="route('operativo.zona.panel', $zonaDelSelector->id)">
                        {{ $zonaDelSelector->nombre }}
                    </x-dropdown-link>
                @endforeach
            </x-slot>
        </x-dropdown>
    </div>
@endif
