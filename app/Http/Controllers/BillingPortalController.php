<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

final class BillingPortalController
{
    /**
     * Redirect to Stripe billing portal.
     */
    public function __invoke(): RedirectResponse
    {
        $user = Auth::user();

        if (! $user->hasStripeId()) {
            return redirect()->back()->with('error', 'No billing information found. You need to make a purchase first.');
        }

        try {
            $billingPortalUrl = $user->billingPortalUrl(route('billing.index'));

            return redirect()->away($billingPortalUrl);
        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Failed to open billing portal.');
        }
    }
}
