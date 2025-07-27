<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\License;

use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controller for assigning a license to a guild.
 * Only the license owner can assign their license.
 */
final class AssignLicenseController
{
    /**
     * Assign a license to a guild.
     */
    public function __invoke(Request $request, License $license): JsonResponse
    {
        $request->validate([
            'guild_id' => 'required|string|exists:guilds,id',
        ]);

        $guild = Guild::findOrFail($request->guild_id);

        try {
            $license->assignToGuild($guild);

            return response()->json([
                'message' => 'License assigned successfully',
                'license' => $license->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
