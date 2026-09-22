<?php

/**
 * Comando Artisan que expira suscripciones Creator Plus vencidas.
 */

namespace App\Console\Commands;

use App\Services\SubscriptionService;
use Illuminate\Console\Command;

class ExpireCreatorPlusSubscriptions extends Command
{
    protected $signature = 'gofio:expire-subscriptions';

    protected $description = 'Expira suscripciones Creator Plus vencidas';

    /**
     * Marca como expiradas las suscripciones Creator Plus vencidas.
     */
    public function handle(SubscriptionService $subscriptionService): int
    {
        $count = $subscriptionService->expireDueSubscriptions();

        $this->info("Suscripciones expiradas: {$count}");

        return self::SUCCESS;
    }
}
