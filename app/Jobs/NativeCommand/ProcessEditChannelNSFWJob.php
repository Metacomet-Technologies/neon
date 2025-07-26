<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessEditChannelNSFWJob extends ProcessBaseJob
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
        [$channelId, $newValue] = Discord::parseChannelEditCommand($this->messageContent, 'edit-channel-nsfw');

        if (! $channelId || ! $newValue) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($channelId);

        // 3. Validate boolean input
        $nsfwSetting = $this->validateBoolean($newValue, 'NSFW setting');

        // 4. Perform update using service
        $success = $this->discord->updateChannel($channelId, ['nsfw' => $nsfwSetting]);

        if (! $success) {
            $this->sendApiError('update channel');
            throw new Exception('Failed to update channel.', 500);
        }

        // 5. Send confirmation
        $statusText = $nsfwSetting ? 'Enabled' : 'Disabled';
        $this->sendChannelActionConfirmation('updated', $channelId, "NSFW: {$statusText}");
    }
}
