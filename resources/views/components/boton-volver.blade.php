@props(['texto' => null])

{{--
    A dónde vuelve cada rol. Es lo único que sobrevive del antiguo $readonly,
    que decidía dos cosas bajo un nombre: si podías editar —ya no aplica, el
    admin también edita— y a dónde volvías.

    Vive en un componente y no repetido en las diecinueve vistas a propósito:
    ese ternario replicado es exactamente la forma que tomó el fallo que dejó
    al admin viendo enlaces de edición durante toda una rama.

    El texto por defecto se deriva del rol igual que el destino: son la misma
    decisión. Si uno dependiera del rol y el otro no, alguien acabaría
    poniendo un texto que no corresponde al sitio al que lleva. El prop
    $texto sigue permitiendo sobreescribirlo donde haga falta.
--}}

@php
    $esAdmin = auth()->user()->esAdmin();
@endphp

<a href="{{ $esAdmin ? route('admin.zonas.index') : route('operativo.dashboard') }}"
   {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-base text-gray-700 hover:bg-gray-50 shadow-sm']) }}>
    {{ $texto ?? ($esAdmin ? 'Volver a Zonas' : 'Mis Zonas') }}
</a>
