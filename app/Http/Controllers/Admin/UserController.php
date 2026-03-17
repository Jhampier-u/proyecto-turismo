<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('role')->paginate(10);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $roles = Role::all();
        $user = new User();
        return view('admin.users.form', compact('roles', 'user'));
    }

    public function store(Request $request)
    {
        $validado = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);

        User::create($validado);

        return redirect()->route('admin.users.index')->with('success', "Usuario creado correctamente");
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.form', compact('roles', 'user'));
    }

    public function update(Request $request, User $user)
    {
        $validado = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'password' => ['required', 'confirmed', 'min:8'],
            'role_id' => ['required', 'exists:roles,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);
        if (empty($validado['password'])) {
            unset($validado['password']);
        }

        $user->update($validado);

        return redirect()->route('admin.users.index')->with('success', "Usuario actualizado correctamente");
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
