<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use App\Services\DiscordApiService;
use Illuminate\Support\Facades\Cache;

final class GetBotGuilds
{
    public function __invoke()
    {
        $key = 'neon:guilds';
        $ttl = 300;

        return Cache::remember($key, $ttl, function () {
            return $this->getGuildIds();
        });

        return $guilds;
    }

    public static function make()
    {
        return (new self)();
    }

    public function getGuildsFromDiscord()
    {
        $discordService = app(DiscordApiService::class);
        $response = $discordService->get('/users/@me/guilds');

        return $response->successful() ? $response->json() : [];
    }

    public function getGuildIds()
    {
        $guilds = $this->getGuildsFromDiscord();

        return collect($guilds)->pluck('id')->all();
    }
}
