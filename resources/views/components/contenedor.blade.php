@props(['ancho' => 'normal', 'padding' => true])

{{--
    El ancho del documento, en un solo sitio.

    Antes vivía en 39 ficheros con 9 valores distintos, y el <main> del layout
    no tenía ninguno: cada vista decidía por su cuenta lo ancha que era la
    aplicación. Cambiar el ancho de la aplicación era editar 39 ficheros; ahora
    es editar esta tabla.

    Fluido con tope: en monitores grandes se comporta como un ancho fijo de
    1440, y en portátiles de 1280-1366 aprovecha todo el ancho en vez de dejar
    muerto el margen del contenedor.

    «estrecho» existe porque no todas las vistas mienten al ser estrechas: un
    formulario de cuatro campos a 1440px es peor, no mejor.

    Sin padding para los que se anidan dentro del layout. Son dos —los dos
    formularios estrechos— y no se arreglan borrándolos, que es lo primero que
    parece: son estrechos a propósito, así que quitarlos los mandaría a 1440.
    Lo único que sobra al anidar es el padding, que se aplicaba dos veces (311
    px de ancho útil en vez de 343 a 375 px de pantalla). Mismo prop y misma
    razón que <x-tarjeta :padding="false">.

    Las clases van literales en el array y no construidas con el nombre del
    ancho: Tailwind purga las que no aparezcan tal cual en el fuente.
--}}

@php
    $anchos = [
        'normal'   => 'max-w-[1440px]',
        'estrecho' => 'max-w-2xl',
    ];

    if (! isset($anchos[$ancho])) {
        throw new \InvalidArgumentException(
            "<x-contenedor>: ancho «{$ancho}» desconocido; los válidos son normal y estrecho."
        );
    }
@endphp

<div {{ $attributes->merge([
    'class' => 'w-full ' . $anchos[$ancho] . ' mx-auto' . ($padding ? ' px-4 sm:px-6 lg:px-8' : ''),
]) }}>
    {{ $slot }}
</div>
