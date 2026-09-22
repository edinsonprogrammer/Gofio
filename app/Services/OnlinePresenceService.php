<?php

namespace App\Services;

/**
 * Gestiona presencia online de usuarios mediante marcas temporales en caché.
 */

use Illuminate\Support\Facades\Cache;

class OnlinePresenceService
{
    private const TTL_SECONDS = 300;

    private const TOUCH_THROTTLE_SECONDS = 60;

    /**
     * Registra actividad reciente del usuario, con throttle para evitar escrituras excesivas.
     */
    public function touch(int $userId): void
    {
        $throttleKey = $this->throttleKey($userId);

        if (Cache::has($throttleKey) && Cache::has($this->key($userId))) {
            return;
        }

        Cache::put($this->key($userId), now()->timestamp, self::TTL_SECONDS);
        Cache::put($throttleKey, 1, self::TOUCH_THROTTLE_SECONDS);
    }

    /**
     * Indica si el usuario tiene una marca de presencia activa en caché.
     */
    public function isOnline(int $userId): bool
    {
        return Cache::has($this->key($userId));
    }

    /**
     * Devuelve un mapa userId => bool de presencia para una lista de IDs en una sola consulta.
     */
    public function onlineMap(array $userIds): array
    {
        if ($userIds === []) {
            return [];
        }

        $keys = [];
        foreach ($userIds as $userId) {
            $keys[$userId] = $this->key($userId);
        }

        $values = Cache::many(array_values($keys));
        $map = [];

        foreach ($keys as $userId => $cacheKey) {
            $map[$userId] = array_key_exists($cacheKey, $values) && $values[$cacheKey] !== null;
        }

        return $map;
    }

    /**
     * Genera la clave de caché de presencia para un usuario.
     */
    private function key(int $userId): string
    {
        return "gofio:user_online:{$userId}";
    }

    /**
     * Genera la clave de throttle para limitar actualizaciones de presencia.
     */
    private function throttleKey(int $userId): string
    {
        return "gofio:user_online_throttle:{$userId}";
    }
}
