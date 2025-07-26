<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\Discord\GetGuilds;
use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

final class BillingController
{
    /**
     * Display the billing dashboard.
     */
    public function index(): Response
    {
        $user = Auth::user();

        // Get billing information
        $licenses = $user->licenses()->with(['guild'])->get();
        $subscriptions = $user->subscriptions()->get();
        $paymentMethods = [];

        // Get payment methods if user has Stripe customer ID
        if ($user->hasStripeId()) {
            try {
                $paymentMethods = $user->paymentMethods()->map(function ($paymentMethod) {
                    return [
                        'id' => $paymentMethod->id,
                        'type' => $paymentMethod->type,
                        'brand' => $paymentMethod->card->brand ?? '',
                        'last_four' => $paymentMethod->card->last4 ?? '',
                        'exp_month' => $paymentMethod->card->exp_month ?? '',
                        'exp_year' => $paymentMethod->card->exp_year ?? '',
                    ];
                })->toArray();
            } catch (Exception $e) {
                $paymentMethods = [];
            }
        }

        // Get user's Discord guilds and sync with database
        $userGuilds = [];
        try {
            $getGuilds = new GetGuilds($user);
            $discordGuilds = $getGuilds->getGuildsWhereUserHasPermission();

            // Sync guilds with database
            foreach ($discordGuilds as $discordGuild) {
                Guild::updateOrCreate(
                    ['id' => $discordGuild['id']],
                    [
                        'name' => $discordGuild['name'],
                        'icon' => $discordGuild['icon'],
                    ]
                );

                $userGuilds[] = [
                    'id' => $discordGuild['id'],
                    'name' => $discordGuild['name'],
                    'icon' => $discordGuild['icon'] ? "https://cdn.discordapp.com/icons/{$discordGuild['id']}/{$discordGuild['icon']}.png" : null,
                ];
            }
        } catch (Exception $e) {
            // Handle error silently, guilds will be empty
        }

        return Inertia::render('Billing/Index', [
            'billing' => [
                'licenses' => $licenses->map(function ($license) {
                    return [
                        'id' => $license->id,
                        'type' => $license->type,
                        'status' => $license->status,
                        'assigned_guild_id' => $license->assigned_guild_id,
                        'assigned_guild_name' => $license->guild?->name,
                        'last_assigned_at' => $license->last_assigned_at?->toISOString(),
                        'created_at' => $license->created_at->toISOString(),
                    ];
                }),
                'subscriptions' => $subscriptions->map(function ($subscription) {
                    return [
                        'id' => $subscription->id,
                        'stripe_status' => $subscription->stripe_status,
                        'trial_ends_at' => $subscription->trial_ends_at?->toDateString(),
                        'ends_at' => $subscription->ends_at?->toDateString(),
                        'created_at' => $subscription->created_at->toDateString(),
                    ];
                }),
                'payment_methods' => $paymentMethods,
            ],
            'guilds' => $userGuilds,
        ]);
    }

    /**
     * Assign a license to a guild.
     */
    public function assignLicense(Request $request, License $license): RedirectResponse
    {
        $this->authorize('assign', $license);

        $request->validate([
            'guild_id' => 'required|string|exists:guilds,id',
        ]);

        $guild = Guild::findOrFail($request->guild_id);

        try {
            $license->assignToGuild($guild);

            return redirect()->back()->with('success', 'License assigned successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Park a license (remove from guild).
     */
    public function parkLicense(Request $request, License $license): RedirectResponse
    {
        $this->authorize('park', $license);

        try {
            $license->park();

            return redirect()->back()->with('success', 'License parked successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Transfer a license to a different guild.
     */
    public function transferLicense(Request $request, License $license): RedirectResponse
    {
        $this->authorize('transfer', $license);

        $request->validate([
            'guild_id' => 'required|string|exists:guilds,id',
        ]);

        $guild = Guild::findOrFail($request->guild_id);

        try {
            $license->transferToGuild($guild);

            return redirect()->back()->with('success', 'License transferred successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Redirect to Stripe billing portal.
     */
    public function billingPortal(): RedirectResponse
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
