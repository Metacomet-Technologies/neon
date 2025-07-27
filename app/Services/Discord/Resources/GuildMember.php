<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\DiscordClient;
use App\Services\Discord\Enums\PermissionEnum;

/**
 * GuildMember resource for Discord operations.
 */
final class GuildMember
{
    private array $memberData;

    public function __construct(
        private readonly DiscordClient $client,
        private readonly string $guildId,
        private readonly string $userId
    ) {
        $this->memberData = $this->client->get("/guilds/{$this->guildId}/members/{$this->userId}");
    }

    /**
     * Get member data.
     */
    public function get(): array
    {
        return $this->memberData;
    }

    /**
     * Check if member is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasPermission(PermissionEnum::ADMINISTRATOR);
    }

    /**
     * Check if member can manage roles.
     */
    public function canManageRoles(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_ROLES);
    }

    /**
     * Check if member can manage channels.
     */
    public function canManageChannels(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_CHANNELS);
    }

    /**
     * Check if member can kick members.
     */
    public function canKickMembers(): bool
    {
        return $this->hasPermission(PermissionEnum::KICK_MEMBERS);
    }

    /**
     * Check if member can ban members.
     */
    public function canBanMembers(): bool
    {
        return $this->hasPermission(PermissionEnum::BAN_MEMBERS);
    }

    /**
     * Check if member can send polls.
     */
    public function canSendPolls(): bool
    {
        return $this->hasPermission(PermissionEnum::SEND_POLLS);
    }

    /**
     * Check if member can manage nicknames.
     */
    public function canManageNicknames(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_NICKNAMES);
    }

    /**
     * Check if member can manage events.
     */
    public function canManageEvents(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_EVENTS);
    }

    /**
     * Check if member can manage messages.
     */
    public function canManageMessages(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_MESSAGES);
    }

    /**
     * Check if member has specific permission.
     */
    private function hasPermission(PermissionEnum $permission): bool
    {
        $permissions = (int) ($this->memberData['permissions'] ?? 0);

        // Admin bypasses all permissions
        if ($permissions & PermissionEnum::ADMINISTRATOR->value) {
            return true;
        }

        return ($permissions & $permission->value) !== 0;
    }
}
