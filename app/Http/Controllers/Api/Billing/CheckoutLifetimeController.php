<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Billing;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

class CheckoutLifetimeController
{
    /**
     * Create a one-time payment checkout session for lifetime license.
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

            // Create checkout session for one-time payment
            $checkout = $user->checkout([
                $request->price_id => 1,
            ], [
                'success_url' => 'https://neon.test/dashboard?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => 'https://neon.test/dashboard',
                'metadata' => [
                    'user_id' => $user->id,
                    'license_type' => 'lifetime',
                    'price_id' => $request->price_id,
                ],
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
