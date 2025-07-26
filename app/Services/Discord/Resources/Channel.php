<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\Discord;
use InvalidArgumentException;

/**
 * Channel resource for expressive Discord API operations.
 *
 * Usage:
 * $channel = $discord->channel('123456789');
 * $channel->send('Hello world!');
 * $channel->update(['name' => 'new-name']);
 * $channel->delete();
 */
final class Channel
{
    public function __construct(
        private Discord $discord,
        private string $channelId
    ) {}

    /**
     * Get channel information.
     */
    public function get(): array
    {
        return $this->discord->get("/channels/{$this->channelId}");
    }

    /**
     * Update channel.
     */
    public function update(array $data): array|bool
    {
        return $this->discord->patch("/channels/{$this->channelId}", $data);
    }

    /**
     * Delete channel.
     */
    public function delete(): bool
    {
        return $this->discord->delete("/channels/{$this->channelId}");
    }

    /**
     * Set channel name.
     */
    public function setName(string $name): bool
    {
        $this->update(['name' => $name]);

        return true;
    }

    /**
     * Set channel topic.
     */
    public function setTopic(string $topic): bool
    {
        $this->update(['topic' => $topic]);

        return true;
    }

    /**
     * Set channel position.
     */
    public function setPosition(int $position): bool
    {
        $this->update(['position' => $position]);

        return true;
    }

    /**
     * Set channel parent (category).
     */
    public function setParent(?string $parentId): bool
    {
        $this->update(['parent_id' => $parentId]);

        return true;
    }

    /**
     * Send a message to this channel.
     */
    public function send(string|array $content): array
    {
        if (is_string($content)) {
            $content = ['content' => $this->formatMessage($content)];
        } elseif (isset($content['content'])) {
            $content['content'] = $this->formatMessage($content['content']);
        }

        return $this->discord->post("/channels/{$this->channelId}/messages", $content);
    }

    /**
     * Send an embed message to this channel.
     */
    public function sendEmbed(string $title, string $description, int $color = 57358, array $fields = [], array $footer = []): array
    {
        $embed = [
            'title' => $title,
            'description' => $description,
            'color' => $color,
        ];

        if (! empty($fields)) {
            $embed['fields'] = $fields;
        }

        if (! empty($footer)) {
            $embed['footer'] = $footer;
        } else {
            $embed['footer'] = [
                'text' => $this->formatMessage('Sent from Neon'),
            ];
        }

        return $this->send(['embeds' => [$embed]]);
    }

    /**
     * Send a message using the legacy helper format.
     */
    public function sendLegacy(array $command): array
    {
        if ($command['is_embed'] ?? false) {
            $title = $command['embed_title'] ?? 'Title';
            $description = $command['embed_description'] ?? 'Description';
            $color = $command['embed_color'] ?? 57358;
            $fields = $command['embed_fields'] ?? [];

            return $this->sendEmbed($title, $description, $color, $fields);
        } else {
            if (! isset($command['response'])) {
                throw new InvalidArgumentException('Response is required for non-embed messages');
            }

            return $this->send($command['response']);
        }
    }

    /**
     * Create an invite for this channel.
     */
    public function createInvite(array $options = []): array
    {
        $defaults = [
            'max_age' => 86400, // 24 hours
            'max_uses' => 0, // unlimited
            'temporary' => false,
            'unique' => false,
        ];

        $data = array_merge($defaults, $options);

        return $this->discord->post("/channels/{$this->channelId}/invites", $data);
    }

    /**
     * Get channel permissions for a user or role.
     */
    public function permissions(string $overwriteId): array
    {
        $channel = $this->get();
        $overwrites = $channel['permission_overwrites'] ?? [];

        foreach ($overwrites as $overwrite) {
            if ($overwrite['id'] === $overwriteId) {
                return $overwrite;
            }
        }

        return [];
    }

    /**
     * Edit permissions for a user or role.
     */
    public function editPermissions(string $overwriteId, array $permissions, int $type = 0): bool
    {
        return $this->discord->put("/channels/{$this->channelId}/permissions/{$overwriteId}", [
            'allow' => $permissions['allow'] ?? 0,
            'deny' => $permissions['deny'] ?? 0,
            'type' => $type, // 0 = role, 1 = member
        ]);
    }

    /**
     * Delete permissions for a user or role.
     */
    public function deletePermissions(string $overwriteId): bool
    {
        return $this->discord->delete("/channels/{$this->channelId}/permissions/{$overwriteId}");
    }

    /**
     * Get messages from channel.
     */
    public function getMessages(array $options = []): array
    {
        $query = http_build_query($options);
        $endpoint = "/channels/{$this->channelId}/messages";

        if ($query) {
            $endpoint .= "?{$query}";
        }

        return $this->discord->get($endpoint);
    }

    /**
     * Bulk delete messages (2-100 messages).
     */
    public function bulkDeleteMessages(array $messageIds): bool
    {
        if (count($messageIds) < 2 || count($messageIds) > 100) {
            throw new InvalidArgumentException('Bulk delete requires 2-100 messages');
        }

        return $this->discord->post("/channels/{$this->channelId}/messages/bulk-delete", [
            'messages' => $messageIds,
        ]);
    }

    /**
     * Pin a message.
     */
    public function pinMessage(string $messageId): bool
    {
        return $this->discord->put("/channels/{$this->channelId}/pins/{$messageId}");
    }

    /**
     * Unpin a message.
     */
    public function unpinMessage(string $messageId): bool
    {
        return $this->discord->delete("/channels/{$this->channelId}/pins/{$messageId}");
    }

    /**
     * Get pinned messages.
     */
    public function getPinnedMessages(): array
    {
        return $this->discord->get("/channels/{$this->channelId}/pins");
    }

    /**
     * Lock channel (deny send messages for everyone role).
     */
    public function lock(string $everyoneRoleId): bool
    {
        return $this->editPermissions($everyoneRoleId, [
            'deny' => 2048, // SEND_MESSAGES
        ], 0);
    }

    /**
     * Unlock channel (allow send messages for everyone role).
     */
    public function unlock(string $everyoneRoleId): bool
    {
        return $this->editPermissions($everyoneRoleId, [
            'allow' => 2048, // SEND_MESSAGES
        ], 0);
    }

    /**
     * Archive channel (set auto-archive duration).
     */
    public function archive(int $autoArchiveDuration = 1440): bool
    {
        $this->update([
            'default_auto_archive_duration' => $autoArchiveDuration,
        ]);

        return true;
    }

    /**
     * Lock voice channel (deny connect for everyone role).
     */
    public function lockVoice(string $everyoneRoleId): bool
    {
        return $this->editPermissions($everyoneRoleId, [
            'deny' => 1048576, // CONNECT
        ], 0);
    }

    /**
     * Unlock voice channel (allow connect for everyone role).
     */
    public function unlockVoice(string $everyoneRoleId): bool
    {
        return $this->editPermissions($everyoneRoleId, [
            'allow' => 1048576, // CONNECT
        ], 0);
    }

    /**
     * Send a poll message.
     */
    public function sendPoll(string $question, array $answers, int $duration = 24, bool $allowMultiselect = false): array
    {
        $pollData = [
            'poll' => [
                'question' => [
                    'text' => $question,
                ],
                'answers' => array_map(function ($text, $index) {
                    return [
                        'answer_id' => $index + 1,
                        'poll_media' => [
                            'text' => $text,
                        ],
                    ];
                }, $answers, array_keys($answers)),
                'duration' => $duration,
                'allow_multiselect' => $allowMultiselect,
                'layout_type' => 1,
            ],
        ];

        return $this->send($pollData);
    }

    /**
     * Vanish channel (hide from everyone role).
     */
    public function vanish(string $everyoneRoleId): bool
    {
        return $this->editPermissions($everyoneRoleId, [
            'deny' => 1024, // VIEW_CHANNEL
        ], 0);
    }

    /**
     * Unvanish channel (show to everyone role).
     */
    public function unvanish(string $everyoneRoleId): bool
    {
        return $this->editPermissions($everyoneRoleId, [
            'allow' => 1024, // VIEW_CHANNEL
        ], 0);
    }

    /**
     * Set channel slowmode.
     */
    public function setSlowmode(int $seconds): bool
    {
        return $this->update(['rate_limit_per_user' => $seconds]);
    }

    /**
     * Set channel NSFW status.
     */
    public function setNsfw(bool $nsfw): bool
    {
        return $this->update(['nsfw' => $nsfw]);
    }

    /**
     * Format message with environment prefix.
     */
    private function formatMessage(string $message): string
    {
        $environment = config('app.env');

        if ($environment === 'production') {
            return $message;
        }

        return '[' . $environment . '] ' . $message;
    }
}
