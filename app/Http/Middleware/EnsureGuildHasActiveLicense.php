<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Guild;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureGuildHasActiveLicense
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if user is authenticated
        if (! $user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get guild ID from route parameter, request input, or query parameter
        $guildId = $request->route('guild_id') ?? $request->input('guild_id') ?? $request->query('guild_id');

        if (! $guildId) {
            return response()->json(['error' => 'Guild ID is required'], 400);
        }

        // Find the guild
        $guild = Guild::find($guildId);
        if (! $guild) {
            return response()->json(['error' => 'Guild not found'], 404);
        }

        // Check if the guild has an active license
        if (! $guild->hasActiveLicense()) {
            return response()->json([
                'error' => 'This guild does not have an active license',
                'message' => 'A paid license is required to access this content',
            ], 403);
        }

        return $next($request);
    }
}
