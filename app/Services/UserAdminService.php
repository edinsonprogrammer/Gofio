<?php

namespace App\Services;

/**
 * Operaciones administrativas sobre usuarios: baneos, verificación, Creator Plus y edición de datos.
 */

use App\Models\User;
use App\Models\RolRango;
use App\Models\UserSuspension;
use App\Repositories\SiteSettingsRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAdminService
{
    /**
     * Inyecta servicios de auditoría, notificaciones, verificación, suscripción y rangos.
     */
    public function __construct(
        private readonly AdminLogService $adminLogService,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly NotificationService $notificationService,
        private readonly VerificationService $verificationService,
        private readonly SubscriptionService $subscriptionService,
        private readonly RankService $rankService,
    ) {}

    /**
     * Suspende a un usuario creando registro de suspensión, desactivando la cuenta y auditando la acción.
     */
    public function banUser(User $admin, User $target, string $reason, ?int $days = null): void
    {
        if ($target->isAdmin()) {
            throw ValidationException::withMessages(['user' => ['No puedes banear a un administrador.']]);
        }

        $expiresAt = $days ? now()->addDays($days) : null;

        DB::transaction(function () use ($admin, $target, $reason, $expiresAt) {
            UserSuspension::query()
                ->where('user_id', $target->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            UserSuspension::create([
                'user_id' => $target->id,
                'moderator_id' => $admin->id,
                'reason' => $reason,
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'is_active' => true,
            ]);

            $target->update([
                'is_banned' => true,
                'ban_reason' => $reason,
                'banned_until' => $expiresAt,
                'is_active' => false,
            ]);
        });

        $this->adminLogService->log($admin, 'ban_user', 'user', $target->id, $reason);

        if ($admin->hasStaffRank() && ! $admin->isAdmin()) {
            $this->notificationService->notifyStaff(
                \App\Models\AppNotification::TYPE_MODERATOR_ACTION,
                'Acción de moderador',
                "@{$admin->username} baneó a @{$target->username}. Motivo: {$reason}",
                '/admin/moderacion',
                ['actor_id' => $admin->id, 'target_user_id' => $target->id],
            );
        }
    }

    /**
     * Levanta la suspensión activa del usuario y restaura su acceso a la plataforma.
     */
    public function unbanUser(User $admin, User $target, ?string $reason = null): void
    {
        DB::transaction(function () use ($target) {
            UserSuspension::query()
                ->where('user_id', $target->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $target->update([
                'is_banned' => false,
                'ban_reason' => null,
                'banned_until' => null,
                'is_active' => true,
            ]);
        });

        $this->adminLogService->log($admin, 'unban_user', 'user', $target->id, $reason);
    }

    /**
     * Revoca la verificación de identidad del usuario y registra la acción administrativa.
     */
    public function revokeVerification(User $admin, User $target, ?string $reason = null): User
    {
        $this->assertGlobalAdmin($admin, 'revocar verificaciones');

        $this->verificationService->revokeIdentityVerification($target, $admin, $reason);
        $this->adminLogService->log($admin, 'revoke_verification', 'user', $target->id, $reason);

        return $target->fresh();
    }

    /**
     * Revoca Creator Plus del usuario delegando en el servicio de suscripciones.
     */
    public function revokeCreatorPlus(User $admin, User $target, ?string $reason = null): User
    {
        $this->assertGlobalAdmin($admin, 'revocar Creator Plus');

        $this->subscriptionService->revokeCreatorPlus($target, $admin, $reason);

        return $target->fresh();
    }

    /**
     * Actualiza campos administrativos del usuario gestionando cambios de verificación, rango y karma.
     */
    public function updateUser(User $admin, User $target, array $data): User
    {
        // Filtra campos según rol: moderadores no pueden escalar privilegios.
        $data = $this->filterUpdatePayloadForActor($admin, $target, $data);

        if ($target->isAdmin() && isset($data['is_admin']) && ! $data['is_admin']) {
            throw ValidationException::withMessages(['user' => ['No puedes quitar admin al único administrador.']]);
        }

        $previousKarma = $target->karma;
        $previousRangoId = $target->rango_id;
        $previousTipo = $target->tipo_verificacion;
        $newTipo = $data['tipo_verificacion'] ?? $target->tipo_verificacion;

        if (isset($data['tipo_verificacion']) && $data['tipo_verificacion'] !== $previousTipo) {
            $this->assertGlobalAdmin($admin, 'cambiar el tipo de verificación');
            $this->applyVerificationTypeChange($admin, $target, $previousTipo, $newTipo);
            unset($data['tipo_verificacion']);
        }

        $payload = [
            'karma' => $data['karma'] ?? $target->karma,
            'balance_monedas' => $data['balance_monedas'] ?? $target->balance_monedas,
            'rango_id' => $data['rango_id'] ?? $target->rango_id,
            'is_admin' => array_key_exists('is_admin', $data) ? (bool) $data['is_admin'] : $target->is_admin,
        ];

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        if (array_key_exists('nick', $data)) {
            $payload['nick'] = $data['nick'];
        }

        if (isset($data['tipo_verificacion'])) {
            $payload['tipo_verificacion'] = $data['tipo_verificacion'];
        }

        if (isset($data['creator_plus_expires_at'])) {
            $payload['creator_plus_expires_at'] = $data['creator_plus_expires_at'] ?: null;
        }

        $target->update($payload);
        $target->refresh();

        if (isset($data['rango_id']) && (int) $data['rango_id'] !== (int) $previousRangoId) {
            $rango = RolRango::query()->find($data['rango_id']);
            if ($rango) {
                $this->rankService->applyStaffVerification($target, $rango);
            }
        }

        $this->adminLogService->log($admin, 'update_user', 'user', $target->id, null, $data);

        if (
            $admin->hasStaffRank()
            && ! $admin->isAdmin()
            && isset($data['karma'])
            && (int) $data['karma'] !== (int) $previousKarma
        ) {
            $this->notificationService->notifyStaff(
                \App\Models\AppNotification::TYPE_MODERATOR_ACTION,
                'Acción de moderador',
                "@{$admin->username} cambió el karma de @{$target->username} de {$previousKarma} a {$data['karma']}.",
                '/admin/usuarios',
                ['actor_id' => $admin->id, 'target_user_id' => $target->id],
            );
        }

        return $target->fresh();
    }

    /**
     * Restringe la edición de usuarios según rol del actor y bloquea escalada de privilegios.
     */
    private function filterUpdatePayloadForActor(User $admin, User $target, array $data): array
    {
        if ($admin->isAdmin()) {
            return $data;
        }

        if (! $admin->canAccessAdminTab('users')) {
            throw ValidationException::withMessages(['user' => ['No tienes permiso para editar usuarios.']]);
        }

        if ($target->isAdmin()) {
            throw ValidationException::withMessages(['user' => ['No puedes editar a un administrador global.']]);
        }

        $allowedKeys = ['karma', 'is_active', 'nick'];
        $forbidden = array_diff(array_keys($data), $allowedKeys);

        if ($forbidden !== []) {
            throw ValidationException::withMessages([
                'user' => ['No tienes permiso para modificar: '.implode(', ', $forbidden).'.'],
            ]);
        }

        if (isset($data['karma'])) {
            $this->assertModeratorKarmaLimit($target, (int) $data['karma']);
        }

        return array_intersect_key($data, array_flip($allowedKeys));
    }

    /**
     * Comprueba que solo un administrador global ejecute acciones de privilegio elevado.
     */
    private function assertGlobalAdmin(User $admin, string $actionLabel): void
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'user' => ["Solo un administrador global puede {$actionLabel}."],
            ]);
        }
    }

    /**
     * Limita el delta de karma que un moderador puede aplicar en una sola acción.
     */
    private function assertModeratorKarmaLimit(User $target, int $newKarma): void
    {
        $limit = (int) config('gofio.moderator_karma_delta_limit', 500);
        $delta = abs($newKarma - (int) $target->karma);

        if ($delta > $limit) {
            throw ValidationException::withMessages([
                'karma' => ["Un moderador solo puede ajustar el karma ±{$limit} puntos por acción."],
            ]);
        }
    }

    /**
     * Aplica transiciones manuales de tipo_verificacion revocando suscripciones o verificación previa.
     */
    private function applyVerificationTypeChange(User $admin, User $target, string $previous, string $new): void
    {
        if ($new === 'none') {
            if ($target->isCreatorPlus() || $previous === 'creator_plus') {
                $this->subscriptionService->revokeCreatorPlus($target, $admin, 'Cambio manual desde panel de usuarios.');
                $target->refresh();
            }

            if ($target->hasApprovedIdentityVerification()) {
                $this->verificationService->revokeIdentityVerification($target, $admin, 'Cambio manual desde panel de usuarios.');
            } elseif ($target->tipo_verificacion !== 'none') {
                $target->update(['tipo_verificacion' => 'none', 'creator_plus_expires_at' => null]);
            }

            return;
        }

        if ($new === 'user_verified' && $previous === 'creator_plus') {
            $this->subscriptionService->revokeCreatorPlus($target, $admin, 'Cambio a verificado simple.');
            $target->refresh();
            $target->update(['tipo_verificacion' => 'user_verified']);

            return;
        }

        if ($new === 'creator_plus') {
            $target->update([
                'tipo_verificacion' => 'creator_plus',
                'creator_plus_expires_at' => now()->addDays((int) config('gofio.creator_plus_days', 30)),
            ]);

            return;
        }

        if ($new === 'user_verified') {
            $target->update(['tipo_verificacion' => 'user_verified']);
        }
    }
}
