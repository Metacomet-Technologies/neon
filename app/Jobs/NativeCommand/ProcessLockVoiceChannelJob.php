<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Exception;
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
        $discord = new Discord;
        if (! $discord->guild($this->guildId)->member($this->discordUserId)->canManageChannels()) {
            $discord->channel($this->channelId)->send('❌ You do not have permission to lock/unlock voice channels in this server.');
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
        $discord = new Discord;
        
        try {
            $roles = $discord->guild($this->guildId)->roles();
            
            if (!$roles) {
                Log::error("Failed to fetch roles for guild {$this->guildId}");
                $discord->channel($this->channelId)->send('❌ Failed to retrieve roles from the server.');
                throw new Exception('Operation failed', 500);
            }
        } catch (Exception $e) {
            Log::error("Failed to fetch roles for guild {$this->guildId}", ['error' => $e->getMessage()]);
            $discord->channel($this->channelId)->send('❌ Failed to retrieve roles from the server.');
            throw new Exception('Operation failed', 500);
        }
        $failedRoles = [];

        // Lock or Unlock the voice channel by updating permissions for all roles
        foreach ($roles as $role) {
            $roleId = $role['id'];

            $payload = [
                'deny' => $this->lockStatus ? (1 << 13) : 0, // Deny CONNECT if locking, remove if unlocking
                'allow' => $this->lockStatus ? 0 : (1 << 13), // Explicitly allow CONNECT if unlocking
                'type' => 0, // Role
            ];

            try {
                $discord->channel($this->targetChannelId)->permissions()->update($roleId, $payload);
            } catch (Exception $e) {
                Log::error("Exception while updating permissions for role {$roleId} in channel {$this->targetChannelId}", ['error' => $e->getMessage()]);
                $failedRoles[] = $role['name'];
            }
        }
        // Send Response Message
        if (! empty($failedRoles)) {
            $discord->channel($this->channelId)->sendEmbed(
                $this->lockStatus ? '🔒 Lock Voice Channel Failed' : '🔓 Unlock Voice Channel Failed',
                '❌ Failed for roles: ' . implode(', ', $failedRoles),
                15158332 // Red
            );
        } else {
            $discord->channel($this->channelId)->sendEmbed(
                $this->lockStatus ? '🔒 Voice Channel Locked' : '🔓 Voice Channel Unlocked',
                "✅ Voice channel <#{$this->targetChannelId}> has been " . ($this->lockStatus ? 'locked' : 'unlocked') . '.',
                $this->lockStatus ? 15158332 : 3066993 // Red for lock, Green for unlock
            );
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
