<?php

// TODO: Check backoff strategy and retry logic. doesnt seem to be in parity with number stated in message
declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use Exception;
use Illuminate\Support\Facades\Http;

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
        $permissionCheck = GetGuildsByDiscordUserId::getIfUserCanManageMessages($this->guildId, $this->discordUserId);

        if ($permissionCheck !== 'success') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to manage messages in this server.',
            ]);
            throw new Exception('User does not have permission to manage messages in this server.', 403);
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
            throw new Exception('The number of messages to purge must be at least 2.', 400);
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
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ No messages found to delete. Messages older than 14 days cannot be deleted in bulk.',
            ]);
            throw new Exception('No messages found to delete. Messages older than 14 days cannot be deleted in bulk.', 400);
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
            throw new Exception('Operation failed', 500);
        }
        if ($failedBatches === count($batches)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to delete all messages due to rate limits or API errors.',
            ]);
            throw new Exception('Operation failed', 500);
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
