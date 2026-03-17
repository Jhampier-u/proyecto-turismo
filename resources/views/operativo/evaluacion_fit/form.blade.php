<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Evaluación FIT: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ url()->previous()}}"
                class="inline-flex items-center px-4 py-2 mb-4 bg-blue-300 hover:bg-blue-500 text-black-700 font-bold rounded-lg shadow-sm">
                Regresar
            </a>

            <form method="POST" action="{{ route('operativo.evaluacion_fit.update', $zona->id) }}">
                @csrf

                @php
                $esJefe = auth()->user()->role_id == 2;
                $estaConfirmado = $evaluacion->estado === 'confirmado';
                $bloqueado = $estaConfirmado && !$esJefe;
                @endphp

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">

                    @if($estaConfirmado)
                    <div class="mb-6 bg-green-50 border-l-4 border-green-500 text-green-700 p-4 rounded">
                        <div class="flex justify-between items-center">
                            <div>
                                <strong class="font-bold text-lg">✓ Evaluación Validada</strong>
                                <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                            </div>
                            <a href="{{ route('operativo.evaluacion_fit.ponderacion', $zona->id) }}" class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700">
                                Ver Tabla de Ponderación
                            </a>
                        </div>
                    </div>
                    @else
                    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                        <strong class="font-bold">Modo Borrador</strong>
                        <p>Los datos ingresados son preliminares. El Jefe de Zona debe revisar y confirmar para generar los resultados oficiales.</p>
                    </div>
                    @endif

                    <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 text-sm">
                        <p class="font-bold">Instrucciones</p>
                        <p>Califique cada elemento del 0 al 3. (0 = Nulo, 1 = Bajo, 2 = Medio, 3 = Alto).</p>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 mt-2">1. Oferta Turística</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                        <div class="bg-gray-50 p-4 rounded border">
                            <h4 class="font-bold text-gray-600 mb-3">Recursos Turísticos</h4>
                            @include('components.select-0-3', ['label' => 'Culturales', 'name' => 'recursos_culturales', 'val' => $evaluacion->recursos_culturales, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Naturales', 'name' => 'recursos_naturales', 'val' => $evaluacion->recursos_naturales, 'disabled' => $bloqueado])
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            <h4 class="font-bold text-gray-600 mb-3">Atractivos</h4>
                            @include('components.select-0-3', ['label' => 'Manifestaciones Culturales', 'name' => 'atractivos_manifestaciones', 'val' => $evaluacion->atractivos_manifestaciones, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Sitios Naturales', 'name' => 'atractivos_sitios', 'val' => $evaluacion->atractivos_sitios, 'disabled' => $bloqueado])
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            <h4 class="font-bold text-gray-600 mb-3">Prestadores</h4>
                            @include('components.select-0-3', ['label' => 'Alojamiento', 'name' => 'prestadores_alojamiento', 'val' => $evaluacion->prestadores_alojamiento, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Restauración', 'name' => 'prestadores_restauracion', 'val' => $evaluacion->prestadores_restauracion, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Guianza', 'name' => 'prestadores_guianza', 'val' => $evaluacion->prestadores_guianza, 'disabled' => $bloqueado])
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            <h4 class="font-bold text-gray-600 mb-3">Productos</h4>
                            @include('components.select-0-3', ['label' => 'Productos Territoriales', 'name' => 'productos_territoriales', 'val' => $evaluacion->productos_territoriales, 'disabled' => $bloqueado])
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">2. Infraestructura</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div class="bg-gray-50 p-4 rounded border">
                            @include('components.select-0-3', ['label' => 'Infraestructura Básica', 'name' => 'infraestructura_basica', 'val' => $evaluacion->infraestructura_basica, 'disabled' => $bloqueado])
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            @include('components.select-0-3', ['label' => 'Infraestructura de Apoyo', 'name' => 'infraestructura_apoyo', 'val' => $evaluacion->infraestructura_apoyo, 'disabled' => $bloqueado])
                        </div>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">3. Facilidades Turísticas</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                        <div class="bg-gray-50 p-4 rounded border">
                            @include('components.select-0-3', ['label' => 'Señalética', 'name' => 'facilidades_senaletica', 'val' => $evaluacion->facilidades_senaletica, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Recepción Visitantes', 'name' => 'facilidades_recepcion', 'val' => $evaluacion->facilidades_recepcion, 'disabled' => $bloqueado])
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            @include('components.select-0-3', ['label' => 'Centros Interpretación', 'name' => 'facilidades_interpretacion', 'val' => $evaluacion->facilidades_interpretacion, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Senderos', 'name' => 'facilidades_senderos', 'val' => $evaluacion->facilidades_senderos, 'disabled' => $bloqueado])
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            @include('components.select-0-3', ['label' => 'Estacionamientos', 'name' => 'facilidades_estacionamientos', 'val' => $evaluacion->facilidades_estacionamientos, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Campamentos', 'name' => 'facilidades_campamentos', 'val' => $evaluacion->facilidades_campamentos, 'disabled' => $bloqueado])
                        </div>
                        <div class="bg-gray-50 p-4 rounded border">
                            @include('components.select-0-3', ['label' => 'Miradores', 'name' => 'facilidades_miradores', 'val' => $evaluacion->facilidades_miradores, 'disabled' => $bloqueado])
                            @include('components.select-0-3', ['label' => 'Baterías Sanitarias', 'name' => 'facilidades_sanitarios', 'val' => $evaluacion->facilidades_sanitarios, 'disabled' => $bloqueado])
                        </div>
                    </div>

                    <div class="flex justify-end mt-8 gap-4 pt-4 border-t">
                        @if(!$bloqueado)
                        <button type="submit" name="accion_estado" value="borrador" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                            Guardar Borrador
                        </button>

                        @if($esJefe)
                        <button type="submit" name="accion_estado" value="confirmado"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg transform hover:scale-105 transition"
                            onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta evaluación.')">
                            Validar y Finalizar
                        </button>
                        @endif
                        @else
                        <span class="text-gray-500 italic self-center">Solo el Jefe de Zona puede reabrir o editar una evaluación validada.</span>
                        @if($esJefe)
                        <button type="submit" name="accion_estado" value="confirmado" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                            Actualizar Datos
                        </button>
                        @endif
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>