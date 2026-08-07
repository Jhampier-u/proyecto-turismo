@props(['campo', 'criterio', 'bloqueado' => false])

{{--
    Variante compacta de <x-criterio-escala> para criterios cuyas opciones son
    etiquetas de una o dos palabras. Mantiene lo esencial —ver las tres a la vez
    y compararlas antes de elegir— en un tercio del alto: con 34 criterios, las
    tarjetas grandes daban una página de unos 4.000 píxeles.

    El color no se deduce del número sino de la POSICIÓN en la escala: el valor
    más bajo del criterio va en rojo y el más alto en verde. Eso hace que
    Conflictos Paisajísticos —donde 5 es «Controlado» y 0 es «Afectado»— quede
    bien sin ningún caso especial.

    El estado vive en el `x-data` de la sección que envuelve al componente.
--}}

@php
    // Clases completas por posición. Tailwind purga las que no aparezcan
    // literalmente, así que no pueden construirse por concatenación.
    $estilos = [
        0 => ['franja' => 'border-l-red-500',   'activo' => 'bg-red-50 border-red-300 text-red-900 ring-2 ring-red-400',       'numero' => 'text-red-700'],
        1 => ['franja' => 'border-l-amber-500', 'activo' => 'bg-amber-50 border-amber-300 text-amber-900 ring-2 ring-amber-400', 'numero' => 'text-amber-700'],
        2 => ['franja' => 'border-l-green-500', 'activo' => 'bg-green-50 border-green-300 text-green-900 ring-2 ring-green-400', 'numero' => 'text-green-700'],
    ];

    $niveles = $criterio['niveles'];
    ksort($niveles);
@endphp

<div class="border-b border-gray-200 py-4">
    <p class="text-base font-semibold text-gray-900 mb-2 leading-snug">{{ $criterio['nombre'] }}</p>

    <div class="flex flex-wrap gap-2">
        @foreach($niveles as $valor => $etiqueta)
            @php $e = $estilos[$loop->index]; @endphp

            <label class="inline-flex items-center gap-2 border border-l-4 {{ $e['franja'] }} rounded-lg px-4 py-2 transition cursor-pointer"
                   :class="valores['{{ $campo }}'] === {{ $valor }}
                       ? '{{ $e['activo'] }}'
                       : 'bg-white border-gray-200 text-gray-600 hover:bg-gray-50 hover:border-gray-300'">
                <input type="radio"
                       name="{{ $campo }}"
                       value="{{ $valor }}"
                       x-model.number="valores['{{ $campo }}']"
                       @disabled($bloqueado)
                       class="sr-only">

                <span class="font-bold text-base {{ $e['numero'] }}">{{ $valor }}</span>
                <span class="text-sm">{{ $etiqueta }}</span>
            </label>
        @endforeach
    </div>

    @error($campo)
        <p class="text-sm text-red-600 mt-2 font-medium">{{ $message }}</p>
    @enderror
</div>
