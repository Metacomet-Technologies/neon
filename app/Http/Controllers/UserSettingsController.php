<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class UserSettingsController
{
    /**
     * Update user settings.
     */
    public function update(Request $request): Response
    {
        $user = $request->user();

        $request->validate([
            'theme' => 'sometimes|in:light,dark,system',
            'preferences' => 'sometimes|array',
        ]);

        $settings = $user->getOrCreateSettings();

        if ($request->has('theme')) {
            $settings->theme = $request->theme;
        }

        if ($request->has('preferences')) {
            $settings->preferences = array_merge($settings->preferences ?? [], $request->preferences);
        }

        $settings->save();

        // Return empty response for Inertia background requests
        return response()->noContent();
    }

    /**
     * Get user settings.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $user->getOrCreateSettings();

        return response()->json([
            'settings' => [
                'theme' => $settings->theme,
                'preferences' => $settings->preferences,
            ],
        ]);
    }
}
