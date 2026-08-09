{{--
    Un campo numérico por subtipo o subcategoría de Concentración.

    Sin desplegable a propósito -a diferencia de x-select-irritacion y el
    resto de matrices, aquí no hay una escala acotada que ofrecer: son
    conteos de establecimientos y de atractivos, sin tope-. data-grupo y
    data-seccion son ganchos para el JavaScript de subtotales en vivo del
    formulario (ver el <script> al final de form.blade.php); no participan
    ni en la validación ni en el guardado.
--}}
@props(['label', 'name', 'val', 'disabled' => false, 'grupo', 'seccion'])

@php
    // old() gana sobre lo guardado, igual que x-select-irritacion: si la
    // validación rechaza el envío, el formulario devuelve lo que el
    // evaluador acababa de teclear, no lo que ya hubiera en la base.
    $val = old($name, $val);
@endphp

<div>
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    {{-- value="" y no value="0" cuando $val es null: la casilla vacía es
         "todavía no lo he contado", distinto del 0 que sí es un dato -"no
         hay ninguno"-. Por eso tampoco hay un valor por defecto aquí. --}}
    <input type="number" min="0" step="1" inputmode="numeric"
           name="{{ $name }}" id="{{ $name }}" value="{{ $val }}"
           data-grupo="{{ $grupo }}" data-seccion="{{ $seccion }}"
           class="w-full text-base border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
           {{ $disabled ? 'disabled' : '' }}>
    @error($name)
        <span class="text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
