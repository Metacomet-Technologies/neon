<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\DiscordClient;
use Illuminate\Support\Collection;

/**
 * Guild resource for Discord operations.
 */
final class Guild
{
    public function __construct(
        private readonly DiscordClient $client,
        private readonly string $guildId
    ) {}

    /**
     * Get guild information.
     */
    public function get(): array
    {
        return $this->client->get("/guilds/{$this->guildId}");
    }

    /**
     * Get guild roles.
     */
    public function roles(): Collection
    {
        return collect($this->client->get("/guilds/{$this->guildId}/roles"));
    }

    /**
     * Find role by name.
     */
    public function findRole(string $name): ?array
    {
        return $this->roles()->first(fn ($role) => strcasecmp($role['name'], $name) === 0);
    }

    /**
     * Get guild channels.
     */
    public function channels(): Collection
    {
        return collect($this->client->get("/guilds/{$this->guildId}/channels"));
    }

    /**
     * Get guild member.
     */
    public function member(string $userId): array
    {
        return $this->client->get("/guilds/{$this->guildId}/members/{$userId}");
    }

    /**
     * Create role.
     */
    public function createRole(array $data): array
    {
        return $this->client->post("/guilds/{$this->guildId}/roles", $data);
    }

    /**
     * Delete role.
     */
    public function deleteRole(string $roleId): bool
    {
        return $this->client->delete("/guilds/{$this->guildId}/roles/{$roleId}");
    }

    /**
     * Create channel.
     */
    public function createChannel(array $data): array
    {
        return $this->client->post("/guilds/{$this->guildId}/channels", $data);
    }

    /**
     * Assign role to member.
     */
    public function assignRole(string $userId, string $roleId): bool
    {
        return $this->client->put("/guilds/{$this->guildId}/members/{$userId}/roles/{$roleId}");
    }

    /**
     * Remove role from member.
     */
    public function removeRole(string $userId, string $roleId): bool
    {
        return $this->client->delete("/guilds/{$this->guildId}/members/{$userId}/roles/{$roleId}");
    }

    /**
     * Ban member.
     */
    public function ban(string $userId, int $deleteMessageDays = 7): bool
    {
        return $this->client->put("/guilds/{$this->guildId}/bans/{$userId}", [
            'delete_message_days' => $deleteMessageDays,
        ]);
    }

    /**
     * Unban member.
     */
    public function unban(string $userId): bool
    {
        return $this->client->delete("/guilds/{$this->guildId}/bans/{$userId}");
    }

    /**
     * Kick member.
     */
    public function kick(string $userId): bool
    {
        return $this->client->delete("/guilds/{$this->guildId}/members/{$userId}");
    }

    /**
     * Move member to voice channel.
     */
    public function moveMember(string $userId, ?string $channelId): bool
    {
        return $this->client->patch("/guilds/{$this->guildId}/members/{$userId}", [
            'channel_id' => $channelId,
        ]);
    }
}
