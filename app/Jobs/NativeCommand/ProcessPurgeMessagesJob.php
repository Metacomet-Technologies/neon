<?php

declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Jobs\NativeCommand\Base\ProcessBaseJob;
use Exception;

final class ProcessPurgeMessagesJob extends ProcessBaseJob
{
    private const BATCH_SIZE = 100;

    private readonly ?string $targetChannelId;
    private readonly ?int $messageCount;

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

        // Parse purge parameters in constructor
        [$this->targetChannelId, $this->messageCount] = $this->parsePurgeCommand($messageContent);
    }

    protected function executeCommand(): void
    {
        // 1. Check permissions
        $member = $this->getDiscord()->guild($this->guildId)->member($this->discordUserId);
        if (! $member->canManageMessages()) {
            $this->sendPermissionDenied('manage messages');
            throw new Exception('User does not have permission to manage messages.', 403);
        }

        // 2. Validate input
        if (! $this->targetChannelId || ! $this->messageCount) {
            $this->sendUsageAndExample();
            throw new Exception('Missing required parameters.', 400);
        }

        if ($this->messageCount < 2) {
            $this->sendErrorMessage('The number of messages to purge must be at least 2.');
            throw new Exception('Invalid message count.', 400);
        }

        $this->validateChannelId($this->targetChannelId);

        // 3. Purge messages
        $this->purgeMessages();
    }

    private function parsePurgeCommand(string $message): array
    {
        preg_match('/^!purge\s+<#?(\d{17,19})>\s+(\d+)$/', $message, $matches);

        return isset($matches[1], $matches[2]) ? [$matches[1], (int) $matches[2]] : [null, null];
    }

    private function purgeMessages(): void
    {
        $messagesToFetch = $this->messageCount;
        $allMessageIds = [];
        $lastMessageId = null;

        // Fetch messages in batches
        while ($messagesToFetch > 0) {
            $limit = min($messagesToFetch, self::BATCH_SIZE);
            $messages = $this->getDiscord()->getChannelMessages($this->targetChannelId, $limit, $lastMessageId);

            if (empty($messages)) {
                break;
            }

            // Filter messages newer than 14 days
            foreach ($messages as $msg) {
                if ((time() - strtotime($msg['timestamp'])) <= (14 * 24 * 60 * 60)) {
                    $allMessageIds[] = $msg['id'];
                }
            }

            $messagesToFetch -= count($messages);
            $lastMessage = end($messages);
            $lastMessageId = $lastMessage['id'] ?? null;

            if (! $lastMessageId) {
                break;
            }
        }

        if (empty($allMessageIds)) {
            $this->sendErrorMessage('No messages found to delete. Messages older than 14 days cannot be deleted in bulk.');
            throw new Exception('No deletable messages found.', 400);
        }

        // Delete messages in batches
        $totalDeleted = 0;
        $batches = array_chunk($allMessageIds, self::BATCH_SIZE);

        foreach ($batches as $batch) {
            $success = $this->getDiscord()->bulkDeleteMessages($this->targetChannelId, $batch);
            if ($success) {
                $totalDeleted += count($batch);
            }
        }

        if ($totalDeleted === 0) {
            $this->sendApiError('delete messages');
            throw new Exception('Failed to delete messages.', 500);
        }

        $this->sendSuccessMessage(
            'Messages Purged',
            "🧹 Successfully purged {$totalDeleted} messages from <#{$this->targetChannelId}>.",
            3066993 // Green
        );
    }
}
