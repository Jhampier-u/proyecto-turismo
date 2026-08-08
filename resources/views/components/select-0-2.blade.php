@props(['label' => null, 'name', 'val', 'disabled' => false])

@php
    // De la base llega int o null; de old() llega string. Sin normalizar, la
    // comparación estricta de @selected fallaría al repintar tras un error.
    $val = ($val === null || $val === '') ? null : (int) $val;
@endphp

<div class="{{ $label ? 'mb-3' : '' }}">
    @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500 text-sm"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- «Sin responder» tiene que ser distinguible de un 0 elegido a
             conciencia: 0 es «Ausencia», una valoración. Antes este desplegable
             preseleccionaba 0 y los dos casos se guardaban igual, así que 156
             campos sin mirar hundían la media sin que nadie lo notara. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        <option value="0" @selected($val === 0)>🔴 0 - Ausencia</option>
        <option value="1" @selected($val === 1)>🟡 1 - Fragilidad</option>
        <option value="2" @selected($val === 2)>🟢 2 - Aprovechable</option>
    </select>
    @error($name)
        <span class="text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>
