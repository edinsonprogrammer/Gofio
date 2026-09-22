<?php

/**
 * Job en cola que sincroniza medallas, rangos y karma tras actividad del usuario.
 */

namespace App\Jobs;

use App\Models\User;
use App\Services\GamificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncGamificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public int $userId,
    ) {}

    /**
     * Recalcula medallas, rango y karma del usuario tras actividad reciente.
     */
    public function handle(GamificationService $gamificationService): void
    {
        $user = User::query()->find($this->userId);

        if ($user) {
            $gamificationService->syncAfterActivity($user);
        }
    }
}
