<?php

/**
 * Middleware que calcula y adjunta las funcionalidades activas según la suscripción Creator Plus.
 * Los controladores y vistas leen el atributo gofio.features de la petición.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShareCreatorPlusFeatures
{
    /**
     * GET/POST cualquier ruta autenticada — enriquece la petición con flags de Creator Plus y continúa la cadena.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $isCreatorPlus = $user?->isCreatorPlus() ?? false;

        $request->attributes->set('gofio.features', [
            'ads_enabled' => ! $isCreatorPlus,
            'creator_plus' => $isCreatorPlus,
            'exclusive_theme' => $isCreatorPlus,
            'karma_multiplier' => $isCreatorPlus ? 2 : 1,
        ]);

        return $next($request);
    }
}
