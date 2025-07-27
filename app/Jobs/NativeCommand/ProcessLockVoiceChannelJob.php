<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessLockVoiceChannelJob extends ProcessBaseJob
{
    private readonly ?string $targetChannelId;
    private readonly ?bool $lockStatus;

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

        // Parse lock parameters in constructor
        [$this->targetChannelId, $this->lockStatus] = $this->parseLockCommand($messageContent);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Validate input
        if (! $this->targetChannelId || ! is_bool($this->lockStatus)) {
            $this->sendUsageAndExample();
            throw new Exception('Invalid parameters provided.', 400);
        }

        $this->validateChannelId($this->targetChannelId);
        // 3. Get @everyone role
        $everyoneRole = $this->getDiscord()->getEveryoneRole($this->guildId);
        if (! $everyoneRole) {
            $this->sendApiError('find @everyone role');
            throw new Exception('Could not find @everyone role.', 500);
        }

        // 4. Update channel permissions for voice connect
        $permissions = [
            'type' => 0, // Role permission
            'deny' => $this->lockStatus ? (1 << 13) : 0, // Deny CONNECT if locking
            'allow' => $this->lockStatus ? 0 : (1 << 13), // Allow CONNECT if unlocking
        ];

        $success = $this->getDiscord()->updateChannelPermissions(
            $this->targetChannelId,
            $everyoneRole['id'],
            $permissions
        );

        if (! $success) {
            $this->sendApiError($this->lockStatus ? 'lock voice channel' : 'unlock voice channel');
            throw new Exception('Failed to update channel permissions.', 500);
        }

        // 5. Send confirmation
        $action = $this->lockStatus ? 'locked' : 'unlocked';
        $icon = $this->lockStatus ? '🔒' : '🔓';
        $color = $this->lockStatus ? 15158332 : 3066993; // Red for lock, Green for unlock

        $this->sendSuccessMessage(
            "{$icon} Voice Channel " . ucfirst($action),
            "Voice channel <#{$this->targetChannelId}> has been **{$action}**.",
            $color
        );
    }

    private function parseLockCommand(string $message): array
    {
        // Extract the channel ID and lock/unlock flag
        preg_match('/^!lock-voice\s+(\d{17,19})\s+(true|false)$/i', trim($message), $matches);

        if (! isset($matches[1], $matches[2])) {
            return [null, null];
        }

        return [$matches[1], strtolower($matches[2]) === 'true'];
    }
}
