<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Billing;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\ApiErrorException;

final class BillingPortalController
{
    /**
     * Redirect to Stripe billing portal.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            // Check if user has a Stripe customer ID
            if (! $user->hasStripeId()) {
                return response()->json([
                    'error' => 'No billing information found',
                    'message' => 'You need to make a purchase first to access the billing portal.',
                ], 404);
            }

            // Create billing portal session
            $billingPortal = $user->billingPortalUrl('https://neon.test/dashboard');

            return response()->json([
                'url' => $billingPortal,
            ]);

        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => 'Failed to create billing portal session',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
