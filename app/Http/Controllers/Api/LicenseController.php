<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\License;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resource controller for license management.
 * Handles standard CRUD operations for licenses.
 */
final class LicenseController
{
    use AuthorizesRequests;

    /**
     * Get user's licenses.
     * Users can only view their own licenses.
     */
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', License::class);

        $licenses = $request->user()->licenses()->with('guild')->get();

        return response()->json([
            'licenses' => $licenses,
        ]);
    }

    /**
     * Get a specific license.
     * Users can only view their own licenses.
     */
    public function show(License $license): JsonResponse
    {
        $this->authorize('view', $license);

        return response()->json([
            'license' => $license->load('guild'),
        ]);
    }
}
