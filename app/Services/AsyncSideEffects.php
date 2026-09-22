<?php

namespace App\Services;

/**
 * Encola efectos secundarios pesados (gamificación y notificaciones) para ejecución asíncrona vía jobs.
 */

use App\Jobs\DeliverNotificationJob;
use App\Jobs\SyncGamificationJob;
use App\Models\User;

class AsyncSideEffects
{
    /**
     * Encola la sincronización de rango y medallas del usuario en un job en segundo plano.
     */
    public function queueGamificationSync(User|int $user): void
    {
        SyncGamificationJob::dispatch($user instanceof User ? $user->id : $user);
    }

    /**
     * Encola el envío de una notificación in-app al destinatario indicado.
     */
    public function queueNotification(
        User|int $recipient,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?array $data = null,
    ): void {
        DeliverNotificationJob::dispatch(
            $recipient instanceof User ? $recipient->id : $recipient,
            $type,
            $title,
            $body,
            $actionUrl,
            $data,
        );
    }
}
