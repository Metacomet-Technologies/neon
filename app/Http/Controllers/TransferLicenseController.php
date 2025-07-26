<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class TransferLicenseController
{
    /**
     * Transfer a license to a different guild.
     */
    public function __invoke(Request $request, License $license): RedirectResponse
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
        if (! $guild->is_bot_member) {
            return redirect()->back()->with('error', 'The bot must be added to the server before you can transfer a license. Please invite the bot first.');
        }

        try {
            $license->transferToGuild($guild);

            return redirect()->back()->with('success', 'License transferred successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
