<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\License;
use App\Models\User;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Stripe\Checkout\Session;
use Stripe\PaymentIntent;
use Symfony\Component\HttpFoundation\Response;

final class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle checkout session completed events.
     */
    public function handleCheckoutSessionCompleted(array $payload): Response
    {
        $session = $payload['data']['object'];

        if (isset($session['metadata']['user_id']) && isset($session['metadata']['license_type'])) {
            $user = User::find($session['metadata']['user_id']);

            if ($user) {
                $licenseType = $session['metadata']['license_type'];

                if ($licenseType === 'lifetime') {
                    // Create lifetime license for one-time payment
                    License::create([
                        'user_id' => $user->id,
                        'type' => License::TYPE_LIFETIME,
                        'stripe_id' => $session['payment_intent'] ?? $session['id'],
                        'status' => License::STATUS_PARKED,
                    ]);
                }
                // For subscriptions, the license will be created in handleInvoicePaymentSucceeded
            }
        }

        return $this->successMethod();
    }

    /**
     * Handle invoice payment succeeded events (for subscriptions).
     */
    public function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $invoice = $payload['data']['object'];

        // Check if this is the first payment for a subscription (not a renewal)
        if ($invoice['billing_reason'] === 'subscription_create') {
            $subscriptionId = $invoice['subscription'];

            // Find the user by Stripe customer ID
            $user = User::where('stripe_id', $invoice['customer'])->first();

            if ($user) {
                // Create subscription license
                License::create([
                    'user_id' => $user->id,
                    'type' => License::TYPE_SUBSCRIPTION,
                    'stripe_id' => $subscriptionId,
                    'status' => License::STATUS_PARKED,
                ]);
            }
        }

        return parent::handleInvoicePaymentSucceeded($payload);
    }

    /**
     * Handle customer subscription deleted events.
     */
    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        $subscription = $payload['data']['object'];

        // Find and update the corresponding license
        $license = License::where('stripe_id', $subscription['id'])->first();

        if ($license) {
            // Unassign the license when subscription is deleted
            $license->unassign();
        }

        return parent::handleCustomerSubscriptionDeleted($payload);
    }

    /**
     * Handle payment intent succeeded events.
     */
    public function handlePaymentIntentSucceeded(array $payload): Response
    {
        // This is handled by checkout.session.completed for our use case
        return $this->successMethod();
    }
}
