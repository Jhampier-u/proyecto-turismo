@props(['label', 'name', 'val', 'etiquetas', 'disabled' => false])

{{--
    Escala 0-3 de la Matriz de Involucrados Turísticos Territoriales.

    Con el instrumento en el nombre a propósito: select-0-3.blade.php también
    cubría una escala 0-3, pero había nacido para FIT/FET con sus propias
    etiquetas ("Nulo/Bajo/Medio/Alto") — un nombre genérico atado a la
    semántica de esas dos matrices. select-0-2.blade.php tenía el mismo
    problema con Potencialidad ("Ausencia/Fragilidad/Aprovechable"). Aquí 0 no
    es "el peor valor de la escala": es "no posee el criterio", una valoración
    cualitativa distinta. Compartir el componente de FIT/FET habría mezclado
    dos vocabularios que significan cosas distintas con el mismo número.
    (select-0-3 y select-0-2 se borraron en cabos-sueltos, cabo 4, al migrar
    FIT/FET/Potencialidad a <x-criterio-pildoras>; esta nota se conserva
    porque el motivo del nombre sigue siendo el mismo.)

    Las CUATRO PALABRAS no viven aquí: llegan por :etiquetas desde
    App\Matrices\Involucrados::etiquetasEscala(), porque el vocabulario de
    urgencia ("nada sensible / poco sensible...") no es el de poder y
    legitimidad ("no lo posee / poca..."). El componente solo sabe pintar
    cuatro opciones sobre los valores 0-3; cuáles son esas cuatro palabras
    para cada campo es conocimiento del instrumento, no de la vista.
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
        @foreach($etiquetas as $valor => $texto)
            <option value="{{ $valor }}" @selected($val === $valor)>{{ $valor }} — {{ $texto }}</option>
        @endforeach
    </select>
    @error($name)
        <span class="text-sm text-red-500">{{ $message }}</span>
    @enderror
</div>
