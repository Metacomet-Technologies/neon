<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\License;
use Laravel\Cashier\Events\SubscriptionCreated;
use Laravel\Cashier\Events\SubscriptionUpdated;

final class SyncLicenses
{
    public function handle(SubscriptionCreated|SubscriptionUpdated $event): void
    {
        $subscription = $event->subscription;
        $user = $subscription->owner;

        $quantity = $subscription->quantity ?? 1;

        $existing = License::where('stripe_id', $subscription->stripe_id)->get();
        $count = $existing->count();

        if ($count < $quantity) {
            $toAdd = $quantity - $count;
            for ($i = 0; $i < $toAdd; $i++) {
                License::create([
                    'user_id' => $user->id,
                    'stripe_id' => $subscription->stripe_id,
                    'stripe_status' => $subscription->stripe_status,
                    'plan_id' => $subscription->stripe_price,
                ]);
            }
        }

        if ($count > $quantity) {
            $toRemove = $count - $quantity;
            $existing->sortByDesc('created_at')->take($toRemove)->each->delete();
        }

        License::where('stripe_id', $subscription->stripe_id)->update([
            'stripe_status' => $subscription->stripe_status,
        ]);
    }
}
