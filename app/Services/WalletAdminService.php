<?php

namespace App\Services;

/**
 * Expone analíticas de billetera para administración y registra depósitos manuales de monedas.
 */

use App\Models\User;
use App\Models\WalletTransaction;
use App\Repositories\SiteSettingsRepository;
use App\Repositories\WalletRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WalletAdminService
{
    /**
     * Inyecta repositorios de billetera y ajustes del sitio para métricas y comisiones.
     */
    public function __construct(
        private readonly WalletRepository $walletRepository,
        private readonly SiteSettingsRepository $siteSettingsRepository,
    ) {}

    /**
     * Agrega estadísticas globales de propinas, depósitos, rankings y actividad reciente para el panel admin.
     */
    public function analytics(): array
    {
        $platformUser = $this->walletRepository->findPlatformUser();
        $feePercent = (float) $this->siteSettingsRepository->get('platform_fee_percent', 10);

        $tipStats = WalletTransaction::query()
            ->where('type', 'tip_post')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as gross, COALESCE(SUM(platform_fee), 0) as fees')
            ->first();

        $depositStats = WalletTransaction::query()
            ->where('type', 'deposit')
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as total')
            ->first();

        $todayTips = WalletTransaction::query()
            ->where('type', 'tip_post')
            ->where('created_at', '>=', now()->startOfDay())
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(amount), 0) as gross, COALESCE(SUM(platform_fee), 0) as fees')
            ->first();

        $last30DaysRaw = WalletTransaction::query()
            ->where('type', 'tip_post')
            ->where('created_at', '>=', now()->subDays(30)->startOfDay())
            ->orderBy('created_at')
            ->get(['amount', 'platform_fee', 'created_at']);

        $dailyMap = [];
        foreach ($last30DaysRaw as $tx) {
            $day = $tx->created_at?->toDateString();
            if (! $day) {
                continue;
            }
            if (! isset($dailyMap[$day])) {
                $dailyMap[$day] = ['day' => $day, 'count' => 0, 'gross' => 0.0, 'fees' => 0.0];
            }
            $dailyMap[$day]['count']++;
            $dailyMap[$day]['gross'] += (float) $tx->amount;
            $dailyMap[$day]['fees'] += (float) $tx->platform_fee;
        }
        $last30Days = array_values($dailyMap);

        $topReceivers = WalletTransaction::query()
            ->where('type', 'tip_post')
            ->select('receiver_id', DB::raw('COUNT(*) as tips_count'), DB::raw('COALESCE(SUM(amount - platform_fee), 0) as net_received'))
            ->groupBy('receiver_id')
            ->orderByDesc('net_received')
            ->limit(10)
            ->with('receiver:id,username')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->receiver_id,
                'username' => $row->receiver?->username,
                'tips_count' => (int) $row->tips_count,
                'net_received' => (float) $row->net_received,
            ])
            ->values()
            ->all();

        $topSenders = WalletTransaction::query()
            ->where('type', 'tip_post')
            ->select('sender_id', DB::raw('COUNT(*) as tips_count'), DB::raw('COALESCE(SUM(amount), 0) as gross_sent'))
            ->groupBy('sender_id')
            ->orderByDesc('gross_sent')
            ->limit(10)
            ->with('sender:id,username')
            ->get()
            ->map(fn ($row) => [
                'user_id' => $row->sender_id,
                'username' => $row->sender?->username,
                'tips_count' => (int) $row->tips_count,
                'gross_sent' => (float) $row->gross_sent,
            ])
            ->values()
            ->all();

        $postsWithTips = WalletTransaction::query()
            ->where('type', 'tip_post')
            ->whereNotNull('post_id')
            ->distinct('post_id')
            ->count('post_id');

        return [
            'platform_fee_percent' => $feePercent,
            'allow_tips' => (bool) $this->siteSettingsRepository->get('allow_tips', true),
            'platform_balance' => $platformUser ? (float) $platformUser->balance_monedas : 0,
            'platform_username' => $platformUser?->username,
            'summary' => [
                'tips_count' => (int) ($tipStats->count ?? 0),
                'tips_gross' => (float) ($tipStats->gross ?? 0),
                'platform_fees_collected' => (float) ($tipStats->fees ?? 0),
                'creators_net' => (float) (($tipStats->gross ?? 0) - ($tipStats->fees ?? 0)),
                'deposits_count' => (int) ($depositStats->count ?? 0),
                'deposits_total' => (float) ($depositStats->total ?? 0),
                'posts_tipped' => $postsWithTips,
                'today_tips_count' => (int) ($todayTips->count ?? 0),
                'today_tips_gross' => (float) ($todayTips->gross ?? 0),
                'today_platform_fees' => (float) ($todayTips->fees ?? 0),
            ],
            'daily_tips' => $last30Days,
            'top_receivers' => $topReceivers,
            'top_senders' => $topSenders,
        ];
    }

    /**
     * Devuelve movimientos recientes de billetera paginados para el panel administrativo.
     */
    public function recentTransactionsPaginated(int $perPage = 25): LengthAwarePaginator
    {
        return WalletTransaction::query()
            ->with(['sender:id,username', 'receiver:id,username', 'post:id,title,slug'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn (WalletTransaction $tx) => [
                'id' => $tx->id,
                'type' => $tx->type,
                'amount' => (float) $tx->amount,
                'platform_fee' => (float) $tx->platform_fee,
                'sender' => $tx->sender?->username,
                'receiver' => $tx->receiver?->username,
                'post_title' => $tx->post?->title,
                'post_slug' => $tx->post?->slug,
                'created_at' => $tx->created_at?->toISOString(),
            ]);
    }

    /**
     * Acredita monedas al usuario en transacción bloqueada y registra el movimiento como depósito.
     */
    public function registerDeposit(User $user, float $amount, ?User $admin = null): WalletTransaction
    {
        if ($amount <= 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'amount' => ['El monto debe ser mayor a cero.'],
            ]);
        }

        return DB::transaction(function () use ($user, $amount, $admin) {
            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();
            $lockedUser->increment('balance_monedas', $amount);

            return $this->walletRepository->createTransaction([
                'sender_id' => $admin?->id,
                'receiver_id' => $lockedUser->id,
                'amount' => $amount,
                'platform_fee' => 0,
                'type' => 'deposit',
                'created_at' => now(),
            ]);
        });
    }
}
