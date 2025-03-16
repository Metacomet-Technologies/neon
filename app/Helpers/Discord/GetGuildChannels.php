<?php

declare(strict_types=1);

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

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    public function getTextChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 0; // 0 is the type for text channels
        });
    }

    public function getVoiceChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 2; // 2 is the type for voice channels
        });
    }

    public function getCategoryChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 4; // 4 is the type for category channels
        });
    }

    public function getStageChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 13; // 13 is the type for stage channels
        });
    }

    public function getAnnouncementChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 5; // 5 is the type for announcement channels
        });
    }

    public function getStoreChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 6; // 6 is the type for store channels
        });
    }

    public function getNewsChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 10; // 10 is the type for news channels
        });
    }

    public function getPrivateChannels(): array
    {
        return array_filter($this->getChannels(), function ($channel) {
            return $channel['type'] === 1; // 1 is the type for private channels
        });
    }

    public function getAllChannels(): array
    {
        return $this->getChannels();
    }

    public function getChannelById(string $channelId): ?array
    {
        $channels = $this->getChannels();
        foreach ($channels as $channel) {
            if ($channel['id'] === $channelId) {
                return $channel;
            }
        }

        return null; // Return null if channel not found
    }

    public function getChannelByName(string $channelName): ?array
    {
        $channels = $this->getChannels();
        foreach ($channels as $channel) {
            if ($channel['name'] === $channelName) {
                return $channel;
            }
        }

        return null; // Return null if channel not found
    }
}
