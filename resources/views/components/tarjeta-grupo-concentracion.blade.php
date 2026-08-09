{{--
    Una tarjeta de tipo (atractivos) o sector (planta): encabezado con su
    subtotal en vivo y la rejilla de campos numéricos del grupo.

    Se extrae porque las dos secciones del formulario -atractivos y
    planta- repetían la misma tarjeta letra por letra, con la única
    diferencia de qué nivel del array la alimenta: el mismo problema de
    "un bloque nuevo hay que tocarlo dos veces sin que nada avise si se
    olvida una" que evaluacion_irritacion/form.blade.php ya evitó al unir
    sus dos bloques en un solo @foreach.
--}}
@props(['titulo', 'campos', 'grupoId', 'seccion', 'evaluacion', 'disabled' => false, 'colorTexto' => 'text-gray-700'])

<div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
    <div class="flex justify-between items-center mb-3">
        <h5 class="font-semibold text-gray-800">{{ $titulo }}</h5>
        <span class="text-sm font-bold {{ $colorTexto }}">
            Subtotal: <span id="subtotal-{{ $grupoId }}">0</span>
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($campos as $campo => $etiqueta)
            <x-input-concentracion
                :label="$etiqueta"
                :name="$campo"
                :val="$evaluacion->$campo"
                :disabled="$disabled"
                :grupo="$grupoId"
                :seccion="$seccion" />
        @endforeach
    </div>
</div>
