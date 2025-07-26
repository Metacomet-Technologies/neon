<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Models\License;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class ParkLicenseController
{
    use AuthorizesRequests;

    /**
     * Park a license (remove from guild).
     */
    public function __invoke(Request $request, License $license): RedirectResponse
    {
        $this->authorize('park', $license);

        try {
            $license->park();

            return redirect()->back()->with('success', 'License parked successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
