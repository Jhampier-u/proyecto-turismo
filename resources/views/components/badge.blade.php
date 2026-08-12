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
