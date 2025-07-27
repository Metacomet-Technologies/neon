<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Mail\LicensePurchasedMail;
use App\Models\License;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Stripe\PaymentIntent;
use Stripe\Stripe;
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
                    $license = License::create([
                        'user_id' => $user->id,
                        'type' => License::TYPE_LIFETIME,
                        'stripe_id' => $session['payment_intent'] ?? $session['id'],
                        'status' => License::STATUS_PARKED,
                    ]);

                    // Send purchase confirmation email
                    $this->sendPurchaseEmail($license, $session);
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
                $license = License::create([
                    'user_id' => $user->id,
                    'type' => License::TYPE_SUBSCRIPTION,
                    'stripe_id' => $subscriptionId,
                    'status' => License::STATUS_PARKED,
                ]);

                // Send purchase confirmation email
                Mail::to($user->email)->send(
                    new LicensePurchasedMail(
                        $license,
                        $user->email,
                        $invoice['amount_paid'],
                        strtoupper($invoice['currency'])
                    )
                );
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

    /**
     * Handle payment intent payment failed events.
     */
    public function handlePaymentIntentPaymentFailed(array $payload): Response
    {
        $paymentIntent = $payload['data']['object'];

        Log::warning('Payment failed', [
            'payment_intent_id' => $paymentIntent['id'],
            'customer' => $paymentIntent['customer'],
            'amount' => $paymentIntent['amount'],
            'currency' => $paymentIntent['currency'],
            'last_payment_error' => $paymentIntent['last_payment_error'],
        ]);

        return $this->successMethod();
    }
    
    /**
     * Handle checkout session expired events.
     */
    public function handleCheckoutSessionExpired(array $payload): Response
    {
        $session = $payload['data']['object'];
        
        Log::info('Checkout session expired', [
            'session_id' => $session['id'],
            'customer' => $session['customer'],
            'metadata' => $session['metadata'],
        ]);
        
        // Could send an email reminder here if needed
        
        return $this->successMethod();
    }

    /**
     * Handle invoice payment failed events.
     */
    public function handleInvoicePaymentFailed(array $payload): Response
    {
        $invoice = $payload['data']['object'];

        Log::warning('Invoice payment failed', [
            'invoice_id' => $invoice['id'],
            'customer' => $invoice['customer'],
            'subscription' => $invoice['subscription'],
            'amount' => $invoice['amount_due'],
            'currency' => $invoice['currency'],
            'attempt_count' => $invoice['attempt_count'],
        ]);

        return parent::handleInvoicePaymentFailed($payload);
    }

    /**
     * Send purchase confirmation email for lifetime licenses.
     */
    private function sendPurchaseEmail(License $license, array $session): void
    {
        try {
            // Set Stripe API key
            Stripe::setApiKey(config('cashier.secret'));

            // Get payment intent to extract amount
            $paymentIntent = PaymentIntent::retrieve($session['payment_intent']);

            Mail::to($license->user->email)->send(
                new LicensePurchasedMail(
                    $license,
                    $license->user->email,
                    $paymentIntent->amount,
                    strtoupper($paymentIntent->currency)
                )
            );
        } catch (Exception $e) {
            // Log error but don't fail the webhook
            Log::error('Failed to send purchase email', [
                'license_id' => $license->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
