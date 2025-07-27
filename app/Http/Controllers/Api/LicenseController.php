<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\License;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Resource controller for license management.
 * Handles standard CRUD operations for licenses.
 */
final class LicenseController
{
    /**
     * Get user's licenses.
     * Users can only view their own licenses.
     */
    public function index(Request $request): JsonResponse
    {
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
        return response()->json([
            'license' => $license->load('guild'),
        ]);
    }
}
