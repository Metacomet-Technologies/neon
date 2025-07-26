<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\Discord;

/**
 * Role resource for expressive Discord API operations.
 *
 * Usage:
 * $role = $guild->roles()->find('roleId');
 * $role->update(['name' => 'New Role Name']);
 * $role->delete();
 */
final class Role
{
    public function __construct(
        private Discord $discord,
        private string $guildId,
        private string $roleId
    ) {}

    /**
     * Update role.
     */
    public function update(array $data): array
    {
        return $this->discord->patch("/guilds/{$this->guildId}/roles/{$this->roleId}", $data);
    }

    /**
     * Delete role.
     */
    public function delete(): bool
    {
        return $this->discord->delete("/guilds/{$this->guildId}/roles/{$this->roleId}");
    }

    /**
     * Set role name.
     */
    public function setName(string $name): array
    {
        return $this->update(['name' => $name]);
    }

    /**
     * Set role color.
     */
    public function setColor(int $color): array
    {
        return $this->update(['color' => $color]);
    }

    /**
     * Set role permissions.
     */
    public function setPermissions(int $permissions): array
    {
        return $this->update(['permissions' => $permissions]);
    }

    /**
     * Set role position.
     */
    public function setPosition(int $position): array
    {
        return $this->update(['position' => $position]);
    }

    /**
     * Set whether role is hoisted (displayed separately).
     */
    public function setHoist(bool $hoist): array
    {
        return $this->update(['hoist' => $hoist]);
    }

    /**
     * Set whether role is mentionable.
     */
    public function setMentionable(bool $mentionable): array
    {
        return $this->update(['mentionable' => $mentionable]);
    }
}
