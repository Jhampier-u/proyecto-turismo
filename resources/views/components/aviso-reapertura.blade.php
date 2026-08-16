@props(['sustantivo' => 'matriz'])

{{--
    El aviso que ve el Jefe de Zona cuando guardar sobre una matriz o
    evaluación ya validada la va a devolver a borrador. Su hermano
    <x-aviso-bloqueo-matriz> -que avisaba a quien NO puede tocar el
    formulario- lo absorbió la franja de estado en la Fase 4. Este se queda
    porque avisa a quien SÍ puede y no sabe la consecuencia, y desde esa fase
    acompaña a los DOS botones de guardar: el del pie y el de la barra
    lateral. Mismo prop $sustantivo para "matriz" vs "evaluación": el
    componente pone el texto, la vista decide si mostrarlo. La condición
    `@if($validada && $esJefe)` se queda en cada formulario porque el nombre
    de esas dos variables cambia de un fichero a otro ($estaConfirmado /
    $esJefe en siete, $isConfirmado / $user->esJefe() en Potencialidad).

    Sin clases de color o tamaño por defecto que el llamante no pueda tocar:
    los ocho formularios de origen usaban dos disposiciones distintas
    (mb-3 suelto, o w-full mb-1 dentro de un contenedor flex), así que ese
    margen y ese ancho siguen siendo cosa de cada vista via $attributes.
--}}
<p {{ $attributes->merge(['class' => 'text-sm text-amber-700']) }}>
    <strong>Esta {{ $sustantivo }} está validada.</strong>
    Guardarla la devolverá a borrador y habrá que validarla de nuevo.
</p>
