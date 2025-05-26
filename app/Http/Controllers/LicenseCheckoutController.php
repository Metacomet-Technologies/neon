<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeCheckout;

final class LicenseCheckoutController
{
    public function __invoke(Request $request): JsonResponse
    {
        $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $user = $request->user();

        try {
            if (! $user->hasStripeId()) {
                $user->createAsStripeCustomer();
            }

            Stripe::setApiKey(config('cashier.secret'));

            $session = StripeCheckout::create([
                'customer' => $user->stripe_id,
                'payment_method_types' => ['card'],
                'mode' => 'subscription',
                'line_items' => [[
                    'price' => 'price_1RT4H1QjQeaATcC6tWr4Jgjd',
                    'quantity' => $request->integer('quantity'),
                ]],
                'success_url' => route('licenses.index'),
                'cancel_url' => route('licenses.purchase'),
            ]);

            return response()->json([
                'checkout_url' => $session->url,
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Checkout initialization failed. Please try again.',
                'error' => app()->isLocal() ? $e->getMessage() : null,
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
