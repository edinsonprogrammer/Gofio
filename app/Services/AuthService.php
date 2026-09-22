<?php

namespace App\Services;

/**
 * Gestiona el registro, inicio de sesión y cierre de sesión de usuarios, respetando el modo mantenimiento.
 */

use App\Models\User;
use App\Repositories\SiteSettingsRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthService
{
    /**
     * Inyecta repositorios y servicios necesarios para autenticación y configuración del sitio.
     */
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly MaintenanceModeService $maintenanceMode,
        private readonly SiteSettingsRepository $siteSettingsRepository,
    ) {}

    /**
     * Registra un usuario nuevo, valida restricciones del sitio e inicia su sesión autenticada.
     */
    public function register(array $data): User
    {
        // Validación: mantenimiento y registro habilitado
        if ($this->maintenanceMode->isActive()) {
            throw ValidationException::withMessages([
                'email' => ['El registro está desactivado durante el mantenimiento.'],
            ]);
        }

        if (! $this->siteSettingsRepository->get('registration_enabled', true)) {
            throw ValidationException::withMessages([
                'email' => ['El registro de nuevas cuentas está desactivado.'],
            ]);
        }

        // Creación del usuario con rango inicial por defecto
        $user = $this->userRepository->create([
            'username' => $data['username'],
            'nick' => $data['nick'] ?? null,
            'email' => $data['email'],
            'password' => $data['password'],
            'rango_id' => (int) $this->siteSettingsRepository->get('default_rango_id', 1),
        ]);

        Auth::login($user);
        $this->beginAuthenticatedSession();

        return $user;
    }

    /**
     * Autentica credenciales por email o username, verifica acceso en mantenimiento y regenera la sesión.
     */
    public function login(array $credentials, bool $remember = false): User
    {
        $loginField = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attempt([$loginField => $credentials['login'], 'password' => $credentials['password']], $remember)) {
            throw ValidationException::withMessages([
                'login' => ['Las credenciales no coinciden con nuestros registros.'],
            ]);
        }

        $user = Auth::user();
        $user->load('rango');

        // Bloqueo de acceso durante mantenimiento para usuarios no staff
        if ($this->maintenanceMode->isActive() && ! $this->maintenanceMode->canAccessSite($user)) {
            Auth::logout();

            throw ValidationException::withMessages([
                'login' => ['Estamos en mantenimiento. Solo el equipo staff puede iniciar sesión por ahora.'],
            ]);
        }

        session()->regenerate();
        $this->beginAuthenticatedSession();

        return $user;
    }

    /**
     * Marca en sesión el momento de bienvenida tras autenticarse correctamente.
     */
    private function beginAuthenticatedSession(): void
    {
        session(['session_welcome_at' => now()->timestamp]);
    }

    /**
     * Cierra la sesión activa del usuario autenticado.
     */
    public function logout(): void
    {
        Auth::logout();
    }
}
