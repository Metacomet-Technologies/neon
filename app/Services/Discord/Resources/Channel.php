<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\DiscordClient;

/**
 * Channel resource for Discord operations.
 */
final class Channel
{
    public function __construct(
        private readonly DiscordClient $client,
        private readonly string $channelId
    ) {}

    /**
     * Get channel information.
     */
    public function get(): array
    {
        return $this->client->get("/channels/{$this->channelId}");
    }

    /**
     * Send message to channel.
     */
    public function send(string|array $message): array
    {
        $data = is_string($message) ? ['content' => $message] : $message;

        return $this->client->post("/channels/{$this->channelId}/messages", $data);
    }

    /**
     * Send embed message.
     */
    public function sendEmbed(string $title, string $description, int $color = 0x00FF00): array
    {
        return $this->send([
            'embeds' => [[
                'title' => $title,
                'description' => $description,
                'color' => $color,
            ]],
        ]);
    }

    /**
     * Update channel.
     */
    public function update(array $data): bool
    {
        return $this->client->patch("/channels/{$this->channelId}", $data);
    }

    /**
     * Delete channel.
     */
    public function delete(): bool
    {
        return $this->client->delete("/channels/{$this->channelId}");
    }

    /**
     * Update channel permissions.
     */
    public function setPermissions(string $targetId, array $permissions, string $type = 'role'): bool
    {
        return $this->client->put("/channels/{$this->channelId}/permissions/{$targetId}", [
            'allow' => $permissions['allow'] ?? 0,
            'deny' => $permissions['deny'] ?? 0,
            'type' => $type === 'role' ? 0 : 1,
        ]);
    }

    /**
     * Set channel name.
     */
    public function setName(string $name): bool
    {
        return $this->update(['name' => $name]);
    }

    /**
     * Set channel topic.
     */
    public function setTopic(string $topic): bool
    {
        return $this->update(['topic' => $topic]);
    }

    /**
     * Set slowmode.
     */
    public function setSlowmode(int $seconds): bool
    {
        return $this->update(['rate_limit_per_user' => $seconds]);
    }

    /**
     * Set NSFW status.
     */
    public function setNsfw(bool $nsfw): bool
    {
        return $this->update(['nsfw' => $nsfw]);
    }

    /**
     * Send poll to channel.
     */
    public function sendPoll(string $question, array $options, int $duration = 24, bool $allowMultiselect = false): array
    {
        $answers = array_map(fn ($option, $index) => [
            'answer_id' => $index + 1,
            'poll_media' => ['text' => $option],
        ], $options, array_keys($options));

        return $this->send([
            'poll' => [
                'question' => ['text' => $question],
                'answers' => $answers,
                'duration' => $duration,
                'allow_multiselect' => $allowMultiselect,
            ],
        ]);
    }

    /**
     * Bulk delete messages.
     */
    public function bulkDelete(array $messageIds): bool
    {
        return $this->client->post("/channels/{$this->channelId}/messages/bulk-delete", [
            'messages' => $messageIds,
        ]);
    }

    /**
     * Pin message.
     */
    public function pinMessage(string $messageId): bool
    {
        return $this->client->put("/channels/{$this->channelId}/pins/{$messageId}");
    }

    /**
     * Unpin message.
     */
    public function unpinMessage(string $messageId): bool
    {
        return $this->client->delete("/channels/{$this->channelId}/pins/{$messageId}");
    }

    /**
     * Get pinned messages.
     */
    public function getPinnedMessages(): array
    {
        return $this->client->get("/channels/{$this->channelId}/pins");
    }

    /**
     * Get messages from channel.
     */
    public function getMessages(int $limit = 100, ?string $before = null): array
    {
        $params = ['limit' => $limit];
        if ($before) {
            $params['before'] = $before;
        }

        return $this->client->get("/channels/{$this->channelId}/messages", $params);
    }
}
