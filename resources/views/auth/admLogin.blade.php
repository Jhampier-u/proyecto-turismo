<x-layout>

    <x-auth-card>
        
        <h2 class="text-2xl font-semibold mb-6 text-center text-gray-800">
            Iniciar Sesión
        </h2>
        
        <form method="POST" action="{{ route('admLogin') }}">
            @csrf

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="w-full border border-gray-300 p-2 rounded-md mt-1">
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700">Contrase&ntilde;a</label>
                <input id="password" type="password" name="password" required autocomplete="current-password" class="w-full border border-gray-300 p-2 rounded-md mt-1">
            </div>

            <div class="flex items-center justify-end">
                <button type="submit" class="w-full bg-blue-600 text-white p-2 rounded-md hover:bg-blue-700 transition">
                    Acceder
                </button>
            </div>
            @if ($errors->any())
            <ul class="px-4 py-2 bg-red-100">
                @foreach ($errors->all() as $error)
                <li class="my-2 text-red-500">{{ $error }}</li>
                @endforeach
            </ul>
            @endif
        </form>

    </x-auth-card>
    
</x-layout>