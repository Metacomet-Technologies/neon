<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\Discord;

/**
 * User resource for expressive Discord API operations.
 *
 * Usage:
 * $user = $discord->user('123456789');
 * $info = $user->get();
 * $dm = $user->createDM();
 */
final class User
{
    public function __construct(
        private Discord $discord,
        private string $userId
    ) {}

    /**
     * Get user information.
     */
    public function get(): array
    {
        return $this->discord->get("/users/{$this->userId}");
    }

    /**
     * Create a DM channel with this user.
     */
    public function createDM(): array
    {
        return $this->discord->post('/users/@me/channels', [
            'recipient_id' => $this->userId,
        ]);
    }

    /**
     * Send a DM to this user.
     */
    public function sendDM(string|array $content): array
    {
        $dmChannel = $this->createDM();
        $channelId = $dmChannel['id'];

        if (is_string($content)) {
            $content = ['content' => $content];
        }

        return $this->discord->post("/channels/{$channelId}/messages", $content);
    }
}
