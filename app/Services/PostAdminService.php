<?php

namespace App\Services;

/**
 * Acciones administrativas sobre posts: estado, destacados, sticky y avisos al staff.
 */

use App\Models\AppNotification;
use App\Models\Post;
use App\Models\User;

class PostAdminService
{
    /**
     * Inyecta servicios de log administrativo y notificaciones.
     */
    public function __construct(
        private readonly AdminLogService $adminLogService,
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Cambia el estado del post, registra log y notifica al autor si fue baneado.
     */
    public function setStatus(User $admin, Post $post, string $status, ?string $reason = null): Post
    {
        $wasPublished = $post->status === 'published';
        $post->update(['status' => $status]);
        $this->adminLogService->log($admin, 'post_status_'.$status, 'post', $post->id, $reason);

        if ($status === 'published' && ! $wasPublished && ! $post->is_private) {
            app(\App\Services\SeoService::class)->notifyPublishedPost($post->fresh());
        }

        if ($status === 'banned') {
            $post->loadMissing('user');
            $this->notificationService->notify(
                $post->user,
                AppNotification::TYPE_POST_REMOVED,
                'Post eliminado',
                "Tu post «{$post->title}» fue retirado por moderación.".($reason ? " Motivo: {$reason}" : ''),
                "/perfil/{$post->user->username}",
                ['post_id' => $post->id, 'post_slug' => $post->slug, 'reason' => $reason],
            );

            $this->notifyModeratorAction($admin, "retiró el post «{$post->title}» de @{$post->user->username}", $reason);
        }

        return $post->fresh();
    }

    /**
     * Alterna el flag de post destacado y registra la acción administrativa.
     */
    public function toggleFeatured(User $admin, Post $post): Post
    {
        $featured = ! $post->is_featured;

        $post->update([
            'is_featured' => $featured,
            'featured_at' => $featured ? now() : null,
            'featured_by' => $featured ? $admin->id : null,
        ]);

        $this->adminLogService->log($admin, $featured ? 'feature_post' : 'unfeature_post', 'post', $post->id);

        return $post->fresh();
    }

    /**
     * Alterna el flag de post fijado (sticky) en el feed.
     */
    public function toggleSticky(User $admin, Post $post): Post
    {
        $post->update(['is_sticky' => ! $post->is_sticky]);
        $this->adminLogService->log($admin, $post->is_sticky ? 'sticky_post' : 'unsticky_post', 'post', $post->id);

        return $post->fresh();
    }

    /**
     * Notifica al staff cuando un moderador (no admin) realiza una acción de moderación.
     */
    private function notifyModeratorAction(User $admin, string $action, ?string $reason = null): void
    {
        if ($admin->isAdmin() || ! $admin->hasStaffRank()) {
            return;
        }

        $body = "@{$admin->username} {$action}.";
        if ($reason) {
            $body .= " Motivo: {$reason}";
        }

        $this->notificationService->notifyStaff(
            AppNotification::TYPE_MODERATOR_ACTION,
            'Acción de moderador',
            $body,
            '/admin/moderacion',
            ['actor_id' => $admin->id, 'action' => $action],
        );
    }
}
