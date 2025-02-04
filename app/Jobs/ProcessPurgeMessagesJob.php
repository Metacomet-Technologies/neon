<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessPurgeMessagesJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !purge #channel <number>';
    public string $exampleMessage = 'Example: !purge #general 250';

    private string $baseUrl;
    private string $targetChannelId;
    private int $messageCount;

    private int $retryDelay = 4000; // 4-second delay
    private int $maxRetries = 3;
    private int $batchSize = 100; // Max messages per API call

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
        [$this->targetChannelId, $this->messageCount] = $this->parseMessage($this->messageContent);

        if (! $this->targetChannelId || ! $this->messageCount) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);
            throw new Exception('Invalid input for !purge. Expected a valid channel and number of messages.');
        }
    }

    public function handle(): void
    {
        if (! $this->userHasPermission($this->discordUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to purge messages.',
            ]);

            return;
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
        return GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $userId) === 'success'
            || GetGuildsByDiscordUserId::getIfUserCanManageMessages($this->guildId, $userId) === 'success';
    }

    private function purgeMessages(): void
    {
        if ($this->messageCount < 2) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ The number of messages to purge must be at least 2.',
            ]);

            return;
        }

        $messagesToFetch = $this->messageCount;
        $allMessages = [];
        $lastMessageId = null;

        while ($messagesToFetch > 0) {
            $limit = min($messagesToFetch, 100);
            $url = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages?limit={$limit}"
                   . ($lastMessageId ? "&before={$lastMessageId}" : '');

            $response = retry(5, function () use ($url) {
                return Http::withToken(config('discord.token'), 'Bot')->get($url);
            }, [4000, 6000, 12000, 20000, 30000]); // Backoff strategy

            if ($response->failed()) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => '❌ Failed to fetch messages. Please try again later.',
                ]);

                return;
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
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ No messages found to delete. Messages older than 14 days cannot be deleted in bulk.',
            ]);

            return;
        }

        $batches = array_chunk($messageIds, 100);
        $failedBatches = 0;

        foreach ($batches as $batchIndex => $batch) {
            $deleteUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages/bulk-delete";

            $deleteResponse = retry(5, function () use ($deleteUrl, $batch) {
                return Http::withToken(config('discord.token'), 'Bot')
                    ->post($deleteUrl, ['messages' => $batch]);
            }, [4000, 6000, 12000, 20000, 30000]);

            if ($deleteResponse->failed()) {
                $failedBatches++;
            }
        }

        if ($failedBatches === count($batches)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to delete all messages due to rate limits or API errors.',
            ]);
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🧹 Messages Purged',
                'embed_description' => '✅ Successfully purged ' . count($messageIds) . " messages from <#{$this->targetChannelId}>.",
                'embed_color' => 3066993,
            ]);
        }
    }
}
