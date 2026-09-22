<?php

/**
 * Middleware que impide el acceso a usuarios suspendidos y cierra su sesión automáticamente.
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotBanned
{
    /**
     * GET/POST rutas autenticadas — valida estado de suspensión; redirige a login o libera bans expirados.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_banned) {
            if ($user->banned_until && $user->banned_until->isPast()) {
                $user->update([
                    'is_banned' => false,
                    'ban_reason' => null,
                    'banned_until' => null,
                    'is_active' => true,
                ]);
            } else {
                auth()->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                // Redirige al login con mensaje de cuenta suspendida
                return redirect()->route('login')->with('error', 'Tu cuenta está suspendida: '.($user->ban_reason ?? 'Contacta al administrador.'));
            }
        }

        return $next($request);
    }
}
