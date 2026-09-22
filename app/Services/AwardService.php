<?php

namespace App\Services;

/**
 * Otorga, revoca y formatea premios (awards) asignados a perfiles de usuario.
 */

use App\Models\AppNotification;
use App\Models\Award;
use App\Models\User;

class AwardService
{
    /**
     * Inyecta el servicio de notificaciones para avisar al usuario al recibir un premio.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Asigna un premio al usuario si está activo y aún no lo posee; notifica al destinatario.
     */
    public function grant(User $user, Award $award, ?User $grantedBy = null, ?string $note = null): bool
    {
        if (! $award->is_active) {
            return false;
        }

        if ($user->awards()->where('awards.id', $award->id)->exists()) {
            return false;
        }

        $user->awards()->attach($award->id, [
            'granted_by' => $grantedBy?->id,
            'note' => $note,
            'granted_at' => now(),
        ]);

        $this->notificationService->notify(
            $user,
            AppNotification::TYPE_AWARD_RECEIVED,
            'Nuevo premio',
            "Recibiste el premio «{$award->name}».",
            "/perfil/{$user->username}",
            ['award_id' => $award->id, 'award_name' => $award->name],
        );

        return true;
    }

    /**
     * Desvincula un premio previamente otorgado al usuario.
     */
    public function revoke(User $user, Award $award): void
    {
        $user->awards()->detach($award->id);
    }

    /**
     * Serializa los premios del usuario para mostrarlos en su perfil público.
     */
    public function formatForProfile(User $user): array
    {
        return $user->awards()
            ->with('category')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Award $award) => [
                'id' => $award->id,
                'name' => $award->name,
                'description' => $award->description,
                'icon' => $award->icon,
                'color' => $award->color,
                'category' => $award->category?->name,
                'granted_at' => $award->pivot->granted_at,
            ])
            ->values()
            ->all();
    }
}
