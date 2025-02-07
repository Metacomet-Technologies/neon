<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

final class ProcessUnpinMessagesJob implements ShouldQueue
{
    use Queueable;

    public string $usageMessage;
    public string $exampleMessage;

    //     'slug' => 'unpin',
    //     'description' => 'Unpins a specified message.',
    //     'class' => \App\Jobs\ProcessUnpinMessagesJob::class,
    //     'usage' => 'Usage: !unpin <message-id> | oldest | latest',
    //     'example' => 'Example: !unpin 123456789012345678',
    //     'is_active' => true,
    private string $baseUrl;
    private ?string $messageId = null;
    private ?string $unpinType = null; // "latest" or "oldest"

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(
        public string $discordUserId,
        public string $channelId,
        public string $guildId,
        public string $messageContent,
    ) {
        $command = DB::table('native_commands')->where('slug', 'unpin')->first();
        $this->usageMessage = $command->usage;
        $this->exampleMessage = $command->example;
        $this->baseUrl = config('services.discord.rest_api_url');

        // Parse the message content
        [$this->messageId, $this->unpinType] = $this->parseMessage($this->messageContent);

        // 🚨 If no valid argument is provided, show help message
        if (empty(trim($this->messageContent)) || ($this->messageId === null && $this->unpinType === null)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Invalid input.\n\n{$this->usageMessage}\n{$this->exampleMessage}",
            ]);
            throw new Exception('Invalid input for !unpin. Expected a valid message ID, "latest", or "oldest".');
        }
    }

    public function handle(): void
    {
        // 1️⃣ Check if user has permission to unpin messages
        if (! $this->userHasPermission($this->discordUserId)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You do not have permission to unpin messages.',
            ]);

            return;
        }

        // 2️⃣ Handle specific unpin types (latest/oldest)
        if ($this->unpinType) {
            $this->unpinPinnedMessage($this->unpinType);

            return;
        }

        // 3️⃣ Unpin specific message by ID
        $this->unpinMessage($this->messageId);
    }

    private function parseMessage(string $message): array
    {
        $message = strtolower(trim($message));

        // Handle specific keywords
        if ($message === '!unpin latest') {
            return [null, 'latest'];
        } elseif ($message === '!unpin oldest') {
            return [null, 'oldest'];
        }

        // Handle direct message ID
        preg_match('/^!unpin\s+(\d{17,19})$/', $message, $matches);

        return isset($matches[1]) ? [$matches[1], null] : [null, null];
    }

    private function userHasPermission(string $userId): bool
    {
        return GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $userId) === 'success'
            || GetGuildsByDiscordUserId::getIfUserCanManageMessages($this->guildId, $userId) === 'success';
    }

    private function unpinMessage(string $messageId): void
    {
        $url = "{$this->baseUrl}/channels/{$this->channelId}/pins/{$messageId}";
        $response = Http::withToken(config('discord.token'), 'Bot')->delete($url);

        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => "❌ Failed to unpin message ID `{$messageId}`. Please try again later.",
            ]);

            return;
        }

        // ✅ Success
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '📌 Message Unpinned',
            'embed_description' => "✅ Successfully unpinned message ID `{$messageId}` in this channel.",
            'embed_color' => 3066993,
        ]);
    }

    private function unpinPinnedMessage(string $type): void
    {
        $url = "{$this->baseUrl}/channels/{$this->channelId}/pins";
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ Failed to fetch pinned messages. Please try again later.',
            ]);

            return;
        }

        $pinnedMessages = $response->json();

        if (empty($pinnedMessages)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ There are no pinned messages in this channel.',
            ]);

            return;
        }

        // Determine which message to unpin
        $messageToUnpin = ($type === 'latest')
            ? end($pinnedMessages) // Most recent pinned message
            : reset($pinnedMessages); // Oldest pinned message

        $this->unpinMessage($messageToUnpin['id']);
    }
}
