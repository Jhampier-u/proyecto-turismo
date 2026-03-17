<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    ⭐ Potencialidad Turística — {{ $zona->nombre }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">Vista de administrador · Solo lectura</p>
            </div>
            <a href="{{ route('admin.zonas.index') }}" class="text-sm text-blue-600 hover:underline">← Volver a Zonas</a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if(!$evaluacion)
                {{-- Sin evaluación aún --}}
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg shadow">
                    <div class="flex items-center gap-4">
                        <span class="text-4xl">📋</span>
                        <div>
                            <h3 class="font-bold text-yellow-800 text-lg">Evaluación aún no realizada</h3>
                            <p class="text-yellow-700 mt-1">
                                El equipo de la zona <strong>{{ $zona->nombre }}</strong> todavía no ha completado la
                                Matriz de Potencialidad Turística.
                            </p>
                            <p class="text-yellow-600 text-sm mt-2">
                                Como administrador puedes consultar el formulario, pero <strong>no puedes validar los datos</strong>.
                                La validación es exclusiva del Jefe de Zona.
                            </p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <a href="{{ route('operativo.evaluacion_potencialidad.edit', $zona->id) }}"
                           class="inline-block bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 px-5 rounded shadow transition">
                            Ver Formulario de Evaluación
                        </a>
                    </div>
                </div>

            @else
                {{-- Encabezado de estado --}}
                <div class="bg-white p-5 rounded-lg shadow flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <span class="text-xs text-gray-400 uppercase font-semibold">Estado</span>
                        <div class="mt-1">
                            @if($evaluacion->estado === 'confirmado')
                                <span class="inline-flex items-center gap-2 bg-green-100 text-green-800 font-bold px-3 py-1 rounded-full text-sm">
                                    ✅ Confirmado / Validado
                                </span>
                            @else
                                <span class="inline-flex items-center gap-2 bg-yellow-100 text-yellow-800 font-bold px-3 py-1 rounded-full text-sm">
                                    ✏️ Borrador
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        Última actualización: {{ $evaluacion->updated_at?->format('d/m/Y H:i') ?? '—' }}
                    </div>
                    <a href="{{ route('operativo.evaluacion_potencialidad.ponderacion', $zona->id) }}"
                       class="bg-purple-600 hover:bg-purple-700 text-white font-bold py-2 px-5 rounded shadow transition text-sm">
                        Ver Gráfica de Resultados
                    </a>
                </div>

                {{-- Cuadrante --}}
                @php
                    $fn = $evaluacion->fn_total;
                    $fx = $evaluacion->fx_total;
                    if ($fn >= 1 && $fx >= 1)      { $cuadrante = 'Alto Potencial Turístico';              $color = 'green';  $emoji = '🟢'; }
                    elseif ($fn >= 1 && $fx < 1)   { $cuadrante = 'Potencial Endógeno — Demanda Limitada'; $color = 'yellow'; $emoji = '🟡'; }
                    elseif ($fn < 1 && $fx >= 1)   { $cuadrante = 'Potencial Exógeno — Oferta Limitada';   $color = 'blue';   $emoji = '🔵'; }
                    else                            { $cuadrante = 'Bajo Potencial Turístico';              $color = 'red';    $emoji = '🔴'; }
                @endphp

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-white p-5 rounded-lg shadow text-center">
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Factores Endógenos (FN)</p>
                        <p class="text-4xl font-bold text-blue-700">{{ number_format($fn, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">Escala 0 – 2</p>
                    </div>
                    <div class="bg-white p-5 rounded-lg shadow text-center">
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Factores Exógenos (FX)</p>
                        <p class="text-4xl font-bold text-indigo-700">{{ number_format($fx, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">Escala 0 – 2</p>
                    </div>
                    <div class="bg-{{ $color }}-50 border border-{{ $color }}-200 p-5 rounded-lg shadow text-center">
                        <p class="text-xs text-gray-400 uppercase font-semibold mb-1">Cuadrante</p>
                        <p class="text-2xl font-bold text-{{ $color }}-700">{{ $emoji }}</p>
                        <p class="text-sm font-semibold text-{{ $color }}-800 mt-1">{{ $cuadrante }}</p>
                    </div>
                </div>

                {{-- Tabla de subresultados --}}
                <div class="bg-white rounded-lg shadow overflow-hidden">
                    <div class="px-5 py-4 border-b bg-gray-50">
                        <h3 class="font-bold text-gray-700">Resumen de Ponderaciones</h3>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 divide-y md:divide-y-0 md:divide-x divide-gray-100">
                        <div class="p-5 space-y-3">
                            <p class="font-bold text-blue-800 text-sm uppercase mb-2">Factores Endógenos</p>
                            @foreach([
                                'Recursos Turísticos' => $evaluacion->val_recursos_turisticos,
                                '↳ Recursos Naturales' => $evaluacion->val_recursos_naturales,
                                '↳ Recursos Culturales' => $evaluacion->val_recursos_culturales,
                                'Planta Turística' => $evaluacion->val_planta_turistica,
                                'Tipologías de Turismo' => $evaluacion->val_tipologias,
                                'Infraestructura' => $evaluacion->val_infraestructura,
                            ] as $label => $val)
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">{{ $label }}</span>
                                <span class="font-bold {{ $val >= 1 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ number_format($val, 2) }}
                                </span>
                            </div>
                            @endforeach
                            <div class="border-t pt-2 flex justify-between font-bold text-blue-700">
                                <span>FN Total</span>
                                <span>{{ number_format($fn, 2) }}</span>
                            </div>
                        </div>
                        <div class="p-5 space-y-3">
                            <p class="font-bold text-indigo-800 text-sm uppercase mb-2">Factores Exógenos</p>
                            @foreach([
                                'Afluencia Turística' => $evaluacion->val_afluencia,
                                'Marketing Turístico' => $evaluacion->val_marketing,
                                'Superestructura' => $evaluacion->val_superestructura,
                            ] as $label => $val)
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">{{ $label }}</span>
                                <span class="font-bold {{ $val >= 1 ? 'text-green-600' : 'text-red-500' }}">
                                    {{ number_format($val, 2) }}
                                </span>
                            </div>
                            @endforeach
                            <div class="border-t pt-2 flex justify-between font-bold text-indigo-700">
                                <span>FX Total</span>
                                <span>{{ number_format($fx, 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-800">
                    ⚠️ <strong>Nota:</strong> Como administrador puedes ver los resultados pero no puedes confirmar ni modificar la evaluación.
                    La validación es exclusiva del <strong>Jefe de Zona</strong>.
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
