<?php

declare(strict_types=1);

namespace App\Helpers\Discord;

use App\Enums\DiscordPermissionEnum;
use Illuminate\Support\Facades\Http;

final class GetGuildsByDiscordUserId
{
    /**
     * Get all guilds for the user.
     *
     * @return array<string>
     */
    public static function getGuildRoles(string $guildId, string $userId): array
    {
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/guilds/' . $guildId . '/members/' . $userId;
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return [];
        }

        $data = $response->json();

        if (! isset($data['roles'])) {
            return [];
        }

        return $data['roles'];
    }

    public static function getRoleFromGuild(string $guildId, string $roleId): ?string
    {
        $baseUrl = config('services.discord.rest_api_url');
        $url = $baseUrl . '/guilds/' . $guildId . '/roles/' . $roleId;
        $response = Http::withToken(config('discord.token'), 'Bot')->get($url);

        if ($response->failed()) {
            return null;
        }

        $data = $response->json();

        if (! isset($data['permissions'])) {
            return null;
        }

        return $data['permissions'];
    }

    /**
     * Get all guilds where the user has the given permission.
     */
    public static function getIfUserCanManageChannels(string $guildId, string $userId, DiscordPermissionEnum $permission = DiscordPermissionEnum::MANAGE_CHANNELS): string
    {
        $roles = self::getGuildRoles($guildId, $userId);

        if (empty($roles)) {
            return 'failed';
        }

        $adminPermission = DiscordPermissionEnum::ADMINISTRATOR;

        foreach ($roles as $role) {
            $rolePermission = self::getRoleFromGuild($guildId, $role);
            if (! $rolePermission) {
                continue;
            }

            // Ensure $rolePermission is an integer before performing the bitwise operation
            $rolePermission = (int) $rolePermission;

            if (($rolePermission & $permission->value) || ($rolePermission & $adminPermission->value)) {
                return 'success';
            }
        }

        return 'failed';
    }

    public static function getIfUserCanMoveMembers(string $guildId, string $userId): string
    {
        $roles = self::getGuildRoles($guildId, $userId);

        if (empty($roles)) {
            return 'failed';
        }

        $adminPermission = DiscordPermissionEnum::ADMINISTRATOR;
        $permission = DiscordPermissionEnum::MOVE_MEMBERS;

        foreach ($roles as $role) {
            $rolePermission = self::getRoleFromGuild($guildId, $role);
            if (! $rolePermission) {
                continue;
            }

            // Ensure $rolePermission is an integer before performing the bitwise operation
            $rolePermission = (int) $rolePermission;

            if (($rolePermission & $permission->value) || ($rolePermission & $adminPermission->value)) {
                return 'success';
            }
        }

        return 'failed';
    }
}
