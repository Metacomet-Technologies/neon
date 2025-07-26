<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

// TODO: this job may not be locking vc's as expected. Something about the roles and permissions is off.
final class ProcessLockVoiceChannelJob extends ProcessBaseJob
{
    private ?string $targetChannelId = null;
    private ?bool $lockStatus = null;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        string $discordUserId,
        string $channelId,
        string $guildId,
        string $messageContent,
        array $command,
        string $commandSlug,
        array $parameters = []
    ) {
        parent::__construct($discordUserId, $channelId, $guildId, $messageContent, $command, $commandSlug, $parameters);
    }

    protected function executeCommand(): void
    {
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to lock/unlock voice channels in this server.',
            ]);
            throw new Exception('User does not have permission to manage channels', 403);
        }
        // Check if the user only typed "!lock-voice" with no arguments
        if (trim($this->messageContent) === '!lock-voice') {
            $this->sendUsageAndExample();

            throw new Exception('No parameters provided.', 400);
        }
        // Parse the message
        [$this->targetChannelId, $this->lockStatus] = $this->parseMessage($this->messageContent);

        // If parsing fails, return early
        if (! $this->targetChannelId || ! is_bool($this->lockStatus)) {
            $this->sendUsageAndExample();

            throw new Exception('Invalid parameters provided.', 400);
        }
        // Get all roles in the guild
        $rolesUrl = "{$this->baseUrl}/guilds/{$this->guildId}/roles";

        $rolesResponse = retry($this->maxRetries, function () use ($rolesUrl) {
            return Http::withToken(config('discord.token'), 'Bot')->get($rolesUrl);
        }, $this->retryDelay);

        if ($rolesResponse->failed()) {
            Log::error("Failed to fetch roles for guild {$this->guildId}");
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to retrieve roles from the server.',
            ]);
            throw new Exception('Operation failed', 500);
        }
        $roles = $rolesResponse->json();
        $failedRoles = [];

        // Lock or Unlock the voice channel by updating permissions for all roles
        foreach ($roles as $role) {
            $roleId = $role['id'];
            $permissionsUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/permissions/{$roleId}";

            $payload = [
                'deny' => $this->lockStatus ? (1 << 13) : 0, // Deny CONNECT if locking, remove if unlocking
                'allow' => $this->lockStatus ? 0 : (1 << 13), // Explicitly allow CONNECT if unlocking
                'type' => 0, // Role
            ];

            $permissionsResponse = retry($this->maxRetries, function () use ($permissionsUrl, $payload) {
                return Http::withToken(config('discord.token'), 'Bot')->put($permissionsUrl, $payload);
            }, $this->retryDelay);

            if ($permissionsResponse->failed()) {
                $failedRoles[] = $role['name'];
            }
        }
        // Send Response Message
        if (! empty($failedRoles)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => $this->lockStatus ? '🔒 Lock Voice Channel Failed' : '🔓 Unlock Voice Channel Failed',
                'embed_description' => '❌ Failed for roles: ' . implode(', ', $failedRoles),
                'embed_color' => 15158332, // Red
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => $this->lockStatus ? '🔒 Voice Channel Locked' : '🔓 Voice Channel Unlocked',
                'embed_description' => "✅ Voice channel <#{$this->targetChannelId}> has been " . ($this->lockStatus ? 'locked' : 'unlocked') . '.',
                'embed_color' => $this->lockStatus ? 15158332 : 3066993, // Red for lock, Green for unlock
            ]);
        }
    }

    /**
     * Parses the message content for command parameters.
     */
    private function parseMessage(string $message): array
    {
        // Extract the channel ID (only numbers) and lock/unlock flag
        preg_match('/^!lock-voice\s+(\d{17,19})\s+(true|false)$/i', trim($message), $matches);

        if (! isset($matches[1], $matches[2])) {
            Log::error('Failed to parse command: ' . $message);

            return [null, null];
        }

        return [trim($matches[1]), strtolower(trim($matches[2])) === 'true'];
    }
}
