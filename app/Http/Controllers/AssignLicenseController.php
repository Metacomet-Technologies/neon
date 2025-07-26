<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class AssignLicenseController
{
    /**
     * Assign a license to a guild.
     */
    public function __invoke(Request $request, License $license): RedirectResponse
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
        if (! $guild->is_bot_member) {
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
}
