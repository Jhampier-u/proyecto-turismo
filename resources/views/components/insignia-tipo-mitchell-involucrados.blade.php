@props(['valor'])

{{--
    Insignia de color para el tipo de Mitchell de un actor de la Matriz de
    Involucrados. El nombre lleva "involucrados" y no solo "mitchell": el
    color de cada tipo es una decisión de PRESENTACIÓN de esta matriz —igual
    que insignia-clasificacion-irritacion lleva "irritacion" en el nombre por
    su paleta, que tampoco es vocabulario universal de ninguna escala—, no
    parte del modelo académico de Mitchell, Agle y Wood. Un componente
    llamado solo "insignia-mitchell" invitaría a que otra matriz que también
    clasificara actores lo heredara en silencio con una paleta pensada para
    esta.

    Las clases de Tailwind viven aquí y no en App\Matrices\Involucrados, que
    no tiene ninguna: las dos cosas —componente sin el instrumento en el
    nombre y color colado en app/— fueron hallazgos de revisión en la matriz
    anterior (Irritación, ver el historial de insignia-clasificacion).

    Sin color conocido cae en gris en vez de romper, igual que su hermana de
    Irritación: tipoDe() siempre devuelve una de las ocho claves de abajo,
    pero degradar en silencio es más barato que una insignia en blanco por un
    nombre de tipo renombrado sin actualizar este mapa.
--}}

@php
    $colores = [
        // Los cuatro tipos "en tierra de nadie" (un solo atributo, o
        // ninguno) en tonos fríos y apagados; los otros cuatro —con dos o
        // tres atributos, más salientes para el instrumento— en tonos
        // cálidos, de menor a mayor urgencia de atención.
        'No es actor relevante' => 'bg-gray-100 text-gray-600',
        'Adormecido'            => 'bg-sky-100 text-sky-800',
        'Discrecional'          => 'bg-teal-100 text-teal-800',
        'Exigente'              => 'bg-blue-100 text-blue-800',
        'Dependiente'           => 'bg-amber-100 text-amber-800',
        'Dominante'             => 'bg-purple-100 text-purple-800',
        'Peligroso'             => 'bg-orange-100 text-orange-800',
        'Definitivo'            => 'bg-red-100 text-red-800',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-1 rounded text-sm font-semibold ' . ($colores[$valor] ?? 'bg-gray-100 text-gray-800')]) }}>
    {{ $valor }}
</span>
