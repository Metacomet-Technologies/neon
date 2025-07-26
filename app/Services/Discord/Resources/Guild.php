<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\Discord;
use Illuminate\Support\Collection;

/**
 * Guild resource for expressive Discord API operations.
 *
 * Usage:
 * $guild = $discord->guild('123456789');
 * $roles = $guild->roles()->get();
 * $member = $guild->member('987654321');
 * $channel = $guild->createChannel(['name' => 'general', 'type' => 0]);
 */
final class Guild
{
    public function __construct(
        private Discord $discord,
        private string $guildId
    ) {}

    /**
     * Get guild information.
     */
    public function get(): array
    {
        return $this->discord->get("/guilds/{$this->guildId}");
    }

    /**
     * Get a member from this guild.
     */
    public function member(string $userId): Member
    {
        return new Member($this->discord, $this->guildId, $userId);
    }

    /**
     * Get all members (paginated).
     */
    public function members(int $limit = 1000, ?string $after = null): Collection
    {
        $query = "?limit={$limit}";
        if ($after) {
            $query .= "&after={$after}";
        }

        $members = $this->discord->get("/guilds/{$this->guildId}/members{$query}");

        return collect($members);
    }

    /**
     * Get role builder for this guild.
     */
    public function roles(): RoleBuilder
    {
        return new RoleBuilder($this->discord, $this->guildId);
    }

    /**
     * Get channel builder for this guild.
     */
    public function channels(): ChannelBuilder
    {
        return new ChannelBuilder($this->discord, $this->guildId);
    }

    /**
     * Create a new channel.
     */
    public function createChannel(array $data): array
    {
        return $this->discord->post("/guilds/{$this->guildId}/channels", $data);
    }

    /**
     * Create a new role.
     */
    public function createRole(array $data): array
    {
        return $this->discord->post("/guilds/{$this->guildId}/roles", $data);
    }

    /**
     * Ban a user.
     */
    public function ban(string $userId, int $deleteMessageDays = 7): bool
    {
        return $this->discord->put("/guilds/{$this->guildId}/bans/{$userId}", [
            'delete_message_days' => $deleteMessageDays,
        ]);
    }

    /**
     * Unban a user.
     */
    public function unban(string $userId): bool
    {
        return $this->discord->delete("/guilds/{$this->guildId}/bans/{$userId}");
    }

    /**
     * Get everyone role.
     */
    public function everyoneRole(): ?array
    {
        $roles = $this->roles()->get();

        return $roles->first(fn ($role) => $role['name'] === '@everyone');
    }

    /**
     * Get scheduled events.
     */
    public function scheduledEvents(): Collection
    {
        $events = $this->discord->get("/guilds/{$this->guildId}/scheduled-events");

        return collect($events);
    }

    /**
     * Create a scheduled event.
     */
    public function createScheduledEvent(array $data): array
    {
        return $this->discord->post("/guilds/{$this->guildId}/scheduled-events", $data);
    }

    /**
     * Delete a scheduled event.
     */
    public function deleteScheduledEvent(string $eventId): bool
    {
        return $this->discord->delete("/guilds/{$this->guildId}/scheduled-events/{$eventId}");
    }

    /**
     * Disconnect a member from voice channel.
     */
    public function disconnectMember(string $userId): bool
    {
        return $this->discord->patch("/guilds/{$this->guildId}/members/{$userId}", [
            'channel_id' => null,
        ]);
    }

    /**
     * Move a member to another voice channel.
     */
    public function moveMemberToChannel(string $userId, string $channelId): bool
    {
        return $this->discord->patch("/guilds/{$this->guildId}/members/{$userId}", [
            'channel_id' => $channelId,
        ]);
    }

    /**
     * Set guild AFK channel and timeout.
     */
    public function setAfkChannel(string $channelId, int $timeout = 300): bool
    {
        return $this->discord->patch("/guilds/{$this->guildId}", [
            'afk_channel_id' => $channelId,
            'afk_timeout' => $timeout,
        ]);
    }

    /**
     * Enable/disable guild boost progress bar.
     */
    public function setBoostProgressBar(bool $enabled): bool
    {
        return $this->discord->patch("/guilds/{$this->guildId}", [
            'premium_progress_bar_enabled' => $enabled,
        ]);
    }

    /**
     * Prune inactive members.
     */
    public function pruneMembers(int $days = 7, bool $computePruneCount = true): array
    {
        return $this->discord->post("/guilds/{$this->guildId}/prune", [
            'days' => $days,
            'compute_prune_count' => $computePruneCount,
        ]);
    }

    /**
     * Get prune count (dry run).
     */
    public function getPruneCount(int $days = 7): array
    {
        return $this->discord->get("/guilds/{$this->guildId}/prune?days={$days}");
    }
}
