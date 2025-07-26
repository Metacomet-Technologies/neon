<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\Discord\CheckBotMembership;
use App\Helpers\Discord\GetGuilds;
use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Stripe\Checkout\Session;
use Stripe\Stripe;

final class BillingController
{
    /**
     * Display the billing dashboard.
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();


        // Handle Stripe checkout success/failure
        $checkoutMessage = null;
        $checkoutType = null;

        if ($request->has('session_id') && $request->has('success')) {
            $checkoutMessage = 'Payment successful! Your license has been created and is ready to assign.';
            $checkoutType = 'success';
        } elseif ($request->has('session_id') && $request->has('cancelled')) {
            $checkoutMessage = 'Payment was cancelled. You can try again when you\'re ready.';
            $checkoutType = 'error';
        } elseif ($request->has('session_id')) {
            // Check the session status to provide better error details
            try {
                Stripe::setApiKey(config('cashier.secret'));
                $session = Session::retrieve($request->session_id);

                if ($session->payment_status === 'unpaid') {
                    $checkoutMessage = 'Payment was not completed. Please try again or contact support if you continue to have issues.';
                    $checkoutType = 'error';
                } elseif ($session->status === 'expired') {
                    $checkoutMessage = 'Your checkout session has expired. Please start a new purchase.';
                    $checkoutType = 'error';
                }
            } catch (Exception $e) {
                Log::error('Failed to retrieve checkout session', [
                    'session_id' => $request->session_id,
                    'error' => $e->getMessage(),
                ]);
                $checkoutMessage = 'There was an issue processing your payment. Please contact support.';
                $checkoutType = 'error';
            }
        }

        // Handle Stripe checkout success/failure
        $checkoutMessage = null;
        $checkoutType = null;


        if ($request->has('session_id') && $request->has('success')) {
            $checkoutMessage = 'Payment successful! Your license has been created and is ready to assign.';
            $checkoutType = 'success';
        } elseif ($request->has('session_id') && $request->has('cancelled')) {
            $checkoutMessage = 'Payment was cancelled. You can try again when you\'re ready.';
            $checkoutType = 'error';
        } elseif ($request->has('session_id')) {
            // Check the session status to provide better error details
            try {
                Stripe::setApiKey(config('cashier.secret'));
                $session = Session::retrieve($request->session_id);

                if ($session->payment_status === 'unpaid') {
                    $checkoutMessage = 'Payment was not completed. Please try again or contact support if you continue to have issues.';
                    $checkoutType = 'error';
                } elseif ($session->status === 'expired') {
                    $checkoutMessage = 'Your checkout session has expired. Please start a new purchase.';
                    $checkoutType = 'error';
                }
            } catch (Exception $e) {
                Log::error('Failed to retrieve checkout session', [
                    'session_id' => $request->session_id,
                    'error' => $e->getMessage(),
                ]);
                $checkoutMessage = 'There was an issue processing your payment. Please contact support.';
                $checkoutType = 'error';
            }
        }

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

            $botChecker = new CheckBotMembership();

            // Sync guilds with database and check bot membership
            foreach ($discordGuilds as $discordGuild) {
                $guild = Guild::updateOrCreate(
                    ['id' => $discordGuild['id']],
                    [
                        'name' => $discordGuild['name'],
                        'icon' => $discordGuild['icon'],
                    ]
                );

                // Check bot membership if we haven't checked recently
                if ($guild->needsBotMembershipCheck()) {
                    try {
                        $isBotMember = $botChecker->isBotInGuild($guild->id);
                        $guild->update([
                            'is_bot_member' => $isBotMember,
                            'last_bot_check_at' => now(),
                            'bot_joined_at' => $isBotMember && !$guild->is_bot_member ? now() : $guild->bot_joined_at,
                            'bot_left_at' => !$isBotMember && $guild->is_bot_member ? now() : $guild->bot_left_at,
                        ]);
                    } catch (Exception $e) {
                        Log::warning('Failed to check bot membership for guild', [
                            'guild_id' => $guild->id,
                            'error' => $e->getMessage(),
                        ]);
                        // Don't update is_bot_member if check failed
                    }
                }

                // Include all admin guilds (bot membership will be checked during assignment)
                $userGuilds[] = [
                    'id' => $discordGuild['id'],
                    'name' => $discordGuild['name'],
                    'icon' => $discordGuild['icon'] ? "https://cdn.discordapp.com/icons/{$discordGuild['id']}/{$discordGuild['icon']}.png" : null,
                    'is_bot_member' => $guild->is_bot_member, // Include this info for the frontend
                ];
            }
        } catch (Exception $e) {
            Log::error('Failed to fetch user guilds', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
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
            'checkout' => [
                'message' => $checkoutMessage,
                'type' => $checkoutType,
            ],
        ]);
    }

    /**
     * Assign a license to a guild.
     */
    public function assignLicense(Request $request, License $license): RedirectResponse
    {
        // Ensure the license belongs to the current user
        if ($license->user_id !== auth()->id()) {
            return redirect()->back()->with('error', 'License not found.');
        }

        Gate::authorize('assign', $license);

        $request->validate([
            'guild_id' => 'required|string|exists:guilds,id',
        ]);

        $guild = Guild::findOrFail($request->guild_id);

        // Check if license is already active
        if ($license->status === License::STATUS_ACTIVE) {
            return redirect()->back()->with('error', 'This license is already assigned to a server.');
        }

        // Check if bot is in the guild
        if (!$guild->is_bot_member) {
            return redirect()->back()->with('error', 'The bot must be added to the server before you can assign a license. Please invite the bot first.');
        }

        try {
            $license->assignToGuild($guild);

            return redirect()->back()->with('success', 'License assigned successfully!');
        } catch (Exception $e) {
            Log::error('License assignment failed', [
                'license_id' => $license->id,
                'guild_id' => $guild->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Park a license (remove from guild).
     */
    public function parkLicense(Request $request, License $license): RedirectResponse
    {
        // Ensure the license belongs to the current user
        if ($license->user_id !== auth()->id()) {
            return redirect()->back()->with('error', 'License not found.');
        }

        Gate::authorize('park', $license);

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
        // Ensure the license belongs to the current user
        if ($license->user_id !== auth()->id()) {
            return redirect()->back()->with('error', 'License not found.');
        }

        Gate::authorize('transfer', $license);

        $request->validate([
            'guild_id' => 'required|string|exists:guilds,id',
        ]);

        $guild = Guild::findOrFail($request->guild_id);

        // Check if bot is in the guild
        if (!$guild->is_bot_member) {
            return redirect()->back()->with('error', 'The bot must be added to the server before you can transfer a license. Please invite the bot first.');
        }

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
