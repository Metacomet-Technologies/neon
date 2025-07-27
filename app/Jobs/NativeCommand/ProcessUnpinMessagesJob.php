<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessUnpinMessagesJob extends ProcessBaseJob
{
    private readonly ?string $messageId;
    private readonly ?string $unpinType; // "latest" or "oldest"

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

        // Parse unpin parameters in constructor
        [$this->messageId, $this->unpinType] = $this->parseUnpinCommand($messageContent);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageMessages()) {
            $this->sendPermissionDenied('manage messages');
            throw new Exception('User does not have permission to manage messages', 403);
        }

        // 2. Validate input
        if (! $this->messageId && ! $this->unpinType) {
            $this->sendUsageAndExample();
            throw new Exception('Invalid input for unpin command.', 400);
        }

        // 3. Handle unpinning
        if ($this->unpinType) {
            $this->unpinByType($this->unpinType);
        } else {
            $this->unpinSpecificMessage($this->messageId);
        }
    }

    private function parseUnpinCommand(string $message): array
    {
        $message = strtolower(trim($message));

        // Handle specific keywords
        if (str_contains($message, 'latest')) {
            return [null, 'latest'];
        } elseif (str_contains($message, 'oldest')) {
            return [null, 'oldest'];
        }

        // Handle direct message ID
        preg_match('/^!unpin\s+(\d{17,19})$/', $message, $matches);

        return isset($matches[1]) ? [$matches[1], null] : [null, null];
    }

    private function unpinSpecificMessage(string $messageId): void
    {
        $success = $this->getDiscord()->unpinMessage($this->channelId, $messageId);

        if (! $success) {
            $this->sendApiError('unpin message');
            throw new Exception('Failed to unpin message.', 500);
        }

        $this->sendSuccessMessage(
            'Message Unpinned',
            "📌 Successfully unpinned message ID `{$messageId}` in this channel.",
            3066993 // Green
        );
    }

    private function unpinByType(string $type): void
    {
        $pinnedMessages = $this->getDiscord()->getPinnedMessages($this->channelId);

        if (empty($pinnedMessages)) {
            $this->sendErrorMessage('There are no pinned messages in this channel.');
            throw new Exception('No pinned messages found.', 400);
        }

        // Determine which message to unpin
        $messageToUnpin = ($type === 'latest')
            ? end($pinnedMessages) // Most recent pinned message
            : reset($pinnedMessages); // Oldest pinned message

        $this->unpinSpecificMessage($messageToUnpin['id']);
    }
}
