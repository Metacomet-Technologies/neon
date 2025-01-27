<?php

declare (strict_types = 1);

namespace App\Http\Controllers;

use App\Enums\DiscordPermissionEnum;
use App\Http\DiscordRefreshToken;
use App\Models\NeonCommand;
use DateInterval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

final class NeonCommandController
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $token = $user->access_token;
        $discordId = $user->discord_id;
        $cacheKey = 'guilds_' . $discordId;
        $dateInterval = DateInterval::createFromDateString('90 seconds');
        $baseUrl = config('services.discord.rest_api_url');

        $guilds = Cache::remember($cacheKey, $dateInterval, function () use ($token, $user, $baseUrl) {
            $url = $baseUrl . '/users/@me/guilds';
            $response = Http::withToken($token)->get($url);

            if ($response->status() === 401 || $response->status() === 403) {
                $newToken = (new DiscordRefreshToken($user))->refreshToken();
                if (!$newToken) {
                    return redirect()->route('login');
                }
                $response = Http::withToken($token)->get($url);
            }

            return $response->successful() ? $response->json() : [];
        });

        $guilds = array_filter($guilds, function ($guild) {
            return $guild['permissions'] & DiscordPermissionEnum::ADMINISTRATOR->value;
        });

        $guilds = array_values($guilds);

        if (!$guilds) {
            return Inertia::render('Commands/Index', [
                'commands' => NeonCommand::query()
                    ->with(['createdByUser', 'updatedByUser'])
                    ->whereRaw('1=2')
                    ->latest()
                    ->paginate(10),
                'guilds' => [],
            ])->with('flash', [
                'type' => 'error',
                'message' => 'You are not administrator on any servers.',
            ]);
        }

        $currentGuildId = $request->query('guild_id', $guilds[0]['id']);
        return Inertia::render('Commands/Index', [
            'commands' => Inertia::defer(function () use ($currentGuildId) {
                return NeonCommand::query()
                    ->with(['createdByUser', 'updatedByUser'])
                    ->where('guild_id', $currentGuildId)
                    ->latest()
                    ->paginate(10);

            }, 'commands'),
            'guilds' => $guilds,
            'currentGuildId' => $currentGuildId,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): \Inertia\Response
    {
        $user = $request->user();
        $token = $user->access_token;
        $discordId = $user->discord_id;
        $cacheKey = 'guilds_' . $discordId;
        $dateInterval = DateInterval::createFromDateString('90 seconds');

        $guilds = Cache::remember($cacheKey, $dateInterval, function () use ($token) {
            $response = Http::withToken($token)->get('https://discord.com/api/v10/users/@me/guilds');
            if ($response->successful()) {
                return $response->json();
            }
            return [];
        });

        if (!$guilds) {
            return Inertia::render('Commands/Index', [
                'commands' => [],
                'guilds' => [],
            ])->with('flash', [
                'type' => 'danger',
                'message' => 'You are not in any guilds.',
            ]);
        }

        if (!$guilds->successful()) {
            return Inertia::render('Commands/Create', [
                'guilds' => [],
            ]);
        }

        if ($guilds->successful()) {
            return Inertia::render('Commands/Create', [
                'guilds' => $guilds->json(),
            ]);
        }

        return Inertia::render('Commands/Create', [
            'guilds' => [],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        dd($request->all());
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(NeonCommand $neonCommand)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, NeonCommand $neonCommand)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(NeonCommand $neonCommand)
    {
        //
    }
}
