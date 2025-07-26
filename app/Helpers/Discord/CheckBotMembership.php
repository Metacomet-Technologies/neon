<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class CheckBotMembership
{
    private string $botToken;
    private string $apiUrl;

    public function __construct()
    {
        $this->botToken = config('services.discord.bot_token', '');
        $this->apiUrl = config('services.discord.rest_api_url', 'https://discord.com/api/v10');
    }

    /**
     * Check if the bot is a member of a specific guild
     */
    public function isBotInGuild(string $guildId): bool
    {
        try {
            $response = Http::withToken($this->botToken, 'Bot')
                ->get("{$this->apiUrl}/guilds/{$guildId}");

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
            $response = Http::withToken($this->botToken, 'Bot')
                ->get("{$this->apiUrl}/users/@me/guilds");

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
            $response = Http::withToken($this->botToken, 'Bot')
                ->get("{$this->apiUrl}/guilds/{$guildId}");

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
