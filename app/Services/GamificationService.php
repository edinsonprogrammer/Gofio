<?php

namespace App\Services;

/**
 * Sincroniza rango y medallas tras actividad del usuario, emitiendo notificaciones de logros.
 */

use App\Models\AppNotification;
use App\Models\User;

class GamificationService
{
    /**
     * Inyecta servicios de rango, medallas y notificaciones.
     */
    public function __construct(
        private readonly RankService $rankService,
        private readonly MedalService $medalService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Recalcula rango y medallas del usuario y notifica subidas de rango o medallas nuevas.
     */
    public function syncAfterActivity(User $user): void
    {
        $user->refresh();
        $previousRangoId = $user->rango_id;

        $newRango = $this->rankService->syncUserRank($user);

        if ($newRango && $newRango->id !== $previousRangoId) {
            $user->loadMissing('rango');

            // Otorga karma por ascenso de rango (resuelto vía app() para evitar dependencia circular)
            app(KarmaRuleService::class)->evaluateRankPromoted($user, $newRango->id);

            $this->notificationService->notify(
                $user,
                AppNotification::TYPE_RANK_UP,
                '¡Subiste de rango!',
                "Ahora eres {$newRango->nombre}. Sigue participando para desbloquear más beneficios.",
                "/perfil/{$user->username}",
                ['rango_id' => $newRango->id, 'rango_nombre' => $newRango->nombre],
            );
        }

        $granted = $this->medalService->evaluateUser($user);

        foreach ($granted as $medal) {
            $this->notificationService->notify(
                $user,
                AppNotification::TYPE_MEDAL_RECEIVED,
                'Nueva medalla',
                "Obtuviste la medalla «{$medal->title}».",
                "/perfil/{$user->username}",
                ['medal_id' => $medal->id, 'medal_title' => $medal->title],
            );
        }
    }
}
