<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessPinMessagesJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !pin <message-id> or !pin this';
    public string $exampleMessage = 'Example: !pin 123456789012345678 or !pin this';

    private string $baseUrl;
    private string $messageId;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId, // Command sender (user executing !pin)
        public string $channelId,     // The channel where the command was sent
        public string $guildId,       // The guild (server) ID
        public string $messageContent, // The raw message content
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->messageId = $this->parseMessage($this->messageContent);

        if (! $this->messageId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input. Please provide a valid message ID or use 'this' to pin the last message.\n\n{$this->usageMessage}",
            ]);
            throw new Exception('Invalid input for !pin. Expected a valid message ID or the keyword "this".');
        }
    }

    public function handle(): void
    {
        // 1️⃣ Check if user has permission to pin messages using the same helper methods as before
        if ($this->userHasPermission($this->discordUserId)) {
            $this->pinMessage();

            return;
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => '❌ You do not have permission to pin messages.',
        ]);
    }

    private function parseMessage(string $message): ?string
    {
        // Check if the command contains the keyword "this" to pin the last message
        if (stripos($message, 'this') !== false) {
            return $this->getLastMessageId();
        }

        // Otherwise, extract the message ID
        preg_match('/^!pin\s+(\d{17,19})$/', $message, $matches);

        return isset($matches[1]) ? $matches[1] : null;
    }

    private function getLastMessageId(): ?string
    {
        // Fetch the most recent messages (2 messages to avoid the current command message)
        $url = "{$this->baseUrl}/channels/{$this->channelId}/messages?limit=2";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to fetch the last message. Please try again later.',
            ]);

            return null;
        }

        $messages = $response->json();

        // The first message will be the last message (the current command)
        // The second message will be the message above it
        if (count($messages) < 2) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ There is no previous message to pin.',
            ]);

            return null;
        }

        $previousMessage = $messages[1]; // Get the second message (the one before the command)

        return $previousMessage['id'] ?? null;
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

    private function pinMessage(): void
    {
        $url = "{$this->baseUrl}/channels/{$this->channelId}/pins/{$this->messageId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->put($url);

        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to pin the message. Please try again later.',
            ]);

            return;
        }

        // ✅ Success! Send confirmation message
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '📌 Message Pinned',
            'embed_description' => "✅ Successfully pinned message ID `{$this->messageId}` in this channel.",
            'embed_color' => 3066993,
        ]);
    }
}
