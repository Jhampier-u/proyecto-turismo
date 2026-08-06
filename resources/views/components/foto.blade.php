@props(['ruta' => null, 'alt' => 'Imagen'])

{{--
    Renderiza una imagen almacenada en el disco por defecto.

    Storage::url() resuelve la URL según el disco configurado: una ruta relativa
    con el disco 'public' local, o la URL completa del CDN con almacenamiento
    S3. Así la misma vista sirve en desarrollo y en producción.

    Si el archivo ya no existe —caso frecuente con los registros anteriores a
    tener almacenamiento persistente— se muestra un marcador en lugar del icono
    de imagen rota del navegador.
--}}

@php
    $clasesMarcador = $attributes->get('class', '')
        . ' bg-gray-100 border border-dashed border-gray-300 flex items-center justify-center text-gray-400 text-xs text-center px-2';
@endphp

@if($ruta)
    <img src="{{ Storage::url($ruta) }}"
         alt="{{ $alt }}"
         {{ $attributes }}
         data-marcador="{{ $clasesMarcador }}"
         onerror="this.onerror=null;
                  this.outerHTML='<div class=&quot;' + this.dataset.marcador + '&quot;>Imagen no disponible</div>';">
@else
    <div class="{{ $clasesMarcador }}">Sin imagen</div>
@endif
