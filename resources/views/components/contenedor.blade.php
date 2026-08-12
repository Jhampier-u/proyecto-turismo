@props(['ancho' => 'normal'])

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

<div {{ $attributes->merge(['class' => 'w-full ' . $anchos[$ancho] . ' mx-auto px-4 sm:px-6 lg:px-8']) }}>
    {{ $slot }}
</div>
