<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\License;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Carbon;

final class LicenseController
{
    /**
     * Display a listing of the user's licenses.
     */
    public function index(Request $request)
    {
        return inertia('Licenses/Index', [
            'licenses' => $request->user()->licenses()->get(),
        ]);
    }

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
     * Update the license's guild assignment.
     */
    public function update(Request $request, License $license): RedirectResponse
    {
        $request->validate([
            'guild_id' => ['required', 'string', 'regex:/^\d{17,20}$/'],
        ]);

        $user = $request->user();
        $newGuildId = $request->input('guild_id');

        if ($license->user_id !== $user->id) {
            abort(403, 'You do not own this license.');
        }

        $now = now();

        if ($license->guild_id) {
            $lastMoved = $license->last_moved_at ?? $license->assigned_at;
            $cooldownEnds = Carbon::parse($lastMoved)->addDays(30);

            if ($now->lessThan($cooldownEnds) && $license->guild_id !== $newGuildId) {
                return redirect()->back()->withErrors([
                    'license' => 'You can only reassign this license to a different guild after 30 days.'
                ]);
            }
        }

        $license->update([
            'previous_guild_id' => $license->guild_id,
            'guild_id' => $newGuildId,
            'assigned_at' => $now,
            'last_moved_at' => $now,
        ]);

        Log::info("[License] License ID {$license->id} assigned to guild {$newGuildId} by user {$user->id}");

        return redirect()->back()->with('success', 'License assigned to your guild.');
    }

    /**
     * Unassign the license (park it).
     */
    public function destroy(Request $request, License $license): RedirectResponse
    {
        if ($license->user_id !== $request->user()->id) {
            abort(403, 'You do not own this license.');
        }

        $license->update([
            'previous_guild_id' => $license->guild_id,
            'guild_id' => null,
            'assigned_at' => null,
        ]);

        Log::info("[License] License ID {$license->id} unassigned by user {$license->user_id}");

        return redirect()->back()->with('success', 'License has been parked.');
    }
}
