<?php

namespace App\Listeners;

use Laravel\Cashier\Events\WebhookReceived;
use App\Models\User;
use App\Models\License;
use Illuminate\Support\Facades\Log;

class CreateLicenseFromSubscription
{
    /**
     * Handle the event.
     */
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;

        if (! isset($payload['type']) || $payload['type'] !== 'customer.subscription.created') {
            return;
        }

        $subscription = $payload['data']['object'];

        $stripeCustomerId = $subscription['customer'] ?? null;
        $subscriptionId = $subscription['id'] ?? null;
        $status = $subscription['status'] ?? 'incomplete';
        $priceId = $subscription['items']['data'][0]['price']['id'] ?? null;

        if (! $stripeCustomerId || ! $subscriptionId) {
            Log::warning('Missing Stripe customer or subscription ID in webhook payload.');
            return;
        }

        $user = User::where('stripe_id', $stripeCustomerId)->first();
        if (! $user) {
            Log::error("User not found for Stripe customer: {$stripeCustomerId}");
            return;
        }

        if (License::where('stripe_id', $subscriptionId)->exists()) {
            return; // License already created
        }

        License::create([
            'user_id' => $user->id,
            'stripe_id' => $subscriptionId,
            'stripe_status' => $status,
            'plan_id' => $priceId,
        ]);

        Log::info("License created for user {$user->id} from subscription {$subscriptionId}");
    }
}
