<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $buscar = trim((string) $request->query('buscar', ''));
        $rol    = $request->query('rol');

        $users = User::with('role')
            // Las zonas se cargan por adelantado: la vista las pinta en cada
            // fila, y sin esto serían dos consultas por usuario listado.
            //
            // jefe_user_id va en la lista de columnas aunque no se muestre: al
            // seleccionar columnas sueltas en un hasMany hay que traer la clave
            // foránea, o Eloquent no puede emparejar cada zona con su usuario y
            // la relación vuelve vacía sin dar ningún error.
            ->with(['zonasComoJefe:id,nombre,jefe_user_id', 'zonasComoEquipo:id,nombre'])
            ->when($buscar !== '', function ($q) use ($buscar) {
                // El paréntesis importa: sin él, el orWhere se saldría del
                // filtro de rol y devolvería usuarios de cualquier rol.
                $q->where(function ($q) use ($buscar) {
                    $q->where('name', 'like', "%{$buscar}%")
                      ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->when($rol, fn($q) => $q->where('role_id', $rol))
            ->orderBy('name')
            ->paginate(10)
            // Sin esto, pasar de página pierde el filtro y el usuario cree que
            // la búsqueda no funciona.
            ->withQueryString();

        $roles = Role::orderBy('id')->get();

        return view('admin.users.index', compact('users', 'roles', 'buscar', 'rol'));
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
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', 'min:8'],
            'role_id'  => ['required', 'exists:roles,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);

        $user = new User(Arr::except($validado, 'role_id'));
        // Asignación explícita: role_id no es mass-assignable a propósito.
        $user->role_id = $validado['role_id'];
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuario creado correctamente');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        return view('admin.users.form', compact('roles', 'user'));
    }

    public function update(Request $request, User $user)
    {
        $validado = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // ✅ FIX: nullable + sometimes para que no falle si viene vacío
            'password' => ['nullable', 'sometimes', 'confirmed', 'min:8'],
            'role_id'  => ['required', 'exists:roles,id'],
            'telefono' => ['nullable', 'string', 'max:20'],
        ]);

        // Si la contraseña viene vacía, se omite del update (no se sobreescribe)
        if (empty($validado['password'])) {
            unset($validado['password']);
        }

        $user->fill(Arr::except($validado, 'role_id'));
        $user->role_id = $validado['role_id'];
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Usuario actualizado correctamente');
    }

    public function destroy(User $user)
    {
        if (Auth::id() === $user->id) {
            return back()->with('error', 'No puedes eliminar tu propia cuenta.');
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            return back()->with('error', 'No se pudo eliminar el usuario porque tiene registros asociados.');
        }

        return redirect()->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
