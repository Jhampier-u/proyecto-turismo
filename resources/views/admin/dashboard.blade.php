<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Panel de Administración') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-bold mb-4">Centro de Control</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 hover:shadow-lg transition">
                            <h4 class="font-bold text-blue-800 text-lg mb-2">Usuarios</h4>
                            <p class="text-sm text-gray-600 mb-4">Gestiona Jefes de Zona, Equipos y Accesos.</p>
                            <a href="{{ route('admin.users.index') }}" class="text-blue-600 font-bold hover:underline">Gestionar &rarr;</a>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-6 hover:shadow-lg transition">
                            <h4 class="font-bold text-red-800 text-lg mb-2">Lugares</h4>
                            <p class="text-sm text-gray-600 mb-4">Crea nuevos lugares</p>
                            <a href="{{ route('admin.lugares.index') }}" class="text-red-600 font-bold hover:underline">Gestionar &rarr;</a>
                        </div>
                        <div class="bg-green-50 border border-green-200 rounded-lg p-6 hover:shadow-lg transition">
                            <h4 class="font-bold text-green-800 text-lg mb-2">Zonas</h4>
                            <p class="text-sm text-gray-600 mb-4">Crea nuevas zonas y asigna supervisores.</p>
                            <a href="{{ route('admin.zonas.index') }}" class="text-green-600 font-bold hover:underline">Gestionar &rarr;</a>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>