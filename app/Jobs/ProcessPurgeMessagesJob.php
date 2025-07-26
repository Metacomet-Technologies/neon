<?php

// TODO: Check backoff strategy and retry logic. doesnt seem to be in parity with number stated in message
declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
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
    }    private function parseMessage(string $message): array
    {
        // Support both channel names (#channel), mentions (<#channelId>), and "this"
        preg_match('/^!purge\s+(.+?)\s+(\d+|all)$/i', $message, $matches);

        if (isset($matches[1], $matches[2])) {
            $channelInput = trim($matches[1]);
            $messageCount = strtolower($matches[2]) === 'all' ? 1000 : (int) $matches[2]; // Use 1000 for "all"

            // Handle "this" channel reference
            if (strtolower($channelInput) === 'this') {
                return [$this->channelId, $messageCount]; // Use current channel ID
            }

            // Resolve channel input to actual Discord channel ID
            $channelId = $this->resolveChannelToId($channelInput);

            return [$channelId, $messageCount];
        }

        return [null, null];
    }

    /**
     * Resolve channel name or ID to Discord channel ID
     */
    private function resolveChannelToId(string $channelInput): ?string
    {
        // If it's already a numeric Discord ID, return it
        if (preg_match('/^\d{17,19}$/', $channelInput)) {
            return $channelInput;
        }

        // If channel mention format (<#channelID>), extract the numeric ID
        if (preg_match('/^<#(\d{17,19})>$/', $channelInput, $channelMatches)) {
            return $channelMatches[1]; // Extract numeric channel ID
        }

        // Extract channel name from input (with or without # prefix)
        $channelName = $channelInput;
        if (preg_match('/^#(.+)$/', $channelInput, $nameMatches)) {
            $channelName = $nameMatches[1]; // Remove # prefix if present
        }

        // Fetch all channels in the guild to resolve name to ID
        $channelsUrl = $this->baseUrl . "/guilds/{$this->guildId}/channels";
        $channelsResponse = retry(3, function () use ($channelsUrl) {
            return Http::withToken(config('discord.token'), 'Bot')
                ->timeout(10)
                ->get($channelsUrl);
        }, [1000, 2000, 3000]);

        if ($channelsResponse->successful()) {
            $channels = $channelsResponse->json();

            // Find channel by name (case-insensitive)
            foreach ($channels as $channel) {
                if (strtolower($channel['name']) === strtolower($channelName)) {
                    return $channel['id'];
                }
            }
        }

        return null; // Could not resolve channel
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
                'failed',
                'No messages found to delete. Messages older than 14 days cannot be deleted in bulk.',
                null,
                400
            );
            return;
        }

        if (count($messageIds) === 1) {
            // Single message deletion - use different endpoint
            $deleteUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages/{$messageIds[0]}";

            $deleteResponse = retry(3, function () use ($deleteUrl) {
                return Http::withToken(config('discord.token'), 'Bot')->delete($deleteUrl);
            }, [2000, 4000, 6000]);

            if ($deleteResponse->failed()) {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => false,
                    'response' => '❌ Failed to delete the message.',
                ]);
                $this->updateNativeCommandRequestFailed(
                    'failed',
                    'Failed to delete single message.',
                    null,
                    $deleteResponse->status()
                );
            } else {
                SendMessage::sendMessage($this->channelId, [
                    'is_embed' => true,
                    'embed_title' => '🧹 Message Purged',
                    'embed_description' => "✅ Successfully purged 1 message from <#{$this->targetChannelId}>.",
                    'embed_color' => 3066993,
                ]);
                $this->updateNativeCommandRequestComplete();
            }
            return;
        }

        $batches = array_chunk($messageIds, 100);
        $failedBatches = 0;
        $successfullyDeletedMessages = 0;

        foreach ($batches as $batchIndex => $batch) {
            if (count($batch) === 1) {
                // Single message deletion
                $deleteUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages/{$batch[0]}";

                $deleteResponse = retry(3, function () use ($deleteUrl) {
                    return Http::withToken(config('discord.token'), 'Bot')->delete($deleteUrl);
                }, [2000, 4000, 6000]);
            } else {
                // Bulk message deletion (2-100 messages)
                $deleteUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages/bulk-delete";

                $deleteResponse = retry(5, function () use ($deleteUrl, $batch) {
                    return Http::withToken(config('discord.token'), 'Bot')
                        ->post($deleteUrl, ['messages' => $batch]);
                }, [6000, 8000, 12000, 20000, 30000]);
            }

            if ($deleteResponse->failed()) {
                $failedBatches++;
                \Illuminate\Support\Facades\Log::error("Failed to delete message batch", [
                    'batch_index' => $batchIndex,
                    'batch_size' => count($batch),
                    'status_code' => $deleteResponse->status(),
                    'response' => $deleteResponse->json(),
                    'channel_id' => $this->targetChannelId
                ]);
            } else {
                $successfullyDeletedMessages += count($batch);
                \Illuminate\Support\Facades\Log::info("Successfully deleted message batch", [
                    'batch_index' => $batchIndex,
                    'batch_size' => count($batch),
                    'total_deleted_so_far' => $successfullyDeletedMessages
                ]);
            }

            // Add delay between batches to avoid rate limits
            if ($batchIndex < count($batches) - 1) {
                sleep(2);
            }
        }

        if ($failedBatches === count($batches)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to delete all messages due to rate limits or API errors.',
            ]);
            $this->updateNativeCommandRequestFailed(
                'failed',
                'Failed to delete all messages due to rate limits or API errors.',
                null,
                400
            );
        } else if ($failedBatches > 0) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🧹 Messages Partially Purged',
                'embed_description' => "⚠️ Successfully purged {$successfullyDeletedMessages} out of " . count($messageIds) . " messages from <#{$this->targetChannelId}>.\n\n{$failedBatches} batch(es) failed due to rate limits or API errors.",
                'embed_color' => 16776960, // Yellow for partial success
            ]);
            $this->updateNativeCommandRequestComplete();
        } else {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => true,
                'embed_title' => '🧹 Messages Purged',
                'embed_description' => "✅ Successfully purged {$successfullyDeletedMessages} messages from <#{$this->targetChannelId}>.",
                'embed_color' => 3066993,
            ]);
            $this->updateNativeCommandRequestComplete();
        }
    }
}
