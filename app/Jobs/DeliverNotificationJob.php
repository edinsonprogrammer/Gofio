<?php

/**
 * Job en cola que entrega una notificación in-app a un usuario específico.
 */

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class DeliverNotificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $recipientId,
        public string $type,
        public string $title,
        public string $body,
        public ?string $actionUrl = null,
        public ?array $data = null,
    ) {}

    /**
     * Crea y entrega una notificación in-app al destinatario indicado.
     */
    public function handle(NotificationService $notificationService): void
    {
        $recipient = User::query()->find($this->recipientId);

        if (! $recipient) {
            return;
        }

        $notificationService->notify(
            $recipient,
            $this->type,
            $this->title,
            $this->body,
            $this->actionUrl,
            $this->data,
        );
    }
}
