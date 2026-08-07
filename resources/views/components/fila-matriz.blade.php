@props(['fila'])

@php
    // Clases completas: Tailwind purga las construidas por concatenación.
    // El color codifica SOLO el estado. La identidad la dan icono y nombre.
    $estilos = [
        'sin_empezar' => ['icono' => 'text-gray-400',  'detalle' => 'text-gray-500'],
        'sin_estado'  => ['icono' => 'text-gray-500',  'detalle' => 'text-gray-600'],
        'borrador'    => ['icono' => 'text-amber-600', 'detalle' => 'text-amber-700'],
        'validada'    => ['icono' => 'text-green-600', 'detalle' => 'text-gray-600'],
        'bloqueada'   => ['icono' => 'text-gray-300',  'detalle' => 'text-gray-400'],
    ][$fila->estado];

    $bloqueada = $fila->estado === 'bloqueada';
@endphp

<div class="flex items-center gap-4 py-4 border-t border-gray-200">
    <x-icono :nombre="$bloqueada ? 'candado' : $fila->icono"
             class="w-6 h-6 shrink-0 {{ $estilos['icono'] }}" />

    <div class="flex-1 min-w-0">
        <p class="text-base {{ $bloqueada ? 'text-gray-400' : 'text-gray-900' }}">
            {{ $fila->nombre }}
        </p>
        <p class="text-sm {{ $estilos['detalle'] }}">{{ $fila->detalle }}</p>

        {{-- Sin botones desactivados: donde el equipo no puede validar, va el
             texto que dice quién lo hace. --}}
        @if($fila->avisoValidacion)
            <p class="text-sm text-amber-700 mt-1">{{ $fila->avisoValidacion }}</p>
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
