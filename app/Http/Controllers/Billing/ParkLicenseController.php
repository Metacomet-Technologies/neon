<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Models\License;
use Exception;
use Illuminate\Http\RedirectResponse;

final class ParkLicenseController
{
    /**
     * Park a license (remove from guild).
     */
    public function __invoke(License $license): RedirectResponse
    {
        try {
            $license->park();

            return redirect()->back()->with('success', 'License parked successfully!');
        } catch (Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
