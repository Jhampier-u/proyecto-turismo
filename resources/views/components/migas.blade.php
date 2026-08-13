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
        $entrada  = \App\Matrices\Registro::ENTRADAS[$clave];
        $tramos[] = [
            'texto'   => $entrada['nombre'],
            'destino' => route($entrada['rutas']['editar'], $zona->id),
        ];
    }

    if ($actual !== null) {
        $tramos[] = ['texto' => $actual, 'destino' => null];
    }

    // El último nunca es enlace, lo haya puesto quien lo haya puesto: un enlace
    // a la página en la que ya estás no hace nada y se pulsa igual.
    $tramos[array_key_last($tramos)]['destino'] = null;
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
                @else
                    <span class="font-medium text-gray-900" aria-current="page">{{ $tramo['texto'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
