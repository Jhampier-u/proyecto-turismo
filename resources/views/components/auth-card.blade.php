<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100">
    
    <div class="text-2xl font-bold text-gray-700 mb-4">
        Acceso al Sistema
    </div>

    <x-tarjeta :padding="false" class="w-full sm:max-w-md mt-6 px-6 py-4 overflow-hidden">
        {{ $slot }} </x-tarjeta>
</div>