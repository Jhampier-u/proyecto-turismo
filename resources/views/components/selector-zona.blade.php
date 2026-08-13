@props(['zonas'])

{{--
    Saltar de zona sin subir a «Mis Zonas».

    El árbol tiene tres niveles y el salto que de verdad se repite es el
    primero: estás dentro de una matriz de una zona y quieres la misma matriz de
    otra. Antes había que subir dos niveles y volver a bajar dos.

    Es lo que sustituye al `Cmd+K` que la Fase 0 dejó aplazado. Una paleta de
    comandos resuelve saltos arbitrarios en un árbol ancho, y este no lo es
    -diez matrices por zona y tres niveles-; además esto funciona en móvil,
    donde un atajo de teclado no sirve de nada.

    Reusa <x-dropdown>, el mismo que el menú de usuario. Un segundo desplegable
    escrito a mano sería un segundo sistema para lo mismo.

    Este es el desplegable de ESCRITORIO. El menú móvil ofrece el mismo salto
    con el marcado de sus enlaces, y los dos recorren la lista que decide
    `navigation.blade.php` -quién lo ve y qué zonas ofrece se resuelve allí, una
    sola vez, porque si no las dos versiones pueden divergir-.

    Sin zonas no se pinta nada: un selector vacío es peor que ausente, porque
    promete una navegación que no existe.
--}}

@if($zonas->isNotEmpty())
    {{-- Solo `ms-6`: el grupo que lo envuelve ya es `hidden sm:flex
         sm:items-center`, y repetirlo aquí escondía que el reparto de la barra
         se decide un nivel más arriba. --}}
    <div id="selector-zona" class="ms-6">
        <x-dropdown align="left" width="48">
            <x-slot name="trigger">
                <button class="inline-flex items-center gap-2 px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition">
                    <x-icono nombre="lista" class="w-4 h-4" />
                    Cambiar de zona
                </button>
            </x-slot>

            <x-slot name="content">
                @foreach($zonas as $zonaDelSelector)
                    <x-dropdown-link :href="route('operativo.zona.panel', $zonaDelSelector->id)">
                        {{ $zonaDelSelector->nombre }}
                    </x-dropdown-link>
                @endforeach
            </x-slot>
        </x-dropdown>
    </div>
@endif
