@props(['label' => null, 'name', 'val', 'disabled' => false])

<div class="{{ $label ? 'mb-3' : '' }}">
    @if($label)
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-1">{{ $label }}</label>
    @endif
    <select name="{{ $name }}" id="{{ $name }}"
            class="w-full border-gray-300 rounded shadow-sm focus:ring-indigo-500 focus:border-indigo-500 disabled:bg-gray-100 disabled:text-gray-500 text-sm"
            {{ $disabled ? 'disabled' : '' }}>
        <option value="0" {{ ($val ?? 0) == 0 ? 'selected' : '' }}>🔴 0 - Ausencia</option>
        <option value="1" {{ ($val ?? 0) == 1 ? 'selected' : '' }}>🟡 1 - Fragilidad</option>
        <option value="2" {{ ($val ?? 0) == 2 ? 'selected' : '' }}>🟢 2 - Aprovechable</option>
    </select>
    @error($name)
        <span class="text-xs text-red-500">{{ $message }}</span>
    @enderror
</div>
