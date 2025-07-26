<?php

declare(strict_types=1);

namespace App\Services;

use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Service for standardized Discord API operations.
 */
final class DiscordApiService
{
    private string $baseUrl;
    private string $token;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct()
    {
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->token = config('discord.token');
        $this->maxRetries = 3;
        $this->retryDelay = 2000;
    }

    /**
     * Get guild roles.
     */
    public function getGuildRoles(string $guildId): Collection
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/roles";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->get($url);
        }, $this->retryDelay);

        if ($response->failed()) {
            Log::error("Failed to fetch roles for guild {$guildId}");
            throw new Exception('Failed to retrieve roles from the server.', 500);
        }

        return collect($response->json());
    }

    /**
     * Find role by name (case insensitive).
     */
    public function findRoleByName(string $guildId, string $roleName): ?array
    {
        $roles = $this->getGuildRoles($guildId);

        return $roles->first(fn ($role) => strcasecmp($role['name'], $roleName) === 0);
    }

    /**
     * Get guild member information.
     */
    public function getGuildMember(string $guildId, string $userId): array
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/members/{$userId}";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->get($url);
        }, $this->retryDelay);

        if ($response->failed()) {
            throw new Exception('Failed to retrieve member information.', 404);
        }

        return $response->json();
    }

    /**
     * Get user's highest role position.
     */
    public function getUserHighestRolePosition(string $guildId, string $userId): int
    {
        try {
            $member = $this->getGuildMember($guildId, $userId);
            $roles = $member['roles'] ?? [];

            if (empty($roles)) {
                return 0;
            }

            $allRoles = $this->getGuildRoles($guildId);
            $userRoles = $allRoles->whereIn('id', $roles);

            return $userRoles->max('position') ?? 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Assign role to user.
     */
    public function assignRole(string $guildId, string $userId, string $roleId): bool
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/members/{$userId}/roles/{$roleId}";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->put($url);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Remove role from user.
     */
    public function removeRole(string $guildId, string $userId, string $roleId): bool
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/members/{$userId}/roles/{$roleId}";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->delete($url);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Ban user from guild.
     */
    public function banUser(string $guildId, string $userId, int $deleteMessageDays = 7): bool
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/bans/{$userId}";

        $response = retry($this->maxRetries, function () use ($url, $deleteMessageDays) {
            return Http::withToken($this->token, 'Bot')->put($url, [
                'delete_message_days' => $deleteMessageDays,
            ]);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Unban user from guild.
     */
    public function unbanUser(string $guildId, string $userId): bool
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/bans/{$userId}";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->delete($url);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Kick user from guild.
     */
    public function kickUser(string $guildId, string $userId): bool
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/members/{$userId}";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->delete($url);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Update channel information.
     */
    public function updateChannel(string $channelId, array $data): bool
    {
        $url = "{$this->baseUrl}/channels/{$channelId}";

        $response = retry($this->maxRetries, function () use ($url, $data) {
            return Http::withToken($this->token, 'Bot')->patch($url, $data);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Create new role.
     */
    public function createRole(string $guildId, array $roleData): ?array
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/roles";

        $response = retry($this->maxRetries, function () use ($url, $roleData) {
            return Http::withToken($this->token, 'Bot')->post($url, $roleData);
        }, $this->retryDelay);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $guildId, string $roleId): bool
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/roles/{$roleId}";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->delete($url);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Create new channel.
     */
    public function createChannel(string $guildId, array $channelData): ?array
    {
        $url = "{$this->baseUrl}/guilds/{$guildId}/channels";

        $response = retry($this->maxRetries, function () use ($url, $channelData) {
            return Http::withToken($this->token, 'Bot')->post($url, $channelData);
        }, $this->retryDelay);

        if ($response->failed()) {
            return null;
        }

        return $response->json();
    }

    /**
     * Delete channel.
     */
    public function deleteChannel(string $channelId): bool
    {
        $url = "{$this->baseUrl}/channels/{$channelId}";

        $response = retry($this->maxRetries, function () use ($url) {
            return Http::withToken($this->token, 'Bot')->delete($url);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Perform batch operations with rate limiting.
     */
    public function batchOperation(array $items, callable $operation, int $batchSize = 5): array
    {
        $results = ['successful' => [], 'failed' => []];
        $chunks = array_chunk($items, $batchSize);

        foreach ($chunks as $batchIndex => $batch) {
            foreach ($batch as $item) {
                try {
                    $success = $operation($item);
                    if ($success) {
                        $results['successful'][] = $item;
                    } else {
                        $results['failed'][] = $item;
                    }
                } catch (Exception $e) {
                    $results['failed'][] = $item;
                    Log::error('Batch operation failed', ['item' => $item, 'error' => $e->getMessage()]);
                }
            }

            // Delay between batches to respect rate limits
            if ($batchIndex < count($chunks) - 1) {
                usleep($this->retryDelay * 1000);
            }
        }

        return $results;
    }

    /**
     * Get everyone role for guild.
     */
    public function getEveryoneRole(string $guildId): ?array
    {
        $roles = $this->getGuildRoles($guildId);

        return $roles->first(fn ($role) => $role['name'] === '@everyone');
    }
}
