<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Billing;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ResumeSubscriptionController
{
    /**
     * Resume a cancelled subscription.
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

            $subscription->resume();

            return response()->json([
                'message' => 'Subscription resumed successfully',
                'subscription' => [
                    'id' => $subscription->id,
                    'stripe_status' => $subscription->stripe_status,
                    'ends_at' => $subscription->ends_at,
                ],
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to resume subscription',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
