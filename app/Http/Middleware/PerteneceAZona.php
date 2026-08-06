<?php

namespace App\Http\Middleware;

use App\Models\Zona;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe las rutas de /operativo/zona/{zona}/... a quienes están asignados
 * a esa zona concreta: su jefe o algún miembro de su equipo.
 *
 * El middleware `personal` solo comprueba que el usuario tenga un rol operativo,
 * lo que permitía a cualquier usuario autenticado leer y editar los datos de
 * cualquier zona escribiendo el ID en la URL.
 *
 * El administrador puede consultar cualquier zona pero no escribir en ella:
 * sus vistas enlazan a estas páginas en modo consulta, y la validación de las
 * evaluaciones es competencia exclusiva del Jefe de Zona.
 */
class PerteneceAZona
{
    public function handle(Request $request, Closure $next): Response
    {
        $zona = Zona::find($request->route('zona'));

        if (! $zona) {
            abort(404);
        }

        $user = $request->user();

        if ($user->esAdmin()) {
            abort_unless(
                $request->isMethodSafe(),
                403,
                'El administrador puede consultar las evaluaciones, pero no modificarlas.'
            );

            return $next($request);
        }

        $autorizado = (int) $zona->jefe_user_id === (int) $user->id
            || $zona->equipo()->whereKey($user->id)->exists();

        abort_unless($autorizado, 403, 'No tienes acceso a esta zona.');

        return $next($request);
    }
}
