<?php

/**
 * Middleware que autoriza el acceso al panel admin según rol global o pestañas del rango staff.
 */

namespace App\Http\Middleware;

use App\Support\AdminPermissions;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanel
{
    /**
     * Permite administradores globales o staff con permiso sobre la pestaña de la ruta actual.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->isAdmin() && ! $user->hasStaffRank())) {
            abort(403, 'Acceso restringido al panel de administración.');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $tab = AdminPermissions::tabForRoute($request->route()?->getName());

        if ($tab === null) {
            abort(403, 'Esta sección no está disponible para tu rango.');
        }

        if (! in_array($tab, AdminPermissions::tabsForUser($user), true)) {
            abort(403, 'No tienes permiso para acceder a esta sección del panel.');
        }

        return $next($request);
    }
}
