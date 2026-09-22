<?php

namespace App\Services;

/**
 * Evalúa permisos globales y por rango para enviar y recibir propinas en posts.
 */

use App\Models\Post;
use App\Models\RolRango;
use App\Models\User;
use App\Repositories\SiteSettingsRepository;

class TipPermissionService
{
    /**
     * Inyecta el repositorio de ajustes del sitio para leer flags y comisiones.
     */
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
    ) {}

    /**
     * Indica si el sistema de propinas está habilitado a nivel global en el sitio.
     */
    public function tipsGloballyEnabled(): bool
    {
        return (bool) $this->siteSettingsRepository->get('allow_tips', true);
    }

    /**
     * Devuelve el porcentaje de comisión de plataforma aplicado a cada propina.
     */
    public function platformFeePercent(): float
    {
        return (float) $this->siteSettingsRepository->get('platform_fee_percent', 10);
    }

    /**
     * Verifica si el usuario puede enviar propinas según configuración global, rango y rol admin.
     */
    public function canSendTips(User $user): bool
    {
        if (! $this->tipsGloballyEnabled()) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $user->loadMissing('rango');

        return $this->rankAllows($user->rango, 'can_send_tips', true);
    }

    /**
     * Verifica si el usuario puede recibir propinas según configuración global y permisos de su rango.
     */
    public function canReceiveTips(User $user): bool
    {
        if (! $this->tipsGloballyEnabled()) {
            return false;
        }

        $user->loadMissing('rango');

        return $this->rankAllows($user->rango, 'can_receive_tips', true);
    }

    /**
     * Determina si un usuario puede dar propina a un post específico validando ambas partes y visibilidad.
     */
    public function canTipPost(User $sender, Post $post): bool
    {
        if ($post->user_id === $sender->id) {
            return false;
        }

        if ($post->status !== 'published' || $post->is_private) {
            return false;
        }

        if (! $this->canSendTips($sender)) {
            return false;
        }

        $post->loadMissing('user.rango');

        return $this->canReceiveTips($post->user);
    }

    /**
     * Expone los flags de propina configurados en los permisos del rango indicado.
     */
    public function rankTipFlags(?RolRango $rango): array
    {
        return [
            'can_send_tips' => $this->rankAllows($rango, 'can_send_tips', true),
            'can_receive_tips' => $this->rankAllows($rango, 'can_receive_tips', true),
        ];
    }

    /**
     * Lee un flag booleano de propina desde post_permissions del rango con valor por defecto.
     */
    private function rankAllows(?RolRango $rango, string $key, bool $default): bool
    {
        if ($rango === null) {
            return $default;
        }

        $permissions = $rango->post_permissions;

        if (! is_array($permissions) || ! array_key_exists($key, $permissions)) {
            return $default;
        }

        return (bool) $permissions[$key];
    }
}
