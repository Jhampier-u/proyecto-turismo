@props(['campo', 'criterio', 'bloqueado' => false])

{{--
    Tres tarjetas seleccionables, cada una con la descripción textual de su
    nivel. La descripción es la opción: es lo que permite que dos evaluadores
    distintos puntúen igual, y evita tener que consultar el instrumento aparte.

    El estado vive en el `x-data` de la sección que envuelve al componente, no
    aquí: así la sección puede sumar el subtotal de todos sus criterios.
--}}
<fieldset class="border-b border-gray-200 py-4">
    <legend class="font-semibold text-gray-800 text-sm mb-3">
        {{ $criterio['nombre'] }}
        <span class="text-xs text-gray-400 font-normal">({{ $criterio['sigla'] }} · peso {{ $criterio['peso'] * 100 }}%)</span>
    </legend>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
        @foreach($criterio['niveles'] as $nivel => $descripcion)
            <label class="block border rounded-lg p-3 transition cursor-pointer"
                   :class="valores['{{ $campo }}'] === {{ $nivel }}
                       ? 'ring-2 ring-indigo-500 bg-indigo-50'
                       : 'bg-white hover:bg-gray-50'">
                <input type="radio"
                       name="{{ $campo }}"
                       value="{{ $nivel }}"
                       x-model.number="valores['{{ $campo }}']"
                       @disabled($bloqueado)
                       class="sr-only">
                <span class="block font-bold text-sm mb-1"
                      :class="valores['{{ $campo }}'] === {{ $nivel }} ? 'text-indigo-700' : 'text-gray-400'">
                    {{ $nivel }}
                </span>
                <span class="block text-xs text-gray-600 leading-snug">{{ $descripcion }}</span>
            </label>
        @endforeach
    </div>

    @error($campo)
        <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
    @enderror
</fieldset>
