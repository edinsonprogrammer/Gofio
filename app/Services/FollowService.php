<?php

namespace App\Services;

/**
 * Gestiona relaciones de seguimiento entre usuarios y expone estadísticas de followers.
 */

use App\Models\AppNotification;
use App\Models\Follow;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class FollowService
{
    /**
     * Inyecta servicios de notificación y evaluación de karma por hitos de seguidores.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly KarmaRuleService $karmaRuleService,
    ) {}

    /**
     * Crea la relación de seguimiento, actualiza contadores y notifica al usuario seguido.
     */
    public function follow(User $follower, User $target): array
    {
        if ($follower->id === $target->id) {
            throw ValidationException::withMessages([
                'follow' => ['No puedes seguirte a ti mismo.'],
            ]);
        }

        $follow = Follow::firstOrCreate([
            'follower_id' => $follower->id,
            'following_id' => $target->id,
        ]);

        if ($follow->wasRecentlyCreated) {
            User::query()->whereKey($target->id)->increment('followers_count');
            User::query()->whereKey($follower->id)->increment('following_count');

            $this->notificationService->notify(
                $target,
                AppNotification::TYPE_NEW_FOLLOWER,
                'Nuevo seguidor',
                "@{$follower->username} comenzó a seguirte.",
                "/perfil/{$follower->username}",
                ['actor_id' => $follower->id, 'actor_username' => $follower->username],
            );

            $target->refresh();
            $this->karmaRuleService->evaluateFollowersMilestone($target, (int) $target->followers_count);
        }

        return $this->statsFor($target, $follower);
    }

    /**
     * Elimina la relación de seguimiento y decrementa contadores si existía.
     */
    public function unfollow(User $follower, User $target): array
    {
        $deleted = Follow::query()
            ->where('follower_id', $follower->id)
            ->where('following_id', $target->id)
            ->delete();

        if ($deleted) {
            User::query()
                ->whereKey($target->id)
                ->where('followers_count', '>', 0)
                ->decrement('followers_count');

            User::query()
                ->whereKey($follower->id)
                ->where('following_count', '>', 0)
                ->decrement('following_count');
        }

        return $this->statsFor($target->fresh(), $follower);
    }

    /**
     * Indica si el follower sigue actualmente al usuario target.
     */
    public function isFollowing(User $follower, User $target): bool
    {
        return Follow::query()
            ->where('follower_id', $follower->id)
            ->where('following_id', $target->id)
            ->exists();
    }

    /**
     * Devuelve contadores de seguidores/seguidos y si el viewer sigue al target.
     */
    public function statsFor(User $target, ?User $viewer = null): array
    {
        return [
            'followers_count' => (int) ($target->followers_count ?? 0),
            'following_count' => (int) ($target->following_count ?? 0),
            'is_following' => $viewer && $viewer->id !== $target->id
                ? $this->isFollowing($viewer, $target)
                : false,
        ];
    }
}
