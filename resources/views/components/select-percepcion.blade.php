@props(['label', 'name', 'val', 'disabled' => false])

<div class="mb-3">
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500"
            {{ $disabled ? 'disabled' : '' }}>
        <option value="" {{ empty($val) ? 'selected' : '' }}>— Seleccione —</option>
        <option value="1" {{ ($val ?? 0) == 1 ? 'selected' : '' }}>1 — Negativo</option>
        <option value="2" {{ ($val ?? 0) == 2 ? 'selected' : '' }}>2 — Neutral</option>
        <option value="3" {{ ($val ?? 0) == 3 ? 'selected' : '' }}>3 — Positivo</option>
    </select>
    @error($name)
        <span class="text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>
