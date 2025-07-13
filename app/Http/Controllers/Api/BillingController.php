<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Checkout;
use Stripe\Exception\ApiErrorException;

class BillingController
{
    /**
     * Create a subscription checkout session.
     */
    public function checkoutSubscription(Request $request): JsonResponse
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
                    'success_url' => 'https://neon.test/dashboard?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => 'https://neon.test/dashboard',
                    'metadata' => [
                        'user_id' => $user->id,
                        'license_type' => 'subscription',
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

    /**
     * Create a one-time payment checkout session for lifetime license.
     */
    public function checkoutLifetime(Request $request): JsonResponse
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

    /**
     * Redirect to Stripe billing portal.
     */
    public function portal(Request $request): JsonResponse
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
                'portal_url' => $billingPortal,
            ]);

        } catch (ApiErrorException $e) {
            return response()->json([
                'error' => 'Failed to create billing portal session',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get user's billing information including subscriptions and licenses.
     */
    public function getBillingInfo(Request $request): JsonResponse
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

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(Request $request): JsonResponse
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

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to cancel subscription',
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Resume a cancelled subscription.
     */
    public function resumeSubscription(Request $request): JsonResponse
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

        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to resume subscription',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
