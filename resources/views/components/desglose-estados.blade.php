@props(['progreso'])

{{--
    Cuántas matrices de una zona hay en cada estado.

    Sustituye al «3 / 10», que metía en el mismo saco lo que nadie ha abierto
    y lo que espera validación: un 3/10 con siete borradores completos y uno
    con siete sin abrir piden cosas distintas y se leían igual.

    Es <x-badge> y su ranura, que existe justo para esto: conserva el color
    del estado y cambia el texto. NO hay una insignia de «zona terminada» a
    propósito —los colores de ESTILOS_ESTADO significan el estado de una
    MATRIZ, y dárselos a una ZONA es el error que este proyecto ya pagó una
    vez con <x-insignia-clasificacion>, cuyo nombre genérico invitaba a pintar
    de verde el peor resultado—. Cada insignia de aquí cuenta matrices de ese
    estado, que es lo que su color dice.

    Solo tres estados, y no los cinco del mapa: el denominador es
    Registro::matrices(), las diez validables. 'bloqueada' y 'sin_estado'
    pertenecen a las dos entradas que no cuentan —vtt, que es derivada, e
    inventario, que no tiene estado—, así que aquí no pueden aparecer.

    Orden fijo, no por cuál tenga más: las tres suman el total, y leerlas
    siempre en el mismo sitio es lo que las hace un reparto en vez de tres
    cifras sueltas.
--}}

@php
    $tramos = [
        ['estado' => 'validada',    'cuantas' => $progreso['hechas'],      'etiqueta' => 'validadas'],
        ['estado' => 'borrador',    'cuantas' => $progreso['borradores'],  'etiqueta' => 'en borrador'],
        ['estado' => 'sin_empezar', 'cuantas' => $progreso['sin_empezar'], 'etiqueta' => 'sin empezar'],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-1.5']) }}>
    @foreach($tramos as $tramo)
        {{-- Un estado a cero no se pinta: «0 en borrador» ocupa sitio para no
             decir nada. --}}
        @if($tramo['cuantas'] > 0)
            <x-badge :estado="$tramo['estado']">{{ $tramo['cuantas'] }} {{ $tramo['etiqueta'] }}</x-badge>
        @endif
    @endforeach
</div>
