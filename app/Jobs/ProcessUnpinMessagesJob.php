<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessUnpinMessagesJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage = 'Usage: !unpin <message-id>';

    private string $baseUrl;
    private string $messageId;

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId, // Command sender (user executing !unpin)
        public string $channelId,     // The channel where the command was sent
        public string $guildId,       // The guild (server) ID
        public string $messageContent, // The raw message content
    ) {
        $this->baseUrl = config('services.discord.rest_api_url');
        $this->messageId = $this->parseMessage($this->messageContent);

        if (! $this->messageId) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input. Please provide a valid message ID.\n\n{$this->usageMessage}",
            ]);
            throw new Exception('Invalid input for !unpin. Expected a valid message ID.');
        }
    }

    public function handle(): void
    {
        // 1️⃣ Check if user has permission to unpin messages using the same helper methods as before
        if ($this->userHasPermission($this->discordUserId)) {
            $this->unpinMessage();

            return;
        }

        SendMessage::sendMessage($this->channelId, [
            'is_embed' => false,
            'response' => '❌ You do not have permission to unpin messages.',
        ]);
    }

    private function parseMessage(string $message): ?string
    {
        preg_match('/^!unpin\s+(\d{17,19})$/', $message, $matches);

        return isset($matches[1]) ? $matches[1] : null;
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

    private function unpinMessage(): void
    {
        // We store the message ID before the unpin operation so it doesn't get lost
        $originalMessageId = $this->messageId;

        $url = "{$this->baseUrl}/channels/{$this->channelId}/pins/{$this->messageId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->delete($url);

        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to unpin the message. Please try again later.',
            ]);

            return;
        }

        // ✅ Success! Send confirmation message with the original message ID
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '📌 Message Unpinned',
            'embed_description' => "✅ Successfully unpinned message ID `{$originalMessageId}` in this channel.",
            'embed_color' => 3066993,
        ]);
    }
}
