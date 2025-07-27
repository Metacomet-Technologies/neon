<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Billing;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetBillingInfoController
{
    /**
     * Get user's billing information including subscriptions and licenses.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        $billingInfo = [
            'has_stripe_id' => $user->hasStripeId(),
            'subscriptions' => [],
            'licenses' => [],
            'payment_methods' => [],
        ];

        if ($user->hasStripeId()) {
            // Get active subscriptions
            $billingInfo['subscriptions'] = $user->subscriptions()
                ->active()
                ->get()
                ->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'name' => $subscription->name,
                        'stripe_id' => $subscription->stripe_id,
                        'stripe_status' => $subscription->stripe_status,
                        'trial_ends_at' => $subscription->trial_ends_at,
                        'ends_at' => $subscription->ends_at,
                        'created_at' => $subscription->created_at,
                    ];
                });

            // Get payment methods
            if ($user->hasDefaultPaymentMethod()) {
                $billingInfo['payment_methods'] = $user->paymentMethods()->map(function ($paymentMethod) {
                    return [
                        'id' => $paymentMethod->id,
                        'type' => $paymentMethod->type,
                        'brand' => $paymentMethod->card->brand ?? null,
                        'last_four' => $paymentMethod->card->last4 ?? null,
                        'exp_month' => $paymentMethod->card->exp_month ?? null,
                        'exp_year' => $paymentMethod->card->exp_year ?? null,
                    ];
                });
            }
        }

        // Get user's licenses
        $billingInfo['licenses'] = $user->licenses()->get()->map(function ($license) {
            return [
                'id' => $license->id,
                'type' => $license->type,
                'status' => $license->status,
                'assigned_guild_id' => $license->assigned_guild_id,
                'last_assigned_at' => $license->last_assigned_at,
                'created_at' => $license->created_at,
            ];
        });

        return response()->json($billingInfo);
    }
}
