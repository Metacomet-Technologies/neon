<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\License;

use App\Models\License;
use Illuminate\Http\JsonResponse;

/**
 * Controller for parking a license (removing it from a guild).
 * Only the license owner can park their license.
 */
final class ParkLicenseController
{
    /**
     * Park a license (remove from guild).
     */
    public function __invoke(License $license): JsonResponse
    {
        $license->park();

        return response()->json([
            'message' => 'License parked successfully',
            'license' => $license->fresh(),
        ]);
    }
}
