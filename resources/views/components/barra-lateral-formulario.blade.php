@props(['clave', 'zona', 'secciones', 'bloqueado', 'formulario'])

{{--
    Barra lateral fija de un formulario de matriz: cuántos criterios lleva
    el evaluador, un índice de sus bloques con los completos marcados, y el
    botón de guardar siempre a la vista -sin subir ni bajar hasta los
    extremos del formulario-.

    No deriva el índice de bloques por su cuenta -a diferencia de
    <x-pestanas-matriz>, que sí deriva TODO de $clave y Registro-: las
    diez matrices no comparten una forma común para sus bloques (con
    'criterios' en FIT/FET/Paisaje, con 'items' en Percepción, planas en
    Irritación, en dos niveles en Concentración, sin envoltorio en
    Valoración Territorial). Cada vista resuelve su propio $secciones y
    este componente solo lo pinta, igual que <x-criterio-pildoras> no sabe
    de dónde viene su criterio.

    El total SÍ se deriva de Registro/EstadoZona -como <x-pestanas-matriz>-
    porque ese número es común a las diez matrices.

    Oculto por debajo de 1024px (lg): el formulario vuelve a su única
    columna de siempre, con el botón de guardar de siempre al final. La
    barra es una mejora cuando hay sitio, nunca un requisito para guardar.
--}}

@php
    $entrada = \App\Matrices\Registro::ENTRADAS[$clave];
    $modelo  = $entrada['modelo'];

    $evaluacion  = $modelo::where('zona_id', $zona->id)->first();
    $total       = $entrada['criterios'];
    $respondidos = $evaluacion
        ? \App\Servicios\EstadoZona::criteriosRespondidos($evaluacion)
        : 0;

    $porcentaje = $total > 0 ? round($respondidos / $total * 100) : 0;
@endphp

<aside class="hidden lg:block lg:sticky lg:top-6 lg:self-start w-64 shrink-0">
    <div class="bg-white shadow-sm rounded-lg border border-gray-200 p-4">

        <p class="text-sm font-medium text-gray-900">
            {{ $respondidos }} de {{ $total }} respondidos
        </p>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden mt-2 mb-4">
            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $porcentaje }}%"></div>
        </div>

        <nav class="space-y-1 mb-4">
            @foreach ($secciones as $seccion)
                @php
                    // Un 0 respondido es un dato, no un hueco: las tres
                    // ramas muestran "X/Y" siempre y solo cambian el color
                    // y el marcador, nunca si se ve el número.
                    $completa = $seccion['respondidos'] === $seccion['total'];
                    $empezada = $seccion['respondidos'] > 0;
                    $color    = $completa ? 'text-green-700' : ($empezada ? 'text-gray-900' : 'text-gray-500');
                @endphp
                <a href="#{{ $seccion['ancla'] }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded text-sm hover:bg-gray-50 {{ $color }}">
                    <span class="truncate">
                        @if($completa)<span class="text-green-600">✓</span>@endif
                        {{ $seccion['etiqueta'] }}
                    </span>
                    <span class="text-sm text-gray-400 shrink-0">
                        {{ $seccion['respondidos'] }}/{{ $seccion['total'] }}
                    </span>
                </a>
            @endforeach
        </nav>

        @unless($bloqueado)
            <button type="submit" form="{{ $formulario }}" name="accion_estado" value="borrador"
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">
                Guardar Borrador
            </button>
        @endunless
    </div>
</aside>
