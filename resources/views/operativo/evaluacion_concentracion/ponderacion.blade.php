<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Resultados — Índice de Concentración Turística: {{ $zona->nombre }}
        </h2>
    </x-slot>

    {{--
        Esqueleto de la Tarea 3: las dos tablas de resultados de verdad -la de
        atractivos con su porcentaje sobre el total de su tabla, la de planta
        con Sia, Pi e ICT por sector- son la Tarea 5. Hasta entonces esta
        vista no lee ni un solo campo de $evaluacion, así que nunca corre el
        riesgo de pintar un "0.00" sobre conteos que nadie ha respondido
        todavía -el mismo riesgo que <x-matriz-sin-resultados> ya resuelve
        para el resto de matrices, GuardadoParcialTest lo recorre por HTTP-.
    --}}
    <x-matriz-sin-resultados
        nombre="Índice de Concentración"
        :zona="$zona"
        ruta-formulario="operativo.evaluacion_concentracion.edit" />
</x-app-layout>
