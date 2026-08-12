@props(['fila', 'zona' => null])

@php
    // El color codifica SOLO el estado. La identidad la dan icono y nombre.
    // El mapa vive en EstadoZona porque <x-badge> pinta los mismos cinco
    // estados y dos copias se separan sin que nada falle.
    $estilos = \App\Servicios\EstadoZona::ESTILOS_ESTADO[$fila->estado];

    $bloqueada = $fila->estado === 'bloqueada';
@endphp

<div class="flex items-center gap-4 py-4 border-t border-gray-200">
    <x-icono :nombre="$bloqueada ? 'candado' : $fila->icono"
             class="w-6 h-6 shrink-0 {{ $estilos['icono'] }}" />

    <div class="flex-1 min-w-0">
        @if($zona)
            <p class="text-sm text-gray-500">{{ $zona->nombre }}</p>
        @endif
        <p class="text-base {{ $bloqueada ? 'text-gray-400' : 'text-gray-900' }}">
            {{ $fila->nombre }}
        </p>
        <p class="text-sm {{ $estilos['detalle'] }}">{{ $fila->detalle }}</p>

        {{-- Sin botones desactivados: donde el equipo no puede validar, va el
             texto que dice quién lo hace. --}}
        {{-- Mismo ámbar que el detalle: avisoValidacion solo existe en filas
             en borrador, así que $estilos ya es el de 'borrador' aquí -no es
             un color aparte escrito a mano, es el mismo que la línea de
             arriba, reafirmado en vez de repetido. --}}
        @if($fila->avisoValidacion)
            <p class="text-sm {{ $estilos['detalle'] }} mt-1">{{ $fila->avisoValidacion }}</p>
        @endif

        {{-- La máquina de estados vive en el formulario de la matriz
             (accion_estado=confirmado): no hay ruta para validar desde aquí,
             así que al jefe se le da la pista, no un botón que no lleva a
             ningún sitio. Mismo tratamiento visual que avisoValidacion. --}}
        @if($fila->puedeValidar)
            <p class="text-sm {{ $estilos['detalle'] }} mt-1">Lista para validar</p>
        @endif
    </div>

    @if($fila->url && $fila->accion)
        <a href="{{ $fila->url }}"
           class="shrink-0 inline-flex items-center px-4 py-2 rounded-lg border border-gray-300
                  bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm">
            {{ $fila->accion }}
        </a>
    @endif
</div>
