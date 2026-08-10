@props(['campo', 'criterio', 'bloqueado' => false, 'activoExpr' => null])

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

    $activoExpr es opcional y aditivo: Paisaje, FIT, FET y Percepción nunca lo
    pasan, así que para ellas nada cambia -ni una clase, ni un atributo nuevo
    en el HTML-. Solo Potencialidad lo usa, para su configuración de campos
    activos: :bloqueado ya es estático (fijado por PHP al renderizar, según
    rol/estado), pero "activo" es reactivo en el cliente -un Jefe activa o
    desactiva un campo sin recargar la página-, así que necesita su propio
    x-bind:disabled en vez de otro @disabled(). $activoExpr es la expresión
    Alpine que evalúa a "está activo" en el x-data de quien lo use -en
    Potencialidad, "states['campo']"-; el componente no conoce esa variable,
    solo la referencia por nombre.
--}}

@php
    // Clases completas por posición. Tailwind purga las que no aparezcan
    // literalmente, así que no pueden construirse por concatenación.
    //
    // Paleta según el número de niveles, no una sola fija: Paisaje y
    // ValoracionTerritorial usan 3 (rojo→ámbar→verde, sin cambios); FIT y FET
    // usan una escala genérica de 4 (Nulo/Bajo/Medio/Alto), y reutilizar la
    // paleta de 3 habría dejado un índice sin definir en el cuarto nivel, o
    // habría corrido el verde -"lo mejor"- a media escala. Las dos progresan
    // de la posición peor a la mejor porque en ambos instrumentos el valor
    // más alto es siempre el mejor -ver App\Matrices\Fit y App\Matrices\Fet-.
    $paletas = [
        3 => [
            0 => ['franja' => 'border-l-red-500',   'activo' => 'bg-red-50 border-red-300 text-red-900 ring-2 ring-red-400',       'numero' => 'text-red-700'],
            1 => ['franja' => 'border-l-amber-500', 'activo' => 'bg-amber-50 border-amber-300 text-amber-900 ring-2 ring-amber-400', 'numero' => 'text-amber-700'],
            2 => ['franja' => 'border-l-green-500', 'activo' => 'bg-green-50 border-green-300 text-green-900 ring-2 ring-green-400', 'numero' => 'text-green-700'],
        ],
        4 => [
            0 => ['franja' => 'border-l-red-500',    'activo' => 'bg-red-50 border-red-300 text-red-900 ring-2 ring-red-400',             'numero' => 'text-red-700'],
            1 => ['franja' => 'border-l-orange-500', 'activo' => 'bg-orange-50 border-orange-300 text-orange-900 ring-2 ring-orange-400', 'numero' => 'text-orange-700'],
            2 => ['franja' => 'border-l-amber-500',  'activo' => 'bg-amber-50 border-amber-300 text-amber-900 ring-2 ring-amber-400',     'numero' => 'text-amber-700'],
            3 => ['franja' => 'border-l-green-500',  'activo' => 'bg-green-50 border-green-300 text-green-900 ring-2 ring-green-400',     'numero' => 'text-green-700'],
        ],
    ];

    $niveles = $criterio['niveles'];
    ksort($niveles);

    $estilos = $paletas[count($niveles)] ?? $paletas[3];

    // Si ya está bloqueado por rol/estado, el radio ya sale disabled por
    // @disabled($bloqueado) más abajo y no hace falta nada reactivo: no hay
    // ningún toggle de activo/inactivo visible en modo lectura con el que
    // pueda entrar en conflicto.
    $bindDisabled = ($activoExpr && ! $bloqueado) ? "!({$activoExpr})" : null;
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
                       @if($bindDisabled) x-bind:disabled="{{ $bindDisabled }}" @endif
                       class="sr-only">

                <span class="font-bold text-base {{ $e['numero'] }}">{{ $valor }}</span>
                <span class="text-sm">{{ $etiqueta }}</span>
            </label>
        @endforeach

        {{-- Un grupo de radios no se puede desmarcar por sí solo. Sin esto,
             «sin responder» sería un estado del que se sale una vez y al que no
             se puede volver. Va dentro del flex, alineado con las píldoras,
             para no añadir una fila más a una lista de 34 criterios. --}}
        @unless($bloqueado)
            <button type="button"
                    x-show="valores['{{ $campo }}'] !== null"
                    @click="valores['{{ $campo }}'] = null"
                    @if($bindDisabled) x-bind:disabled="{{ $bindDisabled }}" @endif
                    class="inline-flex items-center px-2 text-sm text-gray-500 underline hover:text-gray-700">
                Borrar respuesta
            </button>
        @endunless
    </div>

    @error($campo)
        <p class="text-sm text-red-600 mt-2 font-medium">{{ $message }}</p>
    @enderror
</div>
