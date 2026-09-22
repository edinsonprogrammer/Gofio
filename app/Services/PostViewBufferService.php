<?php

namespace App\Services;

/**
 * Acumula vistas de posts en caché y las vuelca periódicamente a la base de datos.
 */

use App\Models\Post;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PostViewBufferService
{
    private const KEY_PREFIX = 'gofio:post_views_buffer:';

    private const INDEX_KEY = 'gofio:post_views_buffer:index';

    private const INDEX_LOCK_KEY = 'gofio:post_views_buffer:index:lock';

    /**
     * Incrementa el contador pendiente de vistas del post en caché e indexa su ID.
     */
    public function record(Post|int $post): void
    {
        $postId = $post instanceof Post ? $post->id : $post;
        $key = self::KEY_PREFIX.$postId;

        if (! Cache::has($key)) {
            Cache::put($key, 0, now()->addHours(6));
        }

        Cache::increment($key);

        $this->addPostToIndex($postId);
    }

    /**
     * Devuelve las visitas pendientes de volcar para un post concreto.
     */
    public function getPendingCount(int $postId): int
    {
        return (int) Cache::get(self::KEY_PREFIX.$postId, 0);
    }

    /**
     * Persiste en BD todas las vistas acumuladas y limpia el índice de caché.
     */
    public function flush(): int
    {
        $flushed = 0;

        try {
            Cache::lock(self::INDEX_LOCK_KEY, 30)->block(10, function () use (&$flushed) {
                $index = $this->normalizedIndex(Cache::get(self::INDEX_KEY, []));

                foreach (array_keys($index) as $postId) {
                    $key = self::KEY_PREFIX.$postId;
                    $pending = (int) Cache::pull($key, 0);

                    if ($pending <= 0) {
                        continue;
                    }

                    DB::table('posts')
                        ->where('id', $postId)
                        ->increment('views_count', $pending);

                    $flushed += $pending;
                }

                Cache::forget(self::INDEX_KEY);
            });
        } catch (LockTimeoutException) {
            // Sin bloqueo: volcado best-effort con índice deduplicado por claves.
            $index = $this->normalizedIndex(Cache::get(self::INDEX_KEY, []));

            foreach (array_keys($index) as $postId) {
                $key = self::KEY_PREFIX.$postId;
                $pending = (int) Cache::pull($key, 0);

                if ($pending <= 0) {
                    continue;
                }

                DB::table('posts')
                    ->where('id', $postId)
                    ->increment('views_count', $pending);

                $flushed += $pending;
            }

            Cache::forget(self::INDEX_KEY);
        }

        return $flushed;
    }

    /**
     * Añade un post al índice de pendientes con bloqueo atómico cuando el driver lo soporta.
     */
    private function addPostToIndex(int $postId): void
    {
        $ttl = now()->addHours(6);

        try {
            Cache::lock(self::INDEX_LOCK_KEY, 10)->block(5, function () use ($postId, $ttl) {
                $this->mergePostIntoIndex($postId, $ttl);
            });
        } catch (LockTimeoutException) {
            // Fallback sin bloqueo: fusión con deduplicación por claves del mapa postId => true.
            $this->mergePostIntoIndex($postId, $ttl);
        }
    }

    /**
     * Fusiona un post en el índice en memoria; las claves numéricas evitan duplicados lógicos.
     */
    private function mergePostIntoIndex(int $postId, \DateTimeInterface|\DateInterval|int|null $ttl): void
    {
        $index = $this->normalizedIndex(Cache::get(self::INDEX_KEY, []));
        $index[$postId] = true;
        Cache::put(self::INDEX_KEY, $index, $ttl);
    }

    /**
     * Garantiza un mapa postId => true a partir de datos de caché heterogéneos.
     *
     * @return array<int, true>
     */
    private function normalizedIndex(mixed $index): array
    {
        if (! is_array($index)) {
            return [];
        }

        $normalized = [];

        foreach (array_unique(array_keys($index)) as $postId) {
            if (is_int($postId) || (is_string($postId) && ctype_digit($postId))) {
                $normalized[(int) $postId] = true;
            }
        }

        return $normalized;
    }
}
