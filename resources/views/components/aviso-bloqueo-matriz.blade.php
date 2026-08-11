@props(['sustantivo' => 'matriz'])

{{--
    El texto cuando un formulario de matriz llega bloqueado.

    Antes había dos motivos: el admin no editaba nunca (por ROL) y el equipo
    dejaba de poder al confirmarse (por ESTADO). Desde que el admin edita, solo
    queda el segundo, así que el prop $porRol y su rama desaparecen en vez de
    quedarse como una condición que siempre es falsa.

    En una sola línea a propósito: Blade respeta los saltos del fichero, y una
    frase partida en dos no la encontraría un assertSee() que busque la frase
    entera.
--}}
Solo el Jefe de Zona puede reabrir o editar una {{ $sustantivo }} validada.
