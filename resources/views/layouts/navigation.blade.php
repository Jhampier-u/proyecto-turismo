@php
    // Los destinos de la barra, en UN sitio.
    //
    // Estaban escritos dos veces -una para escritorio y otra para el menú
    // móvil- con su propio @if de rol cada uno. No es un riesgo teórico: el
    // bloque móvil llegó a tener solo 'dashboard', y con él la aplicación era
    // inservible en el teléfono. Un array que los dos recorren hace que
    // añadir una sección no pueda olvidarse en una de las dos.
    $secciones = auth()->user()->esAdmin()
        ? [
            ['texto' => 'Panel Admin', 'destino' => route('admin.dashboard'),     'activa' => request()->routeIs('admin.dashboard')],
            ['texto' => 'Usuarios',    'destino' => route('admin.users.index'),   'activa' => request()->routeIs('admin.users.*')],
            ['texto' => 'Lugares',     'destino' => route('admin.lugares.index'), 'activa' => request()->routeIs('admin.lugares.*')],
            ['texto' => 'Zonas',       'destino' => route('admin.zonas.index'),   'activa' => request()->routeIs('admin.zonas.*')],
        ]
        : [
            ['texto' => 'Mis Zonas',   'destino' => route('operativo.dashboard'), 'activa' => request()->routeIs('operativo.dashboard')],
        ];

    // Las zonas del selector, también en UN sitio y por el mismo motivo: desde
    // que el menú móvil ofrece el salto de zona, hay dos bloques que recorren
    // esta lista.
    //
    // Quién NO lo lleva se decide aquí y no en el componente, porque los dos
    // bloques tienen que tomar la misma decisión:
    //
    // - El admin: ya tiene la sección «Zonas» con su buscador, y un desplegable
    //   con todas las zonas del sistema crece sin techo.
    // - En «Mis Zonas»: esa página ES la lista de zonas, así que el selector
    //   ofrecería en pequeño lo que la pantalla ya enseña entera. Existe para
    //   saltar cuando estás DENTRO de una zona.
    //
    // «Mis zonas» es la UNIÓN de las dos relaciones -las que uno dirige y las
    // que tiene asignadas como equipo-: mirando solo zonasComoJefe, el equipo
    // vería un selector vacío teniendo zonas.
    $usuario = auth()->user();

    $zonasDelSelector = $usuario->esAdmin() || request()->routeIs('operativo.dashboard')
        ? collect()
        : $usuario->zonasComoJefe
            ->merge($usuario->zonasComoEquipo)
            ->unique('id')
            ->sortBy('nombre');

    // Clases literales y completas, nunca compuestas con el estado: Tailwind
    // purga las que no aparezcan tal cual en el fuente. Mismo patrón que
    // <x-pestanas-matriz>, que resuelve exactamente este problema.
    //
    // El activo va a indigo-600, que es el del sistema; <x-nav-link> traía
    // indigo-400 de Breeze y era el único índigo claro que quedaba.
    $enlaceActivo   = 'border-indigo-600 text-gray-900';
    $enlaceInactivo = 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300';

    // `border-s-4` y no `border-l-4`: la propiedad lógica es la que respeta el
    // sentido de escritura, y el resto del marcado de esta barra ya usa
    // ps-/pe-/ms-.
    $enlaceMovilActivo   = 'border-indigo-600 text-indigo-700 bg-indigo-50';
    $enlaceMovilInactivo = 'border-transparent text-gray-600 hover:text-gray-800 hover:bg-gray-50 hover:border-gray-300';
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-gray-100">
    <!-- Primary Navigation Menu -->
    <x-contenedor>
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}">
                       <img src="{{ asset('images/ecuador.png') }}" alt="Mi Logo" class="h-12 w-auto">
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    @foreach($secciones as $seccion)
                        <a href="{{ $seccion['destino'] }}"
                           class="inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium leading-5 transition focus:outline-none {{ $seccion['activa'] ? $enlaceActivo : $enlaceInactivo }}">
                            {{ $seccion['texto'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            {{-- El selector y el menú de usuario van dentro de UN solo hijo, no
                 sueltos: el contenedor de arriba es `justify-between` con dos
                 hijos -izquierda y derecha-, y colgar un tercero reparte el
                 espacio entre tres y deja el selector flotando en mitad de la
                 barra. Ningún test lo vería: afirman sobre el marcado, no sobre
                 el reparto. --}}
            {{-- `hidden sm:flex` y no `flex` a secas: en móvil este grupo entero
                 desaparece y manda el hamburguesa, que es `sm:hidden`. Así en
                 cada punto de ruptura el contenedor de arriba sigue teniendo
                 exactamente dos hijos visibles, que es lo que hace que
                 `justify-between` reparta bien. --}}
            <div class="hidden sm:flex sm:items-center">
                <x-selector-zona :zonas="$zonasDelSelector" />

                {{-- Settings Dropdown. Solo `ms-6`: el `hidden sm:flex
                     sm:items-center` que traía de Breeze ya lo pone el grupo
                     que ahora lo envuelve, y repetirlo aquí solo escondía que
                     el reparto se decide un nivel más arriba. --}}
                <div class="ms-6">
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                                <div>{{ Auth::user()->name }}</div>

                                <div class="ms-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <x-dropdown-link :href="route('profile.edit')">
                                {{ __('Profile') }}
                            </x-dropdown-link>

                            <!-- Authentication -->
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf

                                <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                    {{ __('Log Out') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                </div>
            </div>{{-- cierra el grupo de la derecha: selector + menú de usuario --}}

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </x-contenedor>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        {{-- Recorre el MISMO $secciones que el bloque de escritorio. Antes cada
             uno tenía su propio @if de rol, y este llegó a quedarse con solo
             'dashboard': la aplicación era inservible en móvil y nada lo
             señalaba. --}}
        <div class="pt-2 pb-3 space-y-1">
            @foreach($secciones as $seccion)
                <a href="{{ $seccion['destino'] }}"
                   class="block w-full ps-3 pe-4 py-2 border-s-4 text-start text-base font-medium transition focus:outline-none {{ $seccion['activa'] ? $enlaceMovilActivo : $enlaceMovilInactivo }}">
                    {{ $seccion['texto'] }}
                </a>
            @endforeach
        </div>

        {{-- El salto de zona, también aquí. El desplegable de escritorio vive
             dentro de un grupo `hidden sm:flex`, así que por debajo de ese
             punto de ruptura no existe —y «funciona en móvil, donde un atajo de
             teclado no sirve de nada» es medio argumento por el que este
             selector sustituyó al Cmd+K—.

             Recorre el MISMO $zonasDelSelector que el desplegable: quién lo ve
             y qué zonas ofrece se decidió una sola vez, arriba. --}}
        @if($zonasDelSelector->isNotEmpty())
            <div id="selector-zona-movil" class="pt-4 pb-1 border-t border-gray-200">
                <div class="px-4 text-xs font-semibold uppercase tracking-wide text-gray-400">
                    Cambiar de zona
                </div>

                <div class="mt-3 space-y-1">
                    @foreach($zonasDelSelector as $zonaDelSelector)
                        <a href="{{ route('operativo.zona.panel', $zonaDelSelector->id) }}"
                           class="block w-full ps-3 pe-4 py-2 border-s-4 text-start text-base font-medium transition focus:outline-none {{ $enlaceMovilInactivo }}">
                            {{ $zonaDelSelector->nombre }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <a href="{{ route('profile.edit') }}"
                   class="block w-full ps-3 pe-4 py-2 border-s-4 text-start text-base font-medium transition focus:outline-none {{ $enlaceMovilInactivo }}">
                    {{ __('Profile') }}
                </a>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); this.closest('form').submit();"
                       class="block w-full ps-3 pe-4 py-2 border-s-4 text-start text-base font-medium transition focus:outline-none {{ $enlaceMovilInactivo }}">
                        {{ __('Log Out') }}
                    </a>
                </form>
            </div>
        </div>
    </div>
</nav>