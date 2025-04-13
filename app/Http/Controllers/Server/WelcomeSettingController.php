<?php

declare(strict_types=1);

namespace App\Http\Controllers\Server;

use App\Helpers\Discord\GetGuildChannels;
use App\Models\WelcomeSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WelcomeSettingController
{
    public function index(Request $request, string $serverId)
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

    public function store(Request $request, string $serverId)
    {
        $request->validate([
            'channel.id' => 'required|string',
            'message' => 'required|string|max:2000',
            'is_active' => 'required|boolean',
        ]);

        $welcomeSetting = WelcomeSetting::updateOrCreate(
            ['guild_id' => $serverId],
            [
                'channel_id' => $request->input('channel.id'),
                'message' => $request->input('message'),
                'is_active' => $request->input('is_active'),
            ]
        );

        return redirect()->route('server.settings.welcome', ['serverId' => $serverId])
            ->with(['type' => 'success', 'message' => 'Welcome settings updated successfully']);
    }
}
