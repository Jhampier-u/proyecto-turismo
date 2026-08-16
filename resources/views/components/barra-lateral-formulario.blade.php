@props(['clave', 'zona', 'secciones', 'bloqueado', 'formulario', 'total' => null, 'respondidos' => null])

{{--
    Barra lateral fija de un formulario de matriz: cuántos criterios lleva
    el evaluador, un índice de sus bloques con los completos marcados, y el
    botón de guardar siempre a la vista -sin subir ni bajar hasta los
    extremos del formulario-.

    No deriva el índice de bloques por su cuenta -a diferencia de
    <x-pestanas-matriz>, que sí deriva TODO de $clave y Registro-: las
    diez matrices no comparten una forma común para sus bloques (con
    'criterios' en FIT/FET/Paisaje, con 'items' en Percepción, planas en
    Irritación, en dos niveles en Concentración, sin envoltorio en
    Valoración Territorial). Cada vista resuelve su propio $secciones y
    este componente solo lo pinta, igual que <x-criterio-pildoras> no sabe
    de dónde viene su criterio.

    El total de cabecera SÍ se deriva de Registro/EstadoZona por defecto
    -como <x-pestanas-matriz>- partiendo de que ese número es común a las
    diez matrices. Esa suposición es FALSA para Potencialidad: el Jefe de
    Zona elige qué criterios aplican (PotencialidadCamposActivos), así que
    su denominador se mueve -156 deja de significar nada- y
    EstadoZona::criteriosRespondidos() tampoco sirve, porque cuenta
    cualquier columna no nula sin distinguir un campo activo de uno
    desactivado que conserva a propósito una respuesta antigua
    ("por si vuelve a activarse", ver EvaluacionPotencialidadController::
    prepararDatos()). :total y :respondidos son la válvula de escape para
    ESE caso -no un prop genérico "por si acaso"-: si se pasan, sustituyen
    el cálculo de aquí abajo entero. Se exige que vengan juntos o ninguno
    -nunca uno solo- porque un numerador calculado con un denominador
    ajeno es la manera más fácil de fabricar un «X de Y» que no significa
    nada; si alguien pasa solo uno, esto revienta en vez de adivinar.

    Oculto por debajo de 1024px (lg): el formulario vuelve a su única
    columna de siempre, con el botón de guardar de siempre al final. La
    barra es una mejora cuando hay sitio, nunca un requisito para guardar.
--}}

@php
    if (($total === null) !== ($respondidos === null)) {
        throw new \InvalidArgumentException(
            '<x-barra-lateral-formulario>: :total y :respondidos se pasan juntos o ninguno de los dos.'
        );
    }

    if ($total === null) {
        $entrada = \App\Matrices\Registro::ENTRADAS[$clave];
        $modelo  = $entrada['modelo'];

        $evaluacion  = $modelo::where('zona_id', $zona->id)->first();
        $total       = $entrada['criterios'];
        $respondidos = $evaluacion
            ? \App\Servicios\EstadoZona::criteriosRespondidos($evaluacion)
            : 0;
    }

    $porcentaje = $total > 0 ? round($respondidos / $total * 100) : 0;

    // Deriva de la base, no de la rama de arriba: cuando el llamante pasa
    // :total y :respondidos -Potencialidad-, $evaluacion no se llega a cargar.
    //
    // Para las otras siete vistas esto repite la misma consulta que ya hizo
    // el bloque de arriba (la de $evaluacion, sin el :total explícito): es
    // una columna, una vez por render, y se acepta a sabiendas -la
    // alternativa era pasar un prop por las ocho vistas con el nombre de
    // variable cambiando en cada una, que es justo el problema que
    // <x-franja-matriz> ya resolvió derivando en vez de recibiendo-.
    //
    // Nombrado aparte de $entrada/$modelo/$evaluacion de arriba, y sin la
    // palabra "franja": esto no tiene nada que ver con <x-franja-matriz> -es
    // el aviso de reapertura del botón de la barra lateral-, y reutilizar su
    // nombre solo invitaría a confundirlo con un hermano real.
    $entradaMatriz      = \App\Matrices\Registro::ENTRADAS[$clave];
    $modeloMatriz       = $entradaMatriz['modelo'];
    $filaEstado         = $modeloMatriz::where('zona_id', $zona->id)->first(['estado']);
    $evaluacionValidada = $filaEstado?->estado === 'confirmado';
@endphp

<aside class="hidden lg:block lg:sticky lg:top-6 lg:self-start w-64 shrink-0">
    <x-tarjeta :padding="false" class="p-4">

        <p class="text-sm font-medium text-gray-900">
            {{ $respondidos }} de {{ $total }} respondidos
        </p>
        <div class="h-2 bg-gray-200 rounded-full overflow-hidden mt-2 mb-4">
            <div class="h-full bg-indigo-600 rounded-full" style="width: {{ $porcentaje }}%"></div>
        </div>

        <nav class="space-y-1 mb-4">
            @foreach ($secciones as $seccion)
                @php
                    // Un 0 respondido es un dato, no un hueco: las tres
                    // ramas muestran "X/Y" siempre y solo cambian el color
                    // y el marcador, nunca si se ve el número.
                    $completa = $seccion['respondidos'] === $seccion['total'];
                    $empezada = $seccion['respondidos'] > 0;
                    $color    = $completa ? 'text-green-700' : ($empezada ? 'text-gray-900' : 'text-gray-500');
                @endphp
                <a href="#{{ $seccion['ancla'] }}"
                   class="flex items-center justify-between gap-2 px-2 py-1.5 rounded text-sm hover:bg-gray-50 {{ $color }}">
                    <span class="truncate">
                        @if($completa)<span class="text-green-600">✓</span>@endif
                        {{ $seccion['etiqueta'] }}
                    </span>
                    <span class="text-sm text-gray-400 shrink-0">
                        {{ $seccion['respondidos'] }}/{{ $seccion['total'] }}
                    </span>
                </a>
            @endforeach
        </nav>

        @unless($bloqueado)
            {{-- El aviso acompaña al botón, no a la franja de arriba: no dice
                 QUÉ ES la matriz sino QUÉ VA A HACER este clic. Hasta la Fase
                 4 solo estaba junto a los botones del final del formulario, y
                 este de aquí -el que está siempre a la vista- reabría una
                 matriz validada sin advertirlo. --}}
            @if($evaluacionValidada)
                <x-aviso-reapertura class="mb-2" />
            @endif

            <button type="submit" form="{{ $formulario }}" name="accion_estado" value="borrador"
                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded shadow-sm text-sm">
                Guardar Borrador
            </button>
        @endunless
    </x-tarjeta>
</aside>
