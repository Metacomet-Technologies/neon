<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessEditChannelNameJob extends ProcessBaseJob
{
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
        // 1. Check permissions
        $this->requireChannelPermission();

        // 2. Parse channel edit command
        [$channelId, $newValue] = Discord::parseChannelEditCommand($this->messageContent, 'edit-channel-name');

        if (! $channelId || ! $newValue) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($channelId);

        // 3. Perform update using service
        $success = $this->discord->updateChannel($channelId, ['name' => $newValue]);

        if (! $success) {
            $this->sendApiError('update channel');
            throw new Exception('Failed to update channel.', 500);
        }

        // 4. Send confirmation
        $this->sendChannelActionConfirmation('updated', $channelId, "New name: #{$newValue}");
    }
}
