<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\License;
use Laravel\Cashier\Events\SubscriptionDeleted;

final class ExpireLicenses
{
    public function handle(SubscriptionDeleted $event): void
    {
        $subscription = $event->subscription;

        License::where('stripe_id', $subscription->stripe_id)->update([
            'stripe_status' => $subscription->stripe_status,
            'ends_at' => now(),
        ]);
    }
}
