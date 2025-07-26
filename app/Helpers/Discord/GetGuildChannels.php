<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use App\Services\DiscordApiService;
use Illuminate\Support\Facades\Cache;

final class GetGuildChannels
{
    private DiscordApiService $discordService;
    private string $cacheKey;

    public function __construct(public string $guildId)
    {
        $this->discordService = app(DiscordApiService::class);
        $this->cacheKey = 'guild_channels_' . $this->guildId;
    }

    public function getChannels(): array
    {
        $channels = Cache::remember($this->cacheKey, now()->addSeconds(90), function () {
            try {
                $response = $this->discordService->get("/guilds/{$this->guildId}/channels");
                return $response->successful() ? $response->json() : [];
            } catch (\Exception) {
                return [];
            }
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
