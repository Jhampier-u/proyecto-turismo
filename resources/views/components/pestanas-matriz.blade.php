@props(['clave', 'zona', 'activa' => 'formulario'])

{{--
    Navegación entre las dos caras de una matriz.

    Antes eran nueve enlaces escritos a mano, cada uno con su texto y su sitio,
    y ninguno decía si al otro lado había algo que ver. Ese conocimiento
    repetido es el que dejó a Paisaje sin enlace en el admin durante meses.

    Cuando la matriz no está completa, «Resultados» NO es un botón gris: es
    texto con candado y el motivo. Un botón desactivado no explica nada y se
    pulsa igual.
--}}

@php
    $entrada = \App\Matrices\Registro::ENTRADAS[$clave];
    $modelo  = $entrada['modelo'];

    $evaluacion  = $modelo ? $modelo::where('zona_id', $zona->id)->first() : null;
    $total       = $entrada['criterios'];
    $respondidos = $evaluacion
        ? \App\Servicios\EstadoZona::criteriosRespondidos($evaluacion)
        : 0;

    // El estado manda sobre el recuento, no al revés: Potencialidad es
    // configurable -el Jefe de Zona puede activar solo una parte de sus 156
    // criterios-, así que puede quedar VALIDADA con muchos menos de 156
    // respondidos. Contar primero pintaba candado y «20 de 156 criterios»
    // sobre una matriz confirmada con resultados ya calculados. Confirmado
    // siempre implica que hay resultados que enseñar; el recuento solo
    // decide mientras la matriz sigue sin validar. Esto deja coherentes tres
    // respuestas a la misma pregunta: EstadoZona::filaMatriz() ya mira el
    // estado primero, y evaluacion_potencialidad/ponderacion.blade.php mira
    // si los totales (fn_total/fx_total) son nulos.
    if ($evaluacion && $evaluacion->estado === 'confirmado') {
        $completa = true;
    } elseif ($entrada['tipo'] === 'actores') {
        // Involucrados no es un formulario de criterios sino un CRUD de
        // actores: su 'criterios' en el registro es null y «completa» no es
        // un recuento, es «hay al menos un actor y ninguno a medias».
        $actores  = $zona->involucrados();
        $completa = $actores->count() > 0 && ! $actores->incompletos()->exists();
    } elseif ($entrada['tipo'] === 'sitios') {
        // Misma idea que la rama de actores, con una segunda condición: la
        // ST vive en $evaluacion (el modelo de la entrada es
        // FrecuentacionConfig, ya cargado arriba), no en cada sitio. Las dos
        // se guardan en variables con nombre -y no fundidas en un solo
        // "&&"- porque el candado de abajo necesita saber CUÁL de las dos
        // falla: con los sitios completos pero sin ST, "sin sitios
        // completos" es sencillamente falso, y la franja de resumen de la
        // misma página ya dice lo contrario tres líneas más abajo.
        $sitios          = $zona->frecuentacionSitios();
        $sitiosCompletos = $sitios->count() > 0 && ! $sitios->incompletos()->exists();
        $stDefinida      = ($evaluacion?->st ?? null) !== null && $evaluacion->st > 0;
        $completa        = $sitiosCompletos && $stDefinida;
    } else {
        $completa = $total !== null && $respondidos >= $total;
    }

    // Clases completas: Tailwind purga las construidas por concatenación.
    $estiloActiva   = 'border-indigo-600 text-indigo-700 font-medium';
    $estiloInactiva = 'border-transparent text-gray-600 hover:text-gray-900 hover:border-gray-300';
@endphp

<div class="border-b border-gray-200 mb-6 flex gap-6">
    <a href="{{ route($entrada['rutas']['editar'], $zona->id) }}"
       class="pb-3 border-b-2 text-base {{ $activa === 'formulario' ? $estiloActiva : $estiloInactiva }}">
        Formulario
    </a>

    @if($completa)
        <a href="{{ route($entrada['rutas']['ver'], $zona->id) }}"
           class="pb-3 border-b-2 text-base {{ $activa === 'resultados' ? $estiloActiva : $estiloInactiva }}">
            Resultados
        </a>
    @elseif($entrada['tipo'] === 'actores')
        <span class="pb-3 border-b-2 border-transparent text-base text-gray-400 flex items-center gap-2">
            <x-icono nombre="candado" class="w-4 h-4" />
            Resultados
            <span class="text-sm">— sin actores completos</span>
        </span>
    @elseif($entrada['tipo'] === 'sitios')
        <span class="pb-3 border-b-2 border-transparent text-base text-gray-400 flex items-center gap-2">
            <x-icono nombre="candado" class="w-4 h-4" />
            Resultados
            {{--
                Con los sitios completos y solo la ST pendiente, "sin sitios
                completos" sería falso: los sitios SÍ están completos. El
                motivo real es el mismo que bloquea la validación, así que se
                nombra en vez de repetir una frase que ya no aplica.
            --}}
            <span class="text-sm">— {{ $sitiosCompletos && ! $stDefinida ? 'falta la Superficie Territorial' : 'sin sitios completos' }}</span>
        </span>
    @else
        <span class="pb-3 border-b-2 border-transparent text-base text-gray-400 flex items-center gap-2">
            <x-icono nombre="candado" class="w-4 h-4" />
            Resultados
            <span class="text-sm">— {{ $respondidos }} de {{ $total }} criterios</span>
        </span>
    @endif
</div>
