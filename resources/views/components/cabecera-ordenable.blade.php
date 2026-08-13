@props(['columna', 'orden', 'dir'])

{{--
    Una cabecera de tabla que ordena.

    Es un ENLACE y no un botón con JavaScript, por tres motivos que apuntan al
    mismo sitio: el orden se puede compartir en una URL, funciona sin JS, y lo
    puede comprobar la suite de esta máquina —donde Playwright no está
    instalado, así que una ordenación con Alpine sería una funcionalidad que
    ningún test podría ver—.

    Pulsar la columna activa invierte el sentido; pulsar otra empieza en
    ascendente, sea cual sea. Una regla sin excepciones por columna.

    `aria-sort` va solo en la activa —es lo que un lector de pantalla
    anuncia—, y por eso vive aquí y no repartido por cada <th> de la vista,
    donde habría que acordarse de quitarlo de las otras dos.

    fullUrlWithQuery conserva el resto de parámetros de la URL: hoy no hay
    ninguno más, pero el día que lo haya, cambiar de orden no debería
    perderlos.
--}}

@php
    $activa = $orden === $columna;

    $siguienteDir = $activa && $dir === 'asc' ? 'desc' : 'asc';

    $ariaSort = match (true) {
        ! $activa      => null,
        $dir === 'asc' => 'ascending',
        default        => 'descending',
    };
@endphp

<th scope="col"
    @if($ariaSort) aria-sort="{{ $ariaSort }}" @endif
    {{ $attributes->merge(['class' => 'px-6 py-3 text-left text-sm font-medium text-gray-600']) }}>
    <a href="{{ request()->fullUrlWithQuery(['orden' => $columna, 'dir' => $siguienteDir]) }}"
       class="inline-flex items-center gap-1 hover:text-gray-900">
        {{ $slot }}
        {{-- La flecha es decorativa: quien no ve la pantalla ya tiene
             aria-sort, y leerle «flecha arriba» sería decirlo dos veces mal. --}}
        <span aria-hidden="true" class="{{ $activa ? 'text-gray-900' : 'text-gray-300' }}">
            {{ $activa && $dir === 'desc' ? '↓' : '↑' }}
        </span>
    </a>
</th>
