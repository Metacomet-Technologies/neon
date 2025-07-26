<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\WelcomeSetting;
use App\Services\Discord\Discord;
use Illuminate\Http\Request;
use Inertia\Inertia;

final class ServerController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'You are not authorized to view this page.');
        }

        $discord = new Discord;

        return Inertia::render('Servers/Index', [
            'botGuilds' => $discord->botGuilds(),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $serverId): \Inertia\Response
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

        $discord = new Discord;
        $channels = $discord->guild($serverId)->channels()->text()->get();

        // Transform to array and sort by position
        $channels = $channels->map(function ($channel) {
            return [
                'id' => $channel['id'],
                'name' => $channel['name'],
                'position' => $channel['position'] ?? 0,
            ];
        })->sortBy('position')->map(function ($channel) {
            unset($channel['position']);

            return $channel;
        })->values()->toArray();

        $existingSetting = WelcomeSetting::whereGuildId($serverId)
            ->first();

        return Inertia::render('Servers/Show', [
            'channels' => $channels,
            'existingSetting' => $existingSetting,
        ]);
    }
}
