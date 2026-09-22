<?php

/**
 * Middleware de modo mantenimiento que bloquea el sitio salvo para staff y el formulario de login.
 */

namespace App\Http\Middleware;

use App\Services\MaintenanceModeService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckSiteOffline
{
    public function __construct(
        private readonly MaintenanceModeService $maintenanceMode,
    ) {}

    /**
     * GET/POST cualquier ruta — evalúa modo offline; redirige a login, responde 503 o permite el acceso.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->maintenanceMode->isActive()) {
            return $next($request);
        }

        if ($this->allowsPublicCrawlerAccess($request)) {
            return $next($request);
        }

        $user = $request->user();

        if ($user && $this->maintenanceMode->canAccessSite($user)) {
            return $next($request);
        }

        if ($this->allowsLoginAttempt($request)) {
            return $next($request);
        }

        if ($user && ! $this->maintenanceMode->canAccessSite($user)) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(503, $this->maintenanceMode->message());
            }

            // Redirige al login informando del mantenimiento activo
            return redirect()
                ->route('login')
                ->with('error', 'El sitio está en mantenimiento. Solo el equipo staff puede iniciar sesión.');
        }

        if ($request->routeIs('register') || $request->is('registro')) {
            // Bloquea el registro durante el mantenimiento
            return redirect()->route('login');
        }

        if ($request->expectsJson()) {
            abort(503, $this->maintenanceMode->message());
        }

        return redirect()->route('login');
    }

    /**
     * Determina si la petición actual corresponde a una visita o intento de inicio de sesión.
     */
    private function allowsLoginAttempt(Request $request): bool
    {
        if ($request->routeIs('login', 'login.attempt')) {
            return true;
        }

        return $request->is('login') && in_array($request->method(), ['GET', 'HEAD', 'POST'], true);
    }

    /**
     * Permite rutas SEO y contenido público durante mantenimiento para crawlers e indexación.
     */
    private function allowsPublicCrawlerAccess(Request $request): bool
    {
        if (! in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        return $request->routeIs(
            'seo.robots',
            'seo.sitemap.index',
            'seo.sitemap.part',
            'seo.llms',
            'seo.ai',
            'seo.rss',
            'seo.indexnow.key',
            'posts.show',
            'profile.show',
        ) || $request->is(
            'robots.txt',
            'sitemap.xml',
            'sitemaps/*',
            'llms.txt',
            'ai.txt',
            'feed.xml',
            'post/*',
            'perfil/*',
        );
    }
}
