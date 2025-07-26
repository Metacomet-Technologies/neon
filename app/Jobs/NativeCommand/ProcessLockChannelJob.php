<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;


use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Services\Discord\Discord;
use Exception;

final class ProcessLockChannelJob extends ProcessBaseJob
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

        // Parse parameters immediately in constructor
        [$this->targetChannelId, $newValue] = Discord::parseChannelEditCommand($messageContent, 'lock-channel');
        $this->lockStatus = $newValue ? $this->validateBoolean($newValue, 'Lock status') : null;
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Validate parsed data
        if (! $this->targetChannelId || $this->lockStatus === null) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($this->targetChannelId);

        try {
            // 3. Use SDK to handle channel locking
            $discord = new Discord;
            $guild = $discord->guild($this->guildId);
            $channel = $discord->channel($this->targetChannelId);

            // Get the @everyone role
            $everyoneRole = $guild->everyoneRole();


            if (! $everyoneRole) {
                throw new Exception('Could not find @everyone role.', 500);
            }

            // Lock or unlock the channel
            if ($this->lockStatus) {
                $channel->lock($everyoneRole['id']);
            } else {
                $channel->unlock($everyoneRole['id']);
            }

            // 4. Send confirmation
            $action = $this->lockStatus ? 'locked' : 'unlocked';
            $emoji = $this->lockStatus ? '🔒' : '🔓';
            $this->sendChannelActionConfirmation($action, $this->targetChannelId, "{$emoji} Channel access updated");

        } catch (Exception $e) {
            $this->sendErrorMessage('Failed to update channel permissions. Please try again.');
            throw new Exception('Failed to lock/unlock channel.', 500);
        }
    }
}
