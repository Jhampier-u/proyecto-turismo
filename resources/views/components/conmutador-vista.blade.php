@props(['modelo'])

{{--
    Los dos botones de lista/tarjetas, y nada más.

    No sabe dónde se guarda la preferencia ni cómo se llama la variable: eso lo
    pone quien lo usa. Así el mismo control sirve en Inventario y en las dos
    vistas de zonas sin que ninguna herede las decisiones de otra.
--}}

<div class="inline-flex rounded-lg bg-gray-100 p-1">
    <button type="button" @click="{{ $modelo }} = 'lista'"
            :class="{{ $modelo }} === 'lista' ? 'bg-white shadow text-blue-700' : 'text-gray-500 hover:text-gray-700'"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition">
        <x-icono nombre="lista" class="w-4 h-4" />
        Lista
    </button>

    <button type="button" @click="{{ $modelo }} = 'tarjetas'"
            :class="{{ $modelo }} === 'tarjetas' ? 'bg-white shadow text-blue-700' : 'text-gray-500 hover:text-gray-700'"
            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm font-medium transition">
        <x-icono nombre="cuadricula" class="w-4 h-4" />
        Tarjetas
    </button>
</div>
