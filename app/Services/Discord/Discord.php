<?php

declare(strict_types=1);

namespace App\Services\Discord;

use App\Models\User as UserModel;
use App\Services\Discord\Enums\PermissionEnum;
use App\Services\Discord\Resources\Channel;
use App\Services\Discord\Resources\Guild;
use App\Services\Discord\Resources\User;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Discord SDK - Expressive interface for Discord API operations.
 *
 * Usage:
 * $discord = new Discord();
 * $guild = $discord->guild('123456789');
 * $member = $guild->member('987654321');
 * $member->addRole('roleId');
 */
final class Discord
{
    private string $baseUrl;
    private string $token;
    private int $maxRetries;
    private int $retryDelay;

    public function __construct(?string $token = null, private readonly bool $isUserToken = false)
    {
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->token = $token ?? config('discord.token');
        $this->maxRetries = 3;
        $this->retryDelay = 2000;
    }

    /**
     * Create a Discord instance for user OAuth operations.
     */
    public static function forUser(UserModel $user): self
    {
        return new self($user->access_token, true);
    }

    // ========== Parser Methods (Static) ==========

    /**
     * Extract user ID from mention or direct ID.
     */
    public static function extractUserId(string $input): ?string
    {
        // Match user mention <@123456789> or <@!123456789> or direct ID
        if (preg_match('/^<@!?(\d{17,19})>$/', trim($input), $matches)) {
            return $matches[1];
        }

        // Match direct user ID
        if (preg_match('/^(\d{17,19})$/', trim($input), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract multiple user IDs from mentions.
     */
    public static function extractUserIds(array $mentions): array
    {
        $userIds = [];

        foreach ($mentions as $mention) {
            $userId = self::extractUserId($mention);
            if ($userId) {
                $userIds[] = $userId;
            }
        }

        return $userIds;
    }

    /**
     * Extract channel ID from mention or direct ID.
     */
    public static function extractChannelId(string $input): ?string
    {
        // Match channel mention <#123456789>
        if (preg_match('/^<#(\d{17,19})>$/', trim($input), $matches)) {
            return $matches[1];
        }

        // Match direct channel ID
        if (preg_match('/^(\d{17,19})$/', trim($input), $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Extract role ID from mention or return role name.
     */
    public static function extractRoleIdentifier(string $input): array
    {
        // Match role mention <@&123456789>
        if (preg_match('/^<@&(\d{17,19})>$/', trim($input), $matches)) {
            return ['type' => 'id', 'value' => $matches[1]];
        }

        // Return as role name
        return ['type' => 'name', 'value' => trim($input)];
    }

    /**
     * Parse command with user target (ban, kick, mute, etc.).
     */
    public static function parseUserCommand(string $messageContent, string $command): ?string
    {
        $pattern = "/^!{$command}\s+(<@!?(\d{17,19})>|\d{17,19})$/";

        if (preg_match($pattern, $messageContent, $matches)) {
            return $matches[2] ?? $matches[1] ?? null;
        }

        return null;
    }

    /**
     * Parse command with channel and new name (edit-channel-name, etc.).
     */
    public static function parseChannelEditCommand(string $messageContent, string $command): array
    {
        $pattern = "/^!{$command}\s+(<#(\d{17,19})>|\d{17,19})\s+(.+)$/";

        if (preg_match($pattern, $messageContent, $matches)) {
            $channelId = $matches[2] ?? $matches[1];
            $newValue = trim($matches[3]);

            return [$channelId, $newValue];
        }

        return [null, null];
    }

    /**
     * Parse role assignment command (assign-role, remove-role).
     */
    public static function parseRoleCommand(string $messageContent, string $command): array
    {
        $parts = explode(' ', $messageContent);

        if (count($parts) < 3) {
            return [null, []];
        }

        $roleName = $parts[1];
        $userMentions = array_slice($parts, 2);
        $userIds = self::extractUserIds($userMentions);

        return [$roleName, $userIds];
    }

    /**
     * Parse scheduled message command with channel, datetime, and message.
     */
    public static function parseScheduledMessageCommand(string $messageContent): array
    {
        // Pattern: !scheduled-message <#channel> <YYYY-MM-DD HH:MM> <message>
        $pattern = '/^!scheduled-message\s+(<#(\d{17,19})>)\s+(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})\s+(.+)$/';

        if (preg_match($pattern, $messageContent, $matches)) {
            return [
                'channel_id' => $matches[2],
                'datetime' => $matches[3],
                'message' => trim($matches[4]),
            ];
        }

        return ['channel_id' => null, 'datetime' => null, 'message' => null];
    }

    /**
     * Parse notify command with complex structure.
     */
    public static function parseNotifyCommand(string $messageContent): array
    {
        // Pattern: !notify <#channel> <mentions> [title] | <message>
        $pattern = '/^!notify\s+(<#(\d{17,19})>)\s+([^|]+)\|(.+)$/';

        if (preg_match($pattern, $messageContent, $matches)) {
            $channelId = $matches[2];
            $mentionsPart = trim($matches[3]);
            $messagePart = trim($matches[4]);

            // Check if there's a title before the message
            $titleAndMessage = explode('|', $messagePart, 2);
            if (count($titleAndMessage) === 2) {
                $title = trim($titleAndMessage[0]);
                $message = trim($titleAndMessage[1]);
            } else {
                $title = null;
                $message = $messagePart;
            }

            return [
                'channel_id' => $channelId,
                'mentions' => $mentionsPart,
                'title' => $title,
                'message' => $message,
            ];
        }

        return ['channel_id' => null, 'mentions' => null, 'title' => null, 'message' => null];
    }

    /**
     * Validate Discord ID format (17-19 digits).
     */
    public static function isValidDiscordId(string $id): bool
    {
        return preg_match('/^\d{17,19}$/', $id) === 1;
    }

    /**
     * Extract command parameters after command name.
     */
    public static function extractParameters(string $messageContent, string $command): array
    {
        $pattern = "/^!{$command}\s+(.+)$/";

        if (preg_match($pattern, $messageContent, $matches)) {
            return array_filter(explode(' ', trim($matches[1])));
        }

        return [];
    }

    /**
     * Get a guild resource.
     */
    public function guild(string $guildId): Guild
    {
        return new Guild($this, $guildId);
    }

    /**
     * Get a user resource.
     */
    public function user(string $userId): User
    {
        return new User($this, $userId);
    }

    /**
     * Get a channel resource.
     */
    public function channel(string $channelId): Channel
    {
        return new Channel($this, $channelId);
    }

    /**
     * Get bot's guilds.
     */
    public function guilds(): array
    {
        return $this->get('/users/@me/guilds');
    }

    /**
     * Get bot guilds with caching.
     */
    public function botGuilds(): array
    {
        $key = 'neon:guilds';
        $ttl = 300;

        return Cache::remember($key, $ttl, function () {
            $guilds = $this->guilds();

            return collect($guilds)->pluck('id')->all();
        });
    }

    /**
     * Get user guilds where they have a specific permission.
     */
    public function userGuildsWithPermission(PermissionEnum $permission = PermissionEnum::ADMINISTRATOR): array
    {
        $guilds = $this->guilds();

        return array_values(array_filter($guilds, function ($guild) use ($permission) {
            return (bool) ($guild['permissions'] & $permission->value);
        }));
    }

    /**
     * Make an authenticated GET request.
     */
    public function get(string $endpoint): array
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $response = retry($this->maxRetries, function () use ($url) {
            $tokenType = $this->isUserToken ? 'Bearer' : 'Bot';

            return Http::withToken($this->token, $tokenType)->get($url);
        }, $this->retryDelay);

        if ($response->failed()) {
            throw new Exception("Discord API request failed: {$response->status()}", $response->status());
        }

        return $response->json();
    }

    /**
     * Make an authenticated POST request.
     */
    public function post(string $endpoint, array $data = []): array
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $response = retry($this->maxRetries, function () use ($url, $data) {
            $tokenType = $this->isUserToken ? 'Bearer' : 'Bot';

            return Http::withToken($this->token, $tokenType)->post($url, $data);
        }, $this->retryDelay);

        if ($response->failed()) {
            throw new Exception("Discord API request failed: {$response->status()}", $response->status());
        }

        return $response->json();
    }

    /**
     * Make an authenticated PUT request.
     */
    public function put(string $endpoint, array $data = []): bool
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $response = retry($this->maxRetries, function () use ($url, $data) {
            $tokenType = $this->isUserToken ? 'Bearer' : 'Bot';

            return Http::withToken($this->token, $tokenType)->put($url, $data);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Make an authenticated PATCH request.
     */
    public function patch(string $endpoint, array $data = []): array|bool
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $response = retry($this->maxRetries, function () use ($url, $data) {
            $tokenType = $this->isUserToken ? 'Bearer' : 'Bot';

            return Http::withToken($this->token, $tokenType)->patch($url, $data);
        }, $this->retryDelay);

        if ($response->failed()) {
            throw new Exception("Discord API request failed: {$response->status()}", $response->status());
        }

        return $response->json() ?: true;
    }

    /**
     * Make an authenticated DELETE request.
     */
    public function delete(string $endpoint): bool
    {
        $url = "{$this->baseUrl}{$endpoint}";

        $response = retry($this->maxRetries, function () use ($url) {
            $tokenType = $this->isUserToken ? 'Bearer' : 'Bot';

            return Http::withToken($this->token, $tokenType)->delete($url);
        }, $this->retryDelay);

        return $response->successful();
    }

    /**
     * Get retry delay.
     */
    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }
}
