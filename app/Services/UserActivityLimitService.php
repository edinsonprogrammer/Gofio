<?php

namespace App\Services;

/**
 * Calcula y valida límites diarios de posts, comentarios y votos según rango y ajustes del sitio.
 */

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Repositories\SiteSettingsRepository;
use App\Repositories\VoteLogRepository;
use Illuminate\Validation\ValidationException;

class UserActivityLimitService
{
    /**
     * Inyecta permisos de posts, ajustes globales y registro de votos diarios.
     */
    public function __construct(
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly PostPermissionService $postPermissionService,
        private readonly VoteLogRepository $voteLogRepository,
    ) {}

    /**
     * Devuelve límites configurados y consumo actual del día para posts, comentarios y votos.
     */
    public function forUser(User $user): array
    {
        $permissions = $this->postPermissionService->forUser($user);

        return [
            'max_posts_per_day' => (int) $permissions['max_posts_per_day'],
            'max_comments_per_day' => (int) $permissions['max_comments_per_day'],
            'max_votes_per_day' => $this->maxVotesPerDay($user),
            'posts_today' => (int) $permissions['posts_today'],
            'comments_today' => $this->countCommentsToday($user),
            'votes_today' => $this->voteLogRepository->countVotesToday($user->id),
            'can_create_post' => (bool) $permissions['can_create'],
            'can_create_comment' => $this->countCommentsToday($user) < (int) $permissions['max_comments_per_day'],
        ];
    }

    /**
     * Calcula el máximo de votos diarios según rango del usuario o límites globales del sitio.
     */
    public function maxVotesPerDay(User $user): int
    {
        $user->loadMissing('rango');

        if ($this->usesSiteDefaultLimits($user)) {
            return (int) $this->siteSettingsRepository->get('max_votes_per_day', 5);
        }

        return (int) ($user->rango?->limite_voto_diario ?? $this->siteSettingsRepository->get('max_votes_per_day', 5));
    }

    /**
     * Impide comentar si el usuario alcanzó su cuota diaria según permisos de su rango.
     */
    public function assertCanComment(User $user): void
    {
        $permissions = $this->postPermissionService->forUser($user);
        $maxComments = (int) $permissions['max_comments_per_day'];
        $commentsToday = $this->countCommentsToday($user);

        if ($commentsToday >= $maxComments) {
            throw ValidationException::withMessages([
                'content' => ["Has alcanzado tu límite diario de {$maxComments} comentarios."],
            ]);
        }
    }

    /**
     * Cuenta los comentarios creados por el usuario desde el inicio del día actual.
     */
    public function countCommentsToday(User $user): int
    {
        return Comment::query()
            ->where('user_id', $user->id)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }

    /**
     * Indica si el usuario está en el rango inicial y debe usar límites globales del sitio.
     */
    public function usesSiteDefaultLimits(User $user): bool
    {
        $user->loadMissing('rango');
        $defaultRankId = (int) $this->siteSettingsRepository->get('default_rango_id', 1);

        return $user->rango_id === $defaultRankId
            || strtolower((string) ($user->rango?->nombre ?? '')) === 'newbie';
    }

    /**
     * Lee los límites diarios por defecto configurados globalmente para usuarios nuevos.
     */
    public function siteDefaultLimits(): array
    {
        return [
            'max_posts_per_day' => (int) $this->siteSettingsRepository->get('max_posts_per_day', 10),
            'max_comments_per_day' => (int) $this->siteSettingsRepository->get('max_comments_per_day', 30),
            'max_votes_per_day' => (int) $this->siteSettingsRepository->get('max_votes_per_day', 5),
        ];
    }
}
