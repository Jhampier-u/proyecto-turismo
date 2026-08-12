<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        {{-- bg-gray-50 vale #F8FAFC con el alias de tailwind.config.js: el
             fondo pedido, escrito con el único nombre de gris que usa el
             proyecto. --}}
        <div class="min-h-screen bg-gray-50">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="bg-white shadow">
                    <x-contenedor class="py-6">
                        {{ $header }}
                    </x-contenedor>
                </header>
            @endisset

            {{--
                El contenedor vive aquí y no en cada vista: si la cabecera y el
                cuerpo llevaran anchos distintos, el título de la página no
                alinearía con su contenido, y se nota en todas las páginas a la
                vez.
            --}}
            <main>
                <x-contenedor>
                    {{ $slot }}
                </x-contenedor>
            </main>
        </div>
    </body>
</html>
