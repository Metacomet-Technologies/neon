<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\License;

use App\Models\License;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller for parking a license (removing it from a guild).
 * Only the license owner can park their license.
 */
final class ParkLicenseController
{
    use AuthorizesRequests;

    /**
     * Park a license (remove from guild).
     */
    public function __invoke(Request $request, License $license): JsonResponse
    {
        $this->authorize('park', $license);

        $license->park();

        return response()->json([
            'message' => 'License parked successfully',
            'license' => $license->fresh(),
        ]);
    }
}
