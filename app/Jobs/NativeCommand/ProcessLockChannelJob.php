<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use App\Services\Discord\DiscordService;
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
        [$this->targetChannelId, $newValue] = DiscordService::parseChannelEditCommand($messageContent, 'lock-channel');
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

        // 3. Get the @everyone role
        $everyoneRole = $this->getDiscord()->getEveryoneRole($this->guildId);

        if (! $everyoneRole) {
            $this->sendApiError('find @everyone role');
            throw new Exception('Could not find @everyone role.', 500);
        }

        // 4. Lock or unlock the channel
        $success = $this->lockStatus
            ? $this->getDiscord()->lockChannel($this->targetChannelId, $everyoneRole['id'])
            : $this->getDiscord()->unlockChannel($this->targetChannelId, $everyoneRole['id']);

        if (! $success) {
            $this->sendApiError('update channel permissions');
            throw new Exception('Failed to lock/unlock channel.', 500);
        }

        // 5. Send confirmation
        $action = $this->lockStatus ? 'locked' : 'unlocked';
        $emoji = $this->lockStatus ? '🔒' : '🔓';
        $this->sendChannelActionConfirmation($action, $this->targetChannelId, "{$emoji} Channel access updated");
    }
}
