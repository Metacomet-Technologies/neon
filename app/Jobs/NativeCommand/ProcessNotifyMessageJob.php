<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessNotifyMessageJob extends ProcessBaseJob
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
        $this->requireMemberPermission();

        $parsed = Discord::parseNotifyCommand($this->messageContent);
        if (! $parsed['channel_id'] || ! $parsed['message']) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        $this->validateChannelId($parsed['channel_id']);

        $success = $this->discord->sendNotification($parsed['channel_id'], $parsed);

        if (! $success) {
            $this->sendApiError('send notification');
            throw new Exception('Failed to send notification.', 500);
        }

        $this->sendSuccessMessage('Notification Sent', 'Message delivered successfully.');
    }
}
