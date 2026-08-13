@props(['zona' => null, 'clave' => null, 'actual' => null])

{{--
    Dónde estás, y cómo subir.

    Sustituye a <x-boton-volver> y hereda su decisión central sin reescribirla:
    la navegación es una jerarquía -lista de zonas → zona → matriz- y quién es
    la lista de arriba depende del rol. Ese ternario vive en UN sitio a
    propósito: replicado es exactamente la forma que tomó el fallo que dejó al
    admin viendo enlaces de edición durante toda una rama.

    El nombre de la matriz sale del Registro y nunca de la vista: escrito a
    mano, la miga y la pestaña podrían decir cosas distintas del mismo criterio.

    Los GRUPOS del Registro -'base', 'vocacion'...- no entran en el rastro
    aunque tengan título. Ninguno tiene ruta, así que serían un tramo intermedio
    no navegable, y una miga que no lleva a ningún sitio enseña una jerarquía
    que la aplicación no tiene.
--}}

@php
    if ($clave !== null && $zona === null) {
        throw new \InvalidArgumentException(
            '<x-migas>: :zona es obligatoria cuando se da una clave; la ruta de una matriz necesita el id de la zona.'
        );
    }

    if ($clave !== null && ! isset(\App\Matrices\Registro::ENTRADAS[$clave])) {
        throw new \InvalidArgumentException(
            "<x-migas>: clave «{$clave}» desconocida; las válidas son "
            . implode(', ', array_keys(\App\Matrices\Registro::ENTRADAS)) . '.'
        );
    }

    $esAdmin = auth()->user()->esAdmin();

    $tramos = [[
        'texto'   => $esAdmin ? 'Zonas' : 'Mis Zonas',
        'destino' => $esAdmin ? route('admin.zonas.index') : route('operativo.dashboard'),
    ]];

    if ($zona) {
        $tramos[] = [
            'texto'   => $zona->nombre,
            'destino' => route('operativo.zona.panel', $zona->id),
        ];
    }

    if ($clave !== null) {
        $entrada = \App\Matrices\Registro::ENTRADAS[$clave];

        // `editar` y no `ver` porque la miga lleva al sitio donde se trabaja,
        // no al de solo lectura. Y con `?? null` porque no todas las entradas
        // lo tienen: `vtt` es de tipo 'resultado' -se calcula a partir de FIT
        // y FET- y no tiene formulario al que llevar. Ese tramo se pinta
        // igual, porque el nombre hace falta para saber dónde estás, pero sin
        // enlace: es más honesto que inventarle un destino.
        $rutaEditar = $entrada['rutas']['editar'] ?? null;

        $tramos[] = [
            'texto'   => $entrada['nombre'],
            'destino' => $rutaEditar ? route($rutaEditar, $zona->id) : null,
        ];
    }

    if ($actual !== null) {
        $tramos[] = ['texto' => $actual, 'destino' => null];
    }

    // Cuál es la hoja se decide por posición y NO por «no tiene destino»: son
    // dos cosas distintas desde que hay matrices sin formulario. Confundirlas
    // marcaría un tramo intermedio como la página actual.
    $ultimo = array_key_last($tramos);

    // La hoja nunca es enlace, lo haya puesto quien lo haya puesto: un enlace
    // a la página en la que ya estás no hace nada y se pulsa igual.
    $tramos[$ultimo]['destino'] = null;

    // Y la regla vale para TODO el rastro, no solo para la hoja. La ruta
    // `editar` de una matriz es la página del formulario, así que en los nueve
    // formularios el tramo de la matriz apuntaba a la pantalla que se estaba
    // viendo: «Mis Zonas / Zona / FIT / Formulario», y pulsar «FIT» recargaba
    // lo mismo. El tramo se sigue pintando -el nombre dice dónde estás- pero
    // sin enlace, igual que una matriz derivada.
    $aqui = url()->current();

    foreach ($tramos as $i => $tramo) {
        if ($tramo['destino'] === $aqui) {
            $tramos[$i]['destino'] = null;
        }
    }
@endphp

<nav aria-label="Migas de pan" {{ $attributes->merge(['class' => 'mb-4']) }}>
    <ol class="flex flex-wrap items-center gap-2 text-sm text-gray-500">
        @foreach($tramos as $i => $tramo)
            @if($i > 0)
                <li aria-hidden="true" class="text-gray-300">/</li>
            @endif

            <li>
                @if($tramo['destino'])
                    <a href="{{ $tramo['destino'] }}" class="hover:text-gray-900 hover:underline">
                        {{ $tramo['texto'] }}
                    </a>
                @elseif($i === $ultimo)
                    <span class="font-medium text-gray-900" aria-current="page">{{ $tramo['texto'] }}</span>
                @else
                    {{-- Tramo intermedio sin destino: una matriz derivada, que
                         no tiene formulario al que llevar. Ni enlace ni
                         aria-current, porque no es la página actual. --}}
                    <span>{{ $tramo['texto'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
