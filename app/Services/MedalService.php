<?php

namespace App\Services;

/**
 * Evalúa condiciones automáticas de medallas, otorga manualmente y formatea medallas de perfil.
 */

use App\Models\AppNotification;
use App\Models\Medal;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class MedalService
{
    private const ACTIVE_MEDALS_CACHE_KEY = 'gofio:active_medals';

    private const ACTIVE_MEDALS_TTL = 300;

    /**
     * Inyecta el servicio de notificaciones para avisar al recibir medallas.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Recorre medallas activas no manuales y otorga las que cumplan condiciones al usuario.
     */
    public function evaluateUser(User $user): Collection
    {
        $user->loadCount(['posts', 'comments']);

        $ownedIds = $user->medals()->pluck('medals.id');

        $granted = collect();

        $this->activeMedals()
            ->each(function (Medal $medal) use ($user, $ownedIds, $granted) {
                if ($ownedIds->contains($medal->id)) {
                    return;
                }

                if (! $this->meetsCondition($user, $medal)) {
                    return;
                }

                $user->medals()->attach($medal->id, [
                    'granted_at' => now(),
                ]);

                $granted->push($medal);
            });

        return $granted;
    }

    /**
     * Asigna una medalla manualmente al usuario si aún no la posee y le notifica.
     */
    public function grantManual(User $user, Medal $medal, ?User $grantedBy = null, ?string $note = null): bool
    {
        if ($user->medals()->where('medals.id', $medal->id)->exists()) {
            return false;
        }

        $user->medals()->attach($medal->id, [
            'granted_by' => $grantedBy?->id,
            'note' => $note,
            'granted_at' => now(),
        ]);

        $this->notificationService->notify(
            $user,
            AppNotification::TYPE_MEDAL_RECEIVED,
            'Nueva medalla',
            "Obtuviste la medalla «{$medal->title}».",
            "/perfil/{$user->username}",
            ['medal_id' => $medal->id, 'medal_title' => $medal->title],
        );

        return true;
    }

    /**
     * Retira una medalla previamente asignada al usuario.
     */
    public function revoke(User $user, Medal $medal): void
    {
        $user->medals()->detach($medal->id);
    }

    /**
     * Invalida la caché de medallas activas tras cambios en administración.
     */
    public function flushActiveMedalsCache(): void
    {
        Cache::forget(self::ACTIVE_MEDALS_CACHE_KEY);
    }

    /**
     * Obtiene medallas automáticas activas desde caché o base de datos.
     */
    private function activeMedals(): Collection
    {
        return Cache::remember(self::ACTIVE_MEDALS_CACHE_KEY, self::ACTIVE_MEDALS_TTL, function () {
            return Medal::query()
                ->where('is_active', true)
                ->where('condition_type', '!=', 'manual')
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Evalúa si el usuario cumple la condición configurada de la medalla (karma, posts, etc.).
     */
    private function meetsCondition(User $user, Medal $medal): bool
    {
        return match ($medal->condition_type) {
            'karma' => $user->karma >= $medal->condition_value,
            'posts' => $user->posts_count >= $medal->condition_value,
            'comments' => $user->comments_count >= $medal->condition_value,
            'verified' => $user->isVerified(),
            default => false,
        };
    }

    /**
     * Serializa las medallas del usuario para mostrarlas en su perfil público.
     */
    public function formatForProfile(User $user): array
    {
        return $user->medals()
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Medal $medal) => [
                'id' => $medal->id,
                'title' => $medal->title,
                'slug' => $medal->slug,
                'description' => $medal->description,
                'icon' => $medal->icon,
                'color' => $medal->color,
                'granted_at' => $medal->pivot->granted_at,
            ])
            ->values()
            ->all();
    }
}
