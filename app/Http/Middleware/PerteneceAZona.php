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
 * El administrador entra a cualquier zona con cualquier método: escribe
 * evaluaciones y gestiona el inventario como uno más. Lo único que no puede
 * hacer es validar, competencia exclusiva del Jefe de Zona -esa guarda vive
 * en los controladores, no aquí-.
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
            // El admin trabaja en cualquier zona, con cualquier método.
            //
            // Antes esto le limitaba a métodos seguros. Se abrió a propósito:
            // rellena formularios y gestiona el inventario como uno más. Lo
            // único que NO puede es validar, y esa guarda no vive aquí sino en
            // los controladores —InvolucradosController::validar() aborta si no
            // eres jefe, y EvaluacionZonaController degrada a borrador a quien
            // no lo sea—.
            //
            // Consecuencia: una ruta de escritura nueva en este grupo queda
            // permitida al admin por omisión. Si alguna acción debe ser solo
            // del jefe, guárdala en su controlador;
            // PermisosAdminTest::test_toda_ruta_de_validacion_sigue_exigiendo_jefe
            // recorre las rutas y lo comprueba.
            return $next($request);
        }

        $autorizado = (int) $zona->jefe_user_id === (int) $user->id
            || $zona->equipo()->whereKey($user->id)->exists();

        abort_unless($autorizado, 403, 'No tienes acceso a esta zona.');

        return $next($request);
    }
}
