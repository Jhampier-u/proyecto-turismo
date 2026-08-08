@props(['label', 'name', 'val', 'disabled' => false])

@php
    // De la base llega int o null; de old() llega string. Sin normalizar, la
    // comparación estricta de @selected fallaría al repintar tras un error.
    $val = ($val === null || $val === '') ? null : (int) $val;
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- La opción vacía ya existía, pero se marcaba con empty(), que
             también es cierto para el 0. La escala de Percepción empieza en 1,
             así que hoy no da guerra; con la comparación estricta deja de
             depender de eso. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        <option value="1" @selected($val === 1)>1 — Negativo</option>
        <option value="2" @selected($val === 2)>2 — Neutral</option>
        <option value="3" @selected($val === 3)>3 — Positivo</option>
    </select>
    @error($name)
        <span class="text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>
