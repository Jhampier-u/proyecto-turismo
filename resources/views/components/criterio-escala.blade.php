@props(['campo', 'criterio', 'bloqueado' => false])

{{--
    Tres tarjetas seleccionables, cada una con la descripción textual de su
    nivel. La descripción es la opción: es lo que permite que dos evaluadores
    distintos puntúen igual, y evita tener que consultar el instrumento aparte.

    El color codifica la escala: rojo el nivel menos favorable, ámbar el
    intermedio, verde el más favorable. Verificado en los 21 criterios de
    Valoración Territorial: el 0 es siempre el peor de los tres.

    La franja superior es fija —da la lectura semántica mientras se rellena— y
    el relleno completo aparece solo al seleccionar, para que lo elegido
    destaque en una lista de 21 criterios sin convertirla en un semáforo.

    El estado vive en el `x-data` de la sección que envuelve al componente, no
    aquí: así la sección puede sumar su subtotal y contar los respondidos.
--}}

@php
    // Clases completas por nivel. Tailwind purga las que no aparezcan
    // literalmente, así que no pueden construirse por concatenación.
    $estilos = [
        0 => [
            'franja'      => 'border-t-red-500',
            'seleccionado'=> 'bg-red-50 border-red-300 ring-2 ring-red-400',
            'numero'      => 'text-red-700',
            'texto'       => 'text-red-900',
        ],
        1 => [
            'franja'      => 'border-t-amber-500',
            'seleccionado'=> 'bg-amber-50 border-amber-300 ring-2 ring-amber-400',
            'numero'      => 'text-amber-700',
            'texto'       => 'text-amber-900',
        ],
        2 => [
            'franja'      => 'border-t-green-500',
            'seleccionado'=> 'bg-green-50 border-green-300 ring-2 ring-green-400',
            'numero'      => 'text-green-700',
            'texto'       => 'text-green-900',
        ],
    ];
@endphp

<fieldset class="border-b border-gray-200 py-5">
    <legend class="mb-3">
        <span class="block text-base font-semibold text-gray-900 leading-snug">
            {{ $criterio['nombre'] }}
        </span>
        <span class="inline-flex items-center gap-2 mt-1 text-sm">
            <span class="px-2 py-0.5 rounded bg-gray-100 text-gray-700 font-mono text-xs font-semibold">
                {{ $criterio['sigla'] }}
            </span>
            <span class="text-gray-500">
                Peso {{ rtrim(rtrim(number_format($criterio['peso'] * 100, 1), '0'), '.') }}%
            </span>
        </span>
    </legend>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($criterio['niveles'] as $nivel => $descripcion)
            @php $e = $estilos[$nivel]; @endphp

            <label class="block border border-t-4 {{ $e['franja'] }} rounded-lg p-4 transition cursor-pointer"
                   :class="valores['{{ $campo }}'] === {{ $nivel }}
                       ? '{{ $e['seleccionado'] }}'
                       : 'bg-white border-gray-200 hover:bg-gray-50 hover:border-gray-300'">
                <input type="radio"
                       name="{{ $campo }}"
                       value="{{ $nivel }}"
                       x-model.number="valores['{{ $campo }}']"
                       @disabled($bloqueado)
                       class="sr-only">

                <span class="flex items-center gap-2 mb-2">
                    <span class="text-lg font-bold {{ $e['numero'] }}">{{ $nivel }}</span>
                    <span class="w-4 h-4 rounded-full border-2 flex-shrink-0"
                          :class="valores['{{ $campo }}'] === {{ $nivel }}
                              ? 'border-current bg-current'
                              : 'border-gray-300'"></span>
                </span>

                <span class="block text-sm leading-relaxed"
                      :class="valores['{{ $campo }}'] === {{ $nivel }}
                          ? '{{ $e['texto'] }}'
                          : 'text-gray-600'">{{ $descripcion }}</span>
            </label>
        @endforeach
    </div>

    @error($campo)
        <p class="text-sm text-red-600 mt-3 font-medium">{{ $message }}</p>
    @enderror
</fieldset>
