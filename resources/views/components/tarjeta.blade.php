@props(['padding' => true])

{{--
    La caja blanca sobre el fondo.

    Estaba escrita a mano 52 veces, cada una con su variación mínima de
    sombra, radio y espaciado. No es que faltara diseño: es que no había
    sistema, y cada vista reinventó el suyo.

    El borde a 80% de opacidad en vez de opaco es lo que separa una tarjeta
    del fondo sin dibujarle una raya: con shadow-sm sola las cajas se
    desdibujan sobre un fondo tan claro como #F8FAFC.

    Sin padding para las que envuelven una tabla a sangre: con él, la cabecera
    gris de la tabla queda flotando dentro de un marco blanco.
--}}

<div {{ $attributes->merge([
    'class' => 'bg-white border border-gray-200/80 rounded-xl shadow-sm' . ($padding ? ' p-6' : ''),
]) }}>
    {{ $slot }}
</div>
