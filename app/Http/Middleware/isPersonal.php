<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class isPersonal
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        if (!auth()->check() || !in_array(auth()->user()->role_id, [1, 2, 3])) {
            abort(403, 'No tienes permisos operativos en esta zona.');
        }

        return $next($request);
    }
}
