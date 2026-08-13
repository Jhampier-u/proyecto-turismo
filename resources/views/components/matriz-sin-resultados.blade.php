@props(['nombre', 'zona', 'rutaFormulario'])

{{--
    Aviso compartido por las vistas de resultados cuando no hay nada que
    calcular: la matriz no existe todavía, o existe a medias y sus totales están
    en null. Sin esto, number_format(null) pinta «0,00» y ese cero es
    indistinguible de un territorio valorado con ceros de verdad.

    Es un componente y no varias copias a propósito: el conocimiento repetido
    entre vistas es lo que dejó la Matriz de Paisaje sin enlace en el admin
    durante meses.
--}}

<div class="py-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-yellow-50 border-l-4 border-yellow-500 text-yellow-800 p-6 rounded shadow">
            <h3 class="font-bold text-lg mb-2">{{ $nombre }} sin resultados</h3>
            <p class="text-base">
                <strong>{{ $zona->nombre }}</strong>:
                {{-- En una sola línea a propósito: hay un test que busca esta
                     frase literal, y un salto la partiría en el HTML. --}}
                esta matriz todavía no está completa, así que no hay resultado que calcular.
                Responde los criterios que faltan y volverá a aparecer.
            </p>

            {{-- Slot opcional: alguna matriz necesita precisar qué le falta
                 —el Índice de Irritación tiene dos bloques independientes y
                 "no está completa" por sí solo no dice si falta uno o los
                 dos—. Sin contenido no se pinta nada de más. --}}
            @if($slot->isNotEmpty())
                <p class="text-sm mt-2">{{ $slot }}</p>
            @endif
        </div>

        {{-- Todos pueden ir al formulario ahora, admin incluido: el enlace ya
             no depende del rol. El botón de volver siempre tiene una zona
             aquí -es el prop que ya recibe este componente-, así que vuelve
             al panel de esa zona para los tres roles, no a una lista. --}}
        <div class="mt-6 flex justify-center gap-3">
            <x-boton :href="route($rutaFormulario, $zona->id)">
                Ir al formulario
            </x-boton>
            <x-boton-volver :zona="$zona" />
        </div>
    </div>
</div>
