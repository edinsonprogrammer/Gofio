<?php

namespace App\Services;

/**
 * Procesa propinas entre usuarios y expone el resumen de billetera con historial de transacciones.
 */

use App\Events\TipReceived;
use App\Models\AppNotification;
use App\Models\Post;
use App\Models\User;
use App\Repositories\SiteSettingsRepository;
use App\Repositories\WalletRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletService
{
    /**
     * Inyecta repositorio de billetera, permisos de propina, efectos asíncronos y reglas de karma.
     */
    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly SiteSettingsRepository $siteSettingsRepository,
        private readonly TipPermissionService $tipPermissionService,
        private readonly AsyncSideEffects $asyncSideEffects,
        private readonly KarmaRuleService $karmaRuleService,
    ) {}

    /**
     * Transfiere monedas del emisor al autor del post descontando comisión de plataforma en transacción atómica.
     */
    public function sendTip(User $sender, Post $post, float $amount): array
    {
        if (! $this->tipPermissionService->tipsGloballyEnabled()) {
            throw ValidationException::withMessages([
                'amount' => ['El sistema de propinas está desactivado.'],
            ]);
        }

        if (! $this->tipPermissionService->canTipPost($sender, $post)) {
            throw ValidationException::withMessages([
                'amount' => ['No puedes enviar propina a este post.'],
            ]);
        }

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['El monto debe ser mayor a cero.'],
            ]);
        }

        $platformUser = $this->walletRepository->findPlatformUser();

        if (! $platformUser) {
            throw ValidationException::withMessages([
                'amount' => ['Cuenta de plataforma no configurada.'],
            ]);
        }

        $feePercent = $this->tipPermissionService->platformFeePercent();
        $platformFee = round($amount * ($feePercent / 100), 2);
        $netAmount = round($amount - $platformFee, 2);

        return DB::transaction(function () use ($sender, $post, $amount, $platformFee, $netAmount, $platformUser, $feePercent) {
            $lockedSender = User::query()->where('id', $sender->id)->lockForUpdate()->first();
            $receiver = User::query()->where('id', $post->user_id)->lockForUpdate()->first();
            $lockedPlatform = User::query()->where('id', $platformUser->id)->lockForUpdate()->first();
            $lockedPost = Post::query()->where('id', $post->id)->lockForUpdate()->first();

            if ((float) $lockedSender->balance_monedas < $amount) {
                throw ValidationException::withMessages([
                    'amount' => ['Saldo insuficiente. Tienes '.number_format((float) $lockedSender->balance_monedas, 2).' monedas disponibles.'],
                ]);
            }

            $lockedSender->decrement('balance_monedas', $amount);
            $receiver->increment('balance_monedas', $netAmount);
            $lockedPlatform->increment('balance_monedas', $platformFee);

            $lockedPost->increment('tips_total', $amount);
            $lockedPost->increment('tips_count');

            $transaction = $this->walletRepository->createTransaction([
                'sender_id' => $lockedSender->id,
                'receiver_id' => $receiver->id,
                'post_id' => $lockedPost->id,
                'amount' => $amount,
                'platform_fee' => $platformFee,
                'type' => 'tip_post',
                'created_at' => now(),
            ]);

            $this->karmaRuleService->evaluateTipSent($lockedSender->fresh(), $transaction->id);
            $this->karmaRuleService->evaluateTipReceived($receiver->fresh(), $transaction->id);

            broadcast(new TipReceived(
                $receiver->id,
                $lockedSender->username,
                $amount,
                $netAmount,
                $lockedPost->id,
                $lockedPost->title,
            ));

            $this->asyncSideEffects->queueNotification(
                $receiver,
                AppNotification::TYPE_TIP_RECEIVED,
                'Propina recibida',
                "@{$lockedSender->username} te envió {$amount} monedas en «{$lockedPost->title}».",
                "/post/{$lockedPost->slug}",
                [
                    'actor_id' => $lockedSender->id,
                    'actor_username' => $lockedSender->username,
                    'post_id' => $lockedPost->id,
                    'post_slug' => $lockedPost->slug,
                    'amount' => $amount,
                ],
            );

            $lockedPost->refresh();

            return [
                'success' => true,
                'amount' => $amount,
                'net_amount' => $netAmount,
                'platform_fee' => $platformFee,
                'platform_fee_percent' => $feePercent,
                'sender_balance' => (float) $lockedSender->fresh()->balance_monedas,
                'tips_total' => (float) $lockedPost->tips_total,
                'tips_count' => (int) $lockedPost->tips_count,
                'message' => "Propina de {$amount} monedas enviada a {$receiver->username}.",
            ];
        });
    }

    /**
     * Devuelve saldo, permisos de propina y últimas transacciones del usuario autenticado.
     */
    public function getWalletSummary(User $user): array
    {
        return [
            'balance' => (float) $user->balance_monedas,
            'can_send_tips' => $this->tipPermissionService->canSendTips($user),
            'platform_fee_percent' => $this->tipPermissionService->platformFeePercent(),
            'tips_enabled' => $this->tipPermissionService->tipsGloballyEnabled(),
            'transactions' => $this->walletRepository->getTransactionsForUser($user->id)
                ->map(fn ($tx) => [
                    'id' => $tx->id,
                    'amount' => (float) $tx->amount,
                    'platform_fee' => (float) $tx->platform_fee,
                    'type' => $tx->type,
                    'post_id' => $tx->post_id,
                    'created_at' => $tx->created_at?->toISOString(),
                    'is_incoming' => $tx->receiver_id === $user->id,
                ]),
        ];
    }
}
