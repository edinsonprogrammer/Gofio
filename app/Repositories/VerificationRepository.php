<?php

namespace App\Repositories;

/**
 * Acceso a solicitudes de verificación de identidad y listados administrativos relacionados.
 */

use App\Models\IdentityVerificationRequest;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class VerificationRepository
{
    /**
     * Obtiene la solicitud de verificación más reciente enviada por un usuario.
     */
    public function findLatestForUser(int $userId): ?IdentityVerificationRequest
    {
        return IdentityVerificationRequest::query()
            ->where('user_id', $userId)
            ->latest()
            ->first();
    }

    /**
     * Indica si el usuario tiene alguna solicitud de verificación en estado pendiente.
     */
    public function hasPendingRequest(int $userId): bool
    {
        return IdentityVerificationRequest::query()
            ->where('user_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }

    /**
     * Crea un nuevo registro de solicitud de verificación de identidad.
     */
    public function create(array $data): IdentityVerificationRequest
    {
        return IdentityVerificationRequest::create($data);
    }

    /**
     * Busca una solicitud de verificación por identificador numérico.
     */
    public function findById(int $id): ?IdentityVerificationRequest
    {
        return IdentityVerificationRequest::query()->find($id);
    }

    /**
     * Pagina solicitudes pendientes con búsqueda opcional por usuario, correo o nombre legal.
     */
    public function getPendingPaginated(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        return IdentityVerificationRequest::query()
            ->with('user:id,username,email,tipo_verificacion')
            ->where('status', 'pending')
            ->when($search, function ($query, $search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('full_name', 'like', $like)
                        ->orWhereHas('user', function ($userQuery) use ($like) {
                            $userQuery->where('username', 'like', $like)
                                ->orWhere('email', 'like', $like);
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Marca como revocadas todas las solicitudes aprobadas del usuario registrando al administrador.
     */
    public function revokeApprovedRequests(User $user, User $admin, ?string $adminNotes = null): int
    {
        return IdentityVerificationRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->update([
                'status' => 'revoked',
                'admin_notes' => $adminNotes,
                'reviewed_by' => $admin->id,
                'reviewed_at' => now(),
            ]);
    }

    /**
     * Pagina usuarios verificados con búsqueda opcional por usuario o correo.
     */
    public function getVerifiedUsersPaginated(int $perPage = 20, ?string $search = null): LengthAwarePaginator
    {
        return User::query()
            ->where(function ($query) {
                $query->whereHas('identityVerificationRequests', function ($sub) {
                    $sub->where('status', 'approved');
                })->orWhere('tipo_verificacion', 'user_verified');
            })
            ->when($search, function ($query, $search) {
                $like = '%'.$search.'%';
                $query->where(function ($q) use ($like) {
                    $q->where('username', 'like', $like)
                        ->orWhere('email', 'like', $like);
                });
            })
            ->orderByDesc('updated_at')
            // Usa query key distinto para no colisionar con el paginador de solicitudes pendientes
            ->paginate($perPage, ['id', 'username', 'email', 'tipo_verificacion', 'creator_plus_expires_at'], 'verified_page')
            ->withQueryString();
    }

    /**
     * Cuenta solicitudes pendientes de revisión (para badges del panel).
     */
    public function countPending(): int
    {
        return IdentityVerificationRequest::query()
            ->where('status', 'pending')
            ->count();
    }

    /**
     * Cuenta usuarios con verificación activa por solicitud o tipo manual.
     */
    public function countVerifiedUsers(): int
    {
        return User::query()
            ->where(function ($query) {
                $query->whereHas('identityVerificationRequests', function ($sub) {
                    $sub->where('status', 'approved');
                })->orWhere('tipo_verificacion', 'user_verified');
            })
            ->count();
    }

    /**
     * Obtiene solicitudes pendientes por IDs para acciones en lote.
     */
    public function getPendingByIds(array $ids): Collection
    {
        return IdentityVerificationRequest::query()
            ->with('user')
            ->whereIn('id', $ids)
            ->where('status', 'pending')
            ->get();
    }

    /**
     * Recupera las últimas solicitudes ya revisadas con usuario y revisor asociados.
     */
    public function getRecentReviewed(int $limit = 10): Collection
    {
        return IdentityVerificationRequest::query()
            ->with(['user:id,username', 'reviewer:id,username'])
            ->whereIn('status', ['approved', 'rejected', 'revoked'])
            ->latest('reviewed_at')
            ->limit($limit)
            ->get();
    }
}
