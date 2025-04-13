<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GetBotGuilds
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
        $response = Http::withToken(config('discord.token'), 'Bot')
            ->baseUrl(config('services.discord.rest_api_url'))
            ->get('/users/@me/guilds');

        return $response->successful() ? $response->json() : [];
    }

    public function getGuildIds()
    {
        $guilds = $this->getGuildsFromDiscord();

        return collect($guilds)->pluck('id')->all();
    }
}
