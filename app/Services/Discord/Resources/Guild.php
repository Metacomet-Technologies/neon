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
    public function member(string $userId): GuildMember
    {
        return new GuildMember($this->client, $this->guildId, $userId);
    }

    /**
     * Update guild settings.
     */
    public function updateSettings(array $data): bool
    {
        return $this->client->patch("/guilds/{$this->guildId}", $data);
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
     * Get guild members.
     */
    public function members(int $limit = 1000): array
    {
        $response = $this->client->get("/guilds/{$this->guildId}/members", ['limit' => $limit]);

        return is_array($response) ? $response : [];
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

    /**
     * Update role.
     */
    public function updateRole(string $roleId, array $data): bool
    {
        return $this->client->patch("/guilds/{$this->guildId}/roles/{$roleId}", $data);
    }

    /**
     * Create scheduled event.
     */
    public function createEvent(array $data): array
    {
        return $this->client->post("/guilds/{$this->guildId}/scheduled-events", $data);
    }

    /**
     * Delete scheduled event.
     */
    public function deleteEvent(string $eventId): bool
    {
        return $this->client->delete("/guilds/{$this->guildId}/scheduled-events/{$eventId}");
    }

    /**
     * Get scheduled events.
     */
    public function events(): Collection
    {
        return collect($this->client->get("/guilds/{$this->guildId}/scheduled-events"));
    }

    /**
     * Get single event by ID.
     */
    public function getEvent(string $eventId): ?array
    {
        return $this->client->get("/guilds/{$this->guildId}/scheduled-events/{$eventId}");
    }

    /**
     * Update member.
     */
    public function updateMember(string $userId, array $data): bool
    {
        return $this->client->patch("/guilds/{$this->guildId}/members/{$userId}", $data);
    }

    /**
     * Prune inactive members.
     */
    public function pruneMembers(int $days = 7, bool $dryRun = false): array
    {
        $method = $dryRun ? 'get' : 'post';
        $endpoint = "/guilds/{$this->guildId}/prune";
        $params = ['days' => $days];

        if ($dryRun) {
            return $this->client->get($endpoint, $params);
        }

        return $this->client->post($endpoint, $params);
    }
}
