<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Evaluación FET: {{ $zona->nombre }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <a href="{{ url()->previous()}}"
                class="inline-flex items-center px-4 py-2 mb-4 bg-blue-300 hover:bg-blue-500 text-black-700 font-bold rounded-lg shadow-sm">
                Regresar
            </a>
            
            <form method="POST" action="{{ route('operativo.evaluacion_fet.update', $zona->id) }}">
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
                                <strong class="font-bold text-lg">✓ Evaluación FET Validada</strong>
                                <p>Esta evaluación ha sido confirmada por el Jefe de Zona.</p>
                            </div>

                            <a href="{{ route('operativo.evaluacion_fet.ponderacion', $zona->id) }}"
                                class="bg-green-600 text-white px-4 py-2 rounded font-bold hover:bg-green-700">
                                Ver Tabla de Ponderación
                            </a>
                        </div>
                    </div>
                    @else
                    <div class="mb-6 bg-yellow-50 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded">
                        <strong class="font-bold">Modo Borrador</strong>
                        <p>Los datos ingresados son preliminares.</p>
                    </div>
                    @endif


                    <div class="mb-4 bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 text-sm">
                        <p class="font-bold">Instrucciones</p>
                        <p>Califique cada elemento del 0 al 3 (0 = Nulo/Malo, 3 = Alto/Excelente).</p>
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4 mt-2">1. Demanda Turística</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        @include('components.select-0-3', ['label' => 'Flujos Turísticos', 'name' => 'demanda_flujos', 'val' => $evaluacion->demanda_flujos, 'disabled' => $bloqueado])
                        @include('components.select-0-3', ['label' => 'Estadía Promedio', 'name' => 'demanda_estadia', 'val' => $evaluacion->demanda_estadia, 'disabled' => $bloqueado])
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">2. Superestructura</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        @include('components.select-0-3', ['label' => 'Institucionalidad Turística', 'name' => 'super_institucionalidad', 'val' => $evaluacion->super_institucionalidad, 'disabled' => $bloqueado])
                        @include('components.select-0-3', ['label' => 'Organización Comunitaria en Turismo', 'name' => 'super_organizacion', 'val' => $evaluacion->super_organizacion, 'disabled' => $bloqueado])
                        @include('components.select-0-3', ['label' => 'Planificación Turística', 'name' => 'super_planificacion', 'val' => $evaluacion->super_planificacion, 'disabled' => $bloqueado])
                    </div>

                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">3. Imagen del Sitio</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
                        @include('components.select-0-3', ['label' => 'Grado de Apertura de la Comunidad', 'name' => 'imagen_apertura', 'val' => $evaluacion->imagen_apertura, 'disabled' => $bloqueado])
                        @include('components.select-0-3', ['label' => 'Seguridad del Destino', 'name' => 'imagen_seguridad', 'val' => $evaluacion->imagen_seguridad, 'disabled' => $bloqueado])
                        @include('components.select-0-3', ['label' => 'Imagen Percibida del Visitante', 'name' => 'imagen_percibida', 'val' => $evaluacion->imagen_percibida, 'disabled' => $bloqueado])
                        @include('components.select-0-3', ['label' => 'Marketing Turístico', 'name' => 'imagen_marketing', 'val' => $evaluacion->imagen_marketing, 'disabled' => $bloqueado])
                    </div>

                    <div class="flex justify-end mt-8 gap-4 pt-4 border-t">
                        @if(!$bloqueado)
                        <button type="submit" name="accion_estado" value="borrador" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-3 px-6 rounded shadow-lg">
                            Guardar Borrador
                        </button>

                        @if($esJefe)
                        <button type="submit" name="accion_estado" value="confirmado"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-6 rounded shadow-lg transform hover:scale-105 transition"
                            onclick="return confirm('¿Está seguro? Al confirmar, el equipo ya no podrá editar esta evaluación.')">
                            Validar y Finalizar FET
                        </button>
                        @endif
                        @else
                        <span class="text-gray-500 italic self-center">Evaluación FET cerrada.</span>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>