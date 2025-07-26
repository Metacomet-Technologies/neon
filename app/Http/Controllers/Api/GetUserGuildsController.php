<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Helpers\Discord\GetGuilds;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class GetUserGuildsController
{
    /**
     * Get the authenticated user's Discord guilds where they have admin permissions.
     */
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $getGuilds = new GetGuilds($user);

            // Get guilds where user has admin permissions (can manage the bot)
            $guilds = $getGuilds->getGuildsWhereUserHasPermission();

            // Format for frontend
            $formattedGuilds = array_map(function ($guild) {
                return [
                    'id' => $guild['id'],
                    'name' => $guild['name'],
                    'icon' => $guild['icon'] ? "https://cdn.discordapp.com/icons/{$guild['id']}/{$guild['icon']}.png" : null,
                ];
            }, $guilds);

            return response()->json([
                'guilds' => $formattedGuilds,
            ]);

        } catch (Exception $e) {
            return response()->json([
                'error' => 'Failed to fetch guilds',
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
