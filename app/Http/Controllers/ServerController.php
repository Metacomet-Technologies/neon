<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Helpers\Discord\GetBotGuilds;
use App\Helpers\Discord\GetGuildChannels;
use App\Models\WelcomeSetting;
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

        return Inertia::render('Servers/Index', [
            'botGuilds' => GetBotGuilds::make(),
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

        $channels = (new GetGuildChannels($serverId))->getTextChannels();
        $channels = array_map(function ($channel) {
            return [
                'id' => $channel['id'],
                'name' => $channel['name'],
                'position' => $channel['position'],
            ];
        }, $channels);
        $channels = array_values($channels);
        // order by position
        usort($channels, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });
        // remove position
        $channels = array_map(function ($channel) {
            unset($channel['position']);

            return $channel;
        }, $channels);

        $existingSetting = WelcomeSetting::whereGuildId($serverId)
            ->first();

        return Inertia::render('Servers/Show', [
            'channels' => $channels,
            'existingSetting' => $existingSetting,
        ]);
    }
}
