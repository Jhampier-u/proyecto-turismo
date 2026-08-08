@props(['nombre', 'zona', 'rutaFormulario'])

{{--
    Aviso compartido por las cinco vistas de resultados cuando no hay nada que
    calcular: la matriz no existe todavía, o existe a medias y sus totales están
    en null. Sin esto, number_format(null) pinta «0,00» y ese cero es
    indistinguible de un territorio valorado con ceros de verdad.

    Es un componente y no cinco copias a propósito: el conocimiento repetido
    entre vistas es lo que dejó la Matriz de Paisaje sin enlace en el admin
    durante meses.
--}}

@php
    // Del rol, no de una variable del controlador, por el mismo motivo que en
    // las vistas de resultados: una variable que alguien olvida pasar se queda
    // en falso para siempre sin que nada lo detecte.
    $readonly = ! auth()->user()->puedeEditarEvaluaciones();
@endphp

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-6 rounded shadow">
            <h3 class="font-bold text-lg mb-2">{{ $nombre }} sin resultados</h3>
            <p class="text-base">
                <strong>{{ $zona->nombre }}</strong>:
                {{-- En una sola línea a propósito: hay un test que busca esta
                     frase literal, y un salto la partiría en el HTML. --}}
                esta matriz todavía no está completa, así que no hay resultado que calcular.
                @if($readonly)
                    Aparecerá aquí en cuanto la zona termine de responderla.
                @else
                    Responde los criterios que faltan y volverá a aparecer.
                @endif
            </p>
        </div>

        <div class="mt-6 text-center">
            <a href="{{ $readonly
                        ? route('operativo.zona.panel', $zona->id)
                        : route($rutaFormulario, $zona->id) }}"
               class="inline-block px-5 py-2 bg-gray-200 text-black font-bold rounded-lg hover:bg-gray-400 shadow">
                {{ $readonly ? 'Volver a la zona' : 'Ir al formulario' }}
            </a>
        </div>
    </div>
</div>
