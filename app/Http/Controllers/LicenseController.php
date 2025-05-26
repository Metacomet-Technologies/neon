<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LicenseController
{
    /**
     * Store a license after user completes subscription checkout.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $subscription = $user->subscription('default');

        if (! $subscription) {
            abort(400, 'No subscription found for user.');
        }

        DB::transaction(function () use ($user, $subscription) {
            License::create([
                'user_id' => $user->id,
                'stripe_id' => $subscription->stripe_id,
                'stripe_status' => $subscription->stripe_status,
                'plan_id' => $subscription->stripe_price,
            ]);
        });

        return redirect()->route('licenses.index')->with('success', 'License created and linked to your subscription.');
    }

    /**
     * Show all licenses owned by the user.
     */
    public function index(Request $request)
    {
        return inertia('Licenses/Index', [
            'licenses' => $request->user()->licenses()->get(),
        ]);
    }
}
