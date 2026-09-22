<?php

namespace App\Repositories;

/**
 * Persistencia de suscripciones Creator Plus y consultas de expiración por usuario.
 */

use App\Models\CreatorSubscription;
use App\Models\User;
use Illuminate\Support\Collection;

class SubscriptionRepository
{
    /**
     * Obtiene la suscripción activa no vencida más reciente de un usuario.
     */
    public function getActiveForUser(int $userId): ?CreatorSubscription
    {
        return CreatorSubscription::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->where('expires_at', '>', now())
            ->latest('expires_at')
            ->first();
    }

    /**
     * Inserta un nuevo registro de suscripción Creator Plus.
     */
    public function create(array $data): CreatorSubscription
    {
        return CreatorSubscription::create($data);
    }

    /**
     * Marca como expiradas todas las suscripciones activas del usuario indicado.
     */
    public function expireActiveForUser(int $userId): void
    {
        CreatorSubscription::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->update(['status' => 'expired']);
    }

    /**
     * Lista usuarios cuyo Creator Plus expiró según fecha de vencimiento en perfil.
     */
    public function getExpiredCreatorPlusUsers(): Collection
    {
        return User::query()
            ->with('theme')
            ->where('tipo_verificacion', 'creator_plus')
            ->whereNotNull('creator_plus_expires_at')
            ->where('creator_plus_expires_at', '<=', now())
            ->get();
    }
}
