<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessPinMessagesJob extends ProcessBaseJob
{
    private readonly ?string $messageId;

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

        // Parse message ID in constructor
        $this->messageId = $this->parseMessageId($messageContent);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageMessages()) {
            $this->sendPermissionDenied('manage messages');
            throw new Exception('User does not have permission to manage messages', 403);
        }

        // 2. Validate message ID
        if (! $this->messageId) {
            $this->sendUsageAndExample();
            throw new Exception('Invalid input for pin command.', 400);
        }

        // 3. Pin the message
        $success = $this->getDiscord()->pinMessage($this->channelId, $this->messageId);

        if (! $success) {
            $this->sendApiError('pin message');
            throw new Exception('Failed to pin message.', 500);
        }

        // 4. Send confirmation
        $this->sendSuccessMessage(
            'Message Pinned',
            "📌 Successfully pinned message ID `{$this->messageId}` in this channel.",
            3066993 // Green
        );
    }

    private function parseMessageId(string $message): ?string
    {
        // Check if the command contains the keyword "this" to pin the last message
        if (stripos($message, 'this') !== false) {
            return $this->getLastMessageId();
        }

        // Otherwise, extract the message ID
        preg_match('/^!pin\s+(\d{17,19})$/', $message, $matches);

        return $matches[1] ?? null;
    }

    private function getLastMessageId(): ?string
    {
        $messages = $this->getDiscord()->getChannelMessages($this->channelId, 2);

        // The first message is the command, second is the previous message
        if (count($messages) < 2) {
            return null;
        }

        return $messages[1]['id'] ?? null;
    }
}
