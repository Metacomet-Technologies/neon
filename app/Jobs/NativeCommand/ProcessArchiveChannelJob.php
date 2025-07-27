<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessArchiveChannelJob extends ProcessBaseJob
{
    private readonly ?string $targetChannelId;
    private readonly ?bool $archiveStatus;

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

        // Parse the message in constructor
        [$this->targetChannelId, $this->archiveStatus] = $this->parseMessage($messageContent);
    }

    protected function executeCommand(): void
    {
        // 1. Validate input
        if (! $this->targetChannelId || is_null($this->archiveStatus)) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        // 2. Check permissions
        $this->requireChannelPermission();

        // 3. Validate channel ID format
        $this->validateChannelId($this->targetChannelId);

        // 4. Archive/unarchive the channel using Discord service
        $success = $this->archiveStatus
            ? $this->getDiscord()->archiveChannel($this->targetChannelId)
            : $this->getDiscord()->updateChannel($this->targetChannelId, ['archived' => false]);

        if (! $success) {
            $this->sendApiError('update channel archive status');
            throw new Exception('Failed to update channel.', 500);
        }

        // 5. Send confirmation
        $action = $this->archiveStatus ? 'archived' : 'unarchived';
        $this->sendSuccessMessage(
            'Channel Archive Status Updated',
            "📁 Channel <#{$this->targetChannelId}> has been **{$action}**."
        );
    }

    private function parseMessage(string $message): array
    {
        preg_match('/^!archive-channel\s*(<#(\d+)>|(\d+))?\s*(true|false)?$/i', $message, $matches);

        $channelId = null;
        $archiveStatus = null;

        if (isset($matches[2]) && $matches[2]) {
            $channelId = $matches[2];
        } elseif (isset($matches[3]) && $matches[3]) {
            $channelId = $matches[3];
        }

        if (isset($matches[4])) {
            $archiveStatus = strtolower($matches[4]) === 'true';
        }

        return [$channelId, $archiveStatus];
    }
}
