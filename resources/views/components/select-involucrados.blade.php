@props(['label', 'name', 'val', 'disabled' => false])

{{--
    Escala 0-3 de la Matriz de Involucrados Turísticos Territoriales.

    Con el instrumento en el nombre a propósito: select-0-3.blade.php ya existe
    y también cubre una escala 0-3, pero nació para FIT/FET con sus propias
    etiquetas ("Nulo/Bajo/Medio/Alto") — un nombre genérico atado a la
    semántica de esas dos matrices. select-0-2.blade.php tiene el mismo
    problema con Potencialidad ("Ausencia/Fragilidad/Aprovechable"). Aquí 0 no
    es "el peor valor de la escala": es "no posee el criterio", una valoración
    cualitativa distinta. Compartir el componente de FIT/FET habría mezclado
    dos vocabularios que significan cosas distintas con el mismo número.
--}}

@php
    // Mismo tratamiento que el resto de desplegables de escala: old() gana
    // sobre lo guardado tras un error de validación, y el valor llega como
    // string desde old() o int desde la base, nunca los dos a la vez.
    $val = old($name, $val);
    $val = ($val === null || $val === '') ? null : (int) $val;
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        <option value="" @selected($val === null)>— sin responder —</option>
        <option value="0" @selected($val === 0)>0 — No lo posee</option>
        <option value="1" @selected($val === 1)>1 — Poca</option>
        <option value="2" @selected($val === 2)>2 — Media</option>
        <option value="3" @selected($val === 3)>3 — Máxima</option>
    </select>
    @error($name)
        <span class="text-sm text-red-500">{{ $message }}</span>
    @enderror
</div>
