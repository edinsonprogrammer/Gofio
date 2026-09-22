<?php

namespace App\Services;

/**
 * Sincroniza, asigna y formatea rangos de gamificación según karma y roles de staff.
 */

use App\Models\AppNotification;
use App\Models\RolRango;
use App\Models\User;

class RankService
{
    /**
     * Inyecta el servicio de notificaciones para avisar ascensos y acciones de moderación.
     */
    public function __construct(
        private readonly NotificationService $notificationService,
    ) {}

    /**
     * Promueve automáticamente al usuario al rango más alto alcanzable según su karma actual.
     */
    public function syncUserRank(User $user): ?RolRango
    {
        $user->loadMissing('rango');

        if ($user->hasStaffRank() || $user->rango_locked) {
            return $user->rango;
        }

        $bestRank = RolRango::query()
            ->where('auto_promote', true)
            ->where('is_staff', false)
            ->where('puntos_requeridos', '<=', $user->karma)
            ->orderByDesc('puntos_requeridos')
            ->first();

        if (! $bestRank || $bestRank->id === $user->rango_id) {
            return $user->rango;
        }

        $user->update(['rango_id' => $bestRank->id]);
        $user->setRelation('rango', $bestRank);

        return $bestRank;
    }

    /**
     * Asigna un rango manualmente, bloquea promoción automática y notifica al usuario y al staff.
     */
    public function assignManually(User $user, RolRango $rango, ?User $assignedBy = null): void
    {
        $previousRangoId = $user->rango_id;

        $user->update([
            'rango_id' => $rango->id,
            'rango_locked' => true,
        ]);
        $user->setRelation('rango', $rango);

        if ($previousRangoId !== $rango->id) {
            $this->notificationService->notify(
                $user,
                AppNotification::TYPE_RANK_UP,
                '¡Subiste de rango!',
                "Ahora eres {$rango->nombre}.",
                "/perfil/{$user->username}",
                ['rango_id' => $rango->id, 'rango_nombre' => $rango->nombre],
            );
        }

        if ($assignedBy && $assignedBy->hasStaffRank() && ! $assignedBy->isAdmin()) {
            $this->notificationService->notifyStaff(
                AppNotification::TYPE_MODERATOR_ACTION,
                'Acción de moderador',
                "@{$assignedBy->username} asignó el rango «{$rango->nombre}» a @{$user->username}.",
                '/admin/rangos',
                ['actor_id' => $assignedBy->id, 'target_user_id' => $user->id],
            );
        }

        $this->applyStaffVerification($user->fresh(), $rango);
    }

    /**
     * Otorga verificación automática a miembros del staff que no sean admin ni Creator Plus.
     */
    public function applyStaffVerification(User $user, RolRango $rango): void
    {
        if (! $rango->is_staff || $user->isAdmin()) {
            return;
        }

        if ($user->isCreatorPlus()) {
            return;
        }

        if ($user->tipo_verificacion !== 'user_verified') {
            $user->update(['tipo_verificacion' => 'user_verified']);
        }
    }

    /**
     * Desbloquea el rango del usuario y recalcula su ascenso automático según karma.
     */
    public function unlock(User $user): ?RolRango
    {
        $user->update(['rango_locked' => false]);
        $user->setRelation('rango', $user->rango);

        return $this->syncUserRank($user);
    }

    /**
     * Serializa los campos públicos del rango para respuestas de API.
     */
    public function formatRango(?RolRango $rango): ?array
    {
        if (! $rango) {
            return null;
        }

        return $rango->only(['id', 'nombre', 'slug', 'color', 'icon', 'poder_voto', 'limite_voto_diario']);
    }
}
