<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\Discord;
use App\Services\Discord\Enums\PermissionEnum;
use DateTimeInterface;

/**
 * Member resource for expressive Discord API operations.
 *
 * Usage:
 * $member = $guild->member('987654321');
 * $member->addRole('roleId');
 * $member->removeRole('roleId');
 * $member->kick();
 * $member->setNickname('New Name');
 */
final class Member
{
    public function __construct(
        private Discord $discord,
        private string $guildId,
        private string $userId
    ) {}

    /**
     * Get member information.
     */
    public function get(): array
    {
        return $this->discord->get("/guilds/{$this->guildId}/members/{$this->userId}");
    }

    /**
     * Add a role to this member.
     */
    public function addRole(string $roleId): bool
    {
        return $this->discord->put("/guilds/{$this->guildId}/members/{$this->userId}/roles/{$roleId}");
    }

    /**
     * Remove a role from this member.
     */
    public function removeRole(string $roleId): bool
    {
        return $this->discord->delete("/guilds/{$this->guildId}/members/{$this->userId}/roles/{$roleId}");
    }

    /**
     * Update member attributes.
     */
    public function update(array $data): array|bool
    {
        return $this->discord->patch("/guilds/{$this->guildId}/members/{$this->userId}", $data);
    }

    /**
     * Set member nickname.
     */
    public function setNickname(string $nickname): bool
    {
        $this->update(['nick' => $nickname]);

        return true;
    }

    /**
     * Kick this member from the guild.
     */
    public function kick(): bool
    {
        return $this->discord->delete("/guilds/{$this->guildId}/members/{$this->userId}");
    }

    /**
     * Get member's roles.
     */
    public function roles(): array
    {
        $member = $this->get();

        return $member['roles'] ?? [];
    }

    /**
     * Get member's highest role position.
     */
    public function highestRolePosition(): int
    {
        $memberRoles = $this->roles();

        if (empty($memberRoles)) {
            return 0;
        }

        $guild = new Guild($this->discord, $this->guildId);
        $allRoles = $guild->roles()->get();
        $userRoles = $allRoles->whereIn('id', $memberRoles);

        return $userRoles->max('position') ?? 0;
    }

    /**
     * Check if member has a specific role.
     */
    public function hasRole(string $roleId): bool
    {
        return in_array($roleId, $this->roles());
    }

    /**
     * Timeout (mute) member.
     */
    public function timeout(?DateTimeInterface $until = null): bool
    {
        $data = [
            'communication_disabled_until' => $until?->format('c'),
        ];

        $this->update($data);

        return true;
    }

    /**
     * Mute user in all text channels.
     */
    public function muteInChannels(array $channelIds): array
    {
        $results = [];

        foreach ($channelIds as $channelId) {
            $success = $this->discord->put("/channels/{$channelId}/permissions/{$this->userId}", [
                'deny' => 2048, // SEND_MESSAGES
                'type' => 1, // member
            ]);

            $results[$channelId] = $success;
        }

        return $results;
    }

    /**
     * Unmute user in all text channels.
     */
    public function unmuteInChannels(array $channelIds): array
    {
        $results = [];

        foreach ($channelIds as $channelId) {
            $success = $this->discord->delete("/channels/{$channelId}/permissions/{$this->userId}");
            $results[$channelId] = $success;
        }

        return $results;
    }

    /**
     * Disconnect from voice channel.
     */
    public function disconnectFromVoice(): bool
    {
        return $this->update(['channel_id' => null]);
    }

    /**
     * Move member to another voice channel.
     */
    public function moveToChannel(string $channelId): bool
    {
        return $this->update(['channel_id' => $channelId]);
    }

    /**
     * Get member's permissions in a role.
     */
    public function getRolePermissions(string $roleId): ?int
    {
        $response = $this->discord->get("/guilds/{$this->guildId}/roles/{$roleId}");

        return isset($response['permissions']) ? (int) $response['permissions'] : null;
    }

    /**
     * Check if member has a specific permission.
     */
    public function hasPermission(PermissionEnum $permission): bool
    {
        $memberRoles = $this->roles();

        if (empty($memberRoles)) {
            return false;
        }

        // Check each role for the permission
        foreach ($memberRoles as $roleId) {
            $rolePermissions = $this->getRolePermissions($roleId);
            if (! $rolePermissions) {
                continue;
            }

            // Check for administrator permission (overrides all)
            if ($rolePermissions & PermissionEnum::ADMINISTRATOR->value) {
                return true;
            }

            // Check for specific permission
            if ($rolePermissions & $permission->value) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if member can manage channels.
     */
    public function canManageChannels(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_CHANNELS);
    }

    /**
     * Check if member can manage roles.
     */
    public function canManageRoles(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_ROLES);
    }

    /**
     * Check if member can manage messages.
     */
    public function canManageMessages(): bool
    {
        return $this->hasPermission(PermissionEnum::MANAGE_MESSAGES);
    }

    /**
     * Check if member can kick members.
     */
    public function canKickMembers(): bool
    {
        return $this->hasPermission(PermissionEnum::KICK_MEMBERS);
    }

    /**
     * Check if member can mute members.
     */
    public function canMuteMembers(): bool
    {
        return $this->hasPermission(PermissionEnum::MUTE_MEMBERS);
    }

    /**
     * Check if member can move members.
     */
    public function canMoveMembers(): bool
    {
        return $this->hasPermission(PermissionEnum::MOVE_MEMBERS);
    }

    /**
     * Check if member can create events.
     */
    public function canCreateEvents(): bool
    {
        return $this->hasPermission(PermissionEnum::CREATE_EVENTS);
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
     * Check if member is admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasPermission(PermissionEnum::ADMINISTRATOR);
    }
}
