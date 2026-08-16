@props(['evaluacion', 'niveles' => null])

{{--
    Una sola franja donde antes había tres cajas: la línea de «Última
    edición», el banner de estado y la tarjeta de la escala.

    DERIVA el estado en vez de recibirlo. Las ocho vistas que la usan no se
    ponen de acuerdo en cómo llamarlo -siete dicen $estaConfirmado/$bloqueado
    y Potencialidad dice $isConfirmado/$soloLectura-, así que un prop booleano
    serían ocho oportunidades de pasarle el contrario. Con $evaluacion y el
    rol de quien mira, la respuesta es una sola y sale de aquí.

    TRES estados, no dos. Antes, una matriz validada pintaba el mismo verde
    para todos: quien no puede editarla leía «todo correcto» y descubría el
    bloqueo al final de la página. El verde queda para «validada y todavía
    puedes editarla»; cerrada se pinta neutro. CLAUDE.md recuerda que una fase
    anterior de este mismo rediseño pintó de verde un estado bloqueado.

    $niveles a null significa SIN ESCALA, y por eso el defecto no es 0/1/2
    como en la <x-leyenda-escala> que esto sustituye: con aquel defecto,
    «no tengo escala» y «tengo la escala corriente» se escribían igual, y
    Concentración e Irritación -que no tienen escala- habrían recibido una
    inventada. Valoración Territorial, que se apoyaba en ese defecto, ahora
    pasa la suya explícitamente.
--}}

@php
    $confirmada = $evaluacion?->exists && $evaluacion->estado === 'confirmado';
    $esJefe     = auth()->user()->esJefe();

    $estado = match (true) {
        $confirmada && $esJefe => 'validada',
        $confirmada            => 'cerrada',
        default                => 'borrador',
    };

    // Clases enteras en cada rama: Tailwind purga las construidas por
    // concatenación. Mismo criterio que EstadoZona::ESTILOS_ESTADO.
    $estilos = [
        'borrador' => ['marco' => 'border-l-amber-500', 'texto' => 'text-amber-700', 'etiqueta' => 'Borrador'],
        'validada' => ['marco' => 'border-l-green-500', 'texto' => 'text-green-700', 'etiqueta' => 'Validada'],
        'cerrada'  => ['marco' => 'border-l-gray-400',  'texto' => 'text-gray-700',  'etiqueta' => 'Validada · solo lectura'],
    ][$estado];

    // Indexadas por POSICIÓN, no por el valor del nivel: Valoración
    // Territorial usa 0/1/2, Paisaje usa 0/3/5 y FIT/FET usan cuatro niveles
    // (0-3). La de 4 evita repetir el verde a media escala.
    $paletas = [
        3 => ['bg-red-500', 'bg-amber-500', 'bg-green-500'],
        4 => ['bg-red-500', 'bg-orange-500', 'bg-amber-500', 'bg-green-500'],
    ];

    if ($niveles !== null) {
        ksort($niveles);
        $colores = $paletas[count($niveles)] ?? $paletas[3];
    }
@endphp

<div class="bg-white border border-gray-200/80 border-l-4 {{ $estilos['marco'] }} rounded-xl shadow-sm px-4 py-3 mb-6">
    <div class="flex flex-wrap items-center gap-x-4 gap-y-2 text-sm">
        <span class="font-semibold {{ $estilos['texto'] }}">{{ $estilos['etiqueta'] }}</span>

        @if($niveles !== null)
            <span class="text-gray-300" aria-hidden="true">|</span>

            @foreach($niveles as $nivel => $etiqueta)
                <span class="flex items-center gap-1.5 text-gray-700">
                    <span class="w-5 h-1.5 rounded-full {{ $colores[$loop->index] }}"></span>
                    <span class="font-semibold">{{ $nivel }}</span>
                    <span>{{ $etiqueta }}</span>
                </span>
            @endforeach
        @endif

        @if($evaluacion?->exists && $evaluacion->user)
            <span class="ml-auto text-gray-500">
                {{ $evaluacion->user->name }}, {{ $evaluacion->updated_at->diffForHumans() }}
            </span>
        @endif
    </div>

    {{-- La frase que sobrevive del párrafo de la escala: no explica cómo
         funciona el sistema -eso se aprende una vez- sino cómo se puntúa
         bien, que es lo que cambia el dato. Sin escala no tiene sentido. --}}
    @if($niveles !== null)
        <p class="text-sm text-gray-500 mt-2">
            Elige la descripción que coincide con el territorio, no el número.
        </p>
    @endif
</div>
