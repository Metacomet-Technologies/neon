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
    public string $exampleMessage = 'Example: !purge #general 100';

    private string $baseUrl;
    private string $targetChannelId;
    private int $messageCount;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId, // Command sender (user executing !purge)
        public string $channelId,     // The channel where the command was sent
        public string $guildId,       // The guild (server) ID
        public string $messageContent, // The raw message content
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

    //TODO: add batch jobs to send more than 100 messages at a time.
    public function handle(): void
    {
        // 1️⃣ Check if user has permission to purge messages using the new helper
        if ($this->userHasPermission($this->discordUserId)) {
            $this->purgeMessages();

            return;
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => '❌ You do not have permission to purge messages.',
        ]);
    }

    private function parseMessage(string $message): array
    {
        preg_match('/^!purge\s+<#?(\d{17,19})>\s+(\d+)$/', $message, $matches);

        return isset($matches[1], $matches[2]) ? [$matches[1], (int) $matches[2]] : [null, null];
    }

    private function userHasPermission(string $userId): bool
    {
        // Check for both the ADMINISTRATOR and MANAGE_MESSAGES permissions using the helper
        if (GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $userId) === 'success') {
            return true;
        }

        if (GetGuildsByDiscordUserId::getIfUserCanManageMessages($this->guildId, $userId) === 'success') {
            return true;
        }

        return false;
    }

    private function purgeMessages(): void
    {
        // Step 1: Validate the message count to be between 2 and 100
        if ($this->messageCount < 2 || $this->messageCount > 100) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ The number of messages to purge must be between 2 and 100.',
            ]);

            return;
        }

        // Step 2: Fetch the recent messages from the channel to get the message IDs
        $url = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages?limit={$this->messageCount}";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        // Check if fetching the messages failed
        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to fetch messages. Please try again later.',
            ]);

            return;
        }

        // Step 3: Extract message IDs from the fetched messages
        $messages = $response->json();
        $messageIds = array_map(function ($message) {
            return $message['id'];
        }, $messages);

        // Step 4: Send the bulk delete request with the message IDs
        $deleteUrl = "{$this->baseUrl}/channels/{$this->targetChannelId}/messages/bulk-delete";
        $deleteResponse = Http::withToken(config('discord.token'), 'Bot')->post($deleteUrl, [
            'messages' => $messageIds,
        ]);

        // Check if the bulk delete request failed
        if ($deleteResponse->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to delete messages. Please try again later.',
            ]);

            return;
        }

        // Step 5: Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '🧹 Messages Purged',
            'embed_description' => "✅ Successfully purged {$this->messageCount} messages from <#{$this->targetChannelId}>.",
            'embed_color' => 3066993,
        ]);
    }
}
