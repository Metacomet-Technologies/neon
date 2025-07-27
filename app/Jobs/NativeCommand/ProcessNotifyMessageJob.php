<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
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

        // Extract parameters from message - format: !notify #channel message
        $content = trim(str_replace('!notify', '', $this->messageContent));

        if (empty($content)) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        // Parse channel and message
        preg_match('/^<?#?(\d{17,19})>?\s+(.+)$/s', $content, $matches);

        if (count($matches) < 3) {
            $this->sendUsageAndExample();
            throw new Exception('Invalid format. Use: !notify #channel message', 400);
        }

        $channelId = $matches[1];
        $message = $matches[2];

        $notificationData = [
            'channel_id' => $channelId,
            'message' => $message,
        ];

        $success = $this->getDiscord()->sendNotification($channelId, $notificationData);

        if (! $success) {
            $this->sendApiError('send notification');
            throw new Exception('Failed to send notification.', 500);
        }

        $this->sendSuccessMessage('Notification Sent', 'Message delivered successfully.');
    }
}
