{{--
    Antes se llamaba select-0-10, por su escala. Pero a diferencia de sus
    hermanos (select-0-3, select-0-2, select-percepcion, todos autónomos y ya
    borrados junto con las matrices que migraron a <x-criterio-pildoras>;
    select-involucrados es el único que queda), este desplegable llama a
    Irritacion::ESCALA_MIN/MAX y clasificar(): pinta «7 — Crítico», no un 7
    suelto. Esa clasificación es semántica del instrumento —qué valor es
    bueno, qué significa cada número—, no de la escala 0-10 en abstracto. La
    próxima matriz que también vaya de 0 a 10 no tiene por qué compartir esa
    lectura, así que el nombre lleva "irritacion" para que nadie la reutilice
    pensando que "0 a 10" ya implica "escala inversa de Irritación".
--}}
@props(['label', 'name', 'val', 'disabled' => false])

@php
    // old() gana sobre lo guardado: si la validación rechaza el envío, el
    // formulario tiene que devolver lo que el usuario acababa de responder.
    $val = old($name, $val);

    // De la base llega int o null; de old() llega string. Sin normalizar, la
    // comparación estricta de @selected fallaría justo al repintar tras el error.
    $val = ($val === null || $val === '') ? null : (int) $val;
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full text-base border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- Sin responder tiene que ser distinguible de un 0 elegido a
             conciencia: aquí el 0 es el mejor resultado posible, no un hueco. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        {{-- La escala es INVERSA: 0 es el mejor caso y 10 el peor. Se
             consulta la definición del instrumento —rango y umbrales— en vez
             de reimplementarla aquí: son los mismos que se aplican al
             promedio del bloque, y duplicarlos dejaría dos sitios que
             corregir el día que el instrumento cambie uno. --}}
        @for($n = \App\Matrices\Irritacion::ESCALA_MIN; $n <= \App\Matrices\Irritacion::ESCALA_MAX; $n++)
            <option value="{{ $n }}" @selected($val === $n)>{{ $n }} — {{ \App\Matrices\Irritacion::clasificar($n) }}</option>
        @endfor
    </select>
    @error($name)
        <span class="text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
