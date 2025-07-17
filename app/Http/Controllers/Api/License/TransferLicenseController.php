<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\License;

use App\Models\Guild;
use App\Models\License;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Controller for transferring a license to a different guild.
 * Only the license owner can transfer their license.
 */
final class TransferLicenseController
{
    use AuthorizesRequests;

    /**
     * Transfer a license to a different guild.
     */
    public function __invoke(Request $request, License $license): JsonResponse
    {
        $this->authorize('transfer', $license);

        $request->validate([
            'guild_id' => 'required|string|exists:guilds,id',
        ]);

        $guild = Guild::findOrFail($request->guild_id);

        try {
            $license->transferToGuild($guild);

            return response()->json([
                'message' => 'License transferred successfully',
                'license' => $license->fresh(),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], Response::HTTP_BAD_REQUEST);
        }
    }
}
