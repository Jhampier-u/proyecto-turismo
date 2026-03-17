<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $user->exists ? 'Editar Usuario: ' . $user->name : 'Crear Nuevo Usuario' }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
                    @csrf
                    
                    @if($user->exists)
                        @method('PUT')
                    @endif

                    <div class="mb-4">
                        <label for="name" class="block text-gray-700 font-bold mb-2">Nombre Completo</label>
                        <input type="text" name="name" id="name" class="w-full border-gray-300 rounded-md shadow-sm" 
                               value="{{ old('name', $user->name) }}" required>
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-gray-700 font-bold mb-2">Correo Electrónico</label>
                        <input type="email" name="email" id="email" class="w-full border-gray-300 rounded-md shadow-sm" 
                               value="{{ old('email', $user->email) }}" required>
                        <x-input-error :messages="$errors->get('email')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label for="role_id" class="block text-gray-700 font-bold mb-2">Rol del Usuario</label>
                        <select name="role_id" id="role_id" class="w-full border-gray-300 rounded-md shadow-sm">
                            <option value="" disabled {{ old('role_id', $user->role_id) ? '' : 'selected' }}>Selecciona un rol</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                    {{ $role->nombre }}
                                </option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('role_id')" class="mt-2" />
                    </div>

                    <div class="mb-4">
                        <label for="telefono" class="block text-gray-700 font-bold mb-2">Teléfono</label>
                        <input type="text" name="telefono" id="telefono" class="w-full border-gray-300 rounded-md shadow-sm" 
                               value="{{ old('telefono', $user->telefono) }}">
                    </div>

                    <hr class="my-6 border-gray-200">

                    <div class="mb-4">
                        <label for="password" class="block text-gray-700 font-bold mb-2">
                            {{ $user->exists ? 'Nueva Contraseña (Opcional)' : 'Contraseña' }}
                        </label>
                        
                        @if($user->exists)
                            <p class="text-sm text-gray-500 mb-2">Deja esto en blanco si no quieres cambiar la contraseña actual.</p>
                        @endif

                        <input type="password" name="password" id="password" class="w-full border-gray-300 rounded-md shadow-sm"
                               {{ $user->exists ? '' : 'required' }}>
                        
                        <x-input-error :messages="$errors->get('password')" class="mt-2" />
                    </div>

                    <div class="mb-6">
                        <label for="password_confirmation" class="block text-gray-700 font-bold mb-2">Confirmar Contraseña</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="w-full border-gray-300 rounded-md shadow-sm"
                               {{ $user->exists ? '' : 'required' }}>
                    </div>

                    <div class="flex items-center justify-end">
                        <a href="{{ route('admin.users.index') }}" class="text-gray-600 hover:text-gray-900 mr-4">Cancelar</a>
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded shadow">
                            {{ $user->exists ? 'Actualizar Usuario' : 'Guardar Usuario' }}
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>