<?php

declare(strict_types=1);

namespace App\Services\Discord;

use App\Models\User;
use App\Services\Discord\Enums\PermissionEnum;
use App\Services\Discord\Resources\Channel;
use App\Services\Discord\Resources\Guild;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Main Discord service providing resource access and utility methods.
 */
final class DiscordService
{
    public function __construct(
        private readonly DiscordClient $client
    ) {}

    /**
     * Create Discord instance for user.
     */
    public static function forUser(User $user): self
    {
        return new self(DiscordClient::forUser($user));
    }

    // ========== Parser Utilities ==========

    /**
     * Extract user ID from mention or direct ID.
     */
    public static function extractUserId(string $input): ?string
    {
        if (preg_match('/^<@!?(\d{17,19})>$/', trim($input), $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(\d{17,19})$/', trim($input))) {
            return trim($input);
        }

        return null;
    }

    /**
     * Extract multiple user IDs from mentions.
     */
    public static function extractUserIds(array $mentions): array
    {
        return array_filter(array_map([self::class, 'extractUserId'], $mentions));
    }

    /**
     * Extract channel ID from mention or direct ID.
     */
    public static function extractChannelId(string $input): ?string
    {
        if (preg_match('/^<#(\d{17,19})>$/', trim($input), $matches)) {
            return $matches[1];
        }

        if (preg_match('/^(\d{17,19})$/', trim($input))) {
            return trim($input);
        }

        return null;
    }

    /**
     * Validate Discord ID format.
     */
    public static function isValidId(string $id): bool
    {
        return preg_match('/^\d{17,19}$/', $id) === 1;
    }

    /**
     * Alias for isValidId (for backwards compatibility).
     */
    public static function isValidDiscordId(string $id): bool
    {
        return self::isValidId($id);
    }

    /**
     * Parse command with user target.
     */
    public static function parseUserCommand(string $message, string $command): ?string
    {
        $pattern = "/^!{$command}\s+(.+)$/";
        if (preg_match($pattern, $message, $matches)) {
            return self::extractUserId($matches[1]);
        }

        return null;
    }

    /**
     * Parse channel edit command.
     */
    public static function parseChannelEditCommand(string $message, string $command): array
    {
        $pattern = "/^!{$command}\s+(<#(\d{17,19})>|\d{17,19})\s+(.+)$/";
        if (preg_match($pattern, $message, $matches)) {
            $channelId = $matches[2] ?? $matches[1];
            $value = trim($matches[3]);

            return [$channelId, $value];
        }

        return [null, null];
    }

    /**
     * Validate channel name.
     */
    public static function validateChannelName(string $name): array
    {
        if (strlen($name) > 100) {
            return ['valid' => false, 'message' => 'Channel name too long (max 100 characters)'];
        }

        if (! preg_match('/^[a-z0-9_-]+$/', $name)) {
            return ['valid' => false, 'message' => 'Invalid characters in channel name'];
        }

        return ['valid' => true, 'message' => 'Valid channel name'];
    }

    /**
     * Refresh a user's Discord access token.
     */
    public function refreshUserToken(User $user): bool
    {
        if (! $user->refresh_token) {
            return false;
        }

        try {
            $response = Http::asForm()->post('https://discord.com/api/oauth2/token', [
                'client_id' => config('services.discord.client_id'),
                'client_secret' => config('services.discord.client_secret'),
                'grant_type' => 'refresh_token',
                'refresh_token' => $user->refresh_token,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                $user->update([
                    'access_token' => $data['access_token'],
                    'refresh_token' => $data['refresh_token'] ?? $user->refresh_token,
                    'token_expires_at' => now()->addSeconds($data['expires_in'] ?? 3600),
                    'refresh_token_expires_at' => now()->addDays(30), // Discord refresh tokens last ~30 days
                ]);

                return true;
            }

            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get a guild resource.
     */
    public function guild(string $guildId): Guild
    {
        return new Guild($this->client, $guildId);
    }

    /**
     * Get a channel resource.
     */
    public function channel(string $channelId): Channel
    {
        return new Channel($this->client, $channelId);
    }

    /**
     * Get bot's guilds.
     */
    public function guilds(): Collection
    {
        return collect($this->client->get('/users/@me/guilds'));
    }

    /**
     * Check if bot is member of guild.
     */
    public function isBotInGuild(string $guildId): bool
    {
        try {
            $this->client->get("/guilds/{$guildId}");

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get guild details (if bot is member).
     */
    public function getGuildDetails(string $guildId): ?array
    {
        try {
            return $this->client->get("/guilds/{$guildId}");
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Get user's guilds where they have the specified permission.
     */
    public function userGuildsWithPermission(PermissionEnum $permission = PermissionEnum::ADMINISTRATOR): array
    {
        try {
            $userGuilds = $this->client->get('/users/@me/guilds');

            return array_filter($userGuilds, function ($guild) use ($permission) {
                $permissions = (int) $guild['permissions'];

                // Check if user has administrator permission (bypasses all other permissions)
                if ($permissions & PermissionEnum::ADMINISTRATOR->value) {
                    return true;
                }

                // Check if user has the specific permission
                return ($permissions & $permission->value) !== 0;
            });
        } catch (Exception) {
            return [];
        }
    }

    // ========== Convenience Methods for Jobs ==========

    /**
     * Get guild roles.
     */
    public function getGuildRoles(string $guildId): Collection
    {
        return $this->guild($guildId)->roles();
    }

    /**
     * Find role by name.
     */
    public function findRoleByName(string $guildId, string $roleName): ?array
    {
        return $this->guild($guildId)->findRole($roleName);
    }

    /**
     * Get guild member.
     */
    public function getGuildMember(string $guildId, string $userId): array
    {
        return $this->guild($guildId)->member($userId);
    }

    /**
     * Get everyone role.
     */
    public function getEveryoneRole(string $guildId): ?array
    {
        return $this->guild($guildId)->findRole('@everyone');
    }

    /**
     * Get guild channels.
     */
    public function getGuildChannels(string $guildId): Collection
    {
        return $this->guild($guildId)->channels();
    }

    /**
     * Create channel.
     */
    public function createChannel(string $guildId, array $data): ?array
    {
        try {
            return $this->guild($guildId)->createChannel($data);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Update channel.
     */
    public function updateChannel(string $channelId, array $data): bool
    {
        return $this->channel($channelId)->update($data);
    }

    /**
     * Delete channel.
     */
    public function deleteChannel(string $channelId): bool
    {
        return $this->channel($channelId)->delete();
    }

    /**
     * Assign role to user.
     */
    public function assignRole(string $guildId, string $userId, string $roleId): bool
    {
        return $this->guild($guildId)->assignRole($userId, $roleId);
    }

    /**
     * Remove role from user.
     */
    public function removeRole(string $guildId, string $userId, string $roleId): bool
    {
        return $this->guild($guildId)->removeRole($userId, $roleId);
    }

    /**
     * Create role.
     */
    public function createRole(string $guildId, array $data): ?array
    {
        try {
            return $this->guild($guildId)->createRole($data);
        } catch (Exception) {
            return null;
        }
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $guildId, string $roleId): bool
    {
        return $this->guild($guildId)->deleteRole($roleId);
    }

    /**
     * Ban user.
     */
    public function banUser(string $guildId, string $userId, int $deleteMessageDays = 7): bool
    {
        return $this->guild($guildId)->ban($userId, $deleteMessageDays);
    }

    /**
     * Unban user.
     */
    public function unbanUser(string $guildId, string $userId): bool
    {
        return $this->guild($guildId)->unban($userId);
    }

    /**
     * Kick user.
     */
    public function kickUser(string $guildId, string $userId): bool
    {
        return $this->guild($guildId)->kick($userId);
    }

    /**
     * Move user to voice channel.
     */
    public function moveUserToChannel(string $guildId, string $userId, string $channelId): bool
    {
        return $this->guild($guildId)->moveMember($userId, $channelId);
    }

    /**
     * Update channel permissions.
     */
    public function updateChannelPermissions(string $channelId, string $overwriteId, array $permissions): bool
    {
        return $this->channel($channelId)->setPermissions($overwriteId, $permissions);
    }

    /**
     * Send notification.
     */
    public function sendNotification(string $channelId, array $data): bool
    {
        try {
            $message = $data['message'] ?? $data['content'] ?? '';
            $this->channel($channelId)->send($message);

            return true;
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Get rate limit statistics for Discord API usage.
     */
    public function getRateLimitStats(): array
    {
        return DiscordClient::getRateLimitStats();
    }
}
