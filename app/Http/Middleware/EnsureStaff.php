<?php

/**
 * Middleware de autorización que restringe el acceso al equipo de moderación (staff o admin).
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaff
{
    /**
     * GET/POST rutas admin de moderación — permite continuar solo a staff; de lo contrario responde 403.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->hasStaffRank())) {
            abort(403, 'Acceso restringido al equipo de moderación.');
        }

        return $next($request);
    }
}
