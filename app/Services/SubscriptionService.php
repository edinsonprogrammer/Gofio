<?php

namespace App\Services;

/**
 * Administra suscripciones Creator Plus: cotización, activación, expiración y revocación administrativa.
 */

use App\Models\AppNotification;
use App\Models\User;
use App\Repositories\SubscriptionRepository;
use App\Repositories\ThemeRepository;
use App\Repositories\WalletRepository;
use App\Support\CreatorPlusBenefits;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SubscriptionService
{
    /**
     * Inyecta repositorios de suscripción, billetera, temas y servicios de auditoría.
     */
    public function __construct(
        private readonly SubscriptionRepository $subscriptionRepository,
        private readonly WalletRepository $walletRepository,
        private readonly ThemeRepository $themeRepository,
        private readonly NotificationService $notificationService,
        private readonly AdminLogService $adminLogService,
    ) {}

    /**
     * Devuelve el estado completo de Creator Plus del usuario con precios, saldo y beneficios.
     */
    public function getStatus(User $user): array
    {
        $active = $this->subscriptionRepository->getActiveForUser($user->id);
        $monthlyPrice = $this->monthlyPrice();
        $monthsOptions = [1, 3, 6, 12];

        return [
            'is_creator_plus' => $user->isCreatorPlus(),
            'tipo_verificacion' => $user->tipo_verificacion,
            'expires_at' => $user->creator_plus_expires_at?->toISOString(),
            'monthly_price' => $monthlyPrice,
            'days_per_month' => $this->daysPerMonth(),
            'months_options' => array_map(fn (int $m) => $this->quoteForMonths($m), $monthsOptions),
            'can_subscribe' => (float) $user->balance_monedas >= $monthlyPrice,
            'balance_monedas' => (float) $user->balance_monedas,
            'payment_gateway_enabled' => false,
            'payment_gateway_message' => 'La pasarela de pago con tarjeta estará disponible próximamente. Por ahora puedes activar Creator Plus con monedas de tu billetera.',
            'active_subscription' => $active ? [
                'starts_at' => $active->starts_at->toISOString(),
                'expires_at' => $active->expires_at->toISOString(),
                'amount_paid' => (float) $active->amount_paid,
            ] : null,
            'benefits' => CreatorPlusBenefits::labels(),
            'benefit_details' => CreatorPlusBenefits::all(),
        ];
    }

    /**
     * Calcula precio total, días y etiqueta para una cantidad de meses de suscripción.
     */
    public function quoteForMonths(int $months): array
    {
        $months = max(1, min(24, $months));
        $monthly = $this->monthlyPrice();
        $total = round($monthly * $months, 2);
        $days = $this->daysPerMonth() * $months;

        return [
            'months' => $months,
            'monthly_price' => $monthly,
            'total_price' => $total,
            'total_days' => $days,
            'label' => $months === 1 ? '1 mes' : "{$months} meses",
        ];
    }

    /**
     * Activa o extiende Creator Plus cobrando monedas al usuario y transfiriéndolas a la cuenta plataforma.
     */
    public function subscribe(User $user, int $months = 1): array
    {
        $months = max(1, min(24, $months));
        $quote = $this->quoteForMonths($months);
        $price = (float) $quote['total_price'];
        $days = (int) $quote['total_days'];
        $platformUser = $this->walletRepository->findPlatformUser();

        if (! $platformUser) {
            throw ValidationException::withMessages([
                'subscription' => ['Cuenta de plataforma no configurada.'],
            ]);
        }

        return DB::transaction(function () use ($user, $price, $days, $platformUser, $months) {
            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->first();
            $lockedPlatform = User::query()->where('id', $platformUser->id)->lockForUpdate()->first();

            if ((float) $lockedUser->balance_monedas < $price) {
                throw ValidationException::withMessages([
                    'subscription' => ["Saldo insuficiente. Necesitas {$price} monedas para {$months} mes(es)."],
                ]);
            }

            $lockedUser->decrement('balance_monedas', $price);
            $lockedPlatform->increment('balance_monedas', $price);

            $baseDate = $lockedUser->creator_plus_expires_at && $lockedUser->creator_plus_expires_at->isFuture()
                ? $lockedUser->creator_plus_expires_at
                : now();

            $expiresAt = $baseDate->copy()->addDays($days);

            $this->subscriptionRepository->expireActiveForUser($lockedUser->id);

            $this->subscriptionRepository->create([
                'user_id' => $lockedUser->id,
                'amount_paid' => $price,
                'starts_at' => now(),
                'expires_at' => $expiresAt,
                'status' => 'active',
            ]);

            $creatorPlusTheme = $this->themeRepository->findBySlug('creator-plus');

            $lockedUser->update([
                'tipo_verificacion' => 'creator_plus',
                'creator_plus_expires_at' => $expiresAt,
                'theme_id' => $creatorPlusTheme?->id ?? $lockedUser->theme_id,
            ]);

            $this->walletRepository->createTransaction([
                'sender_id' => $lockedUser->id,
                'receiver_id' => $lockedPlatform->id,
                'amount' => $price,
                'platform_fee' => 0,
                'type' => 'subscription_premium',
                'created_at' => now(),
            ]);

            return [
                'success' => true,
                'months' => $months,
                'expires_at' => $expiresAt->toISOString(),
                'balance' => (float) $lockedUser->fresh()->balance_monedas,
                'message' => "Creator Plus activo por {$months} mes(es). ¡Disfruta tus beneficios premium!",
            ];
        });
    }

    /**
     * Expira suscripciones vencidas y restaura tipo de verificación y tema del usuario afectado.
     */
    public function expireDueSubscriptions(): int
    {
        $expiredUsers = $this->subscriptionRepository->getExpiredCreatorPlusUsers();
        $count = 0;

        foreach ($expiredUsers as $user) {
            $this->subscriptionRepository->expireActiveForUser($user->id);

            $newType = $user->hasApprovedIdentityVerification() ? 'user_verified' : 'none';

            $updates = [
                'tipo_verificacion' => $newType,
                'creator_plus_expires_at' => null,
            ];

            $user->loadMissing(['theme', 'rango']);
            if (! $user->isAdmin() && ! $user->hasStaffRank()) {
                $updates['theme_id'] = null;
            }

            $user->update($updates);

            $count++;
        }

        return $count;
    }

    /**
     * Revoca Creator Plus de un usuario por acción administrativa, notificándolo y registrando auditoría.
     */
    public function revokeCreatorPlus(User $user, User $admin, ?string $reason = null): bool
    {
        $user->refresh();

        $hadCreatorPlus = $user->isCreatorPlus()
            || $user->tipo_verificacion === 'creator_plus'
            || $this->subscriptionRepository->getActiveForUser($user->id) !== null;

        if (! $hadCreatorPlus) {
            throw ValidationException::withMessages([
                'user' => ['Este usuario no tiene Creator Plus activo.'],
            ]);
        }

        $this->subscriptionRepository->expireActiveForUser($user->id);

        $newType = $user->hasApprovedIdentityVerification() ? 'user_verified' : 'none';

        $updates = [
            'tipo_verificacion' => $newType,
            'creator_plus_expires_at' => null,
        ];

        $user->loadMissing(['theme', 'rango']);
        if (! $user->isAdmin() && ! $user->hasStaffRank()) {
            $updates['theme_id'] = null;
        }

        $user->update($updates);

        $this->notificationService->notify(
            $user->fresh(),
            AppNotification::TYPE_MODERATOR_ACTION,
            'Creator Plus retirado',
            'Tu suscripción Creator Plus fue cancelada por un administrador.'.($reason ? " Motivo: {$reason}" : ''),
            '/configuracion/creator-plus',
            ['action' => 'creator_plus_revoked'],
        );

        $this->adminLogService->log(
            $admin,
            'revoke_creator_plus',
            'user',
            $user->id,
            $reason,
        );

        return true;
    }

    /**
     * Lee el precio mensual de Creator Plus desde la configuración de la aplicación.
     */
    private function monthlyPrice(): float
    {
        return (float) config('gofio.creator_plus_monthly_price', 50);
    }

    /**
     * Lee cuántos días equivalen a un mes de suscripción Creator Plus.
     */
    private function daysPerMonth(): int
    {
        return (int) config('gofio.creator_plus_days', 30);
    }
}
