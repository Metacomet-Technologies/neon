<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Billing;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

final class CheckoutSubscriptionController
{
    /**
     * Create a subscription checkout session.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'price_id' => 'required|string',
        ]);

        try {
            $user = $request->user();

            // Ensure the user is a Stripe customer
            if (! $user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }

            // Create checkout session for subscription
            $checkout = $user->newSubscription('default', $request->price_id)
                ->checkout([
                    'success_url' => url('/billing?session_id={CHECKOUT_SESSION_ID}&success=1'),
                    'cancel_url' => url('/billing?session_id={CHECKOUT_SESSION_ID}&cancelled=1'),
                    'metadata' => [
                        'user_id' => $user->id,
                        'license_type' => 'subscription',
                    ],
                    'expires_at' => now()->addHours(1)->timestamp, // Expire session after 1 hour
                    'payment_method_types' => ['card'],
                    'allow_promotion_codes' => true,
                    'billing_address_collection' => 'auto',
                ]);

            return response()->json([
                'checkout_url' => $checkout->url,
                'session_id' => $checkout->id,
            ]);

        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => 'Failed to create checkout session',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
