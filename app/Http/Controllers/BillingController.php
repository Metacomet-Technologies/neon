<?php

declare (strict_types = 1);

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Services\Discord\Discord;
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
            } catch (Exception) {
                $paymentMethods = [];
            }
        }

        // Get user's Discord guilds and sync with database
        $userGuilds = [];
        try {
            $discord = Discord::forUser($user);
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
                        try {
                            (new Discord)->guild($guild->id)->get();
                            $isBotMember = true;
                        } catch (Exception) {
                            $isBotMember = false;
                        }
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
}
