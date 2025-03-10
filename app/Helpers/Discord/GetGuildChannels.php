<?php

declare (strict_types = 1);

namespace App\Helpers\Discord;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

final class GetGuildChannels
{
    public string $baseUrl;

    public string $token;

    public string $cacheKey;

    public function __construct(public string $guildId)
    {
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->token = config('discord.token');
        $this->cacheKey = 'guild_channels_' . $this->guildId;
    }

    public function getChannels(): array
    {
        $channels = Cache::remember($this->cacheKey, now()->addSeconds(90), function () {
            $response = Http::withToken($this->token, 'Bot')
                ->get("{$this->baseUrl}/guilds/{$this->guildId}/channels");

            return $response->successful() ? $response->json() : [];
        });

        return $channels;
    }
}
