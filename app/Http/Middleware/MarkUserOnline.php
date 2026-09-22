<?php

/**
 * Middleware que actualiza la marca de presencia en línea del usuario tras cada petición.
 */

namespace App\Http\Middleware;

use App\Services\OnlinePresenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MarkUserOnline
{
    public function __construct(
        private readonly OnlinePresenceService $presenceService,
    ) {}

    /**
     * GET/POST cualquier ruta autenticada — registra actividad del usuario y devuelve la respuesta original.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($user = $request->user()) {
            $this->presenceService->touch($user->id);
        }

        return $response;
    }
}
