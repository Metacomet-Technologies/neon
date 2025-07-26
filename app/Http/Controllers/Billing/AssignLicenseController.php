<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class AssignLicenseController
{
    use AuthorizesRequests;

    /**
     * Assign a license to a guild.
     */
    public function __invoke(Request $request, License $license): RedirectResponse
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
}

