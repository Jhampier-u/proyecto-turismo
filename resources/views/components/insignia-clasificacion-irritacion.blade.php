@props(['valor'])

{{--
    Antes se llamaba insignia-clasificacion, a secas, como si "Bajo" fuera
    siempre verde y "Crítico" siempre rojo. Eso solo es cierto en una escala
    INVERSA como la de Irritación, donde 0 es el mejor resultado: en las otras
    seis matrices del sistema (por ejemplo EvaluacionPaisaje::lecturaDe(), que
    devuelve 'Alto'/'Medio'/'Bajo' con Bajo como el peor caso) reutilizar este
    componente pintaría de verde el peor resultado sin que ningún test lo
    note, porque la paleta de abajo es la lectura de Irritación, no un mapa de
    colores universal para cualquier clasificación de tres tramos. El nombre
    lleva "irritacion" para que la próxima matriz con escala normal no lo
    herede en silencio.

    Insignia que pinta una clasificación (Bajo / Moderado / Crítico) con su
    color. La usan hoy la leyenda y los resultados del Índice de Irritación
    —dos vistas distintas que antes repetían la misma paleta cada una por su
    lado—, así que el color vive aquí una sola vez.

    El color es una decisión de presentación, no vocabulario del instrumento:
    por eso no vive en App\Matrices\Irritacion junto a TRAMOS. Un valor sin
    color conocido cae en gris en vez de romper: la clave que llega siempre
    sale de clasificar(), pero degradar en silencio es más barato que un
    índice inexistente en producción por un tramo renombrado.

    El slot es opcional: sin él se pinta solo $valor (el uso de la tabla y los
    paneles de resultados); con él se pinta lo que traiga el slot dentro del
    mismo color de $valor (el uso de la leyenda del formulario, que además
    quiere mostrar el rango antes de la etiqueta).
--}}

@php
    $colores = [
        'Bajo'     => 'bg-green-100 text-green-800',
        'Moderado' => 'bg-yellow-100 text-yellow-800',
        'Crítico'  => 'bg-red-100 text-red-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-1 rounded text-sm font-semibold ' . ($colores[$valor] ?? 'bg-gray-100 text-gray-800')]) }}>
    {{ $slot->isNotEmpty() ? $slot : $valor }}
</span>
