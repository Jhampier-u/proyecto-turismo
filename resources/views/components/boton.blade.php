@props(['variante' => 'primario', 'tamano' => 'normal', 'href' => null])

{{--
    La acción, en tres variantes y dos tamaños.

    Había 12 escrituras distintas del mismo botón primario -py-2 px-5, py-2
    px-4, py-3 px-6; rounded, rounded-lg, rounded-md; en cuatro colores- para
    lo que conceptualmente es «la acción principal de esta pantalla».

    Con href da un <a> y sin él un <button>: hoy la mitad de los botones del
    sistema son enlaces, y esa diferencia es de comportamiento, no de estilo.

    El type="submit" va por defecto porque es lo que hace que sustituir un
    <button type="submit"> no cambie nada, y se puede sobreescribir porque los
    botones de Alpine necesitan type="button" para no enviar el formulario que
    los contiene.

    Clases completas en los arrays, nunca compuestas con el nombre de la
    variante: Tailwind purga las que no aparezcan literales en el fuente.
--}}

@php
    $variantes = [
        'primario'   => 'bg-indigo-600 hover:bg-indigo-700 text-white border-transparent shadow-sm',
        'secundario' => 'bg-white hover:bg-gray-50 text-gray-700 border-gray-300 shadow-sm',
        'peligro'    => 'bg-red-600 hover:bg-red-700 text-white border-transparent shadow-sm',
    ];

    $tamanos = [
        'normal' => 'px-4 py-2 text-sm',
        'grande' => 'px-6 py-3 text-base',
    ];

    if (! isset($variantes[$variante])) {
        throw new \InvalidArgumentException(
            "<x-boton>: variante «{$variante}» desconocida; las válidas son "
            . implode(', ', array_keys($variantes)) . '.'
        );
    }

    if (! isset($tamanos[$tamano])) {
        throw new \InvalidArgumentException(
            "<x-boton>: tamaño «{$tamano}» desconocido; los válidos son "
            . implode(', ', array_keys($tamanos)) . '.'
        );
    }

    $clases = 'inline-flex items-center justify-center gap-2 border rounded-lg font-semibold '
        . 'transition focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 '
        . 'disabled:opacity-50 disabled:cursor-not-allowed '
        . $variantes[$variante] . ' ' . $tamanos[$tamano];
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $clases]) }}>{{ $slot }}</a>
@else
    <button {{ $attributes->merge(['type' => 'submit', 'class' => $clases]) }}>{{ $slot }}</button>
@endif
