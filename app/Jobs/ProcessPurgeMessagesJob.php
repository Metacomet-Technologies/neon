<?php

//TODO: Check backoff strategy and retry logic. doesnt seem to be in parity with number stated in message
declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class ProcessPurgeMessagesJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private ?string $targetChannelId = null;
    private ?int $messageCount = null;

    private int $retryDelay = 6000; // 4-second delay
    private int $maxRetries = 3;
    private int $batchSize = 100; // Max messages per API call

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Parse the message
        [$this->targetChannelId, $this->messageCount] = $this->parseMessage($this->messageContent);

        // 🚨 **Validation: Show help message if no arguments are provided**
        if (empty(trim($this->messageContent)) || $this->targetChannelId === null || $this->messageCount === null) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No arguments provided.',
                statusCode: 400,
            );

            return;
        }

        // Ensure the user has permission to manage messages in the target channel
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageMessages($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage messages in this server.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage messages in this server.',
                statusCode: 403,
            );

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
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'The number of messages to purge must be at least 2.',
                statusCode: 400,
            );


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
                $this->updateNativeCommandRequestFailed(
                    status: 'failed',
                    message: 'Failed to fetch messages. Please try again later.',
                    statusCode: 400,
                );


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
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No messages found to delete. Messages older than 14 days cannot be deleted in bulk.',
                statusCode: 400,
            );

            return;
        }

        $batches = array_chunk($messageIds, 100);
        $failedBatches = 0;

        foreach ($batches as $batchIndex => $batch) {
            $deleteUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages/bulk-delete";

            $deleteResponse = retry(5, function () use ($deleteUrl, $batch) {
                return Http::withToken(config('discord.token'), 'Bot')
                    ->post($deleteUrl, ['messages' => $batch]);
            }, [6000, 8000, 12000, 20000, 30000]);

            if ($deleteResponse->failed()) {
                $failedBatches++;
            }
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to create channel.',
                statusCode: $deleteResponse->status(),
                details: $deleteResponse->json(),
            );
        }

        if ($failedBatches === count($batches)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to delete all messages due to rate limits or API errors.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'Failed to delete all messages due to rate limits or API errors.',
                statusCode: 400,
            );

        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🧹 Messages Purged',
                'embed_description' => '✅ Successfully purged ' . count($messageIds) . " messages from <#{$this->targetChannelId}>.",
                'embed_color' => 3066993,
            ]);
            $this->updateNativeCommandRequestComplete();
        }
    }
}
