<?php

namespace App\Services;

/**
 * Registra visitas únicas a posts (por usuario o IP) y expone el contador visible en feed y detalle.
 */

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PostViewService
{
    /** Prefijo de claves de deduplicación en caché. */
    private const DEDUP_PREFIX = 'gofio:post_view_seen:';

    /** Ventana en la que no se vuelve a contar la misma visita. */
    private const DEDUP_TTL_HOURS = 24;

    public function __construct(
        private readonly PostViewBufferService $postViewBufferService,
    ) {}

    /**
     * Registra una visita si el visitante no la contabilizó en la ventana de deduplicación.
     */
    public function record(Post|int $post, ?User $viewer = null, ?string $ipAddress = null): bool
    {
        $postId = $post instanceof Post ? $post->id : $post;
        $dedupKey = self::DEDUP_PREFIX.$postId.':'.$this->visitorKey($viewer, $ipAddress);

        // add() es atómico: evita duplicados concurrentes desde la misma IP o usuario.
        if (! Cache::add($dedupKey, 1, now()->addHours(self::DEDUP_TTL_HOURS))) {
            return false;
        }

        $this->postViewBufferService->record($postId);

        return true;
    }

    /**
     * Devuelve visitas persistidas más las pendientes de volcado en caché.
     */
    public function displayCount(Post|int $post): int
    {
        $postId = $post instanceof Post ? $post->id : $post;
        $persisted = $post instanceof Post ? (int) $post->views_count : (int) (Post::query()->whereKey($postId)->value('views_count') ?? 0);

        return $persisted + $this->postViewBufferService->getPendingCount($postId);
    }

    /**
     * Identificador estable del visitante: usuario autenticado o hash de IP.
     */
    private function visitorKey(?User $viewer, ?string $ipAddress): string
    {
        if ($viewer) {
            return 'user:'.$viewer->id;
        }

        $ip = trim((string) $ipAddress);

        if ($ip === '') {
            $ip = 'unknown';
        }

        return 'ip:'.hash('sha256', $ip);
    }
}
