@props([
    'niveles' => [
        0 => 'Desfavorable',
        1 => 'Parcial',
        2 => 'Favorable',
    ],
])

{{--
    Leyenda de la escala, una sola vez por formulario. No se repite en cada
    criterio: con 21 criterios sería ruido, y la descripción de cada tarjeta
    ya dice lo que significa ese nivel en ese criterio concreto.
--}}

@php
    $colores = [
        0 => 'bg-red-500',
        1 => 'bg-amber-500',
        2 => 'bg-green-500',
    ];
@endphp

<div class="bg-white border border-gray-200 rounded-lg p-4 mb-6">
    <div class="flex flex-wrap items-center gap-x-6 gap-y-3">
        <span class="text-sm font-semibold text-gray-700">Escala de valoración</span>

        @foreach($niveles as $nivel => $etiqueta)
            <span class="flex items-center gap-2 text-sm text-gray-700">
                <span class="w-6 h-1.5 rounded-full {{ $colores[$nivel] }}"></span>
                <span class="font-bold">{{ $nivel }}</span>
                <span>{{ $etiqueta }}</span>
            </span>
        @endforeach
    </div>

    <p class="text-sm text-gray-500 mt-3 leading-relaxed">
        Cada criterio define qué significan sus niveles: elige la descripción que
        coincide con el territorio, no el número. Estas etiquetas son solo una guía
        del sentido general de la escala.
    </p>
</div>
