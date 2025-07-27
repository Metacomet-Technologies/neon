<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Services\Discord\DiscordService;
use Exception;
use Illuminate\Http\Request;
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
        $user = $request->user();

        // Handle Stripe checkout success/failure
        if ($request->has('session_id')) {
            try {
                Stripe::setApiKey(config('cashier.secret'));
                $session = Session::retrieve($request->session_id);

                if ($request->has('success') && $session->payment_status === 'paid') {
                    session()->flash('success', 'Payment successful! Your license has been created and is ready to assign.');
                } elseif ($request->has('cancelled')) {
                    // User clicked the back/cancel button
                    session()->flash('warning', 'Payment was cancelled. Ready to try again when you are!');

                    // Provide a direct retry link if session is still valid
                    if ($session->status === 'open' && $session->url) {
                        session()->flash('info', 'Your checkout session is still active. You can continue where you left off.');

                        return redirect()->away($session->url);
                    }
                } elseif ($session->status === 'expired') {
                    session()->flash('error', 'Your checkout session has expired. Please start a new purchase.');
                } elseif ($session->payment_status === 'unpaid') {
                    // Payment failed or was incomplete
                    session()->flash('error', 'Payment could not be processed. Please try again with a different payment method.');
                } elseif ($session->status === 'complete' && $session->payment_status === 'paid') {
                    // Session was already completed successfully
                    session()->flash('info', 'This payment has already been processed. Check your licenses below.');
                }
            } catch (Exception $e) {
                Log::error('Failed to retrieve checkout session', [
                    'session_id' => $request->session_id,
                    'error' => $e->getMessage(),
                ]);
                session()->flash('error', 'Unable to verify payment status. Please contact support if you were charged.');
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
            } catch (Exception) {
                $paymentMethods = [];
            }
        }

        // Get user's Discord guilds and sync with database
        $userGuilds = [];
        try {
            $discord = DiscordService::forUser($user);
            $discordGuilds = $discord->userGuildsWithPermission();

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
                        $botDiscord = app(DiscordService::class);
                        $isBotMember = $botDiscord->isBotInGuild($guild->id);
                        $guild->update([
                            'is_bot_member' => $isBotMember,
                            'last_bot_check_at' => now(),
                            'bot_joined_at' => $isBotMember && ! $guild->is_bot_member ? now() : $guild->bot_joined_at,
                            'bot_left_at' => ! $isBotMember && $guild->is_bot_member ? now() : $guild->bot_left_at,
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
        ]);
    }
}
