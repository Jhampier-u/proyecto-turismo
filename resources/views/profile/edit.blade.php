<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    {{-- space-y-6 se queda en este div: separaba las tres tarjetas, no fijaba
         el ancho de página, así que no es el contenedor que se borra aquí. --}}
    <div class="py-12 space-y-6">
            <x-tarjeta :padding="false" class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </x-tarjeta>

            <x-tarjeta :padding="false" class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </x-tarjeta>

            <x-tarjeta :padding="false" class="p-4 sm:p-8">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </x-tarjeta>
    </div>
</x-app-layout>
