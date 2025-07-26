<?php

declare(strict_types=1);

namespace App\Services\Discord\Resources;

use App\Services\Discord\Discord;
use Illuminate\Support\Collection;

/**
 * Role builder for expressive Discord API operations.
 *
 * Usage:
 * $roles = $guild->roles()->get();
 * $adminRole = $guild->roles()->findByName('Admin');
 * $newRole = $guild->roles()->create(['name' => 'Moderator']);
 */
final class RoleBuilder
{
    public function __construct(
        private Discord $discord,
        private string $guildId
    ) {}

    /**
     * Get all roles.
     */
    public function get(): Collection
    {
        $roles = $this->discord->get("/guilds/{$this->guildId}/roles");

        return collect($roles);
    }

    /**
     * Find role by ID.
     */
    public function find(string $roleId): Role
    {
        return new Role($this->discord, $this->guildId, $roleId);
    }

    /**
     * Find role by name (case insensitive).
     */
    public function findByName(string $name): ?array
    {
        return $this->get()->first(fn ($role) => strcasecmp($role['name'], $name) === 0);
    }

    /**
     * Create a new role.
     */
    public function create(array $data): Role
    {
        $roleData = $this->discord->post("/guilds/{$this->guildId}/roles", $data);

        return new Role($this->discord, $this->guildId, $roleData['id']);
    }

    /**
     * Get roles sorted by position (highest first).
     */
    public function byPosition(): Collection
    {
        return $this->get()->sortByDesc('position');
    }

    /**
     * Get only hoisted roles.
     */
    public function hoisted(): Collection
    {
        return $this->get()->where('hoist', true);
    }

    /**
     * Get only mentionable roles.
     */
    public function mentionable(): Collection
    {
        return $this->get()->where('mentionable', true);
    }

    /**
     * Get everyone role.
     */
    public function everyone(): ?array
    {
        return $this->findByName('@everyone');
    }
}
