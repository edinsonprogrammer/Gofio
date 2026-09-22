<?php

namespace App\Services;

/**
 * Gestiona la lectura, actualización y notificación de cambios en el perfil de usuario.
 */

use App\Models\AppNotification;
use App\Models\User;
use App\Support\GiphyUrl;
use App\Support\StorageMediaUrl;

class ProfileService
{
    /**
     * Inyecta servicios de notificaciones, karma y sincronización de gamificación.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly KarmaRuleService $karmaRuleService,
        private readonly GamificationService $gamificationService,
    ) {}

    /**
     * Serializa el perfil público de un usuario con estadísticas y datos de contacto visibles.
     */
    public function formatPublicProfile(User $user): array
    {
        return [
            'id' => $user->id,
            'username' => $user->username,
            'nick' => $user->nick,
            'karma' => $user->karma,
            'tipo_verificacion' => $user->displayVerificationTipo(),
            'creator_plus_expires_at' => $user->creator_plus_expires_at?->toISOString(),
            'avatar_url' => $user->avatar_url,
            'banner_url' => $user->banner_url,
            // Offsets para posicionar el banner en el perfil público.
            'banner_offset_x' => $user->banner_offset_x ?? 50,
            'banner_offset_y' => $user->banner_offset_y ?? 50,
            'country' => $user->country,
            'country_code' => $user->country_code,
            'age' => $user->age,
            'bio' => $user->bio,
            'bio_gif_url' => $user->bio_gif_url,
            'whatsapp' => $user->whatsapp,
            'instagram' => $user->instagram,
            'facebook' => $user->facebook,
            'social_x' => $user->social_x,
            'posts_count' => $user->posts_count ?? $user->posts()->count(),
            'comments_count' => $user->comments_count ?? $user->comments()->count(),
            'created_at' => $user->created_at?->toISOString(),
        ];
    }

    /**
     * Serializa los campos editables del perfil para la pantalla de configuración del usuario.
     */
    public function formatForSettings(User $user): array
    {
        return [
            'username' => $user->username,
            'nick' => $user->nick,
            'email' => $user->email,
            'avatar_url' => $user->avatar_url,
            'banner_url' => $user->banner_url,
            // Offsets de posicionamiento del banner (0–100 %).
            'banner_offset_x' => $user->banner_offset_x ?? 50,
            'banner_offset_y' => $user->banner_offset_y ?? 50,
            'country' => $user->country,
            'country_code' => $user->country_code,
            'age' => $user->age,
            'bio' => $user->bio,
            'bio_gif_url' => $user->bio_gif_url,
            'whatsapp' => $user->whatsapp,
            'instagram' => $user->instagram,
            'facebook' => $user->facebook,
            'social_x' => $user->social_x,
        ];
    }

    /**
     * Actualiza datos del perfil, notifica cambios de nick y evalúa reglas de karma y gamificación.
     */
    public function updateProfile(User $user, array $data): User
    {
        $previousNick = $user->nick;

        $payload = [
            'nick' => User::normalizeNick($data['nick'] ?? null),
            'email' => $data['email'],
            'country' => $data['country'] ?? null,
            'country_code' => isset($data['country_code']) ? strtoupper((string) $data['country_code']) : null,
            'age' => isset($data['age']) ? (int) $data['age'] : null,
            'bio' => isset($data['bio']) ? trim((string) $data['bio']) : null,
            'bio_gif_url' => array_key_exists('bio_gif_url', $data)
                ? (GiphyUrl::sanitize((string) ($data['bio_gif_url'] ?? '')) ?: null)
                : $user->bio_gif_url,
            'whatsapp' => $this->normalizePhone($data['whatsapp'] ?? null),
            'instagram' => $this->normalizeSocialHandle($data['instagram'] ?? null),
            'facebook' => $this->normalizeSocialHandle($data['facebook'] ?? null),
            'social_x' => $this->normalizeSocialHandle($data['social_x'] ?? null),
        ];

        if (array_key_exists('avatar_url', $data) && $data['avatar_url']) {
            $avatarUrl = StorageMediaUrl::sanitizeStorageOnly((string) $data['avatar_url']);

            if ($avatarUrl !== null) {
                $payload['avatar_url'] = $avatarUrl;
            }
        }

        if (array_key_exists('banner_url', $data) && $data['banner_url']) {
            $bannerUrl = StorageMediaUrl::sanitizeStorageOnly((string) $data['banner_url']);

            if ($bannerUrl !== null) {
                $payload['banner_url'] = $bannerUrl;
            }
        }

        // Persiste la posición del banner si el usuario la ajustó.
        if (array_key_exists('banner_offset_x', $data)) {
            $payload['banner_offset_x'] = max(0, min(100, (int) $data['banner_offset_x']));
        }

        if (array_key_exists('banner_offset_y', $data)) {
            $payload['banner_offset_y'] = max(0, min(100, (int) $data['banner_offset_y']));
        }

        if (! empty($data['password'])) {
            $payload['password'] = $data['password'];
        }

        $user->update($payload);
        $user = $user->fresh();

        $newNick = $user->nick;
        if ($newNick !== $previousNick) {
            $this->notifyFollowersProfileUpdate($user, 'nick', $previousNick, $newNick);
        }

        $this->karmaRuleService->evaluateProfileCompleted($user);
        $this->gamificationService->syncAfterActivity($user);

        return $user;
    }

    /**
     * Actualiza la URL del avatar, notifica a seguidores y sincroniza karma y gamificación.
     */
    public function updateAvatar(User $user, string $url): User
    {
        $sanitized = StorageMediaUrl::sanitizeStorageOnly($url);

        if ($sanitized === null) {
            return $user;
        }

        $user->update(['avatar_url' => $sanitized]);
        $user = $user->fresh();
        $this->notifyFollowersProfileUpdate($user, 'avatar');
        $this->karmaRuleService->evaluateProfileCompleted($user);
        $this->gamificationService->syncAfterActivity($user);

        return $user;
    }

    /**
     * Actualiza la URL del banner del perfil sin efectos secundarios adicionales.
     */
    public function updateBanner(User $user, string $url): User
    {
        $sanitized = StorageMediaUrl::sanitizeStorageOnly($url);

        if ($sanitized === null) {
            return $user;
        }

        $user->update(['banner_url' => $sanitized]);

        return $user->fresh();
    }

    /**
     * Normaliza un número de teléfono dejando solo dígitos o null si queda vacío.
     */
    private function normalizePhone(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits === '' ? null : $digits;
    }

    /**
     * Limpia handles o URLs de redes sociales extrayendo solo el identificador de usuario.
     */
    private function normalizeSocialHandle(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = preg_replace('#^https?://(www\.)?(instagram\.com|facebook\.com|fb\.com|x\.com|twitter\.com)/#i', '', $value);
        $value = trim($value, '/@ ');

        return $value === '' ? null : $value;
    }

    /**
     * Notifica a los seguidores cuando el usuario cambia nick, avatar u otros datos del perfil.
     */
    private function notifyFollowersProfileUpdate(User $user, string $field, ?string $from = null, ?string $to = null): void
    {
        $body = match ($field) {
            'nick' => $to
                ? ($from
                    ? "@{$user->username} cambió su nick de @{$from} a @{$to}."
                    : "@{$user->username} ahora usa el nick @{$to}.")
                : "@{$user->username} actualizó su nick.",
            'avatar' => "@{$user->username} actualizó su foto de perfil.",
            default => "@{$user->username} actualizó su perfil.",
        };

        $this->notificationService->notifyFollowers(
            $user,
            AppNotification::TYPE_FOLLOWING_UPDATED,
            'Actualización de perfil',
            $body,
            "/perfil/{$user->username}",
            ['field' => $field, 'from' => $from, 'to' => $to],
        );
    }
}
