@props(['texto' => null, 'zona' => null])

{{--
    A dónde vuelve "volver". Con $zona la pregunta deja de ser de rol: la
    navegación de una matriz es una jerarquía -matriz → zona → lista de
    zonas-, y "volver" desde una matriz baja un solo nivel, al panel de ESA
    zona, igual para admin, jefe y equipo. El rol solo sigue pintando
    destinos distintos cuando no hay zona a la que volver -en las listas de
    arriba del todo: admin.zonas.index para el admin, operativo.dashboard
    para el resto-, que es todo lo que queda del antiguo $readonly.

    Vive en un componente y no repetido en las vistas a propósito: un
    ternario replicado es exactamente la forma que tomó el fallo que dejó al
    admin viendo enlaces de edición durante toda una rama.

    El texto por defecto se deriva del mismo dato que el destino -zona
    primero, rol después- y nunca al revés: son la misma decisión. Si el
    destino mirara la zona y el texto siguiera mirando solo el rol, alguien
    acabaría poniendo un texto que no corresponde al sitio al que lleva -un
    botón que ya vuelve a la zona pero sigue diciendo "Mis Zonas"-. El prop
    $texto sigue permitiendo sobreescribirlo donde haga falta, siempre que no
    contradiga a dónde lleva de verdad.
--}}

@php
    $esAdmin = auth()->user()->esAdmin();

    $destino = $zona
        ? route('operativo.zona.panel', $zona->id)
        : ($esAdmin ? route('admin.zonas.index') : route('operativo.dashboard'));

    $textoPorDefecto = $zona
        ? 'Volver a la Zona'
        : ($esAdmin ? 'Volver a Zonas' : 'Mis Zonas');
@endphp

<a href="{{ $destino }}"
   {{ $attributes->merge(['class' => 'inline-flex items-center px-4 py-2 rounded-lg border border-gray-300 bg-white text-base text-gray-700 hover:bg-gray-50 shadow-sm']) }}>
    {{ $texto ?? $textoPorDefecto }}
</a>
