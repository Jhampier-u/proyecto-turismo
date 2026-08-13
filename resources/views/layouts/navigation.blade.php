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
                        <x-nav-link :href="$seccion['destino']" :active="$seccion['activa']">
                            {{ $seccion['texto'] }}
                        </x-nav-link>
                    @endforeach
                </div>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
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
                <x-responsive-nav-link :href="$seccion['destino']" :active="$seccion['activa']">
                    {{ $seccion['texto'] }}
                </x-responsive-nav-link>
            @endforeach
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>