<?php

namespace App\Services;

/**
 * Registra visitas a perfiles Creator Plus y expone el historial al propietario del perfil.
 */

use App\Models\AppNotification;
use App\Models\ProfileVisit;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProfileVisitService
{
    /**
     * Inyecta el servicio de notificaciones para alertar al dueño del perfil.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Registra una visita al perfil y notifica al owner solo la primera vez que ocurre en el día.
     */
    public function recordVisit(User $visitor, User $profileOwner): void
    {
        if ($visitor->id === $profileOwner->id) {
            return;
        }

        if (! $profileOwner->isCreatorPlus()) {
            return;
        }

        $alreadyToday = ProfileVisit::query()
            ->where('profile_user_id', $profileOwner->id)
            ->where('visitor_id', $visitor->id)
            ->where('visited_at', '>=', now()->startOfDay())
            ->exists();

        ProfileVisit::query()->create([
            'profile_user_id' => $profileOwner->id,
            'visitor_id' => $visitor->id,
            'visited_at' => now(),
        ]);

        if ($alreadyToday) {
            return;
        }

        $this->notificationService->notify(
            $profileOwner,
            AppNotification::TYPE_PROFILE_VISIT,
            'Visita a tu perfil',
            "@{$visitor->username} visitó tu perfil.",
            "/perfil/{$profileOwner->username}",
            [
                'visitor_id' => $visitor->id,
                'visitor_username' => $visitor->username,
            ],
        );
    }

    /**
     * Lista paginada de visitas recibidas, restringida a usuarios con Creator Plus activo.
     */
    public function listForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        if (! $user->isCreatorPlus()) {
            abort(403, 'Solo Creator Plus puede ver visitas al perfil.');
        }

        return ProfileVisit::query()
            ->with('visitor:id,username,avatar_url,tipo_verificacion')
            ->where('profile_user_id', $user->id)
            ->orderByDesc('visited_at')
            ->paginate($perPage);
    }
}
