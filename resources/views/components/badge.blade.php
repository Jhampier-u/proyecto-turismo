@props(['estado'])

{{--
    Un estado del sistema, pintado.

    No inventa sus valores: los cinco son los que produce EstadoZona, y los
    colores salen de EstadoZona::ESTILOS_ESTADO, el mismo mapa que usa
    <x-fila-matriz>. Aquí no hay ni un color escrito a mano, a propósito.

    La ranura sustituye el texto y conserva el color. Es lo que resuelve
    «Listo para validar», que no es un estado sino un borrador que además
    está completo: si fuera un valor más del mapa, el sistema tendría seis
    estados en la interfaz y cinco en el servicio.

    ─────────────────────────────────────────────────────────────────────────
    HOY NO LO USA NADIE, Y ES A PROPÓSITO. No es un olvido de la Fase 0.

    Los dos candidatos que hay —«Completo» / «A medias» en
    frecuentacion/index e involucrados/index— usan el mismo verde y el mismo
    ámbar que el mapa da a `validada` y `borrador`: fondo y texto idénticos,
    sin el borde. (Los literales no se copian aquí a propósito: hay un test
    que prohíbe que este fichero contenga colores del mapa, y tiene razón
    aunque sea dentro de un comentario.) Parece adopción cantada, y no lo es:
    lo que esas cuatro píldoras dicen es si una FILA está completa, no el
    estado de una matriz. Un sitio «Completo» dentro de una lista en borrador
    no está validado —nadie lo validó—, así que darle la insignia de ese
    estado ahorraría cuatro copias de color a cambio de que el código afirme
    algo falso.

    Este proyecto ya pagó ese error una vez: el componente que hoy se llama
    <x-insignia-clasificacion-irritacion> se llamaba <x-insignia-clasificacion>
    y su nombre genérico invitaba a reutilizarlo con otra semántica, donde
    habría pintado de verde el peor resultado sin que nada saltara.

    Su primer consumidor natural son las tarjetas de zona de la Fase 2, que sí
    muestran el estado de matrices. Hasta entonces, mejor probado y sin usar
    que usado donde no toca.
    ─────────────────────────────────────────────────────────────────────────
--}}

@php
    $estilos = \App\Servicios\EstadoZona::ESTILOS_ESTADO;

    if (! isset($estilos[$estado])) {
        throw new \InvalidArgumentException(
            "<x-badge>: estado «{$estado}» desconocido; los válidos son "
            . implode(', ', array_keys($estilos)) . '.'
        );
    }
@endphp

<span {{ $attributes->merge([
    'class' => 'inline-flex items-center border rounded-full px-2.5 py-0.5 text-sm font-medium '
        . $estilos[$estado]['insignia'],
]) }}>
    {{ $slot->isEmpty() ? \App\Servicios\EstadoZona::NOMBRES_ESTADO[$estado] : $slot }}
</span>
