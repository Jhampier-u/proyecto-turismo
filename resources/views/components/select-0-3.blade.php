@props(['label', 'name', 'val', 'disabled' => false])

@php
    // old() gana sobre lo guardado: si la validación rechaza el envío, el
    // formulario tiene que devolver lo que el usuario acababa de responder y no
    // lo último que se guardó. Perder diecisiete respuestas por olvidar una es
    // el mismo dolor que el guardado parcial viene a quitar.
    $val = old($name, $val);

    // De la base llega int o null; de old() llega string. Sin normalizar, la
    // comparación estricta de @selected fallaría justo al repintar tras el error.
    $val = ($val === null || $val === '') ? null : (int) $val;
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- Sin responder tiene que ser distinguible de un 0 elegido a
             conciencia: 0 es «Nulo», una valoración, no un hueco. Antes este
             desplegable preseleccionaba 0 y los dos casos se guardaban igual. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        <option value="0" @selected($val === 0)>0 - Nulo</option>
        <option value="1" @selected($val === 1)>1 - Bajo</option>
        <option value="2" @selected($val === 2)>2 - Medio</option>
        <option value="3" @selected($val === 3)>3 - Alto</option>
    </select>
    @error($name)
        <span class="text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>
