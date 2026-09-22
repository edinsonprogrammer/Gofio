<?php

namespace App\Services;

/**
 * Consulta y aplica el modo mantenimiento (offline) del sitio y permisos de acceso staff.
 */

use App\Models\User;
use App\Repositories\SiteSettingsRepository;

class MaintenanceModeService
{
    /**
     * Inyecta el repositorio de configuración del sitio.
     */
    public function __construct(
        private readonly SiteSettingsRepository $settings,
    ) {}

    /**
     * Indica si el modo offline/mantenimiento está activo según site settings.
     */
    public function isActive(): bool
    {
        return filter_var($this->settings->get('offline_mode'), FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Devuelve el mensaje público configurado para mostrar durante el mantenimiento.
     */
    public function message(): string
    {
        return (string) $this->settings->get(
            'offline_message',
            'Estamos en mantenimiento. Vuelve después para que continúe la diversión.'
        );
    }

    /**
     * Determina si el usuario puede acceder al sitio durante mantenimiento (admin o staff).
     */
    public function canAccessSite(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        $user->loadMissing('rango');

        return $user->isAdmin() || $user->hasStaffRank();
    }

    /**
     * Prepara props de mantenimiento para la página de login del frontend.
     */
    public function loginPageProps(): array
    {
        return [
            'maintenance' => [
                'active' => $this->isActive(),
                'message' => $this->message(),
                'staff_only' => true,
            ],
        ];
    }
}
