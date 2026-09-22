<?php

/**
 * Middleware de autorización que restringe el acceso exclusivamente a administradores.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    /**
     * GET/POST rutas del panel admin — permite continuar solo a administradores; de lo contrario responde 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->isAdmin()) {
            abort(403, 'Acceso restringido a administradores.');
        }

        return $next($request);
    }
}
