@props(['label', 'name', 'val', 'disabled' => false])

@php
    use App\Models\EvaluacionIrritacion;

    // old() gana sobre lo guardado: si la validación rechaza el envío, el
    // formulario tiene que devolver lo que el usuario acababa de responder.
    $val = old($name, $val);

    // De la base llega int o null; de old() llega string. Sin normalizar, la
    // comparación estricta de @selected fallaría justo al repintar tras el error.
    $val = ($val === null || $val === '') ? null : (int) $val;

    // La escala es INVERSA: 0 es el mejor caso y 10 el peor. La clasificación
    // se pide al modelo en vez de reimplementarse aquí: son los mismos
    // umbrales que se aplican al promedio del bloque, y duplicarlos dejaría
    // dos sitios que corregir el día que el instrumento cambie uno.
    $clasificacion = fn(int $n) => EvaluacionIrritacion::clasificar((float) $n);
@endphp

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full text-base border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        {{-- Sin responder tiene que ser distinguible de un 0 elegido a
             conciencia: aquí el 0 es el mejor resultado posible, no un hueco. --}}
        <option value="" @selected($val === null)>— sin responder —</option>
        @for($n = 0; $n <= 10; $n++)
            <option value="{{ $n }}" @selected($val === $n)>{{ $n }} — {{ $clasificacion($n) }}</option>
        @endfor
    </select>
    @error($name)
        <span class="text-sm text-red-600">{{ $message }}</span>
    @enderror
</div>
