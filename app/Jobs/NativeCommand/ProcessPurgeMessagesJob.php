<?php

// TODO: Check backoff strategy and retry logic. doesnt seem to be in parity with number stated in message
declare(strict_types=1);

namespace App\Jobs\NativeCommand;

use App\Services\Discord\Discord;
use Exception;

final class ProcessPurgeMessagesJob extends ProcessBaseJob
{
    private ?string $targetChannelId = null;
    private ?int $messageCount = null;

    private int $retryDelay = 6000; // 4-second delay
    private int $maxRetries = 3;
    private int $batchSize = 100; // Max messages per API call

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
        // Parse the message
        [$this->targetChannelId, $this->messageCount] = $this->parseMessage($this->messageContent);

        // 🚨 **Validation: Show help message if no arguments are provided**
        if (empty(trim($this->messageContent)) || $this->targetChannelId === null || $this->messageCount === null) {
            $this->sendUsageAndExample();

            throw new Exception('No arguments provided.', 400);
        }
        // Ensure the user has permission to manage messages in the target channel
        $discord = new Discord;
        if (! $discord->guild($this->guildId)->member($this->discordUserId)->canManageMessages()) {
            $discord->channel($this->channelId)->send('❌ You do not have permission to manage messages in this server.');
            throw new Exception('User does not have permission to manage messages in this server.', 403);
        }
        $this->purgeMessages();
    }

    private function parseMessage(string $message): array
    {
        preg_match('/^!purge\s+<#?(\d{17,19})>\s+(\d+)$/', $message, $matches);

        return isset($matches[1], $matches[2]) ? [$matches[1], (int) $matches[2]] : [null, null];
    }

    private function userHasPermission(string $userId): bool
    {
        $discord = new Discord;
        $member = $discord->guild($this->guildId)->member($userId);

        return $member->canManageChannels() || $member->canManageMessages();
    }

    private function purgeMessages(): void
    {
        if ($this->messageCount < 2) {
            $discord = new Discord;
            $discord->channel($this->channelId)->send('❌ The number of messages to purge must be at least 2.');
            throw new Exception('The number of messages to purge must be at least 2.', 400);
        }
        $messagesToFetch = $this->messageCount;
        $allMessages = [];
        $lastMessageId = null;

        $discordService = app(DiscordApiService::class);
        while ($messagesToFetch > 0) {
            $limit = min($messagesToFetch, 100);
            $queryParams = ['limit' => $limit];
            if ($lastMessageId) {
                $queryParams['before'] = $lastMessageId;
            }

            $response = retry(5, function () use ($discordService, $queryParams) {
                return $discordService->get("/channels/{$this->targetChannelId}/messages", $queryParams);
            }, [4000, 6000, 12000, 20000, 30000]); // Backoff strategy

            if ($response->failed()) {
                $discord->channel($this->channelId)->send('❌ Failed to fetch messages. Please try again later.');
                throw new Exception('Failed to fetch messages. Please try again later.', 400);
            }
            $messages = $response->json();
            if (empty($messages)) {
                break;
            }
            foreach ($messages as $msg) {
                if ((time() - strtotime($msg['timestamp'])) > (14 * 24 * 60 * 60)) {
                    continue; // Skip messages older than 14 days
                }
                $allMessages[] = $msg;
            }
            $messagesToFetch -= count($messages);
            $lastMessageId = end($messages)['id'];

            if (! $lastMessageId) {
                break;
            }
        }
        $messageIds = array_column($allMessages, 'id');

        if (empty($messageIds)) {
            $discord->channel($this->channelId)->send('❌ No messages found to delete. Messages older than 14 days cannot be deleted in bulk.');
            throw new Exception('No messages found to delete. Messages older than 14 days cannot be deleted in bulk.', 400);
        }
        $batches = array_chunk($messageIds, 100);
        $failedBatches = 0;

        foreach ($batches as $batchIndex => $batch) {
            $deleteResponse = retry(5, function () use ($discordService, $batch) {
                return $discordService->post("/channels/{$this->targetChannelId}/messages/bulk-delete", ['messages' => $batch]);
            }, [6000, 8000, 12000, 20000, 30000]);

            if ($deleteResponse->failed()) {
                $failedBatches++;
            }
            throw new Exception('Operation failed', 500);
        }
        if ($failedBatches === count($batches)) {
            $discord->channel($this->channelId)->send('❌ Failed to delete all messages due to rate limits or API errors.');
            throw new Exception('Operation failed', 500);
        } else {
            $discord->channel($this->channelId)->sendEmbed(
                '🧹 Messages Purged',
                '✅ Successfully purged ' . count($messageIds) . " messages from <#{$this->targetChannelId}>.",
                3066993
            );
        }
    }
}
