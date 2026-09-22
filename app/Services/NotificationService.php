<?php

namespace App\Services;

/**
 * Crea, consulta y gestiona notificaciones in-app con retención, badge y URLs de acción.
 */

use App\Models\AppNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class NotificationService
{
    public const RETENTION_DAYS = 15;

    private const HISTORY_LIMIT = 50;

    private const BADGE_CACHE_SECONDS = 30;

    private const STAFF_TYPES = [
        AppNotification::TYPE_USER_REPORTED,
        AppNotification::TYPE_POST_REPORTED,
        AppNotification::TYPE_CONTENT_REPORTED,
        AppNotification::TYPE_SPAM_DETECTED,
        AppNotification::TYPE_MODERATOR_ACTION,
    ];

    /**
     * Crea una notificación para un usuario, omitiendo auto-notificaciones al actor.
     */
    public function notify(
        User $recipient,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?array $data = null,
    ): ?AppNotification {
        if ($recipient->id === ($data['actor_id'] ?? null)) {
            return null;
        }

        $notification = AppNotification::create([
            'user_id' => $recipient->id,
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'action_url' => $actionUrl,
            'data' => $data,
        ]);

        $this->forgetBadgeCache($recipient->id);

        return $notification;
    }

    /**
     * Envía la misma notificación a todos los miembros del staff activo.
     */
    public function notifyStaff(
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?array $data = null,
    ): void {
        $this->staffRecipients()->each(function (User $staff) use ($type, $title, $body, $actionUrl, $data) {
            $this->notify($staff, $type, $title, $body, $actionUrl ?? '/admin/moderacion', $data);
        });
    }

    /**
     * Encola notificación masiva a seguidores del autor mediante job en segundo plano.
     */
    public function notifyFollowers(
        User $author,
        string $type,
        string $title,
        string $body,
        ?string $actionUrl = null,
        ?array $data = null,
    ): void {
        \App\Jobs\NotifyFollowersJob::dispatch(
            $author->id,
            $type,
            $title,
            $body,
            $actionUrl,
            $data,
        );
    }

    /**
     * Elimina notificaciones más antiguas que el periodo de retención configurado.
     */
    public function purgeExpired(?User $user = null): int
    {
        return $this->retentionQuery($user)
            ->where('created_at', '<', now()->subDays(self::RETENTION_DAYS))
            ->delete();
    }

    /**
     * Devuelve el conteo de notificaciones no vistas para el badge, con caché breve.
     */
    public function badgeCount(User $user): int
    {
        return Cache::remember(
            $this->badgeCacheKey($user->id),
            self::BADGE_CACHE_SECONDS,
            fn () => $this->retentionQuery($user)
                ->whereNull('seen_at')
                ->count(),
        );
    }

    /**
     * Lista el historial reciente de notificaciones del usuario formateadas para la UI.
     */
    public function historyFor(User $user): Collection
    {
        return $this->retentionQuery($user)
            ->orderByDesc('created_at')
            ->limit(self::HISTORY_LIMIT)
            ->get()
            ->map(fn (AppNotification $n) => $this->format($n, $user));
    }

    /**
     * Marca una notificación como leída y vista, invalidando caché del badge.
     */
    public function markRead(User $user, int $notificationId): bool
    {
        $notification = $this->retentionQuery($user)
            ->where('id', $notificationId)
            ->first();

        if (! $notification) {
            return false;
        }

        $now = now();

        $notification->update([
            'read_at' => $notification->read_at ?? $now,
            'seen_at' => $notification->seen_at ?? $now,
        ]);

        $this->forgetBadgeCache($user->id);

        return true;
    }

    /**
     * Marca todas las notificaciones pendientes como vistas sin marcarlas leídas.
     */
    public function markAllSeen(User $user): int
    {
        $updated = $this->retentionQuery($user)
            ->whereNull('seen_at')
            ->update(['seen_at' => now()]);

        $this->forgetBadgeCache($user->id);

        return $updated;
    }

    /**
     * Serializa una notificación con URL de acción resuelta según tipo y datos.
     */
    public function format(AppNotification $notification, ?User $viewer = null): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'title' => $notification->title,
            'body' => $notification->body,
            'action_url' => $this->resolveActionUrl($notification, $viewer),
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toISOString(),
            'seen_at' => $notification->seen_at?->toISOString(),
            'is_unread' => $notification->read_at === null,
            'created_at' => $notification->created_at?->toISOString(),
        ];
    }

    /**
     * Determina la URL de destino según el tipo de notificación y metadatos embebidos.
     */
    private function resolveActionUrl(AppNotification $notification, ?User $viewer): string
    {
        $data = $notification->data ?? [];
        $type = $notification->type;

        if (in_array($type, self::STAFF_TYPES, true)) {
            return match ($type) {
                AppNotification::TYPE_MODERATOR_ACTION => $this->staffModeratorUrl($notification->action_url),
                default => '/admin/moderacion',
            };
        }

        return match ($type) {
            AppNotification::TYPE_POST_FROM_FOLLOWING,
            AppNotification::TYPE_TIP_RECEIVED,
            AppNotification::TYPE_REACTION_RECEIVED => $this->postUrl($data, $notification->action_url),

            AppNotification::TYPE_POST_REMOVED => $viewer
                ? "/perfil/{$viewer->username}"
                : ($notification->action_url ?? '/'),

            AppNotification::TYPE_COMMENT_RECEIVED => $this->commentUrl($data, $notification->action_url),

            AppNotification::TYPE_NEW_FOLLOWER,
            AppNotification::TYPE_FOLLOWING_UPDATED => isset($data['actor_username'])
                ? "/perfil/{$data['actor_username']}"
                : ($notification->action_url ?? '/'),

            AppNotification::TYPE_MESSAGE_RECEIVED => isset($data['actor_username'])
                ? "/perfil/{$data['actor_username']}"
                : ($notification->action_url ?? '/'),

            AppNotification::TYPE_RANK_UP,
            AppNotification::TYPE_MEDAL_RECEIVED,
            AppNotification::TYPE_AWARD_RECEIVED => $viewer
                ? "/perfil/{$viewer->username}"
                : ($notification->action_url ?? '/'),

            AppNotification::TYPE_VERIFIED => '/configuracion/verificacion',

            default => $notification->action_url ?? '/',
        };
    }

    /**
     * Construye URL de post a partir del slug en data o fallback almacenado.
     */
    private function postUrl(array $data, ?string $fallback): string
    {
        if (! empty($data['post_slug'])) {
            return '/post/'.$data['post_slug'];
        }

        return $fallback ?? '/';
    }

    /**
     * Construye URL de comentario con ancla, o delega en postUrl si falta comment_id.
     */
    private function commentUrl(array $data, ?string $fallback): string
    {
        if (! empty($data['post_slug']) && ! empty($data['comment_id'])) {
            return '/post/'.$data['post_slug'].'#comment-'.$data['comment_id'];
        }

        return $this->postUrl($data, $fallback);
    }

    /**
     * Normaliza URL de acciones de moderador hacia rutas admin válidas.
     */
    private function staffModeratorUrl(?string $actionUrl): string
    {
        if ($actionUrl && str_starts_with($actionUrl, '/admin')) {
            return $actionUrl;
        }

        return '/admin/moderacion';
    }

    /**
     * Query base filtrada por ventana de retención y opcionalmente por usuario.
     */
    private function retentionQuery(?User $user = null): Builder
    {
        $query = AppNotification::query()
            ->where('created_at', '>=', now()->subDays(self::RETENTION_DAYS));

        if ($user) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    /**
     * Obtiene usuarios admin y staff activos cacheados para notificaciones internas.
     */
    private function staffRecipients(): Collection
    {
        return Cache::remember('gofio:staff_recipients', 300, function () {
            return User::query()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->where('is_admin', true)
                        ->orWhereHas('rango', fn ($rango) => $rango->where('is_staff', true));
                })
                ->get(['id', 'username']);
        });
    }

    /**
     * Genera la clave de caché del badge de notificaciones para un usuario.
     */
    private function badgeCacheKey(int $userId): string
    {
        return "gofio:notif_badge:{$userId}";
    }

    /**
     * Invalida la caché del contador de badge tras cambios en notificaciones.
     */
    private function forgetBadgeCache(int $userId): void
    {
        Cache::forget($this->badgeCacheKey($userId));
    }
}
