<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Billing;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CancelSubscriptionController
{
    /**
     * Cancel a subscription.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'subscription_id' => 'required|string',
        ]);

        try {
            $user = $request->user();
            $subscription = $user->subscriptions()
                ->where('id', $request->subscription_id)
                ->firstOrFail();

            $subscription->cancel();

            return response()->json([
                'message' => 'Subscription cancelled successfully',
                'subscription' => [
                    'id' => $subscription->id,
                    'stripe_status' => $subscription->stripe_status,
                    'ends_at' => $subscription->ends_at,
                ],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel subscription',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
