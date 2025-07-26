<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use Exception;
use Illuminate\Support\Facades\Http;

final class ProcessPinMessagesJob extends ProcessBaseJob
{
    private string $messageId = ''; // ✅ Default to empty string to prevent null error

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

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
        $this->messageId = $this->parseMessage($this->messageContent) ?? ''; // ✅ Ensures it's never null

        // 🚨 **Validation: Prevent execution if no message ID is found**
        if (empty($this->messageId)) {
            $this->sendUsageAndExample();

            throw new Exception('Operation failed', 500);
            throw new Exception('Invalid input for !pin. Expected a valid message ID or the keyword "this".');
        }
        // 1️⃣ Ensure the user has permission to pin messages
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to pin messages.',
            ]);
            throw new Exception('User does not have permission to manage channels', 403);
        }
        $this->pinMessage();
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
            throw new Exception('Operation failed', 500);

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
            throw new Exception('Operation failed', 500);

            return null;
        }
        $previousMessage = $messages[1]; // Get the second message (the one before the command)

        return $previousMessage['id'] ?? null;
    }

    private function userHasPermission(string $userId): bool
    {
        // Check for both the ADMINISTRATOR and MANAGE_MESSAGES permissions
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
            throw new Exception('Failed to pin the message.', 500);
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
