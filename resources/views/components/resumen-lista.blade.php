@props([
    'sustantivo',
    'total',
    'incompletos',
    'plural'          => null,
    'faltante'        => 'sin completar',
    'puedeValidar'    => false,
    'rutaValidar'     => null,
    'avisoValidacion' => false,
    'jefe'            => null,
])

{{--
    Resumen de una lista de filas —actores, sitios— sobre su tabla: cuántas
    hay, cuántas están a medias, y la acción de validar junto al dato que dice
    si ya se puede.

    No deriva nada: cada vista le pasa sus números ya resueltos, igual que
    <x-barra-lateral-formulario>. Las dos listas que lo usan cuentan cosas
    distintas —un actor incompleto puede serlo por varios campos, un sitio solo
    por su DET—, y forzar una forma común entre ellas es lo que produjo el tipo
    `actores` con zona->involucrados() cableado dentro.

    NO es la barra lateral de los formularios de matriz ni se le parece por
    dentro: aquella indexa bloques de criterios y esta cuenta filas. Se
    parecerían en la pantalla y significarían cosas distintas, que es
    exactamente el error que este proyecto ya pagó dos veces.

    El plural se recibe porque en castellano no es añadir una s: «actor» da
    «actores». Por defecto se compone, que sirve para «sitio».
--}}

@php
    $plural ??= $sustantivo . 's';

    // rutaValidar vale null por defecto: con puedeValidar cierto y sin ruta,
    // el formulario de abajo saldría con action="" -la URL del propio
    // índice, que solo tiene GET- y el POST moriría en un 405 silencioso.
    // Mismo patrón que <x-barra-lateral-formulario>: revienta aquí, en vez
    // de dejar que la vista adivine.
    if ($puedeValidar && $rutaValidar === null) {
        throw new \InvalidArgumentException(
            '<x-resumen-lista>: :ruta-validar es obligatoria cuando :puede-validar es cierto.'
        );
    }
@endphp

{{--
    La caja es <x-tarjeta>, no una escrita a mano. La revisión de
    fundacion-visual dejó esta franja fuera del sistema con una premisa falsa:
    que convertirla exigía mover su `flex` a un hijo. No es cierto —
    $attributes->merge concatena sobre el mismo <div>, así que el flex, el
    espaciado y el margen siguen actuando donde actuaban.

    :padding="false" conservando el p-4 propio: esta franja es compacta a
    propósito —va pegada sobre una tabla— y el p-6 del componente la
    engordaría. Es la misma regla que siguió profile/edit con su p-4 sm:p-8.
--}}
<x-tarjeta :padding="false" class="p-4 mb-6 flex flex-wrap items-center justify-between gap-4">

    <div class="flex flex-wrap items-center gap-x-6 gap-y-2 text-base text-gray-700">
        {{-- (int) porque un total="1" en cadena -viajando por un atributo
             Blade sin ":"- no es === 1, y el singular se perdería en «1
             actores». --}}
        <span class="font-medium">{{ $total }} {{ (int) $total === 1 ? $sustantivo : $plural }}</span>

        @if($incompletos > 0)
            <span class="text-amber-700">{{ $incompletos }} {{ $faltante }}</span>
        @elseif($total > 0)
            {{-- Con la lista vacía no se dice «todos completos»: no hay nada
                 completo, y afirmarlo invita a validar lo que no se puede. --}}
            <span class="text-green-700">todos completos</span>
        @endif

        {{--
            Sin color fijo: la ranura la usa hoy Frecuentación para la ST, que
            a veces es un dato neutro («ST: 1200») y a veces la única cosa que
            falta para poder validar («falta la Superficie Territorial»). El
            color es de quien conoce ese significado -la vista que llena la
            ranura-, no de este componente, que no deriva nada y no sabe
            distinguir un dato de un motivo de bloqueo.
        --}}
        @if(trim($slot) !== '')
            <span>{{ $slot }}</span>
        @endif
    </div>

    <div>
        @if($puedeValidar)
            <form action="{{ $rutaValidar }}" method="POST"
                  onsubmit="return confirm('Al validar, la lista queda cerrada para el equipo. ¿Continuar?');">
                @csrf
                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-5 rounded shadow">
                    Validar y Cerrar la Lista
                </button>
            </form>
        @elseif($avisoValidacion)
            {{-- El equipo no recibe un botón gris sino el texto que dice quién
                 valida: regla global de «sin botones desactivados». --}}
            <p class="text-sm text-amber-700">
                Lista para validar — avísale a {{ $jefe ?? 'tu Jefe de Zona' }}
            </p>
        @endif
    </div>

</x-tarjeta>
