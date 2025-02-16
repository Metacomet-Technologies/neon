<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Helpers\Discord\GetGuildsByDiscordUserId;
use App\Helpers\Discord\SendMessage;
use App\Jobs\NativeCommand\ProcessBaseJob;
use App\Models\NativeCommandRequest;
use Exception;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

final class ProcessUnpinMessagesJob extends ProcessBaseJob implements ShouldQueue
{
    use Queueable;

    private ?string $messageId = null;
    private ?string $unpinType = null; // "latest" or "oldest"

    private int $retryDelay = 2000;
    private int $maxRetries = 3;

    public function __construct(public NativeCommandRequest $nativeCommandRequest)
    {
        parent::__construct($nativeCommandRequest);
    }

    public function handle(): void
    {
        // Parse the message content
        [$this->messageId, $this->unpinType] = $this->parseMessage($this->messageContent);

        // 🚨 If no valid argument is provided, show help message
        if (empty(trim($this->messageContent)) || ($this->messageId === null && $this->unpinType === null)) {
            $this->sendUsageAndExample();

            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No message ID provided.',
                statusCode: 400,
            );
            throw new Exception('Invalid input for !unpin. Expected a valid message ID, "latest", or "oldest".');
        }

        // 1️⃣ Ensure the user has permission to pin messages
        $adminCheck = GetGuildsByDiscordUserId::getIfUserCanManageChannels($this->guildId, $this->discordUserId);
        if ($adminCheck === 'failed') {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ You are not allowed to pin messages.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'unauthorized',
                message: 'User does not have permission to manage channels',
                statusCode: 403,
            );

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
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to rename channel.',
                statusCode: $response->status(),
                details: $response->json(),
            );

            return;
        }

        // ✅ Success
        SendMessage::sendMessage($this->channelId, [
            'is_embed' => true,
            'embed_title' => '📌 Message Unpinned',
            'embed_description' => "✅ Successfully unpinned message ID `{$messageId}` in this channel.",
            'embed_color' => 3066993,
        ]);
        $this->updateNativeCommandRequestComplete();
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
            $this->updateNativeCommandRequestFailed(
                status: 'discord_api_error',
                message: 'Failed to fetch pinned messages.',
                statusCode: $response->status(),
                details: $response->json(),
            );

            return;
        }

        $pinnedMessages = $response->json();

        if (empty($pinnedMessages)) {
            SendMessage::sendMessage($this->channelId, [
                'is_embed' => false,
                'response' => '❌ There are no pinned messages in this channel.',
            ]);
            $this->updateNativeCommandRequestFailed(
                status: 'failed',
                message: 'No pinned messages found.',
                statusCode: 400,
            );

            return;
        }

        // Determine which message to unpin
        $messageToUnpin = ($type === 'latest')
            ? end($pinnedMessages) // Most recent pinned message
            : reset($pinnedMessages); // Oldest pinned message

        $this->unpinMessage($messageToUnpin['id']);
    }
}
