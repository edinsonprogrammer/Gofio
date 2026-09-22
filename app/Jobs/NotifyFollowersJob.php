<?php

/**
 * Job en cola que notifica a seguidores en lotes de 500, reencolándose si hay más.
 */

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class NotifyFollowersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(
        public int $authorId,
        public string $type,
        public string $title,
        public string $body,
        public ?string $actionUrl,
        public ?array $data,
        public ?int $afterFollowerId = null,
    ) {}

    /**
     * Notifica a un lote de seguidores y reencola el job si quedan más de 500.
     */
    public function handle(NotificationService $notificationService): void
    {
        $author = User::query()->find($this->authorId);

        if (! $author) {
            return;
        }

        $query = $author->followers()
            ->select('users.id', 'users.username')
            ->orderBy('users.id');

        if ($this->afterFollowerId) {
            $query->where('users.id', '>', $this->afterFollowerId);
        }

        $followers = $query->limit(500)->get();

        if ($followers->isEmpty()) {
            return;
        }

        foreach ($followers as $follower) {
            try {
                $notificationService->notify(
                    $follower,
                    $this->type,
                    $this->title,
                    $this->body,
                    $this->actionUrl,
                    array_merge($this->data ?? [], [
                        'actor_id' => $author->id,
                    ]),
                );
            } catch (\Throwable $exception) {
                Log::warning('notify_followers_chunk_failed', [
                    'author_id' => $author->id,
                    'follower_id' => $follower->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        if ($followers->count() === 500) {
            self::dispatch(
                $this->authorId,
                $this->type,
                $this->title,
                $this->body,
                $this->actionUrl,
                $this->data,
                $followers->last()->id,
            );
        }
    }
}
