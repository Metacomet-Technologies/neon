<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Guild;
use App\Models\WelcomeSetting;
use App\Services\Discord\DiscordService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ServerController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        $guilds = $user->guilds;
        
        // Get list of guild IDs where bot is a member
        $botGuilds = Guild::where('is_bot_member', true)
            ->pluck('id')
            ->toArray();

        return Inertia::render('Servers/Index', [
            'guilds' => $guilds,
            'botGuilds' => $botGuilds,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $serverId): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        /** @var list<array<string, mixed>> $guilds */
        $guilds = $user->guilds;

        if (! in_array($serverId, array_column($guilds, 'id'))) {
            abort(403);
        }

        $user->current_server_id = $serverId;
        $user->save();

        $discord = app(DiscordService::class);
        $channels = $discord->guild($serverId)->channels();

        // Filter text channels and sort by position
        $channels = $channels
            ->filter(function ($channel) {
                // Type 0 is text channel in Discord API
                return ($channel['type'] ?? 0) === 0;
            })
            ->map(function ($channel) {
                return [
                    'id' => $channel['id'],
                    'name' => $channel['name'],
                    'position' => $channel['position'] ?? 0,
                ];
            })
            ->sortBy('position')
            ->map(function ($channel) {
                unset($channel['position']);

                return $channel;
            })
            ->values()
            ->toArray();

        $existingSetting = WelcomeSetting::whereGuildId($serverId)
            ->first();

        return Inertia::render('Servers/Show', [
            'channels' => $channels,
            'existingSetting' => $existingSetting,
        ]);
    }
}
