@props(['porRol', 'sustantivo' => 'matriz'])

{{--
    El texto del aviso cuando un formulario de matriz llega bloqueado, aparte
    de la lógica que decide *si* bloquear.

    Hay dos motivos de bloqueo y no son el mismo: el admin nunca edita
    evaluaciones, esté la matriz en borrador o validada (bloqueo por ROL); el
    equipo deja de poder editar en cuanto el Jefe de Zona confirma (bloqueo
    por ESTADO). FIT, Percepción, Irritación y Concentración mostraban la
    frase de "Solo el Jefe de Zona..." en los dos casos, así que un admin
    consultando un borrador se enteraba de un motivo que no era el suyo: la
    matriz nunca estuvo validada, y el admin tampoco puede editar una vez que
    lo esté. Involucrados ya tenía la frase correcta para su propio caso —el
    suyo es solo de rol, según explica su vista— y es el modelo del texto de
    abajo.

    $porRol lo decide la vista, no este componente: aquí solo se elige el
    texto una vez que la vista ya sabe por qué está bloqueada.

    Cada frase en una sola línea a propósito -igual que en
    evaluacion_concentracion/form.blade.php-: Blade respeta los saltos de
    línea del propio fichero, así que una frase partida en dos no la
    encontraría un assertSee() que buscara la frase entera.
--}}
@if($porRol)
El administrador puede consultar esta {{ $sustantivo }}, pero no puede modificarla.
@else
Solo el Jefe de Zona puede reabrir o editar una {{ $sustantivo }} validada.
@endif
