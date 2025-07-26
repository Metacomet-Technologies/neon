<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\License;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

final class ParkLicenseController
{
    /**
     * Park a license (remove from guild).
     */
    public function __invoke(Request $request, License $license): RedirectResponse
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
}
