<?php

/**
 * Comando Artisan que elimina notificaciones con más de 15 días de antigüedad.
 */

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class PruneAppNotifications extends Command
{
    protected $signature = 'gofio:prune-notifications';

    protected $description = 'Elimina notificaciones con más de 15 días de antigüedad';

    /**
     * Elimina notificaciones expiradas y reporta la cantidad purgada.
     */
    public function handle(NotificationService $notificationService): int
    {
        $count = $notificationService->purgeExpired();

        $this->info("Notificaciones eliminadas: {$count}");

        return self::SUCCESS;
    }
}
