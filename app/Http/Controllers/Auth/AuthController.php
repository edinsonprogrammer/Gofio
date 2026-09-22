<?php

/**
 * Controlador de autenticación: login, registro y cierre de sesión con Inertia.
 */

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Post;
use App\Models\User;
use App\Repositories\SiteSettingsRepository;
use App\Services\AuthService;
use App\Services\MaintenanceModeService;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly MaintenanceModeService $maintenanceMode,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly SeoService $seoService,
    ) {}

    /**
     * GET /login — responde con la vista Inertia Auth/Login y el estado de mantenimiento.
     */
    public function showLogin(): Response
    {
        // Renderiza el formulario de inicio de sesión
        return Inertia::render('Auth/Login', [
            ...$this->maintenanceMode->loginPageProps(),
            'seo' => $this->seoService->defaultMeta(url('/login')),
        ]);
    }

    /**
     * POST /login — valida credenciales, inicia sesión y redirige a la URL prevista o al home.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $this->authService->login(
            $request->validated(),
            $request->boolean('remember')
        );

        // Redirige al destino original o a la página principal
        return redirect()->intended(route('home'));
    }

    /**
     * GET /registro — responde con la vista Inertia Auth/Register o redirige si el registro está deshabilitado.
     */
    public function showRegister(): Response|RedirectResponse
    {
        if ($this->maintenanceMode->isActive()) {
            // Redirige al login durante el mantenimiento
            return redirect()->route('login');
        }

        if (! $this->siteSettingsRepository->get('registration_enabled', true)) {
            // Redirige al login si el registro está desactivado globalmente
            return redirect()->route('login')->with('error', 'El registro de nuevas cuentas está desactivado.');
        }

        // Renderiza el formulario de registro con estadísticas de la comunidad
        return Inertia::render('Auth/Register', [
            'stats' => $this->communityStats(),
            'seo' => $this->seoService->defaultMeta(url('/registro')),
        ]);
    }

    /**
     * POST /registro — valida datos, crea la cuenta y redirige al home con mensaje de bienvenida.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        if ($this->maintenanceMode->isActive()) {
            // Redirige al login si el sitio está en mantenimiento
            return redirect()->route('login')->with('error', 'El registro está desactivado durante el mantenimiento.');
        }

        if (! $this->siteSettingsRepository->get('registration_enabled', true)) {
            // Redirige al login si el registro está desactivado
            return redirect()->route('login')->with('error', 'El registro de nuevas cuentas está desactivado.');
        }
        $this->authService->register($request->validated());

        // Redirige al home tras el registro exitoso
        return redirect()->route('home')->with('success', '¡Bienvenido a Gofio!');
    }

    /**
     * POST /logout — cierra la sesión, invalida la sesión y redirige al login.
     */
    public function logout(Request $request): RedirectResponse
    {
        $this->authService->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Redirige al formulario de login
        return redirect()->route('login');
    }

    /**
     * Obtiene contadores de usuarios y posts publicados para mostrar en el registro.
     */
    private function communityStats(): array
    {
        return [
            'users' => User::count(),
            'posts' => Post::published()->count(),
        ];
    }
}
