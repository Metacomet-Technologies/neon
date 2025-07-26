<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TransferLicenseController
{
    use AuthorizesRequests;

    /**
     * Transfer a license to a different guild.
     */
    public function __invoke(Request $request, License $license): RedirectResponse
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
}
