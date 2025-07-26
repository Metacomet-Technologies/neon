<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use App\Services\DiscordApiService;
use Exception;
use Illuminate\Support\Facades\Log;

final class CheckBotMembership
{
    private DiscordApiService $discordService;

    public function __construct()
    {
        $this->discordService = app(DiscordApiService::class);
    }

    /**
     * Check if the bot is a member of a specific guild
     */
    public function isBotInGuild(string $guildId): bool
    {
        try {
            $response = $this->discordService->get("/guilds/{$guildId}");

            return $response->successful();
        } catch (Exception $e) {
            Log::error('Failed to check bot membership for guild', [
                'guild_id' => $guildId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get all guilds the bot is a member of
     */
    public function getBotGuilds(): array
    {
        try {
            $response = $this->discordService->get('/users/@me/guilds');

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (Exception $e) {
            Log::error('Failed to fetch bot guilds', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Get guild details if bot is a member
     */
    public function getGuildDetails(string $guildId): ?array
    {
        try {
            $response = $this->discordService->get("/guilds/{$guildId}");

            if ($response->successful()) {
                return $response->json();
            }

            return null;
        } catch (Exception $e) {
            Log::error('Failed to fetch guild details', [
                'guild_id' => $guildId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
